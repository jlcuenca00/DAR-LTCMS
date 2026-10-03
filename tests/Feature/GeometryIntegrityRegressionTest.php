<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Parcel;
use App\Models\ParcelGeometryEditSession;
use App\Models\ParcelGeometryRevision;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ParcelGeometryService;
use App\Services\ParcelMapBounds;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class GeometryIntegrityRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function polygon(): array
    {
        return ['type' => 'Polygon', 'coordinates' => [[[123, 9], [124, 9], [124, 10], [123, 9]]]];
    }

    private function openEditor(): array
    {
        $user = User::factory()->create(['role' => 'geodetic']);
        $parcel = Parcel::create(['parcel_code' => 'INTEGRITY-GEOMETRY', 'status' => 'active']);
        $this->actingAs($user)->get(route('geodetic.parcels.geometry.edit', $parcel))->assertOk();
        $session = ParcelGeometryEditSession::where('parcel_id', $parcel->id)->firstOrFail();

        return [$parcel, $session];
    }

    public function test_malformed_and_degenerate_rings_are_rejected_for_writes_and_map_reads(): void
    {
        $valid = $this->polygon();
        $cases = [
            ['type' => 'Polygon', 'coordinates' => ['outer' => $valid['coordinates'][0]]],
            ['type' => 'Polygon', 'coordinates' => [[0 => [123, 9], 2 => [124, 9], 3 => [124, 10], 4 => [123, 9]]]],
            ['type' => 'Polygon', 'coordinates' => [[[123, 9], [123, 9], [123, 9], [123, 9]]]],
            ['type' => 'Polygon', 'coordinates' => [[[123, 9], [124, 10], [125, 11], [123, 9]]]],
            ['type' => 'Polygon', 'coordinates' => [[['123', 9], [124, 9], [124, 10], ['123', 9]]]],
            ['type' => 'Polygon', 'coordinates' => [[[123, 9, 0], [124, 9], [124, 10], [123, 9, 0]]]],
        ];
        foreach ($cases as $geometry) {
            try {
                app(ParcelGeometryService::class)->validatePolygon($geometry);
                $this->fail('Expected malformed or degenerate ring to be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('geometry_geojson', $exception->errors());
            }
            $this->assertNull(ParcelMapBounds::fromGeometry($geometry));
        }
    }

    public function test_valid_holes_and_metadata_survive_save_revision_and_map_read(): void
    {
        [$parcel, $session] = $this->openEditor();
        $geometry = $this->polygon();
        $geometry['coordinates'][] = [[123.7, 9.2], [123.8, 9.2], [123.8, 9.3], [123.7, 9.2]];
        $geometry['reference'] = ['source' => 'survey reference', 'notes' => 'Preserve this'];
        $this->patch(route('geodetic.parcels.geometry.update', $parcel), [
            'geometry_geojson' => json_encode($geometry), 'geometry_version' => 0,
            'edit_session_token' => $session->session_token,
        ])->assertRedirect(route('geodetic.parcels.show', $parcel))->assertSessionHas('success');
        $this->assertEquals($geometry, $parcel->fresh()->geometry_geojson);
        $this->assertEquals($geometry, ParcelGeometryRevision::where('parcel_id', $parcel->id)->firstOrFail()->geometry_geojson);
        $this->assertNotNull(ParcelMapBounds::fromGeometry($parcel->fresh()->geometry_geojson));
        $this->getJson(route('geodetic.parcel-map.feature', $parcel->id))->assertOk()
            ->assertJsonCount(2, 'geometry.coordinates')->assertJsonPath('geometry.reference.notes', 'Preserve this');
    }

    public function test_numeric_representation_and_object_key_order_do_not_create_a_false_geometry_edit(): void
    {
        $geometry = $this->polygon();
        $same = ['coordinates' => array_map(
            fn ($ring) => array_map(fn ($point) => array_map('floatval', $point), $ring), $geometry['coordinates']
        ), 'type' => 'Polygon'];
        $service = app(ParcelGeometryService::class);
        $this->assertTrue($service->geometriesEqual($geometry, $same));
        $this->assertFalse($service->geometriesEqual($geometry + ['reference' => '001'], $same + ['reference' => '1']));
    }

    public function test_audit_failure_rolls_back_geometry_revision_and_session_deletion(): void
    {
        [$parcel, $session] = $this->openEditor();
        $event = 'eloquent.creating: '.AuditLog::class;
        Event::listen($event, function () { throw new RuntimeException('Forced audit failure'); });
        $this->withoutExceptionHandling();
        try {
            $this->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($this->polygon()), 'geometry_version' => 0,
                'edit_session_token' => $session->session_token,
            ]);
            $this->fail('Expected audit failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }
        $this->assertNull($parcel->fresh()->geometry_geojson);
        $this->assertSame(0, $parcel->fresh()->geometry_version);
        $this->assertSame(0, ParcelGeometryRevision::where('parcel_id', $parcel->id)->count());
        $this->assertDatabaseHas('parcel_geometry_edit_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'geodetic_parcel_geometry_updated', 'auditable_id' => $parcel->id]);
    }

    public function test_notification_failure_does_not_report_a_saved_geometry_as_failed(): void
    {
        [$parcel, $session] = $this->openEditor();
        $this->mock(NotificationService::class)->shouldReceive('notifyGeodeticParcelGeometryUpdated')
            ->once()->andThrow(new RuntimeException('Forced notification failure'));
        $this->patch(route('geodetic.parcels.geometry.update', $parcel), [
            'geometry_geojson' => json_encode($this->polygon()), 'geometry_version' => 0,
            'edit_session_token' => $session->session_token,
        ])->assertRedirect(route('geodetic.parcels.show', $parcel))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Notifications could not be delivered'));
        $this->assertSame(1, $parcel->fresh()->geometry_version);
        $this->assertSame(1, ParcelGeometryRevision::where('parcel_id', $parcel->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'geodetic_parcel_geometry_updated', 'auditable_id' => $parcel->id]);
        $this->assertDatabaseMissing('parcel_geometry_edit_sessions', ['id' => $session->id]);
    }
}
