<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeodeticGeometryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_geodetic_queue_lists_only_current_unmapped_parcels(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);

        $unmapped = Parcel::create([
            'parcel_code' => 'GEO-QUEUE-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        Parcel::create([
            'parcel_code' => 'GEO-MAPPED-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Piapi',
            'province' => 'Negros Oriental',
            'status' => 'active',
            'geometry_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [[[123.30, 9.30], [123.31, 9.30], [123.31, 9.31], [123.30, 9.30]]],
            ],
        ]);

        Parcel::create([
            'parcel_code' => 'GEO-INACTIVE-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Looc',
            'province' => 'Negros Oriental',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($geodetic)
            ->getJson(route('geodetic.parcels.awaiting-geometry'));

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('parcels.0.id', $unmapped->id)
            ->assertJsonPath('parcels.0.parcel_code', 'GEO-QUEUE-001');

        $response->assertJsonMissing(['parcel_code' => 'GEO-MAPPED-001']);
        $response->assertJsonMissing(['parcel_code' => 'GEO-INACTIVE-001']);
    }

    public function test_geodetic_can_update_only_geojson_geometry(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-EDIT-001',
            'title_no' => 'T-ORIGINAL-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $geometry = [
            'type' => 'Polygon',
            'coordinates' => [[[123.30, 9.30], [123.31, 9.30], [123.31, 9.31], [123.30, 9.30]]],
        ];

        $this->actingAs($geodetic)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($geometry),
                // These extra fields must never be applied by the Geodetic endpoint.
                'status' => 'inactive',
                'title_no' => 'T-TAMPERED-999',
            ])
            ->assertRedirect(route('geodetic.parcels.show', $parcel))
            ->assertSessionHas('success');

        $fresh = $parcel->fresh();

        $this->assertSame('Polygon', $fresh->geometry_geojson['type']);
        $this->assertSame('active', $fresh->status);
        $this->assertSame('T-ORIGINAL-001', $fresh->title_no);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $geodetic->id,
            'auditable_type' => Parcel::class,
            'auditable_id' => $parcel->id,
            'action' => 'geodetic_parcel_geometry_updated',
        ]);

        $log = AuditLog::where('action', 'geodetic_parcel_geometry_updated')->first();
        $this->assertSame('geometry_geojson only', $log->metadata['editable_scope']);
        $this->assertSame('geodetic', $log->metadata['actor_role']);
    }

    public function test_geodetic_geometry_update_rejects_non_polygon_geojson(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-INVALID-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $this->actingAs($geodetic)
            ->from(route('geodetic.parcels.geometry.edit', $parcel))
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode([
                    'type' => 'Point',
                    'coordinates' => [123.30, 9.30],
                ]),
            ])
            ->assertRedirect(route('geodetic.parcels.geometry.edit', $parcel))
            ->assertSessionHasErrors('geometry_geojson');

        $this->assertNull($parcel->fresh()->geometry_geojson);
    }

    public function test_non_geodetic_users_cannot_use_geodetic_geometry_routes(): void
    {
        $landowner = User::factory()->create(['role' => 'landowner']);
        $staff = User::factory()->create(['role' => 'staff']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-RBAC-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $payload = [
            'geometry_geojson' => json_encode([
                'type' => 'Polygon',
                'coordinates' => [[[123.30, 9.30], [123.31, 9.30], [123.31, 9.31], [123.30, 9.30]]],
            ]),
        ];

        $this->actingAs($landowner)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), $payload)
            ->assertForbidden();

        $this->actingAs($staff)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), $payload)
            ->assertForbidden();

        $this->assertNull($parcel->fresh()->geometry_geojson);
    }
}
