<?php

namespace Tests\Feature;

use App\Models\ApplicationParcel;
use App\Models\AuditLog;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationWorkflowReadinessTest extends TestCase
{
    use RefreshDatabase;

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
                'decision_reason' => 'Decision readiness regression.',
            ])
            ->assertSessionHasErrors(['validation', 'form4']);

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $missingForm4->fresh()->status
        );
    }

    public function test_final_denial_rechecks_workflow_prerequisites_but_can_record_an_adverse_decision(): void
    {
        $staff = $this->staffUser();

        $missingParcel = $this->application(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'READINESS-DENIAL-NO-PARCEL'
        );
        $this->completeForm4($missingParcel, 'denial');
        $this->completePaymentAndCsw($missingParcel, $staff);

        $this->actingAs($staff)
            ->post(route('staff.applications.not_approved', $missingParcel), [
                'final_decision_confirmation' => '1',
                'decision_reason' => 'Adverse PARPO II decision.',
            ])
            ->assertSessionHasErrors(['validation', 'parcel']);

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $missingParcel->fresh()->status
        );

        $complete = $this->application(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'READINESS-DENIAL-COMPLETE'
        );
        $this->linkParcel($complete, 'READINESS-DENIAL-COMPLETE-PARCEL');
        $this->completeForm4($complete, 'denial');
        $this->completePaymentAndCsw($complete, $staff);

        $this->actingAs($staff)
            ->post(route('staff.applications.not_approved', $complete), [
                'final_decision_confirmation' => '1',
                'decision_reason' => 'Substantive review supports denial.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(
            LandTransferApplication::STATUS_DENIED,
            $complete->fresh()->status
        );
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
