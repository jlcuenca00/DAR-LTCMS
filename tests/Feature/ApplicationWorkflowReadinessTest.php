<?php

namespace Tests\Feature;

use App\Models\ApplicationComplianceNotice;
use App\Models\ApplicationParcel;
use App\Models\AuditLog;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\ApplicationWorkflowDependencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationWorkflowReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_legacy_draft_advancement_does_not_normalize_the_status(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_DRAFT, 'READINESS-LEGACY-DRAFT');

        RequiredDocument::forceCreate([
            'name' => 'Legacy Draft Required Document',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'blocks_acceptance' => true,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHasErrors('validation');

        $this->assertSame(
            LandTransferApplication::STATUS_DRAFT,
            $application->fresh()->status
        );
    }

    public function test_stale_advance_form_cannot_replay_into_the_next_stage(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_AWAITING_PAYMENT,
            'READINESS-STALE-ADVANCE'
        );
        DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update([
                'payment_order_reference' => 'OP-READINESS-STALE-ADVANCE',
                'payment_order_issued_at' => now(),
            ]);
        $application->refresh();

        $renderedStatus = LandTransferApplication::STATUS_AWAITING_PAYMENT;
        $renderedRevision = (int) $application->fresh()->workflow_revision;
        $payload = [
            'expected_status' => $renderedStatus,
            'expected_workflow_revision' => $renderedRevision,
            'or_number' => 'OR-READINESS-STALE-ADVANCE',
            'or_date' => now()->toDateString(),
            'amount_paid' => config('dar_ltc.filing_fee', 2000),
        ];

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), $payload)
            ->assertSessionHas('success');

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_LTI,
            $application->fresh()->status
        );

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), $payload)
            ->assertSessionHasErrors('workflow_revision');

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_LTI,
            $application->fresh()->status
        );
    }

    public function test_stale_compliance_request_cannot_attach_to_a_newer_stage(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_ENDORSED_LTI,
            'READINESS-STALE-COMPLIANCE'
        );

        $renderedStatus = LandTransferApplication::STATUS_ENDORSED_LTI;
        $renderedRevision = (int) $application->fresh()->workflow_revision;

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $renderedStatus,
                'expected_workflow_revision' => $renderedRevision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHas('success');

        $this->assertSame(
            LandTransferApplication::STATUS_RETURNED_TO_LEGAL,
            $application->fresh()->status
        );

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.request', $application), [
                'expected_status' => $renderedStatus,
                'expected_workflow_revision' => $renderedRevision,
                'category' => ApplicationComplianceNotice::CATEGORY_CLARIFICATION,
                'details' => 'This request came from a stale page and must not attach to the newer stage.',
            ])
            ->assertSessionHasErrors('workflow_revision');

        $this->assertSame(
            LandTransferApplication::STATUS_RETURNED_TO_LEGAL,
            $application->fresh()->status
        );
        $this->assertDatabaseCount('application_compliance_notices', 0);
    }

    public function test_stale_compliance_resolution_cannot_resolve_a_newer_notice(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_LEGAL_EVALUATION,
            'READINESS-STALE-COMPLIANCE-RESOLVE'
        );

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.request', $application), [
                'expected_status' => LandTransferApplication::STATUS_LEGAL_EVALUATION,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'category' => ApplicationComplianceNotice::CATEGORY_CLARIFICATION,
                'details' => 'First compliance request.',
            ])
            ->assertSessionHas('success');

        $firstNotice = $application->complianceNotices()->latest('id')->firstOrFail();

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.resolve', $application), [
                'expected_status' => LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'compliance_notice_id' => $firstNotice->id,
                'resolution_note' => 'First request resolved.',
            ])
            ->assertSessionHas('success');

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.request', $application), [
                'expected_status' => LandTransferApplication::STATUS_LEGAL_EVALUATION,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'category' => ApplicationComplianceNotice::CATEGORY_ADDITIONAL_INFORMATION,
                'details' => 'Second compliance request.',
            ])
            ->assertSessionHas('success');

        $secondNotice = $application->complianceNotices()->latest('id')->firstOrFail();
        $this->assertNotSame($firstNotice->id, $secondNotice->id);

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.resolve', $application), [
                'expected_status' => LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'compliance_notice_id' => $firstNotice->id,
                'resolution_note' => 'Stale resolution attempt.',
            ])
            ->assertSessionHasErrors('compliance');

        $this->assertSame(
            LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE,
            $application->fresh()->status
        );
        $this->assertNull($secondNotice->fresh()->resolved_at);
    }

    public function test_aba_compliance_cycle_invalidates_old_advance_form_even_when_status_returns_to_same_value(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_ENDORSED_LTI,
            'READINESS-ABA-ADVANCE'
        );

        $renderedStatus = $application->fresh()->status;
        $renderedRevision = (int) $application->fresh()->workflow_revision;

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.request', $application), [
                'expected_status' => $renderedStatus,
                'expected_workflow_revision' => $renderedRevision,
                'category' => ApplicationComplianceNotice::CATEGORY_CLARIFICATION,
                'details' => 'Resolve a clarification and return to the same LTID stage.',
            ])
            ->assertSessionHas('success');

        $noticeId = $application->activeComplianceNotice()->value('id');

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.resolve', $application), [
                'expected_status' => LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'compliance_notice_id' => $noticeId,
                'resolution_note' => 'Clarification resolved.',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame($renderedStatus, $application->status);
        $this->assertGreaterThan($renderedRevision, (int) $application->workflow_revision);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $renderedStatus,
                'expected_workflow_revision' => $renderedRevision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHasErrors('workflow_revision');

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_LTI,
            $application->fresh()->status
        );
    }

    public function test_aba_compliance_cycle_invalidates_old_final_approval_form(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'READINESS-ABA-APPROVAL'
        );
        $this->linkParcel($application, 'READINESS-ABA-APPROVAL-PARCEL');
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);
        $application->refresh();

        $renderedStatus = $application->status;
        $renderedRevision = (int) $application->workflow_revision;
        $renderedDependency = app(ApplicationWorkflowDependencyService::class)->fingerprint($application);

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.request', $application), [
                'expected_status' => $renderedStatus,
                'expected_workflow_revision' => $renderedRevision,
                'category' => ApplicationComplianceNotice::CATEGORY_CLARIFICATION,
                'details' => 'Final review clarification before the decision is recorded.',
            ])
            ->assertSessionHas('success');

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.resolve', $application), [
                'expected_status' => LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'compliance_notice_id' => $application->activeComplianceNotice()->value('id'),
                'resolution_note' => 'Final review clarification resolved.',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame($renderedStatus, $application->status);
        $this->assertGreaterThan($renderedRevision, (int) $application->workflow_revision);

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'expected_status' => $renderedStatus,
                'expected_workflow_revision' => $renderedRevision,
                'expected_workflow_dependency' => $renderedDependency,
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Stale Review Test',
                'decision_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('workflow_revision');

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $application->fresh()->status
        );
        $this->assertDatabaseMissing('application_clearances', [
            'land_transfer_application_id' => $application->id,
        ]);
    }

    public function test_child_record_change_invalidates_old_workflow_form_without_status_change(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_ENDORSED_LTI,
            'READINESS-CHILD-REVISION'
        );

        $renderedStatus = $application->fresh()->status;
        $renderedRevision = (int) $application->fresh()->workflow_revision;

        $this->linkParcel($application, 'READINESS-CHILD-REVISION-PARCEL');

        $application->refresh();
        $this->assertSame($renderedStatus, $application->status);
        $this->assertGreaterThan($renderedRevision, (int) $application->workflow_revision);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $renderedStatus,
                'expected_workflow_revision' => $renderedRevision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHasErrors('workflow_revision');

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_LTI,
            $application->fresh()->status
        );
    }

    public function test_active_workflow_dates_cannot_be_future_dated(): void
    {
        $staff = $this->staffUser();

        $this->actingAs($staff)
            ->post(route('staff.applications.store'), [
                'transferor_name' => 'Future Date Transferor',
                'transferee_name' => 'Future Date Transferee',
                'date_of_application' => now()->addDay()->toDateString(),
                'date_filed' => now()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['date_of_application', 'date_filed']);

        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_ENDORSED_LTI,
            'READINESS-FUTURE-FORM4'
        );

        $this->actingAs($staff)
            ->patch(route('staff.applications.form4.update', $application), [
                'ltc_form4_subject_land_findings' => ['ra6657_not_covered_not_tenanted_retained_area'],
                'ltc_form4_recommendation_findings' => ['application_complete'],
                'ltc_form4_recommendation_decision' => 'approval',
                'ltc_form4_certified_at' => now()->addDay()->toDateString(),
                'ltc_form4_certifying_officer_name' => 'Authorized Review Officer',
            ])
            ->assertSessionHasErrors('ltc_form4_certified_at');
    }

    public function test_official_receipt_date_cannot_be_future_or_predate_the_payment_order(): void
    {
        $staff = $this->staffUser();

        $futureOr = $this->application(
            $staff,
            LandTransferApplication::STATUS_AWAITING_PAYMENT,
            'READINESS-FUTURE-OR'
        );
        DB::table('land_transfer_applications')
            ->where('id', $futureOr->id)
            ->update([
                'payment_order_reference' => 'OP-READINESS-FUTURE-OR',
                'payment_order_issued_at' => now(),
            ]);
        $futureOr->refresh();

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $futureOr), [
                'expected_status' => $futureOr->fresh()->status,
                'expected_workflow_revision' => $futureOr->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($futureOr->fresh()),
                'or_number' => 'OR-FUTURE',
                'or_date' => now()->addDay()->toDateString(),
                'amount_paid' => config('dar_ltc.filing_fee', 2000),
            ])
            ->assertSessionHasErrors('or_date');

        $earlyOr = $this->application(
            $staff,
            LandTransferApplication::STATUS_AWAITING_PAYMENT,
            'READINESS-EARLY-OR'
        );
        DB::table('land_transfer_applications')
            ->where('id', $earlyOr->id)
            ->update([
                'payment_order_reference' => 'OP-READINESS-EARLY-OR',
                'payment_order_issued_at' => now(),
            ]);
        $earlyOr->refresh();

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $earlyOr), [
                'expected_status' => $earlyOr->fresh()->status,
                'expected_workflow_revision' => $earlyOr->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($earlyOr->fresh()),
                'or_number' => 'OR-EARLY',
                'or_date' => now()->subDay()->toDateString(),
                'amount_paid' => config('dar_ltc.filing_fee', 2000),
            ])
            ->assertSessionHasErrors('or_date');

        $this->assertSame(
            LandTransferApplication::STATUS_AWAITING_PAYMENT,
            $earlyOr->fresh()->status
        );
    }

    public function test_final_decision_date_cannot_predate_required_workflow_milestones(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'READINESS-EARLY-DECISION'
        );
        $this->linkParcel($application, 'READINESS-EARLY-DECISION-PARCEL');
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Test Signatory',
                'decision_date' => now()->subDay()->toDateString(),
                'decision_reason' => 'Chronology regression test.',
            ])
            ->assertSessionHasErrors('decision_date');

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $application->fresh()->status
        );
    }

    public function test_application_cannot_enter_parpo_decision_pending_without_a_linked_parcel(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_ENDORSED_PARPO);
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHasErrors(['validation', 'parcel']);

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            $application->fresh()->status
        );
    }

    public function test_application_cannot_enter_parpo_decision_pending_with_inactive_linked_parcel(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_ENDORSED_PARPO, 'READINESS-INACTIVE-PARCEL');
        $applicationParcel = $this->linkParcel($application, 'READINESS-INACTIVE-PARCEL-MASTER');
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);

        // Bypass the normal Parcel model guard to simulate a pre-existing
        // inconsistent row that workflow readiness must still detect.
        DB::table('parcels')
            ->where('id', $applicationParcel->parcel_id)
            ->update(['status' => 'inactive']);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHasErrors(['validation', 'parcel']);

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            $application->fresh()->status
        );
    }

    public function test_application_cannot_enter_parpo_decision_pending_without_positive_transfer_area(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_ENDORSED_PARPO, 'READINESS-MISSING-AREA');
        $applicationParcel = $this->linkParcel($application, 'READINESS-MISSING-AREA-MASTER');
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);

        DB::table('application_parcels')
            ->where('id', $applicationParcel->id)
            ->update(['area_hectares' => null, 'area_square_meters' => null]);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHasErrors(['validation', 'parcel']);

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            $application->fresh()->status
        );
    }

    public function test_application_cannot_enter_parpo_decision_pending_until_form4_is_complete(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_ENDORSED_PARPO);
        $this->linkParcel($application);
        $this->completePaymentAndCsw($application, $staff);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHasErrors(['validation', 'form4']);

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            $application->fresh()->status
        );
    }

    public function test_final_approval_rechecks_parcel_and_form4_readiness(): void
    {
        $staff = $this->staffUser();

        $missingParcel = $this->application($staff, LandTransferApplication::STATUS_FOR_RELEASING, 'READINESS-NO-PARCEL');
        $this->completeForm4($missingParcel);
        $this->completePaymentAndCsw($missingParcel, $staff);

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $missingParcel), [
                'expected_status' => $missingParcel->fresh()->status,
                'expected_workflow_revision' => $missingParcel->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($missingParcel->fresh()),
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Test Signatory',
                'decision_date' => now()->toDateString(),
                'decision_reason' => 'Decision readiness regression.',
            ])
            ->assertSessionHasErrors(['validation', 'parcel']);

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $missingParcel->fresh()->status
        );

        $missingForm4 = $this->application($staff, LandTransferApplication::STATUS_FOR_RELEASING, 'READINESS-NO-FORM4');
        $this->linkParcel($missingForm4, 'READINESS-NO-FORM4-PARCEL');
        $this->completePaymentAndCsw($missingForm4, $staff);

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $missingForm4), [
                'expected_status' => $missingForm4->fresh()->status,
                'expected_workflow_revision' => $missingForm4->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($missingForm4->fresh()),
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Test Signatory',
                'decision_date' => now()->toDateString(),
                'decision_reason' => 'Decision readiness regression.',
            ])
            ->assertSessionHasErrors(['validation', 'form4']);

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $missingForm4->fresh()->status
        );
    }

    public function test_final_decision_stage_uses_compliance_loop_then_resumes_and_approves(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'READINESS-COMPLIANCE-FINAL-STAGE'
        );
        $this->linkParcel($application, 'READINESS-COMPLIANCE-FINAL-STAGE-PARCEL');
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);

        $this->actingAs($staff)
            ->post('/staff/applications/' . $application->id . '/not-approved', [
                'final_decision_confirmation' => '1',
            ])
            ->assertNotFound();

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.request', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'category' => ApplicationComplianceNotice::CATEGORY_OTHER,
                'other_category' => 'PARPO clarification requested',
                'details' => 'Bring the original supporting instrument for clarification before approval can be recorded.',
                'requested_items' => 'Original supporting instrument',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE, $application->status);

        $notice = $application->complianceNotices()->latest('id')->firstOrFail();
        $this->assertSame(LandTransferApplication::STATUS_FOR_RELEASING, $notice->resume_status);
        $this->assertSame(ApplicationComplianceNotice::CATEGORY_OTHER, $notice->category);
        $this->assertSame('PARPO clarification requested', $notice->other_category);
        $this->assertNull($notice->resolved_at);

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.resolve', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'compliance_notice_id' => $application->activeComplianceNotice()->value('id'),
                'resolution_note' => 'Original instrument was presented and reviewed at the office.',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $notice->refresh();

        $this->assertSame(LandTransferApplication::STATUS_FOR_RELEASING, $application->status);
        $this->assertNotNull($notice->resolved_at);
        $this->assertSame($staff->id, $notice->resolved_by);

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Compliance Test Signatory',
                'decision_date' => now()->toDateString(),
                'decision_reason' => 'All compliance issues were resolved.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(
            LandTransferApplication::STATUS_APPROVED,
            $application->fresh()->status
        );
    }

    public function test_form4_remains_editable_during_compliance_when_resume_stage_is_form4_review(): void
    {
        $staff = $this->staffUser();

        foreach ([
            LandTransferApplication::STATUS_ENDORSED_LTI,
            LandTransferApplication::STATUS_RETURNED_TO_LEGAL,
        ] as $index => $resumeStatus) {
            $application = $this->application(
                $staff,
                $resumeStatus,
                'READINESS-COMPLIANCE-FORM4-' . $index
            );

            $this->actingAs($staff)
                ->post(route('staff.applications.compliance.request', $application), [
                'expected_status' => $application->fresh()->status,
                    'expected_workflow_revision' => $application->fresh()->workflow_revision,
                    'category' => ApplicationComplianceNotice::CATEGORY_INCORRECT_DOCUMENT,
                    'details' => 'Correct the LTC Form No. 4 review details before processing continues.',
                    'requested_items' => 'Corrected LTC Form No. 4',
                ])
                ->assertSessionHas('success');

            $application->refresh();

            $this->assertSame(
                LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE,
                $application->status
            );
            $this->assertTrue($application->canEditForm4());

            $this->actingAs($staff)
                ->patch(route('staff.applications.form4.update', $application), [
                    'ltc_form4_subject_land_findings' => ['ra6657_not_covered_not_tenanted_retained_area'],
                    'ltc_form4_recommendation_findings' => ['application_complete'],
                    'ltc_form4_recommendation_decision' => 'approval',
                    'ltc_form4_certified_at' => now()->toDateString(),
                    'ltc_form4_certifying_officer_name' => 'Compliance Review Officer',
                ])
                ->assertSessionHas('success');

            $application->refresh();

            $this->assertSame(
                LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE,
                $application->status
            );
            $this->assertSame('approval', $application->ltc_form4_recommendation_decision);

            $this->actingAs($staff)
                ->post(route('staff.applications.compliance.resolve', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'compliance_notice_id' => $application->activeComplianceNotice()->value('id'),
                    'resolution_note' => 'Corrected LTC Form No. 4 reviewed.',
                ])
                ->assertSessionHas('success');

            $this->assertSame($resumeStatus, $application->fresh()->status);
        }
    }

    public function test_other_compliance_category_requires_custom_text(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_LEGAL_EVALUATION,
            'READINESS-COMPLIANCE-OTHER'
        );

        $this->actingAs($staff)
            ->post(route('staff.applications.compliance.request', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'category' => ApplicationComplianceNotice::CATEGORY_OTHER,
                'details' => 'A case-specific issue requires clarification.',
            ])
            ->assertSessionHasErrors('other_category');

        $this->assertSame(
            LandTransferApplication::STATUS_LEGAL_EVALUATION,
            $application->fresh()->status
        );
        $this->assertDatabaseCount('application_compliance_notices', 0);
    }

    public function test_complete_application_can_follow_the_full_citizens_charter_flow_then_release_the_output(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, 'READINESS-FULL-FLOW');
        $this->linkParcel($application, 'READINESS-FULL-FLOW-PARCEL');
        $this->completeForm4($application);

        // Legal completeness review issues the Payment Order.
        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
                'payment_order_reference' => 'OP-READINESS-FULL-FLOW',
            ])
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_AWAITING_PAYMENT, $application->status);

        // Cashier payment is external; DAR-LTCMS records the resulting OR.
        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
                'or_number' => 'OR-READINESS-001',
                'or_date' => now()->toDateString(),
                'amount_paid' => config('dar_ltc.filing_fee', 2000),
            ])
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_ENDORSED_LTI, $application->status);

        // LTID verification returns the record to Legal.
        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_RETURNED_TO_LEGAL, $application->status);

        // Completed Form 4 permits formal Legal evaluation.
        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_LEGAL_EVALUATION, $application->status);

        // Legal records Completed Staff Work before Chief Legal review.
        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
                'csw_reference' => 'CSW-READINESS-001',
                'csw_notes' => 'Completed Staff Work regression coverage.',
            ])
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL, $application->status);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_ENDORSED_PARPO, $application->status);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
            ])
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_FOR_RELEASING, $application->status);

        $readinessLog = AuditLog::query()
            ->where('land_transfer_application_id', $application->id)
            ->where('action', 'application_status_advanced')
            ->latest('id')
            ->firstOrFail();

        $this->assertTrue($readinessLog->metadata['decision_readiness_checked']);
        $this->assertTrue($readinessLog->metadata['decision_readiness']['linked_parties_complete']);
        $this->assertTrue($readinessLog->metadata['decision_readiness']['has_linked_parcel']);
        $this->assertTrue($readinessLog->metadata['decision_readiness']['payment_complete']);
        $this->assertTrue($readinessLog->metadata['decision_readiness']['form4_complete']);
        $this->assertTrue($readinessLog->metadata['decision_readiness']['csw_complete']);

        // PARPO II approval is the final application decision and freezes edits.
        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Test Signatory',
                'decision_date' => now()->toDateString(),
                'decision_reason' => 'Record is ready for final PARPO II approval.',
                'decision_notes' => 'Citizen Charter workflow regression.',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_APPROVED, $application->status);
        $this->assertTrue($application->isFinalized());
        $this->assertNotNull($application->clearance()->first());
        $this->assertTrue($application->validation_snapshot['workflow_readiness']['payment_complete']);
        $this->assertTrue($application->validation_snapshot['workflow_readiness']['csw_complete']);

        // Delivery is a separate administrative event; it must not change the
        // Approved final decision or mutate ownership records.
        $this->actingAs($staff)
            ->post(route('staff.applications.ready_for_release', $application), [
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(LandTransferApplication::RELEASE_READY, $application->release_status);
        $this->assertSame(LandTransferApplication::STATUS_APPROVED, $application->status);
        $readyAt = $application->ready_for_release_at;

        $this->actingAs($staff)
            ->post(route('staff.applications.ready_for_release', $application), [
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
            ])
            ->assertSessionHasErrors('release');

        $application->refresh();
        $this->assertSame($readyAt?->toDateTimeString(), $application->ready_for_release_at?->toDateTimeString());

        $this->actingAs($staff)
            ->post(route('staff.applications.release', $application), [
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'release_confirmation' => '1',
                'release_recipient_name' => 'Authorized Recipient',
                'release_logbook_reference' => 'LOG-READINESS-001',
                'csm_status' => 'issued',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_APPROVED, $application->status);
        $this->assertSame(LandTransferApplication::RELEASED_TO_CLIENT, $application->release_status);
        $this->assertNotNull($application->released_at);
    }

    public function test_final_decision_locks_shared_transferee_and_parcel_dependencies_before_readiness_snapshot(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Shared row-lock SQL assertion applies to the production PostgreSQL stack.');
        }

        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'READINESS-SHARED-LOCKS'
        );
        $this->linkParcel($application, 'READINESS-SHARED-LOCKS-PARCEL');
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Shared Lock Signatory',
                'decision_date' => now()->toDateString(),
                'decision_reason' => 'Shared dependency lock regression.',
            ])
            ->assertSessionHas('success');

        $this->assertTrue(
            collect($queries)->contains(
                fn ($sql) => str_contains($sql, 'landowners')
                    && str_contains($sql, 'for update')
            ),
            'Expected final-decision readiness to row-lock linked transferee Landowner records.'
        );

        $this->assertTrue(
            collect($queries)->contains(
                fn ($sql) => str_contains($sql, 'parcels')
                    && str_contains($sql, 'for update')
            ),
            'Expected final-decision readiness to row-lock linked Parcel records.'
        );
    }

    public function test_shared_parcel_change_invalidates_pre_final_review_without_changing_application_revision(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            'READINESS-SHARED-PARCEL-FINGERPRINT'
        );
        $applicationParcel = $this->linkParcel($application, 'READINESS-SHARED-PARCEL-FINGERPRINT-PARCEL');
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);

        $application->refresh();
        $renderedRevision = (int) $application->workflow_revision;
        $renderedDependency = app(ApplicationWorkflowDependencyService::class)->fingerprint($application);

        $masterParcel = $applicationParcel->parcel()->firstOrFail();
        $masterParcel->area_hectares = 1.5000;
        $masterParcel->save();

        $application->refresh();
        $this->assertSame($renderedRevision, (int) $application->workflow_revision);
        $this->assertNotSame(
            $renderedDependency,
            app(ApplicationWorkflowDependencyService::class)->fingerprint($application)
        );

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => LandTransferApplication::STATUS_ENDORSED_PARPO,
                'expected_workflow_revision' => $renderedRevision,
                'expected_workflow_dependency' => $renderedDependency,
            ])
            ->assertSessionHasErrors('workflow_dependency');

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            $application->fresh()->status
        );
    }

    public function test_shared_landholding_change_invalidates_final_approval_review_without_changing_application_revision(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'READINESS-SHARED-LANDHOLDING-FINGERPRINT'
        );
        $this->linkParcel($application, 'READINESS-SHARED-LANDHOLDING-FINGERPRINT-PARCEL');
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);

        $application->refresh();
        $renderedRevision = (int) $application->workflow_revision;
        $renderedDependency = app(ApplicationWorkflowDependencyService::class)->fingerprint($application);

        $existingParcel = Parcel::create([
            'parcel_code' => 'READINESS-SHARED-LANDHOLDING-EXISTING',
            'area_hectares' => 1.0000,
            'status' => 'active',
        ]);

        Landholding::create([
            'landowner_id' => $application->transferee_landowner_id,
            'parcel_id' => $existingParcel->id,
            'area_hectares' => 1.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $application->refresh();
        $this->assertSame($renderedRevision, (int) $application->workflow_revision);
        $this->assertNotSame(
            $renderedDependency,
            app(ApplicationWorkflowDependencyService::class)->fingerprint($application)
        );

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'expected_status' => LandTransferApplication::STATUS_FOR_RELEASING,
                'expected_workflow_revision' => $renderedRevision,
                'expected_workflow_dependency' => $renderedDependency,
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Shared Dependency Test',
                'decision_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('workflow_dependency');

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $application->fresh()->status
        );
        $this->assertDatabaseMissing('application_clearances', [
            'land_transfer_application_id' => $application->id,
        ]);
    }

    public function test_pre_final_readiness_locks_shared_transferee_and_parcel_dependencies(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Shared row-lock SQL assertion applies to the production PostgreSQL stack.');
        }

        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            'READINESS-PRE-FINAL-SHARED-LOCKS'
        );
        $this->linkParcel($application, 'READINESS-PRE-FINAL-SHARED-LOCKS-PARCEL');
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);

        $application->refresh();
        $dependencyFingerprint = app(ApplicationWorkflowDependencyService::class)->fingerprint($application);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->status,
                'expected_workflow_revision' => $application->workflow_revision,
                'expected_workflow_dependency' => $dependencyFingerprint,
            ])
            ->assertSessionHas('success');

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $application->fresh()->status
        );

        $this->assertTrue(
            collect($queries)->contains(
                fn ($sql) => str_contains($sql, 'landowners')
                    && str_contains($sql, 'for update')
            ),
            'Expected pre-final readiness to row-lock linked transferee Landowner records.'
        );

        $this->assertTrue(
            collect($queries)->contains(
                fn ($sql) => str_contains($sql, 'parcels')
                    && str_contains($sql, 'for update')
            ),
            'Expected pre-final readiness to row-lock linked Parcel records.'
        );
    }

    public function test_release_tracking_fails_closed_when_application_business_state_is_inconsistent(): void
    {
        $staff = $this->staffUser();
        $application = $this->application(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'READINESS-RELEASE-INTEGRITY'
        );
        $this->linkParcel($application, 'READINESS-RELEASE-INTEGRITY-PARCEL');
        $this->completeForm4($application);
        $this->completePaymentAndCsw($application, $staff);

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Release Integrity Signatory',
                'decision_date' => now()->toDateString(),
                'decision_reason' => 'Release integrity regression.',
            ])
            ->assertSessionHas('success');

        DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update(['ready_for_release_at' => now()]);

        $this->actingAs($staff)
            ->get(route('staff.applications.workflow_state', $application))
            ->assertOk()
            ->assertJson([
                'business_state_integrity_valid' => false,
                'can_mark_ready_for_release' => false,
            ]);

        $this->actingAs($staff)
            ->post(route('staff.applications.ready_for_release', $application), [
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
            ])
            ->assertSessionHasErrors('release_integrity');

        $this->assertSame(
            LandTransferApplication::RELEASE_NOT_READY,
            $application->fresh()->release_status
        );

        DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update(['ready_for_release_at' => null]);

        $this->actingAs($staff)
            ->post(route('staff.applications.ready_for_release', $application), [
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
            ])
            ->assertSessionHas('success');

        DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update([
                'released_at' => now(),
                'release_recipient_name' => 'Premature Recipient',
            ]);

        $this->actingAs($staff)
            ->get(route('staff.applications.workflow_state', $application))
            ->assertOk()
            ->assertJson([
                'business_state_integrity_valid' => false,
                'can_release_output' => false,
            ]);

        $this->actingAs($staff)
            ->post(route('staff.applications.release', $application), [
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'release_confirmation' => '1',
                'release_recipient_name' => 'Authorized Recipient',
            ])
            ->assertSessionHasErrors('release_integrity');

        $this->assertSame(
            LandTransferApplication::RELEASE_READY,
            $application->fresh()->release_status
        );
    }

    public function test_form4_recommendation_does_not_automatically_make_the_final_decision(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_FOR_RELEASING, 'READINESS-RECOMMENDATION');
        $this->linkParcel($application, 'READINESS-RECOMMENDATION-PARCEL');
        $this->completeForm4($application, 'denial');
        $this->completePaymentAndCsw($application, $staff);

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Test Signatory',
                'decision_date' => now()->toDateString(),
                'decision_reason' => 'Authorized PARPO II approval after review of the recommendation.',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_APPROVED, $application->status);

        $approvalLog = AuditLog::query()
            ->where('land_transfer_application_id', $application->id)
            ->where('action', 'application_approved')
            ->firstOrFail();

        $this->assertSame('denial', $approvalLog->metadata['form4_recommendation_decision']);
        $this->assertFalse($approvalLog->metadata['form4_recommendation_matches_final_decision']);
        $this->assertFalse($approvalLog->metadata['ownership_transfer_performed']);
        $this->assertFalse($approvalLog->metadata['registry_mutation_performed']);
    }

    private function staffUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
    }

    private function application(User $staff, string $status, string $code = 'READINESS-APP-001'): LandTransferApplication
    {
        $transferor = Landowner::create([
            'first_name' => 'Readiness',
            'last_name' => 'Transferor-' . $code,
            'province' => 'Negros Oriental',
        ]);

        $transferee = Landowner::create([
            'first_name' => 'Readiness',
            'last_name' => 'Transferee-' . $code,
            'province' => 'Negros Oriental',
        ]);

        return LandTransferApplication::create([
            'application_code' => $code,
            'transferor_name' => $transferor->full_name,
            'transferors' => [[
                'name' => $transferor->full_name,
                'landowner_id' => $transferor->id,
                'parcel_shares' => [],
            ]],
            'transferee_name' => $transferee->full_name,
            'transferees' => [[
                'name' => $transferee->full_name,
                'landowner_id' => $transferee->id,
                'parcel_shares' => [],
            ]],
            'transferor_landowner_id' => $transferor->id,
            'transferee_landowner_id' => $transferee->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => $status,
            'encoded_by' => $staff->id,
        ]);
    }

    private function linkParcel(LandTransferApplication $application, string $code = 'READINESS-PARCEL-001'): ApplicationParcel
    {
        $parcel = Parcel::create([
            'parcel_code' => $code,
            'title_no' => 'T-' . $code,
            'tax_decl_no' => 'TD-' . $code,
            'lot_number' => 'LOT-' . $code,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.2500,
            'area_square_meters' => 12500,
            'status' => 'active',
        ]);

        return ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'tax_decl_no' => $parcel->tax_decl_no,
            'lot_number' => $parcel->lot_number,
            'area_hectares' => 1.2500,
            'area_square_meters' => 12500,
        ]);
    }

    private function completeForm4(LandTransferApplication $application, string $recommendation = 'approval'): void
    {
        $application->forceFill([
            'ltc_form4_subject_land_findings' => ['ra6657_not_covered_not_tenanted_retained_area'],
            'ltc_form4_recommendation_findings' => ['application_complete'],
            'ltc_form4_recommendation_decision' => $recommendation,
            'ltc_form4_certified_at' => now()->toDateString(),
            'ltc_form4_certifying_officer_name' => 'Authorized Review Officer',
        ])->save();
    }

    private function completePaymentAndCsw(LandTransferApplication $application, User $staff): void
    {
        DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update([
                'payment_order_reference' => 'OP-' . $application->application_code,
                'payment_order_issued_at' => now(),
                'or_number' => 'OR-' . $application->id,
                'or_date' => now()->toDateString(),
                'amount_paid' => config('dar_ltc.filing_fee', 2000),
                'csw_reference' => 'CSW-' . $application->application_code,
                'csw_completed_at' => now(),
                'csw_prepared_by' => $staff->id,
            ]);
        $application->refresh();
    }
}
