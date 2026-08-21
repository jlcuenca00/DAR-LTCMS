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

    public function test_application_cannot_enter_for_releasing_without_a_linked_parcel(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_ENDORSED_PARPO);
        $this->completeForm4($application);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application))
            ->assertSessionHasErrors(['validation', 'parcel']);

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            $application->fresh()->status
        );
    }

    public function test_application_cannot_enter_for_releasing_until_form4_is_complete(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_ENDORSED_PARPO);
        $this->linkParcel($application);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application))
            ->assertSessionHasErrors(['validation', 'form4']);

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            $application->fresh()->status
        );
    }

    public function test_release_rechecks_parcel_and_form4_readiness(): void
    {
        $staff = $this->staffUser();

        $missingParcel = $this->application($staff, LandTransferApplication::STATUS_FOR_RELEASING, 'READINESS-NO-PARCEL');
        $this->completeForm4($missingParcel);

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $missingParcel), [
                'final_decision_confirmation' => '1',
                'decision_reason' => 'Release readiness regression.',
            ])
            ->assertSessionHasErrors(['validation', 'parcel']);

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $missingParcel->fresh()->status
        );

        $missingForm4 = $this->application($staff, LandTransferApplication::STATUS_FOR_RELEASING, 'READINESS-NO-FORM4');
        $this->linkParcel($missingForm4, 'READINESS-NO-FORM4-PARCEL');

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $missingForm4), [
                'final_decision_confirmation' => '1',
                'decision_reason' => 'Release readiness regression.',
            ])
            ->assertSessionHasErrors(['validation', 'form4']);

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $missingForm4->fresh()->status
        );
    }

    public function test_complete_application_can_follow_the_full_office_flow_and_release(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, 'READINESS-FULL-FLOW');
        $this->linkParcel($application, 'READINESS-FULL-FLOW-PARCEL');
        $this->completeForm4($application);

        foreach ([
            LandTransferApplication::STATUS_ENDORSED_LTI,
            LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            LandTransferApplication::STATUS_FOR_RELEASING,
        ] as $expectedStatus) {
            $this->actingAs($staff)
                ->post(route('staff.applications.submit', $application))
                ->assertSessionHas('success');

            $application->refresh();
            $this->assertSame($expectedStatus, $application->status);
        }

        $readinessLog = AuditLog::query()
            ->where('land_transfer_application_id', $application->id)
            ->where('action', 'application_status_advanced')
            ->latest('id')
            ->firstOrFail();

        $this->assertTrue($readinessLog->metadata['release_readiness_checked']);
        $this->assertTrue($readinessLog->metadata['release_readiness']['linked_parties_complete']);
        $this->assertTrue($readinessLog->metadata['release_readiness']['has_linked_parcel']);
        $this->assertTrue($readinessLog->metadata['release_readiness']['form4_complete']);

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'final_decision_confirmation' => '1',
                'decision_reason' => 'Record is ready for clearance release.',
                'decision_notes' => 'Workflow readiness regression.',
            ])
            ->assertSessionHas('success');

        $application->refresh();

        $this->assertSame(LandTransferApplication::STATUS_RELEASED, $application->status);
        $this->assertNotNull($application->clearance()->first());
        $this->assertTrue($application->validation_snapshot['workflow_readiness']['linked_parties_complete']);
        $this->assertTrue($application->validation_snapshot['workflow_readiness']['has_linked_parcel']);
        $this->assertTrue($application->validation_snapshot['workflow_readiness']['form4_complete']);
    }

    public function test_form4_recommendation_does_not_automatically_make_the_final_decision(): void
    {
        $staff = $this->staffUser();
        $application = $this->application($staff, LandTransferApplication::STATUS_FOR_RELEASING, 'READINESS-RECOMMENDATION');
        $this->linkParcel($application, 'READINESS-RECOMMENDATION-PARCEL');
        $this->completeForm4($application, 'denial');

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'final_decision_confirmation' => '1',
                'decision_reason' => 'Authorized final release after review of the recommendation.',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(LandTransferApplication::STATUS_RELEASED, $application->status);

        $releaseLog = AuditLog::query()
            ->where('land_transfer_application_id', $application->id)
            ->where('action', 'application_released')
            ->firstOrFail();

        $this->assertSame('denial', $releaseLog->metadata['form4_recommendation_decision']);
        $this->assertFalse($releaseLog->metadata['form4_recommendation_matches_final_decision']);
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
}
