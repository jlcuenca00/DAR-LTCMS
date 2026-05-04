<?php

namespace Tests\Feature;

use App\Models\ApplicationClearance;
use App\Models\LandTransferApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_monitoring_report_with_summary_data(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $approvedApplication = LandTransferApplication::create([
            'application_code' => 'REPORT-APPROVED-001',
            'transferor_name' => 'Report Transferor',
            'transferee_name' => 'Report Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_APPROVED,
            'encoded_by' => $staffUser->id,
            'reviewed_by' => $staffUser->id,
            'reviewed_at' => now(),
        ]);

        LandTransferApplication::create([
            'application_code' => 'REPORT-PENDING-001',
            'transferor_name' => 'Pending Transferor',
            'transferee_name' => 'Pending Transferee',
            'municipality' => 'Valencia',
            'barangay' => 'Poblacion',
            'status' => LandTransferApplication::STATUS_PENDING_REVIEW,
            'encoded_by' => $staffUser->id,
        ]);

        LandTransferApplication::create([
            'application_code' => 'REPORT-NOT-APPROVED-001',
            'transferor_name' => 'Rejected Transferor',
            'transferee_name' => 'Rejected Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Cadawinonan',
            'status' => LandTransferApplication::STATUS_NOT_APPROVED,
            'encoded_by' => $staffUser->id,
            'reviewed_by' => $staffUser->id,
            'reviewed_at' => now(),
        ]);

        ApplicationClearance::create([
            'land_transfer_application_id' => $approvedApplication->id,
            'clearance_number' => 'DAR-CLR-TEST-000001',
            'decision_status' => LandTransferApplication::STATUS_APPROVED,
            'application_code' => $approvedApplication->application_code,
            'transferor_name' => $approvedApplication->transferor_name,
            'transferee_name' => $approvedApplication->transferee_name,
            'municipality' => $approvedApplication->municipality,
            'barangay' => $approvedApplication->barangay,
            'total_area_hectares' => 3.5000,
            'parcel_snapshot' => [],
            'review_officer_name' => $staffUser->name,
            'reviewed_at' => now(),
            'generated_by' => $staffUser->id,
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.reports.monitoring.index'));

        $response->assertOk();

        $response->assertSee('Monitoring and Reports');
        $response->assertSee('Total Applications');
        $response->assertSee('Pending Review');
        $response->assertSee('Generated Clearances');
        $response->assertSee('Municipality Breakdown');
        $response->assertSee('Recent Applications');
        $response->assertSee('Recent Generated Clearances');

        $response->assertSee('REPORT-APPROVED-001');
        $response->assertSee('REPORT-PENDING-001');
        $response->assertSee('REPORT-NOT-APPROVED-001');
        $response->assertSee('DAR-CLR-TEST-000001');
        $response->assertSee('Dumaguete City');
        $response->assertSee('Valencia');
    }

    public function test_landowner_cannot_access_staff_monitoring_report(): void
    {
        $landownerUser = User::factory()->create([
            'role' => 'landowner',
        ]);

        $response = $this->actingAs($landownerUser)
            ->get(route('staff.reports.monitoring.index'));

        $response->assertForbidden();
    }

    public function test_geodetic_user_cannot_access_staff_monitoring_report(): void
    {
        $geodeticUser = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $response = $this->actingAs($geodeticUser)
            ->get(route('staff.reports.monitoring.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_staff_monitoring_report(): void
    {
        $response = $this->get(route('staff.reports.monitoring.index'));

        $response->assertRedirect(route('login'));
    }
}