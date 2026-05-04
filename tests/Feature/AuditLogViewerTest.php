<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_audit_log_viewer(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-VIEW-001',
            'transferor_name' => 'Audit Transferor',
            'transferee_name' => 'Audit Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        AuditLog::create([
            'actor_user_id' => $staffUser->id,
            'action' => 'document_uploaded',
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'metadata' => [
                'document_reference_number' => 'TCT-TEST-001',
                'required_document_name' => 'Electronic Copy of Title',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Audit Log Viewer');
        $response->assertSee('System Activity History');
        $response->assertSee('Document Uploaded');
        $response->assertSee('AUDIT-VIEW-001');
        $response->assertSee('TCT-TEST-001');
        $response->assertSee($staffUser->name);
        $response->assertSee($staffUser->email);
    }

    public function test_staff_can_filter_audit_logs_by_action(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-FILTER-001',
            'transferor_name' => 'Filter Transferor',
            'transferee_name' => 'Filter Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        AuditLog::create([
            'actor_user_id' => $staffUser->id,
            'action' => 'document_uploaded',
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'metadata' => [
                'document_reference_number' => 'VISIBLE-001',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        AuditLog::create([
            'actor_user_id' => $staffUser->id,
            'action' => 'application_approved',
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'metadata' => [
                'decision' => 'HIDDEN-APPROVED-DECISION',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.audit-logs.index', [
                'action' => 'document_uploaded',
            ]));

        $response->assertOk();
        $response->assertSee('Showing 1 of 1 record');
        $response->assertSee('Document Uploaded');
        $response->assertSee('VISIBLE-001');

        // Do not assertDontSee('Application Approved') because it appears in the filter dropdown.
        // Instead, confirm the hidden approved log metadata is not shown in the filtered result table.
        $response->assertDontSee('HIDDEN-APPROVED-DECISION');
    }

    public function test_landowner_cannot_view_staff_audit_logs(): void
    {
        $landownerUser = User::factory()->create([
            'role' => 'landowner',
        ]);

        $response = $this->actingAs($landownerUser)
            ->get(route('staff.audit-logs.index'));

        $response->assertForbidden();
    }

    public function test_geodetic_cannot_view_staff_audit_logs(): void
    {
        $geodeticUser = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $response = $this->actingAs($geodeticUser)
            ->get(route('staff.audit-logs.index'));

        $response->assertForbidden();
    }
}