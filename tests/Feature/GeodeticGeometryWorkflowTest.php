<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Parcel;
use App\Models\ParcelGeometryEditSession;
use App\Models\ParcelGeometryRevision;
use App\Models\SystemNotification;
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

    public function test_geodetic_can_update_only_geojson_geometry_and_revision_is_recorded(): void
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

        $session = $this->openEditor($geodetic, $parcel);
        $geometry = $this->polygon(123.30, 9.30);

        $this->actingAs($geodetic)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($geometry),
                'geometry_version' => 0,
                'edit_session_token' => $session->session_token,
                // These extra fields must never be applied by the Geodetic endpoint.
                'status' => 'inactive',
                'title_no' => 'T-TAMPERED-999',
            ])
            ->assertRedirect(route('geodetic.parcels.show', $parcel))
            ->assertSessionHas('success');

        $fresh = $parcel->fresh();

        $this->assertSame('Polygon', $fresh->geometry_geojson['type']);
        $this->assertSame(1, $fresh->geometry_version);
        $this->assertSame('active', $fresh->status);
        $this->assertSame('T-ORIGINAL-001', $fresh->title_no);

        $this->assertDatabaseHas('parcel_geometry_revisions', [
            'parcel_id' => $parcel->id,
            'geometry_version' => 1,
            'actor_user_id' => $geodetic->id,
            'source' => 'geodetic_edit',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $geodetic->id,
            'auditable_type' => Parcel::class,
            'auditable_id' => $parcel->id,
            'action' => 'geodetic_parcel_geometry_updated',
        ]);

        $log = AuditLog::where('action', 'geodetic_parcel_geometry_updated')->first();
        $this->assertSame('geometry_geojson only', $log->metadata['editable_scope']);
        $this->assertSame('geodetic', $log->metadata['actor_role']);
        $this->assertSame(0, $log->metadata['previous_geometry_version']);
        $this->assertSame(1, $log->metadata['new_geometry_version']);
    }

    public function test_stale_geodetic_save_is_blocked_instead_of_overwriting_newer_geometry(): void
    {
        $first = User::factory()->create(['role' => 'geodetic', 'name' => 'First Engineer']);
        $second = User::factory()->create(['role' => 'geodetic', 'name' => 'Second Engineer']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-CONFLICT-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $firstSession = $this->openEditor($first, $parcel);
        $secondSession = $this->openEditor($second, $parcel);

        $firstGeometry = $this->polygon(123.30, 9.30);
        $secondGeometry = $this->polygon(123.40, 9.40);

        $this->actingAs($first)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($firstGeometry),
                'geometry_version' => 0,
                'edit_session_token' => $firstSession->session_token,
            ])
            ->assertRedirect(route('geodetic.parcels.show', $parcel));

        $this->actingAs($second)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($secondGeometry),
                'geometry_version' => 0,
                'edit_session_token' => $secondSession->session_token,
            ])
            ->assertRedirect(route('geodetic.parcels.geometry.edit', $parcel))
            ->assertSessionHas('error');

        $fresh = $parcel->fresh();
        $this->assertSame(1, $fresh->geometry_version);
        $this->assertSame($firstGeometry['coordinates'], $fresh->geometry_geojson['coordinates']);
        $this->assertSame(1, ParcelGeometryRevision::where('parcel_id', $parcel->id)->where('source', 'geodetic_edit')->count());
    }

    public function test_session_base_version_blocks_hidden_version_tampering_after_newer_save(): void
    {
        $first = User::factory()->create(['role' => 'geodetic', 'name' => 'First Engineer']);
        $second = User::factory()->create(['role' => 'geodetic', 'name' => 'Second Engineer']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-TAMPER-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $firstSession = $this->openEditor($first, $parcel);
        $secondSession = $this->openEditor($second, $parcel);

        $firstGeometry = $this->polygon(123.30, 9.30);
        $secondGeometry = $this->polygon(123.40, 9.40);

        $this->actingAs($first)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($firstGeometry),
                'geometry_version' => 0,
                'edit_session_token' => $firstSession->session_token,
            ])
            ->assertRedirect(route('geodetic.parcels.show', $parcel));

        $this->actingAs($second)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($secondGeometry),
                // Deliberately tamper the hidden field to match the latest Parcel.
                'geometry_version' => 1,
                'edit_session_token' => $secondSession->session_token,
            ])
            ->assertRedirect(route('geodetic.parcels.geometry.edit', $parcel))
            ->assertSessionHas('error');

        $fresh = $parcel->fresh();

        $this->assertSame(1, (int) $fresh->geometry_version);
        $this->assertSame($firstGeometry['coordinates'], $fresh->geometry_geojson['coordinates']);
        $this->assertDatabaseMissing('parcel_geometry_edit_sessions', [
            'id' => $secondSession->id,
        ]);
    }

    public function test_heartbeat_expires_session_when_geometry_version_advances(): void
    {
        $first = User::factory()->create(['role' => 'geodetic', 'name' => 'First Engineer']);
        $second = User::factory()->create(['role' => 'geodetic', 'name' => 'Second Engineer']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-HEARTBEAT-CONFLICT-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $firstSession = $this->openEditor($first, $parcel);
        $secondSession = $this->openEditor($second, $parcel);

        $this->actingAs($first)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($this->polygon(123.30, 9.30)),
                'geometry_version' => 0,
                'edit_session_token' => $firstSession->session_token,
            ])
            ->assertRedirect(route('geodetic.parcels.show', $parcel));

        $this->actingAs($second)
            ->postJson(route('geodetic.parcels.geometry.session.heartbeat', $parcel), [
                'edit_session_token' => $secondSession->session_token,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'This parcel changed after you opened the editor. Reload the latest geometry before continuing.');

        $this->assertDatabaseMissing('parcel_geometry_edit_sessions', [
            'id' => $secondSession->id,
        ]);
    }

    public function test_editor_warns_when_another_geodetic_user_is_active(): void
    {
        $first = User::factory()->create(['role' => 'geodetic', 'name' => 'Jovie Anne']);
        $second = User::factory()->create(['role' => 'geodetic', 'name' => 'Second Engineer']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-PRESENCE-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $this->openEditor($first, $parcel);

        $this->actingAs($second)
            ->get(route('geodetic.parcels.geometry.edit', $parcel))
            ->assertOk()
            ->assertSee('Another Geodetic user is editing this parcel')
            ->assertSee('Jovie Anne');
    }

    public function test_successful_geometry_edit_notifies_other_geodetic_users_and_links_to_parcel(): void
    {
        $actor = User::factory()->create(['role' => 'geodetic', 'name' => 'Mapping Engineer']);
        $other = User::factory()->create(['role' => 'geodetic', 'name' => 'Review Engineer']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-NOTIFY-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $session = $this->openEditor($actor, $parcel);

        $this->actingAs($actor)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($this->polygon(123.30, 9.30)),
                'geometry_version' => 0,
                'edit_session_token' => $session->session_token,
            ])
            ->assertRedirect(route('geodetic.parcels.show', $parcel));

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $other->id,
            'type' => 'geodetic_geometry_updated',
            'related_type' => Parcel::class,
            'related_id' => $parcel->id,
        ]);

        $this->assertDatabaseMissing('system_notifications', [
            'user_id' => $actor->id,
            'type' => 'geodetic_geometry_updated',
            'related_id' => $parcel->id,
        ]);

        $notification = SystemNotification::query()
            ->where('user_id', $other->id)
            ->where('type', 'geodetic_geometry_updated')
            ->firstOrFail();

        $this->assertSame(route('geodetic.parcels.show', $parcel), $notification->targetUrlFor($other));
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

        $session = $this->openEditor($geodetic, $parcel);

        $this->actingAs($geodetic)
            ->from(route('geodetic.parcels.geometry.edit', $parcel))
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode([
                    'type' => 'Point',
                    'coordinates' => [123.30, 9.30],
                ]),
                'geometry_version' => 0,
                'edit_session_token' => $session->session_token,
            ])
            ->assertRedirect(route('geodetic.parcels.geometry.edit', $parcel))
            ->assertSessionHasErrors('geometry_geojson');

        $this->assertNull($parcel->fresh()->geometry_geojson);
    }

    public function test_geodetic_geometry_update_rejects_unclosed_or_malformed_polygon_ring(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-MALFORMED-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $session = $this->openEditor($geodetic, $parcel);

        $this->actingAs($geodetic)
            ->from(route('geodetic.parcels.geometry.edit', $parcel))
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode([
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [123.30, 9.30],
                        [123.31, 9.30],
                        [123.31, 9.31],
                        [123.32, 9.32],
                    ]],
                ]),
                'geometry_version' => 0,
                'edit_session_token' => $session->session_token,
            ])
            ->assertRedirect(route('geodetic.parcels.geometry.edit', $parcel))
            ->assertSessionHasErrors('geometry_geojson');

        $this->assertNull($parcel->fresh()->geometry_geojson);
    }

    public function test_geometry_revisions_are_append_only(): void
    {
        $parcel = Parcel::create([
            'parcel_code' => 'GEO-REVISION-IMMUTABLE-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $revision = ParcelGeometryRevision::create([
            'parcel_id' => $parcel->id,
            'geometry_version' => 1,
            'geometry_geojson' => $this->polygon(123.30, 9.30),
            'source' => 'test_revision',
        ]);

        try {
            $revision->update(['source' => 'tampered']);
            $this->fail('Expected parcel geometry revision update to be rejected.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }

        $revision->refresh();

        try {
            $revision->delete();
            $this->fail('Expected parcel geometry revision deletion to be rejected.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }

        $this->assertDatabaseHas('parcel_geometry_revisions', [
            'id' => $revision->id,
            'source' => 'test_revision',
        ]);
    }

    public function test_archived_parcel_geometry_cannot_be_opened_or_saved(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-ARCHIVED-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $session = $this->openEditor($geodetic, $parcel);

        $parcel->forceFill(['status' => 'inactive'])->save();

        $this->actingAs($geodetic)
            ->get(route('geodetic.parcels.geometry.edit', $parcel))
            ->assertForbidden();

        $this->actingAs($geodetic)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($this->polygon(123.30, 9.30)),
                'geometry_version' => 0,
                'edit_session_token' => $session->session_token,
            ])
            ->assertRedirect(route('geodetic.parcels.show', $parcel))
            ->assertSessionHas('error');

        $fresh = $parcel->fresh();
        $this->assertNull($fresh->geometry_geojson);
        $this->assertSame(0, (int) $fresh->geometry_version);
        $this->assertSame(0, ParcelGeometryRevision::where('parcel_id', $parcel->id)->count());
        $this->assertSame(0, ParcelGeometryEditSession::where('parcel_id', $parcel->id)->count());
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
            'geometry_geojson' => json_encode($this->polygon(123.30, 9.30)),
            'geometry_version' => 0,
            'edit_session_token' => '00000000-0000-4000-8000-000000000000',
        ];

        $this->actingAs($landowner)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), $payload)
            ->assertForbidden();

        $this->actingAs($staff)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), $payload)
            ->assertForbidden();

        $this->assertNull($parcel->fresh()->geometry_geojson);
    }

    private function openEditor(User $user, Parcel $parcel): ParcelGeometryEditSession
    {
        $this->actingAs($user)
            ->get(route('geodetic.parcels.geometry.edit', $parcel))
            ->assertOk();

        return ParcelGeometryEditSession::query()
            ->where('parcel_id', $parcel->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    private function polygon(float $longitude, float $latitude): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => [[
                [$longitude, $latitude],
                [$longitude + 0.01, $latitude],
                [$longitude + 0.01, $latitude + 0.01],
                [$longitude, $latitude],
            ]],
        ];
    }
}
