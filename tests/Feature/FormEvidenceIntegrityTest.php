<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\ApplicationClearanceService;
use App\Services\ApplicationRequirementService;
use App\Services\ClearanceDocumentSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormEvidenceIntegrityTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\WritesWorkflowFixtures;

    public function test_stale_form4_cannot_overwrite_newer_review_and_keeps_old_revision_on_redirect(): void
    {
        [$staff, $application] = $this->context();
        $revision = $application->fresh()->workflow_revision;
        $application->forceFill(['ltc_form4_other_findings' => 'Latest findings'])->save();
        $this->actingAs($staff)->from(route('staff.applications.show', $application))
            ->patch(route('staff.applications.form4.update', $application), [
                'expected_workflow_revision' => $revision, 'ltc_form4_other_findings' => 'Old findings',
            ])->assertSessionHasErrors('workflow_revision');
        $this->assertSame('Latest findings', $application->fresh()->ltc_form4_other_findings);
        $this->get(route('staff.applications.show', $application))->assertOk()
            ->assertSee('name="expected_workflow_revision" value="'.$revision.'"', false);
        $this->patch(route('staff.applications.form4.update', $application), [
            'ltc_form4_other_findings' => 'Missing revision',
        ])->assertSessionHasErrors('expected_workflow_revision');
    }

    public function test_form4_accepts_current_review_and_rejects_foreign_or_ineligible_sources(): void
    {
        [$staff, $application] = $this->context();
        $deed = $this->document($application, 'Original Notarized Deed or Document to be Registered', ['notary_public' => 'Reviewed notary']);
        [, $other] = $this->context('OTHER-FORM-APP');
        $foreign = $this->document($other, 'Original Notarized Deed or Document to be Registered', []);
        $affidavit = $this->document($application, 'Affidavit of Transferor', ['notary_public' => 'Unrelated notary']);
        foreach ([$foreign->id, $affidavit->id] as $id) {
            $this->actingAs($staff)->patch(route('staff.applications.form4.update', $application), [
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'ltc_transfer_document_id' => $id,
            ])->assertSessionHasErrors('ltc_transfer_document_id');
        }
        $this->patch(route('staff.applications.form4.update', $application), [
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
            'ltc_transfer_document_id' => $deed->id, 'ltc_form4_other_findings' => 'Reviewed source',
        ])->assertSessionHasNoErrors();
        $this->assertSame($deed->id, $application->fresh()->ltc_transfer_document_id);
        $this->assertSame('Reviewed source', $application->fresh()->ltc_form4_other_findings);
    }

    public function test_form5_keeps_transfer_date_and_notary_from_one_deed_despite_earlier_other_documents(): void
    {
        [$staff, $application] = $this->context();
        $this->document($application, 'Affidavit of Transferor', [
            'date_issued' => '2025-01-01', 'notarial_document_number' => 'WRONG-101', 'notary_public' => 'Wrong affidavit notary',
        ]);
        $title = $this->document($application, 'Electronic Copy of Original OCT/TCT from Register of Deeds', [
            'title_owner_names' => 'Registered owner', 'date_issued' => '2025-02-01',
        ]);
        $deed = $this->document($application, 'Original Notarized Deed or Document to be Registered', [
            'transfer_document_title' => 'Reviewed deed', 'notarization_date' => '2026-08-19',
            'notary_public' => 'Deed notary', 'notarial_page_number' => '22',
        ]);
        $application = $this->finalizeFixture($application, $staff);
        $clearance = app(ApplicationClearanceService::class)->generateForDecision($application, $staff->id);
        $snapshot = $clearance->form_snapshot;
        $this->assertSame(3, $snapshot['snapshot_version']);
        $this->assertSame($title->id, $snapshot['title_document_id']);
        $this->assertSame($deed->id, $snapshot['transfer_document_id']);
        $this->assertSame('Registered owner', $snapshot['owner_name']);
        $this->assertSame('2026-08-19', $snapshot['subject_date']);
        $this->assertSame('Deed notary', $snapshot['notary_public']);
        $this->assertNull($snapshot['notarial_document_number']);
        $this->assertSame('22', $snapshot['notarial_page_number']);
        $again = app(ApplicationClearanceService::class)->generateForDecision($application, $staff->id);
        $this->assertSame($snapshot, $again->form_snapshot);
    }

    public function test_ambiguous_sources_require_reviewed_selection_instead_of_document_order(): void
    {
        [$staff, $application] = $this->context();
        $first = $this->document($application, 'First transfer evidence', ['transfer_document_title' => 'First deed']);
        $second = $this->document($application, 'Second transfer evidence', ['transfer_document_title' => 'Second deed']);
        try {
            app(ClearanceDocumentSourceService::class)->resolve($application->fresh());
            $this->fail('Ambiguous evidence must require selection.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('ltc_transfer_document_id', $exception->errors());
        }
        $this->actingAs($staff)->get(route('staff.applications.workflow_state', $application))->assertOk()
            ->assertJsonPath('workflow_readiness.form4_complete', false);
        $this->patch(route('staff.applications.form4.update', $application), [
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
            'ltc_transfer_document_id' => $second->id,
        ])->assertSessionHasNoErrors();
        $sources = app(ClearanceDocumentSourceService::class)->resolve($application->fresh());
        $this->assertSame($second->id, $sources['transfer']->id);
        $this->assertNotSame($first->id, $sources['transfer']->id);
    }

    public function test_form3_does_not_claim_completeness_without_subject_parcel_or_check_missing_optional_evidence(): void
    {
        [, $application] = $this->context();
        $mandatory = $this->document($application, 'Required intake evidence', ['document_number' => 'RECORDED']);
        $mandatory->requiredDocument->update(['is_mandatory' => true, 'blocks_acceptance' => true]);
        $optional = RequiredDocument::forceCreate([
            'name' => 'Optional reference evidence', 'applies_to' => 'transferor', 'is_mandatory' => false,
            'requirement_classification' => RequiredDocument::CLASSIFICATION_REFERENCE, 'blocks_acceptance' => false,
        ]);
        $html = $this->form3Html($application->fresh());
        $this->assertStringContainsString('[ ] Complete and in order', $html);
        $this->assertStringContainsString('Link at least one subject Parcel', $html);
        $this->assertMatchesRegularExpression('/\\[ \\]<\/td>\\s*<td>\\s*Optional reference evidence/s', $html);
        $parcel = Parcel::create(['parcel_code' => 'FORM3-SUBJECT', 'area_hectares' => 1, 'status' => 'active']);
        $application->applicationParcels()->create(['parcel_id' => $parcel->id, 'area_hectares' => 1]);
        $html = $this->form3Html($application->fresh());
        $this->assertStringContainsString('[X] Complete and in order', $html);
        $this->assertMatchesRegularExpression('/\\[ \\]<\/td>\\s*<td>\\s*Optional reference evidence/s', $html);
        $this->assertNotNull($optional->id);
    }

    public function test_form_toolbars_do_not_count_notes_only_or_expired_evidence_as_ready(): void
    {
        [$staff, $application] = $this->context();
        $parcel = Parcel::create(['parcel_code' => 'FORM-TOOLBAR-SUBJECT', 'area_hectares' => 1, 'status' => 'active']);
        $application->applicationParcels()->create(['parcel_id' => $parcel->id, 'area_hectares' => 1]);
        $document = $this->document($application, 'Required date-sensitive evidence', []);
        $document->requiredDocument->update(['is_mandatory' => true, 'blocks_acceptance' => true, 'max_age_months' => 6]);
        $document->update(['remarks' => 'Notes only']);
        foreach ([null, '2020-01-01'] as $issued) {
            if ($issued !== null) {
                $document->update(['document_metadata' => ['date_issued' => $issued]]);
            }
            $this->actingAs($staff)->get(route('staff.applications.show', $application))->assertOk()
                ->assertSee('0 / 1 required complete')->assertSee('Requirements pending');
        }
    }

    public function test_form4_pdf_keeps_unrecorded_certification_date_blank_and_labels_draft(): void
    {
        [$staff, $application] = $this->context();
        $html = view('staff.applications.pdfs.form4-attestation-recommendation', ['application' => $application])->render();
        $this->assertStringContainsString('DRAFT', $html);
        $this->assertStringContainsString('Done this ____ day of', $html);
        $application->forceFill([
            'ltc_form4_certified_at' => '2026-08-19', 'ltc_form4_certifying_officer_name' => 'Certifying officer',
            'ltc_form4_recommendation_decision' => 'approval', 'ltc_form4_other_findings' => 'Reviewed',
        ])->save();
        $html = view('staff.applications.pdfs.form4-attestation-recommendation', ['application' => $application->fresh()])->render();
        $this->assertStringNotContainsString('DRAFT', $html);
        $this->assertStringContainsString('Done this 19 day of', $html);
        $this->actingAs($staff)->get(route('staff.applications.form4.pdf', $application))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_future_document_dates_and_decision_before_selected_deed_date_are_rejected(): void
    {
        [$staff, $application] = $this->context();
        $deed = $this->document($application, 'Original Notarized Deed or Document to be Registered', ['notarization_date' => '2026-08-19']);
        foreach (['date_issued', 'notarization_date'] as $key) {
            $this->actingAs($staff)->post(route('staff.applications.documents.store', [$application, $deed->requiredDocument]), [
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'document_metadata' => [$key => now()->addDay()->toDateString()],
            ])->assertSessionHasErrors('document_metadata.'.$key);
        }
        try {
            app(ClearanceDocumentSourceService::class)->assertChronology($application->fresh(), '2026-08-18');
            $this->fail('The selected deed cannot postdate the decision.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('decision_date', $exception->errors());
        }
        app(ClearanceDocumentSourceService::class)->assertChronology($application->fresh(), '2026-08-19');
        $application = $this->finalizeFixture($application, $staff, '2026-08-18');
        try {
            app(ApplicationClearanceService::class)->generateForDecision($application, $staff->id);
            $this->fail('Generation must also enforce selected-document chronology.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('decision_date', $exception->errors());
        }
        $this->assertDatabaseMissing('application_clearances', ['land_transfer_application_id' => $application->id]);
    }

    private function context(string $code = 'FORM-EVIDENCE-AUDIT'): array
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = LandTransferApplication::create([
            'application_code' => $code, 'transferor_name' => 'Transferor', 'transferee_name' => 'Transferee',
            'status' => LandTransferApplication::STATUS_ENDORSED_LTI, 'encoded_by' => $staff->id,
            'date_of_application' => now()->toDateString(),
        ]);
        return [$staff, $application];
    }

    private function document(LandTransferApplication $application, string $name, array $metadata): ApplicationDocument
    {
        $requirement = RequiredDocument::forceCreate(['name' => $name, 'applies_to' => 'transferor', 'is_mandatory' => false]);
        return ApplicationDocument::create([
            'land_transfer_application_id' => $application->id, 'required_document_id' => $requirement->id,
            'document_metadata' => $metadata ?: null,
        ]);
    }

    private function finalizeFixture(LandTransferApplication $application, User $staff, string $date = '2026-08-22'): LandTransferApplication
    {
        $this->writeWorkflowFixture(fn () => DB::table('land_transfer_applications')->where('id', $application->id)->update([
            'status' => LandTransferApplication::STATUS_APPROVED, 'decision_date' => $date,
            'decision_authority' => LandTransferApplication::FINAL_DECISION_AUTHORITY,
            'decision_officer_name' => 'Decision officer', 'decision_recorded_by' => $staff->id,
            'decision_recorded_at' => now(),
        ]));
        return $application->fresh();
    }

    private function form3Html(LandTransferApplication $application): string
    {
        $application->load('documents.requiredDocument');
        $requirements = RequiredDocument::all();
        $evaluation = app(ApplicationRequirementService::class)->evaluate($application);
        return view('staff.applications.pdfs.acknowledgement-receipt', [
            'application' => $application, 'transferorRequirements' => $requirements, 'transfereeRequirements' => collect(),
            'uploaded' => $application->documents->keyBy('required_document_id'),
            'blockingRequirements' => $requirements->filter(fn ($requirement) => $requirement->blocksApplication($application)),
            'requirementEvaluation' => $evaluation,
        ])->render();
    }
}
