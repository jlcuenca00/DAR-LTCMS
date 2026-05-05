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
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        AuditLog::create([
            'actor_id' => $staffUser->id,
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
            'actor_id' => $staffUser->id,
            'action' => 'application_submitted',
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'metadata' => [
                'status' => 'pending_review',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.applications.show', $application));

        $response->assertOk();
        $response->assertSee('Application Timeline / Status History');
        $response->assertSee('Document Uploaded');
        $response->assertSee('Application Submitted');
        $response->assertSee('TCT-TIMELINE-001');
        $response->assertSee('Electronic Copy of Title');
        $response->assertSee('Timeline records are based on audit logs');
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
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.applications.show', $application));

        $response->assertOk();
        $response->assertSee('Application Timeline / Status History');
        $response->assertSee('No timeline records found yet.');
    }
}