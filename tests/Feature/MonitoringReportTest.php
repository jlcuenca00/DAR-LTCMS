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

    public function test_staff_report_totals_and_recorded_output_area_are_mathematically_consistent(): void
    {
        $staff = $this->makeStaff();

        $released = $this->makeApplication($staff, 'REPORT-RELEASED-001', 'Dumaguete City', 'Bantayan', LandTransferApplication::STATUS_RELEASED, '2026-08-10');
        $pending = $this->makeApplication($staff, 'REPORT-PENDING-001', 'Valencia', 'North Poblacion', LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, '2026-08-11');
        $denied = $this->makeApplication($staff, 'REPORT-DENIED-001', 'Dumaguete City', 'Cadawinonan', LandTransferApplication::STATUS_DENIED, '2026-08-12');

        $this->makeClearance($staff, $released, LandTransferApplication::STATUS_RELEASED, 3.5000, '1803-2026-0001 (1)');
        $this->makeClearance($staff, $denied, LandTransferApplication::STATUS_DENIED, 2.0000, '1803-2026-0002 (1)');

        $response = $this->actingAs($staff)->get(route('staff.reports.monitoring.index'));

        $response->assertOk();
        $response->assertViewHas('totalApplications', 3);
        $response->assertViewHas('totalClearances', 2);
        $response->assertViewHas('totalClearanceArea', fn ($value) => abs((float) $value - 5.5) < 0.0001);
        $response->assertViewHas('releasedOutputArea', fn ($value) => abs((float) $value - 3.5) < 0.0001);
        $response->assertViewHas('deniedOutputArea', fn ($value) => abs((float) $value - 2.0) < 0.0001);
        $response->assertViewHas('statusCounts', function ($counts) {
            return (int) ($counts[LandTransferApplication::STATUS_RELEASED] ?? 0) === 1
                && (int) ($counts[LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW] ?? 0) === 1
                && (int) ($counts[LandTransferApplication::STATUS_DENIED] ?? 0) === 1;
        });

        $response->assertSee('Monitoring and Reports');
        $response->assertSee('Report Filters');
        $response->assertSee('Recorded Output Area');
        $response->assertSee('Recorded Results');
        $response->assertSee('Municipality Breakdown');
        $response->assertSee('REPORT-RELEASED-001');
        $response->assertSee('REPORT-PENDING-001');
        $response->assertSee('REPORT-DENIED-001');
        $response->assertSee('1803-2026-0001 (1)');
        $response->assertSee('not ownership transferred');
        $response->assertSee('do not automatically transfer land ownership');

        $this->assertSame($pending->id, $pending->fresh()->id);
    }

    public function test_filters_apply_consistently_to_totals_breakdowns_recent_rows_and_clearances(): void
    {
        $staff = $this->makeStaff();

        $releasedDumaguete = $this->makeApplication($staff, 'FILTER-KEEP-RELEASED', 'Dumaguete City', 'Bantayan', LandTransferApplication::STATUS_RELEASED, '2026-08-10');
        $legacyApprovedDumaguete = $this->makeApplication($staff, 'FILTER-KEEP-LEGACY', 'Dumaguete City', 'Bantayan', LandTransferApplication::STATUS_APPROVED, '2026-08-15');
        $releasedValencia = $this->makeApplication($staff, 'FILTER-DROP-MUNICIPALITY', 'Valencia', 'North Poblacion', LandTransferApplication::STATUS_RELEASED, '2026-08-10');
        $deniedDumaguete = $this->makeApplication($staff, 'FILTER-DROP-STATUS', 'Dumaguete City', 'Bantayan', LandTransferApplication::STATUS_DENIED, '2026-08-10');
        $releasedOld = $this->makeApplication($staff, 'FILTER-DROP-DATE', 'Dumaguete City', 'Bantayan', LandTransferApplication::STATUS_RELEASED, '2026-07-15');

        $this->makeClearance($staff, $releasedDumaguete, LandTransferApplication::STATUS_RELEASED, 3.0000, '1803-2026-0010 (1)');
        $this->makeClearance($staff, $legacyApprovedDumaguete, LandTransferApplication::STATUS_APPROVED, 1.5000, '1803-2026-0011 (1)');
        $this->makeClearance($staff, $releasedValencia, LandTransferApplication::STATUS_RELEASED, 4.0000, '1803-2026-0012 (1)');
        $this->makeClearance($staff, $deniedDumaguete, LandTransferApplication::STATUS_DENIED, 5.0000, '1803-2026-0013 (1)');
        $this->makeClearance($staff, $releasedOld, LandTransferApplication::STATUS_RELEASED, 6.0000, '1803-2026-0014 (1)');

        $params = [
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
            'status' => LandTransferApplication::STATUS_RELEASED,
            'municipality' => 'Dumaguete City',
        ];

        $response = $this->actingAs($staff)->get(route('staff.reports.monitoring.index', $params));

        $response->assertOk();
        $response->assertViewHas('totalApplications', 2);
        $response->assertViewHas('totalClearances', 2);
        $response->assertViewHas('totalClearanceArea', fn ($value) => abs((float) $value - 4.5) < 0.0001);
        $response->assertViewHas('releasedOutputArea', fn ($value) => abs((float) $value - 4.5) < 0.0001);
        $response->assertViewHas('deniedOutputArea', fn ($value) => abs((float) $value) < 0.0001);
        $response->assertViewHas('recentApplications', function ($rows) {
            return $rows->pluck('application_code')->sort()->values()->all() === ['FILTER-KEEP-LEGACY', 'FILTER-KEEP-RELEASED'];
        });
        $response->assertViewHas('recentClearances', function ($rows) {
            return $rows->pluck('application_code')->sort()->values()->all() === ['FILTER-KEEP-LEGACY', 'FILTER-KEEP-RELEASED'];
        });
        $response->assertViewHas('municipalityBreakdown', function ($rows) {
            return $rows->count() === 1
                && $rows->first()->municipality === 'Dumaguete City'
                && (int) $rows->first()->total === 2;
        });

        $response->assertSee('FILTER-KEEP-RELEASED');
        $response->assertSee('FILTER-KEEP-LEGACY');
        $response->assertDontSee('FILTER-DROP-MUNICIPALITY');
        $response->assertDontSee('FILTER-DROP-STATUS');
        $response->assertDontSee('FILTER-DROP-DATE');
        $response->assertSee('From 2026-08-01');
        $response->assertSee('To 2026-08-31');
    }

    public function test_date_bounds_can_be_used_independently(): void
    {
        $staff = $this->makeStaff();

        $this->makeApplication($staff, 'DATE-JULY', 'Dumaguete City', 'Bantayan', LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, '2026-07-15');
        $this->makeApplication($staff, 'DATE-AUGUST', 'Dumaguete City', 'Bantayan', LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, '2026-08-15');

        $fromOnly = $this->actingAs($staff)->get(route('staff.reports.monitoring.index', [
            'date_from' => '2026-08-01',
        ]));
        $fromOnly->assertOk()->assertViewHas('totalApplications', 1);
        $fromOnly->assertSee('DATE-AUGUST')->assertDontSee('DATE-JULY');

        $toOnly = $this->actingAs($staff)->get(route('staff.reports.monitoring.index', [
            'date_to' => '2026-07-31',
        ]));
        $toOnly->assertOk()->assertViewHas('totalApplications', 1);
        $toOnly->assertSee('DATE-JULY')->assertDontSee('DATE-AUGUST');
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)
            ->from(route('staff.reports.monitoring.index'))
            ->get(route('staff.reports.monitoring.index', [
                'date_from' => '2026-08-20',
                'date_to' => '2026-08-01',
            ]))
            ->assertRedirect(route('staff.reports.monitoring.index'))
            ->assertSessionHasErrors('date_to');

        $this->actingAs($staff)
            ->from(route('staff.reports.monitoring.index'))
            ->get(route('staff.reports.monitoring.index', [
                'municipality' => 'Cebu City',
                'status' => 'ownership_transferred',
            ]))
            ->assertRedirect(route('staff.reports.monitoring.index'))
            ->assertSessionHasErrors(['municipality', 'status']);
    }

    public function test_print_report_uses_the_same_filter_dataset_and_scope_language(): void
    {
        $staff = $this->makeStaff();

        $kept = $this->makeApplication($staff, 'PRINT-KEEP-001', 'Dumaguete City', 'Bantayan', LandTransferApplication::STATUS_RELEASED, '2026-08-10');
        $dropped = $this->makeApplication($staff, 'PRINT-DROP-001', 'Valencia', 'North Poblacion', LandTransferApplication::STATUS_RELEASED, '2026-08-10');

        $this->makeClearance($staff, $kept, LandTransferApplication::STATUS_RELEASED, 2.2500, '1803-2026-0020 (1)');
        $this->makeClearance($staff, $dropped, LandTransferApplication::STATUS_RELEASED, 8.0000, '1803-2026-0021 (1)');

        $response = $this->actingAs($staff)->get(route('staff.reports.monitoring.print', [
            'municipality' => 'Dumaguete City',
        ]));

        $response->assertOk();
        $response->assertViewHas('totalApplications', 1);
        $response->assertViewHas('totalClearances', 1);
        $response->assertViewHas('totalClearanceArea', fn ($value) => abs((float) $value - 2.25) < 0.0001);
        $response->assertSee('Monitoring Report');
        $response->assertSee('Department of Agrarian Reform');
        $response->assertSee('Negros Oriental Provincial Office');
        $response->assertSee('Report Filters');
        $response->assertSee('Dumaguete City');
        $response->assertSee('PRINT-KEEP-001');
        $response->assertDontSee('PRINT-DROP-001');
        $response->assertSee('not ownership transferred');
        $response->assertSee('does not constitute a registry mutation');
    }

    public function test_non_staff_users_cannot_access_monitoring_or_print_reports(): void
    {
        foreach ([User::ROLE_LANDOWNER, User::ROLE_GEODETIC] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($user)
                ->get(route('staff.reports.monitoring.index'))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('staff.reports.monitoring.print'))
                ->assertForbidden();
        }
    }

    public function test_guest_is_redirected_from_monitoring_and_print_reports(): void
    {
        $this->get(route('staff.reports.monitoring.index'))->assertRedirect(route('login'));
        $this->get(route('staff.reports.monitoring.print'))->assertRedirect(route('login'));
    }

    private function makeStaff(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
    }

    private function makeApplication(
        User $staff,
        string $code,
        string $municipality,
        string $barangay,
        string $status,
        string $dateOfApplication
    ): LandTransferApplication {
        return LandTransferApplication::create([
            'application_code' => $code,
            'transferor_name' => $code . ' Transferor',
            'transferee_name' => $code . ' Transferee',
            'municipality' => $municipality,
            'barangay' => $barangay,
            'date_of_application' => $dateOfApplication,
            'status' => $status,
            'encoded_by' => $staff->id,
            'reviewed_by' => in_array($status, [
                LandTransferApplication::STATUS_RELEASED,
                LandTransferApplication::STATUS_DENIED,
                LandTransferApplication::STATUS_APPROVED,
                LandTransferApplication::STATUS_NOT_APPROVED,
            ], true) ? $staff->id : null,
            'reviewed_at' => in_array($status, [
                LandTransferApplication::STATUS_RELEASED,
                LandTransferApplication::STATUS_DENIED,
                LandTransferApplication::STATUS_APPROVED,
                LandTransferApplication::STATUS_NOT_APPROVED,
            ], true) ? now() : null,
        ]);
    }

    private function makeClearance(
        User $staff,
        LandTransferApplication $application,
        string $decisionStatus,
        float $area,
        string $clearanceNumber
    ): ApplicationClearance {
        return ApplicationClearance::create([
            'land_transfer_application_id' => $application->id,
            'clearance_number' => $clearanceNumber,
            'decision_status' => $decisionStatus,
            'application_code' => $application->application_code,
            'transferor_name' => $application->transferorDisplayName(),
            'transferee_name' => $application->transfereeDisplayName(),
            'municipality' => $application->municipality,
            'barangay' => $application->barangay,
            'total_area_hectares' => $area,
            'parcel_snapshot' => [],
            'review_officer_name' => $staff->name,
            'reviewed_at' => now(),
            'generated_by' => $staff->id,
            'generated_at' => now(),
        ]);
    }
}
