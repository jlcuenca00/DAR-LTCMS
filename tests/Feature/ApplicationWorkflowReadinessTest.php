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
            ->post(route('staff.applications.submit', $application))
            ->assertSessionHasErrors('validation');

        $this->assertSame(
            LandTransferApplication::STATUS_DRAFT,
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
        $futureOr->forceFill([
            'payment_order_reference' => 'OP-READINESS-FUTURE-OR',
            'payment_order_issued_at' => now(),
        ])->save();

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $futureOr), [
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
        $earlyOr->forceFill([
            'payment_order_reference' => 'OP-READINESS-EARLY-OR',
            'payment_order_issued_at' => now(),
        ])->save();

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $earlyOr), [
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
            ->post(route('staff.applications.submit', $application))
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
            ->post(route('staff.applications.submit', $application))
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
            ->post(route('staff.applications.submit', $application))
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
            ->post(route('staff.applications.submit', $application))
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
                'payment_order_reference' => 'OP-READINESS-FULL-FLOW',
            ])
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_AWAITING_PAYMENT, $application->status);

        // Cashier payment is external; DAR-LTCMS records the resulting OR.
        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'or_number' => 'OR-READINESS-001',
                'or_date' => now()->toDateString(),
                'amount_paid' => config('dar_ltc.filing_fee', 2000),
            ])
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_ENDORSED_LTI, $application->status);

        // LTID verification returns the record to Legal.
        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application))
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_RETURNED_TO_LEGAL, $application->status);

        // Completed Form 4 permits formal Legal evaluation.
        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application))
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_LEGAL_EVALUATION, $application->status);

        // Legal records Completed Staff Work before Chief Legal review.
        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application), [
                'csw_reference' => 'CSW-READINESS-001',
                'csw_notes' => 'Completed Staff Work regression coverage.',
            ])
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL, $application->status);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application))
            ->assertSessionHas('success');
        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_ENDORSED_PARPO, $application->status);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application))
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
            ->post(route('staff.applications.ready_for_release', $application))
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(LandTransferApplication::RELEASE_READY, $application->release_status);
        $this->assertSame(LandTransferApplication::STATUS_APPROVED, $application->status);
        $readyAt = $application->ready_for_release_at;

        $this->actingAs($staff)
            ->post(route('staff.applications.ready_for_release', $application))
            ->assertSessionHasErrors('release');

        $application->refresh();
        $this->assertSame($readyAt?->toDateTimeString(), $application->ready_for_release_at?->toDateTimeString());

        $this->actingAs($staff)
            ->post(route('staff.applications.release', $application), [
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
            ->post(route('staff.applications.ready_for_release', $application))
            ->assertSessionHasErrors('release_integrity');

        $this->assertSame(
            LandTransferApplication::RELEASE_NOT_READY,
            $application->fresh()->release_status
        );

        DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update(['ready_for_release_at' => null]);

        $this->actingAs($staff)
            ->post(route('staff.applications.ready_for_release', $application))
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
        $application->forceFill([
            'payment_order_reference' => 'OP-' . $application->application_code,
            'payment_order_issued_at' => now(),
            'or_number' => 'OR-' . $application->id,
            'or_date' => now()->toDateString(),
            'amount_paid' => config('dar_ltc.filing_fee', 2000),
            'csw_reference' => 'CSW-' . $application->application_code,
            'csw_completed_at' => now(),
            'csw_prepared_by' => $staff->id,
        ])->save();
    }
}
