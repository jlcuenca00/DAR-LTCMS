<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\SourceRecordPackageImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InputSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_record_filters_reject_invalid_or_oversized_input(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.legacy-records.index', [
                'record_type' => 'not-a-real-record-type',
            ]))
            ->assertSessionHasErrors('record_type');

        $this->actingAs($staff)
            ->get(route('staff.legacy-records.index', [
                'search' => str_repeat('x', 256),
            ]))
            ->assertSessionHasErrors('search');
    }

    public function test_import_commit_rejects_rows_not_authorized_by_the_server_preview(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $batch = SourceRecordPackageImportBatch::create([
            'original_filename' => 'input-security.csv',
            'status' => 'previewed',
            'total_rows' => 1,
            'valid_rows' => 1,
            'error_rows' => 0,
            'duplicate_rows' => 0,
            'uploaded_by_user_id' => $staff->id,
            'preview_rows' => [[
                'row_index' => 2,
                'status' => 'valid',
                'possible_duplicate' => false,
                'errors' => [],
                'warnings' => [],
                'data' => [
                    'include_title' => false,
                    'include_landholding' => false,
                    'include_parcel_source' => false,
                    'include_historical_clearance' => false,
                ],
            ]],
            'summary' => [],
        ]);

        $this->actingAs($staff)
            ->from(route('staff.source-record-package-imports.preview', $batch))
            ->post(route('staff.source-record-package-imports.commit', $batch), [
                'selected_rows' => [999],
            ])
            ->assertRedirect(route('staff.source-record-package-imports.preview', $batch))
            ->assertSessionHasErrors('selected_rows');

        $this->assertSame('previewed', $batch->fresh()->status);
    }

    public function test_import_commit_requires_a_bounded_integer_row_array(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $batch = SourceRecordPackageImportBatch::create([
            'original_filename' => 'input-shape.csv',
            'status' => 'previewed',
            'total_rows' => 1,
            'valid_rows' => 1,
            'error_rows' => 0,
            'duplicate_rows' => 0,
            'uploaded_by_user_id' => $staff->id,
            'preview_rows' => [[
                'row_index' => 2,
                'status' => 'valid',
                'possible_duplicate' => false,
                'errors' => [],
                'warnings' => [],
                'data' => [],
            ]],
            'summary' => [],
        ]);

        $this->actingAs($staff)
            ->from(route('staff.source-record-package-imports.preview', $batch))
            ->post(route('staff.source-record-package-imports.commit', $batch), [
                'selected_rows' => '2',
            ])
            ->assertSessionHasErrors('selected_rows');

        $this->actingAs($staff)
            ->from(route('staff.source-record-package-imports.preview', $batch))
            ->post(route('staff.source-record-package-imports.commit', $batch), [
                'selected_rows' => [2, 2],
            ])
            ->assertSessionHasErrors('selected_rows.1');

        $this->assertSame('previewed', $batch->fresh()->status);
    }

    public function test_source_import_preview_enforces_a_bounded_row_count(): void
    {
        config(['dar_ltc.source_import_max_rows' => 2]);

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $csv = $this->sourceImportCsv([
            $this->sourceImportRow('TCT-BOUND-001'),
            $this->sourceImportRow('TCT-BOUND-002'),
            $this->sourceImportRow('TCT-BOUND-003'),
        ]);

        $this->actingAs($staff)
            ->from(route('staff.source-record-package-imports.create'))
            ->post(route('staff.source-record-package-imports.preview.store'), [
                'import_file' => UploadedFile::fake()->createWithContent('bounded-import.csv', $csv),
            ])
            ->assertRedirect(route('staff.source-record-package-imports.create'))
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseCount('source_record_package_import_batches', 0);
    }

    public function test_source_import_preview_uses_manual_field_limits_and_location_normalization(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $invalid = $this->sourceImportRow('TCT-VALIDATION-001');
        $invalid[5] = str_repeat('x', 256);
        $invalid[14] = '1000000';

        $csv = $this->sourceImportCsv([$invalid]);

        $this->actingAs($staff)
            ->post(route('staff.source-record-package-imports.preview.store'), [
                'import_file' => UploadedFile::fake()->createWithContent('validation-import.csv', $csv),
            ])
            ->assertRedirect();

        $batch = SourceRecordPackageImportBatch::latest('id')->firstOrFail();
        $this->assertSame('error', $batch->preview_rows[0]['status']);
        $this->assertNotEmpty($batch->preview_rows[0]['errors']);

        $valid = $this->sourceImportRow('TCT-VALIDATION-002');
        $valid[17] = 'dumaguete city';
        $valid[16] = 'bantayan';

        $this->actingAs($staff)
            ->post(route('staff.source-record-package-imports.preview.store'), [
                'import_file' => UploadedFile::fake()->createWithContent(
                    'normalized-import.csv',
                    $this->sourceImportCsv([$valid])
                ),
            ])
            ->assertRedirect();

        $normalizedBatch = SourceRecordPackageImportBatch::latest('id')->firstOrFail();
        $row = $normalizedBatch->preview_rows[0];

        $this->assertSame('valid', $row['status']);
        $this->assertSame('Dumaguete City', $row['data']['municipality']);
        $this->assertSame('Bantayan', $row['data']['barangay']);
        $this->assertSame('Negros Oriental', $row['data']['province']);
    }

    public function test_form4_rejects_unknown_finding_codes(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'FORM4-INPUT-HARDENING-001',
            'transferor_name' => 'Input Hardening Transferor',
            'transferee_name' => 'Input Hardening Transferee',
            'status' => LandTransferApplication::STATUS_ENDORSED_LTI,
            'encoded_by' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->from(route('staff.applications.show', $application))
            ->patch(route('staff.applications.form4.update', $application), [
                    'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'ltc_form4_subject_land_findings' => ['not_a_real_subject_finding'],
                'ltc_form4_recommendation_findings' => ['application_complete'],
                'ltc_form4_recommendation_decision' => 'approval',
                'ltc_form4_certified_at' => now()->toDateString(),
                'ltc_form4_certifying_officer_name' => 'Authorized Review Officer',
            ])
            ->assertSessionHasErrors('ltc_form4_subject_land_findings.0');

        $application->refresh();
        $this->assertNull($application->ltc_form4_subject_land_findings);
    }

    public function test_form4_readiness_ignores_unknown_persisted_finding_codes(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'FORM4-READINESS-HARDENING-001',
            'transferor_name' => 'Readiness Transferor',
            'transferee_name' => 'Readiness Transferee',
            'status' => LandTransferApplication::STATUS_RETURNED_TO_LEGAL,
            'encoded_by' => $staff->id,
        ]);

        $application->forceFill([
            'ltc_form4_subject_land_findings' => ['not_a_real_subject_finding'],
            'ltc_form4_recommendation_findings' => ['not_a_real_recommendation_finding'],
            'ltc_form4_recommendation_decision' => 'approval',
            'ltc_form4_certified_at' => now()->toDateString(),
            'ltc_form4_certifying_officer_name' => 'Authorized Review Officer',
        ])->save();

        $this->actingAs($staff)
            ->from(route('staff.applications.show', $application))
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
            ])
            ->assertSessionHasErrors('form4');

        $this->assertSame(
            LandTransferApplication::STATUS_RETURNED_TO_LEGAL,
            $application->fresh()->status
        );
    }

    public function test_document_metadata_rejects_unspecified_nested_keys(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'DOC-METADATA-HARDENING-001',
            'transferor_name' => 'Metadata Transferor',
            'transferee_name' => 'Metadata Transferee',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Metadata Hardening Requirement',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
        ]);

        $this->actingAs($staff)
            ->from(route('staff.applications.show', $application))
            ->post(route('staff.applications.documents.store', [$application, $requiredDocument]), [ 'expected_workflow_revision' => $application->fresh()->workflow_revision, 
                'document_metadata' => [
                    'title_number' => 'TCT-SAFE-001',
                    'unexpected_privileged_key' => 'must-not-persist',
                ],
            ])
            ->assertSessionHasErrors('document_metadata');

        $this->assertFalse(ApplicationDocument::query()
            ->where('land_transfer_application_id', $application->id)
            ->where('required_document_id', $requiredDocument->id)
            ->exists());
    }

    public function test_source_record_validation_errors_are_rendered_with_text_content(): void
    {
        $view = file_get_contents(resource_path('views/staff/source-record-packages/create.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString("errorText.textContent = String(message || '');", $view);
        $this->assertStringNotContainsString('errorLine.innerHTML', $view);
    }

    public function test_staff_workflow_failures_do_not_render_raw_exception_messages(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Staff/ApplicationWorkflowController.php'));

        $this->assertIsString($controller);
        $this->assertStringNotContainsString("getMessage()", $controller);
        $this->assertGreaterThanOrEqual(3, substr_count($controller, 'report($e);'));
    }

    private function sourceImportCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, [
            'include_title',
            'include_landholding',
            'include_parcel_source',
            'include_historical_clearance',
            'source_record_scope',
            'landowner_name',
            'parcel_code',
            'title_number',
            'landholding_reference_number',
            'control_number',
            'transferor_name',
            'transferee_name',
            'lot_number',
            'survey_number',
            'area_hectares',
            'crop_or_land_use',
            'barangay',
            'municipality',
            'province',
            'source_geometry_geojson',
            'boundary_description',
            'source_book',
            'page_number',
            'transcribed_by',
            'transcription_date',
            'remarks',
            'source_notes',
        ]);

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return (string) $csv;
    }

    private function sourceImportRow(string $titleNumber): array
    {
        return [
            'yes',
            'no',
            'no',
            'no',
            'reference_only',
            'CSV Import Owner',
            '',
            $titleNumber,
            '',
            '',
            '',
            '',
            '',
            '',
            '1.2500',
            'Agricultural',
            'Bantayan',
            'Dumaguete City',
            'Negros Oriental',
            '',
            '',
            'Security Import Book',
            '',
            'Security Import Test',
            now()->toDateString(),
            '',
            '',
        ];
    }

    public function test_web_mutation_rejects_a_missing_csrf_token(): void
    {
        $originalEnvironment = app()->environment();

        try {
            // Laravel intentionally bypasses CSRF while running unit tests.
            // Temporarily use a non-testing environment so this request exercises
            // the real web middleware contract instead of the testing bypass.
            app()['env'] = 'production';

            $this->post(route('register'), [
                'name' => 'CSRF Probe',
                'username' => 'csrf.probe',
                'password' => 'SecurePass321!',
                'password_confirmation' => 'SecurePass321!',
            ])->assertStatus(419);
        } finally {
            app()['env'] = $originalEnvironment;
        }

        $this->assertDatabaseMissing('users', [
            'username' => 'csrf.probe',
        ]);
    }
}
