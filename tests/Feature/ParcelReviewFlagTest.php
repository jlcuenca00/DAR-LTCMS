<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelReviewFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_flag_active_parcel_without_changing_lifecycle_status(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $parcel = Parcel::create([
            'parcel_code' => 'FLAG-TEST-001',
            'status' => 'active',
            'province' => 'Negros Oriental',
        ]);

        $this->actingAs($staff)
            ->patch(route('staff.records.parcels.review-flag.flag', $parcel), [
                'flag_reason' => 'mapping_geometry_verification',
                'flag_notes' => 'Boundary coordinates require technical checking.',
            ])
            ->assertRedirect(route('staff.records.parcels.review-flag.edit', $parcel))
            ->assertSessionHas('success');

        $parcel->refresh();

        $this->assertSame('active', $parcel->status);
        $this->assertTrue($parcel->is_flagged);
        $this->assertSame('mapping_geometry_verification', $parcel->flag_reason);
        $this->assertSame($staff->id, $parcel->flagged_by);
        $this->assertNotNull($parcel->flagged_at);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'auditable_type' => Parcel::class,
            'auditable_id' => $parcel->id,
            'action' => 'parcel_review_flagged',
        ]);

        $log = AuditLog::where('action', 'parcel_review_flagged')->firstOrFail();
        $this->assertSame('mapping_geometry_verification', $log->metadata['flag_reason']);
        $this->assertStringContainsString('registry records are unchanged', $log->metadata['scope_note']);
    }

    public function test_staff_can_resolve_flag_with_required_resolution_note(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $parcel = Parcel::create([
            'parcel_code' => 'FLAG-TEST-002',
            'status' => 'active',
            'province' => 'Negros Oriental',
            'is_flagged' => true,
            'flag_reason' => 'parcel_information_discrepancy',
            'flag_notes' => 'Reference values require checking.',
            'flagged_by' => $staff->id,
            'flagged_at' => now()->subHour(),
        ]);

        $this->actingAs($staff)
            ->patch(route('staff.records.parcels.review-flag.resolve', $parcel), [
                'resolution_notes' => 'Reference values were verified against the source record.',
            ])
            ->assertRedirect(route('staff.records.parcels.review-flag.edit', $parcel))
            ->assertSessionHas('success');

        $parcel->refresh();

        $this->assertSame('active', $parcel->status);
        $this->assertFalse($parcel->is_flagged);
        $this->assertSame($staff->id, $parcel->flag_resolved_by);
        $this->assertNotNull($parcel->flag_resolved_at);
        $this->assertSame('Reference values were verified against the source record.', $parcel->flag_resolution_notes);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'auditable_type' => Parcel::class,
            'auditable_id' => $parcel->id,
            'action' => 'parcel_review_flag_resolved',
        ]);
    }

    public function test_resolution_note_is_required(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $parcel = Parcel::create([
            'parcel_code' => 'FLAG-TEST-003',
            'status' => 'active',
            'province' => 'Negros Oriental',
            'is_flagged' => true,
            'flag_reason' => 'other',
        ]);

        $this->actingAs($staff)
            ->from(route('staff.records.parcels.review-flag.edit', $parcel))
            ->patch(route('staff.records.parcels.review-flag.resolve', $parcel), [])
            ->assertRedirect(route('staff.records.parcels.review-flag.edit', $parcel))
            ->assertSessionHasErrors('resolution_notes');

        $this->assertTrue($parcel->fresh()->is_flagged);
    }

    public function test_inactive_parcel_cannot_be_newly_flagged(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $parcel = Parcel::create([
            'parcel_code' => 'FLAG-TEST-004',
            'status' => 'inactive',
            'province' => 'Negros Oriental',
        ]);

        $this->actingAs($staff)
            ->from(route('staff.records.parcels.review-flag.edit', $parcel))
            ->patch(route('staff.records.parcels.review-flag.flag', $parcel), [
                'flag_reason' => 'other',
                'flag_notes' => 'Should not be accepted while inactive.',
            ])
            ->assertRedirect(route('staff.records.parcels.review-flag.edit', $parcel))
            ->assertSessionHasErrors('flag_reason');

        $this->assertFalse($parcel->fresh()->is_flagged);
    }

    public function test_non_staff_users_cannot_access_review_flag_workflow(): void
    {
        $parcel = Parcel::create([
            'parcel_code' => 'FLAG-TEST-005',
            'status' => 'active',
            'province' => 'Negros Oriental',
        ]);

        foreach (['landowner', 'geodetic'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('staff.records.parcels.review-flag.edit', $parcel))
                ->assertForbidden();
        }
    }
}
