<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\SourceRecordPackageImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->post(route('staff.applications.submit', $application))
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
            ->post(route('staff.applications.documents.store', [$application, $requiredDocument]), [
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
