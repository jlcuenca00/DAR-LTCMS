<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_application_timeline_on_review_page(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'TIMELINE-001',
            'transferor_name' => 'Timeline Transferor',
            'transferee_name' => 'Timeline Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staffUser->id,
        ]);

        AuditLog::create([
            'actor_user_id' => $staffUser->id,
            'action' => 'document_uploaded',
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'metadata' => [
                'required_document_name' => 'Electronic Copy of Title',
                'document_reference_number' => 'TCT-TIMELINE-001',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        AuditLog::create([
            'actor_user_id' => $staffUser->id,
            'action' => 'application_submitted',
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'metadata' => [
                'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.applications.show', $application));

        $response->assertOk();
        $response->assertSee('Application Timeline', false);
        $response->assertSee('Status History', false);
        $response->assertSee('Document Uploaded', false);
        $response->assertSee('Application Submitted', false);
        $response->assertSee('TCT-TIMELINE-001', false);
        $response->assertSee('Electronic Copy of Title', false);
        $response->assertSee('Timeline records are based on audit logs', false);
    }

    public function test_application_timeline_uses_historical_actor_snapshot_after_account_rename(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $actor = User::factory()->create([
            'name' => 'Timeline Original Actor',
            'username' => 'timeline_original',
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'TIMELINE-SNAPSHOT-001',
            'transferor_name' => 'Timeline Snapshot Transferor',
            'transferee_name' => 'Timeline Snapshot Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $actor->id,
        ]);

        AuditLog::create([
            'actor_user_id' => $actor->id,
            'actor_name_snapshot' => 'Timeline Original Actor',
            'actor_username_snapshot' => 'timeline_original',
            'actor_role_snapshot' => User::ROLE_STAFF,
            'application_code_snapshot' => $application->application_code,
            'action' => 'timeline_snapshot_probe',
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'metadata' => [],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $actor->forceFill([
            'name' => 'Timeline Renamed Actor',
            'username' => 'timeline_renamed',
        ])->save();

        $this->actingAs($viewer)
            ->get(route('staff.applications.show', $application))
            ->assertOk()
            ->assertSee('Timeline Original Actor')
            ->assertSee('@timeline_original')
            ->assertDontSee('Timeline Renamed Actor');
    }

    public function test_application_timeline_shows_empty_state_when_no_audit_logs_exist(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'TIMELINE-EMPTY-001',
            'transferor_name' => 'Empty Timeline Transferor',
            'transferee_name' => 'Empty Timeline Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staffUser->id,
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.applications.show', $application));

        $response->assertOk();
        $response->assertSee('Application Timeline', false);
        $response->assertSee('Status History', false);
        $response->assertSee('No timeline records found yet.', false);
    }
}
