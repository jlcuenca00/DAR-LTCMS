<?php

namespace Tests\Feature;

use App\Models\ApplicationClearance;
use App\Models\ApplicationParcel;
use App\Models\LandTransferApplication;
use App\Models\User;
use App\Services\ApplicationClearanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalLtcForm5Test extends TestCase
{
    use RefreshDatabase;

    public function test_new_clearance_uses_next_annual_ltc_sequence_and_application_page_number(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $existingApplication = $this->makeFinalApplication($staff, 'FORM5-SEED-001', 1);

        ApplicationClearance::create([
            'land_transfer_application_id' => $existingApplication->id,
            'clearance_number' => '1803-2026-0042 (1)',
            'decision_status' => LandTransferApplication::STATUS_RELEASED,
            'application_code' => $existingApplication->application_code,
            'transferor_name' => $existingApplication->transferorDisplayName(),
            'transferee_name' => $existingApplication->transfereeDisplayName(),
            'municipality' => $existingApplication->municipality,
            'barangay' => $existingApplication->barangay,
            'total_area_hectares' => '1.0000',
            'parcel_snapshot' => [],
            'review_officer_name' => $staff->name,
            'reviewed_at' => now(),
            'generated_by' => $staff->id,
            'generated_at' => now(),
        ]);

        $application = $this->makeFinalApplication($staff, 'FORM5-NEXT-001', 7);

        ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => null,
            'parcel_code' => 'FORM5-PARCEL-001',
            'title_no' => 'T-5001',
            'tax_decl_no' => 'TD-5001',
            'lot_number' => 'LOT-5001',
            'survey_plan_number' => 'PSD-5001',
            'title_type' => 'TCT',
            'area_hectares' => 1.5000,
            'area_square_meters' => 15000,
        ]);

        $clearance = app(ApplicationClearanceService::class)
            ->generateForDecision($application, $staff->id);

        $this->assertSame('1803-2026-0043 ( 7 )', $clearance->clearance_number);
        $this->assertSame(LandTransferApplication::STATUS_RELEASED, $clearance->decision_status);
        $this->assertSame('1.5000', (string) $clearance->total_area_hectares);
        $this->assertCount(1, $clearance->parcel_snapshot);
    }

    public function test_form5_renders_all_parcels_combined_area_local_assets_and_clearance_decision_only(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeFinalApplication($staff, 'FORM5-MULTI-001', 3);
        $application->forceFill([
            'or_number' => 'OR-5001',
            'or_date' => '2026-08-20',
            'amount_paid' => 150,
            'transfer_instruments' => [['name' => 'Deed of Absolute Sale']],
        ])->save();
        $application->load('documents');

        $clearance = new ApplicationClearance([
            'land_transfer_application_id' => $application->id,
            'clearance_number' => '1803-2026-0050 ( 3 )',
            'decision_status' => LandTransferApplication::STATUS_RELEASED,
            'application_code' => $application->application_code,
            'transferor_name' => 'Juan Transferor',
            'transferee_name' => 'Maria Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'total_area_hectares' => '3.0000',
            'parcel_snapshot' => [
                [
                    'title_type' => 'TCT',
                    'title_number' => 'T-1001',
                    'tax_decl_no' => 'TD-1001',
                    'lot_number' => 'LOT-1001',
                    'survey_plan_number' => 'PSD-1001',
                    'area_hectares' => '1.0000',
                    'area_square_meters' => '10000',
                ],
                [
                    'title_type' => 'TCT',
                    'title_number' => 'T-1002',
                    'tax_decl_no' => 'TD-1002',
                    'lot_number' => 'LOT-1002',
                    'survey_plan_number' => 'PSD-1002',
                    'area_hectares' => '2.0000',
                    'area_square_meters' => '20000',
                ],
            ],
            'review_officer_name' => $staff->name,
            'reviewed_at' => now(),
            'generated_by' => $staff->id,
            'generated_at' => now(),
        ]);

        $html = view('staff.clearances.partials.form5-content', [
            'application' => $application,
            'clearance' => $clearance,
            'showToolbar' => false,
            'pdfMode' => false,
        ])->render();

        $this->assertStringContainsString('1803-2026-0050 ( 3 )', $html);
        $this->assertStringContainsString('TCT No. T-1001; TCT No. T-1002', $html);
        $this->assertStringContainsString('TD Number TD-1001; TD-1002', $html);
        $this->assertStringContainsString('LOT-1001, PSD-1001; LOT-1002, PSD-1002, with a total area of 30000 sq. m.', $html);
        $this->assertStringContainsString('GRANTED', $html);
        $this->assertStringContainsString('ENGR. MANUEL M. GALON, JR.', $html);
        $this->assertStringContainsString('images/dar-logo.svg', $html);
        $this->assertStringContainsString('images/bagong-pilipinas.png', $html);
        $this->assertStringNotContainsString('raw.githubusercontent.com', $html);
        $this->assertStringNotContainsString('ownership has been transferred', strtolower($html));
        $this->assertStringNotContainsString('registry has been updated', strtolower($html));
    }

    public function test_denied_clearance_renders_denied_not_granted(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeFinalApplication($staff, 'FORM5-DENIED-001', 1, LandTransferApplication::STATUS_DENIED);
        $application->load('documents');

        $clearance = new ApplicationClearance([
            'clearance_number' => '1803-2026-0051 ( 1 )',
            'decision_status' => LandTransferApplication::STATUS_DENIED,
            'application_code' => $application->application_code,
            'transferor_name' => $application->transferorDisplayName(),
            'transferee_name' => $application->transfereeDisplayName(),
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'total_area_hectares' => '1.0000',
            'parcel_snapshot' => [],
            'review_officer_name' => $staff->name,
            'reviewed_at' => now(),
            'generated_by' => $staff->id,
            'generated_at' => now(),
        ]);

        $html = view('staff.clearances.partials.form5-content', [
            'application' => $application,
            'clearance' => $clearance,
            'showToolbar' => false,
            'pdfMode' => false,
        ])->render();

        $this->assertMatchesRegularExpression('/decision-box[^>]*>DENIED</', $html);
        $this->assertDoesNotMatchRegularExpression('/decision-box[^>]*>GRANTED</', $html);
    }

    private function makeFinalApplication(
        User $staff,
        string $code,
        int $pageNumber,
        string $status = LandTransferApplication::STATUS_RELEASED
    ): LandTransferApplication {
        return LandTransferApplication::create([
            'application_code' => $code,
            'transferor_name' => 'Juan Transferor',
            'transferors' => [[
                'name' => 'Juan Transferor',
                'landowner_id' => null,
                'parcel_shares' => [],
            ]],
            'transferee_name' => 'Maria Transferee',
            'transferees' => [[
                'name' => 'Maria Transferee',
                'landowner_id' => null,
                'parcel_shares' => [],
            ]],
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => $status,
            'ltc_page_number' => $pageNumber,
            'encoded_by' => $staff->id,
            'reviewed_by' => $staff->id,
            'reviewed_at' => '2026-08-22 08:00:00',
            'date_of_clearance_release' => '2026-08-22',
        ]);
    }
}
