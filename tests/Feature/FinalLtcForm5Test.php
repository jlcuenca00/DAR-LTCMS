<?php

namespace Tests\Feature;

use App\Models\ApplicationClearance;
use App\Models\ApplicationDocument;
use App\Models\ApplicationParcel;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\ApplicationClearanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $this->assertSame('1803-2026-0043 (7)', $clearance->clearance_number);
        $this->assertSame(LandTransferApplication::STATUS_APPROVED, $clearance->decision_status);
        $this->assertSame(LandTransferApplication::FINAL_DECISION_AUTHORITY, $clearance->decision_authority);
        $this->assertSame('PARPO II Test Signatory', $clearance->decision_officer_name);
        $this->assertSame('2026-08-22', $clearance->decision_date?->toDateString());
        $this->assertSame($staff->id, $clearance->decision_recorded_by);
        $this->assertSame('1.5000', (string) $clearance->total_area_hectares);
        $this->assertCount(1, $clearance->parcel_snapshot);
        $this->assertSame(1, data_get($clearance->form_snapshot, 'snapshot_version'));
    }

    public function test_form5_renders_all_parcels_combined_area_local_assets_and_clearance_decision_only(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeFinalApplication($staff, 'FORM5-MULTI-001', 3);
        $application->forceFill([
            'or_number' => 'OR-5001',
            'or_date' => '2026-08-20',
            'amount_paid' => config('dar_ltc.filing_fee', 2000),
            'transfer_instruments' => [['name' => 'Changed Live Transfer Instrument']],
        ])->save();

        $clearance = new ApplicationClearance([
            'land_transfer_application_id' => $application->id,
            'clearance_number' => '1803-2026-0050 (3)',
            'decision_status' => LandTransferApplication::STATUS_APPROVED,
            'decision_authority' => LandTransferApplication::FINAL_DECISION_AUTHORITY,
            'decision_officer_name' => 'PARPO II Snapshot Signatory',
            'decision_date' => '2026-08-22',
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
            'form_snapshot' => [
                'snapshot_version' => 1,
                'owner_name' => 'Snapshot Title Owner',
                'subject_of' => 'Snapshot Deed of Absolute Sale',
                'subject_date' => '2026-08-19',
                'notarial_document_number' => '77',
                'notarial_page_number' => '88',
                'notarial_book_number' => '9',
                'notarial_series' => '2026',
                'notary_public' => 'Atty. Snapshot Notary',
                'or_number' => 'OR-SNAPSHOT-5001',
                'or_date' => '2026-08-18',
                'amount_paid' => '2000.00',
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

        $this->assertStringContainsString('1803-2026-0050 (3)', $html);
        $this->assertStringContainsString('TCT No. T-1001; TCT No. T-1002', $html);
        $this->assertStringContainsString('TD Number TD-1001; TD-1002', $html);
        $this->assertStringContainsString('LOT-1001, PSD-1001; LOT-1002, PSD-1002, with a total area of 30000 sq. m.', $html);
        $this->assertStringContainsString('GRANTED', $html);
        $this->assertStringContainsString('PARPO II Snapshot Signatory', $html);
        $this->assertStringContainsString(LandTransferApplication::FINAL_DECISION_AUTHORITY, $html);
        $this->assertStringContainsString('Snapshot Title Owner', $html);
        $this->assertStringContainsString('Snapshot Deed of Absolute Sale dated 08/19/2026', $html);
        $this->assertStringContainsString('Doc No. 77, Page No. 88, Book No. 9, Series of 2026', $html);
        $this->assertStringContainsString('Atty. Snapshot Notary', $html);
        $this->assertStringContainsString('OR-SNAPSHOT-5001', $html);
        $this->assertStringContainsString('08/18/2026', $html);
        $this->assertStringNotContainsString('Changed Live Transfer Instrument', $html);
        $this->assertStringNotContainsString('ENGR. MANUEL M. GALON, JR.', $html);
        $this->assertStringContainsString('images/dar-logo.svg', $html);
        $this->assertStringContainsString('images/bagong-pilipinas.png', $html);
        $this->assertStringNotContainsString('raw.githubusercontent.com', $html);
        $this->assertStringNotContainsString('ownership has been transferred', strtolower($html));
        $this->assertStringNotContainsString('registry has been updated', strtolower($html));
    }

    public function test_generated_form5_snapshot_preserves_document_and_payment_values_after_live_records_change(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeFinalApplication($staff, 'FORM5-FULL-SNAPSHOT-001', 2);
        $application->forceFill([
            'or_number' => 'OR-ORIGINAL',
            'or_date' => '2026-08-20',
            'amount_paid' => 2000,
            'transfer_instruments' => [['name' => 'Deed of Absolute Sale']],
        ])->save();

        $requirement = RequiredDocument::forceCreate([
            'name' => 'Form 5 Metadata Source',
            'applies_to' => 'transferor',
            'is_mandatory' => false,
        ]);

        $document = ApplicationDocument::create([
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requirement->id,
            'file_path' => 'applications/form5-metadata-source.pdf',
            'original_filename' => 'form5-metadata-source.pdf',
            'uploaded_by' => $staff->id,
            'document_metadata' => [
                'title_owner_names' => 'Original Snapshot Owner',
                'transfer_document_title' => 'Original Snapshot Deed',
                'notarization_date' => '2026-08-19',
                'notarial_document_number' => '101',
                'notarial_page_number' => '22',
                'notarial_book_number' => '3',
                'notarial_series' => '2026',
                'notary_public' => 'Atty. Original Snapshot',
            ],
        ]);

        $clearance = app(ApplicationClearanceService::class)
            ->generateForDecision($application, $staff->id);

        $this->assertSame('Original Snapshot Owner', data_get($clearance->form_snapshot, 'owner_name'));
        $this->assertSame('Original Snapshot Deed', data_get($clearance->form_snapshot, 'subject_of'));
        $this->assertSame('OR-ORIGINAL', data_get($clearance->form_snapshot, 'or_number'));

        $application->forceFill([
            'or_number' => 'OR-CHANGED-LIVE',
            'or_date' => '2026-08-21',
            'amount_paid' => 9999,
            'transfer_instruments' => [['name' => 'Changed Live Instrument']],
        ])->save();

        $document->forceFill([
            'document_metadata' => [
                'title_owner_names' => 'Changed Live Owner',
                'transfer_document_title' => 'Changed Live Deed',
                'notary_public' => 'Atty. Changed Live',
            ],
        ])->save();

        $html = view('staff.clearances.partials.form5-content', [
            'application' => $application->fresh(),
            'clearance' => $clearance->fresh(),
            'showToolbar' => false,
            'pdfMode' => false,
        ])->render();

        $this->assertStringContainsString('Original Snapshot Owner', $html);
        $this->assertStringContainsString('Original Snapshot Deed dated 08/19/2026', $html);
        $this->assertStringContainsString('Atty. Original Snapshot', $html);
        $this->assertStringContainsString('OR-ORIGINAL', $html);
        $this->assertStringNotContainsString('Changed Live Owner', $html);
        $this->assertStringNotContainsString('Changed Live Deed', $html);
        $this->assertStringNotContainsString('Atty. Changed Live', $html);
        $this->assertStringNotContainsString('OR-CHANGED-LIVE', $html);
    }

    public function test_form5_issuance_date_does_not_change_when_client_release_is_recorded_later(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeFinalApplication(
            $staff,
            'FORM5-IMMUTABLE-DATE-001',
            1,
            LandTransferApplication::STATUS_APPROVED
        );

        $application->forceFill([
            'date_of_clearance_release' => '2026-09-30',
        ])->save();
        $application->load('documents');

        $clearance = new ApplicationClearance([
            'clearance_number' => '1803-2026-0052 (1)',
            'decision_status' => LandTransferApplication::STATUS_APPROVED,
            'decision_authority' => LandTransferApplication::FINAL_DECISION_AUTHORITY,
            'decision_officer_name' => 'PARPO II Test Signatory',
            'decision_date' => '2026-09-19',
            'application_code' => $application->application_code,
            'transferor_name' => $application->transferorDisplayName(),
            'transferee_name' => $application->transfereeDisplayName(),
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'total_area_hectares' => '1.0000',
            'parcel_snapshot' => [],
            'review_officer_name' => $staff->name,
            'reviewed_at' => '2026-09-20 09:00:00',
            'generated_by' => $staff->id,
            'generated_at' => '2026-09-20 09:05:00',
        ]);

        $html = view('staff.clearances.partials.form5-content', [
            'application' => $application,
            'clearance' => $clearance,
            'showToolbar' => false,
            'pdfMode' => false,
        ])->render();

        $this->assertStringContainsString('September 19, 2026', $html);
        $this->assertStringNotContainsString('September 20, 2026', $html);
        $this->assertStringNotContainsString('September 30, 2026', $html);
    }

    public function test_form5_refuses_unknown_decision_status_instead_of_rendering_denied(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeFinalApplication($staff, 'FORM5-INVALID-STATUS-001', 1);

        $clearance = new ApplicationClearance([
            'clearance_number' => '1803-2026-0099 (1)',
            'decision_status' => 'unexpected_status',
            'application_code' => $application->application_code,
            'transferor_name' => $application->transferorDisplayName(),
            'transferee_name' => $application->transfereeDisplayName(),
            'municipality' => $application->municipality,
            'barangay' => $application->barangay,
            'total_area_hectares' => '1.0000',
            'parcel_snapshot' => [],
            'review_officer_name' => $staff->name,
            'reviewed_at' => now(),
            'generated_by' => $staff->id,
            'generated_at' => now(),
        ]);

        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('Invalid final decision status');

        view('staff.clearances.partials.form5-content', [
            'application' => $application,
            'clearance' => $clearance,
            'showToolbar' => false,
            'pdfMode' => false,
        ])->render();
    }

    public function test_new_form5_generation_rejects_historical_negative_status(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeFinalApplication(
            $staff,
            'FORM5-NO-NEW-DENIED-001',
            1,
            LandTransferApplication::STATUS_NOT_APPROVED
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('New LTC Form No. 5 outputs can only be generated for Approved clearance decisions.');

        app(ApplicationClearanceService::class)->generateForDecision($application, $staff->id);
    }

    public function test_historical_not_approved_clearance_still_renders_denied_not_granted(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeFinalApplication($staff, 'FORM5-DENIED-001', 1, LandTransferApplication::STATUS_NOT_APPROVED);
        $application->load('documents');

        $clearance = new ApplicationClearance([
            'clearance_number' => '1803-2026-0051 (1)',
            'decision_status' => LandTransferApplication::STATUS_NOT_APPROVED,
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
        string $status = LandTransferApplication::STATUS_APPROVED
    ): LandTransferApplication {
        $historicalNegative = in_array($status, [
            LandTransferApplication::STATUS_NOT_APPROVED,
            LandTransferApplication::STATUS_DENIED,
        ], true);

        $application = LandTransferApplication::create([
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
            'status' => $historicalNegative
                ? LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW
                : $status,
            'ltc_page_number' => $pageNumber,
            'encoded_by' => $staff->id,
            'reviewed_by' => $staff->id,
            'reviewed_at' => '2026-08-22 08:00:00',
            'decision_authority' => LandTransferApplication::FINAL_DECISION_AUTHORITY,
            'decision_officer_name' => 'PARPO II Test Signatory',
            'decision_date' => '2026-08-22',
            'decision_recorded_by' => $staff->id,
            'decision_recorded_at' => '2026-08-22 08:00:00',
            'date_of_clearance_release' => '2026-08-22',
        ]);

        if ($historicalNegative) {
            DB::table('land_transfer_applications')
                ->where('id', $application->id)
                ->update(['status' => $status]);
            $application->refresh();
        }

        return $application;
    }
}
