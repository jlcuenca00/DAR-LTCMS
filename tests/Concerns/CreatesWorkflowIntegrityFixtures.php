<?php

namespace Tests\Concerns;

use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\User;
use App\Services\ApplicationWorkflowDependencyService;

trait CreatesWorkflowIntegrityFixtures
{
    private function workflowStaff(): User
    {
        return User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true, 'must_change_password' => false]);
    }

    private function workflowApplication(User $staff, string $code): LandTransferApplication
    {
        $ownerUser = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'is_active' => true]);
        $transferor = Landowner::create(['first_name' => 'Atomic', 'last_name' => 'Transferor '.$code, 'province' => 'Negros Oriental']);
        $transferee = Landowner::create(['first_name' => 'Atomic', 'last_name' => 'Transferee '.$code, 'province' => 'Negros Oriental', 'user_id' => $ownerUser->id]);
        $parcel = Parcel::create([
            'parcel_code' => $code, 'title_no' => 'T-'.$code, 'tax_decl_no' => 'TD-'.$code,
            'lot_number' => 'LOT-'.$code, 'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan',
            'province' => 'Negros Oriental', 'area_hectares' => 1, 'area_square_meters' => 10000, 'status' => 'active',
        ]);
        Landholding::create(['landowner_id' => $transferor->id, 'parcel_id' => $parcel->id, 'area_hectares' => 1, 'status' => 'active']);
        // Initial reviewed evidence is allowed on creation; persisted guards remain enabled.
        $application = LandTransferApplication::create([
            'application_code' => $code, 'transferor_name' => $transferor->full_name, 'transferee_name' => $transferee->full_name,
            'transferors' => [['name' => $transferor->full_name, 'landowner_id' => $transferor->id, 'parcel_shares' => []]],
            'transferees' => [['name' => $transferee->full_name, 'landowner_id' => $transferee->id, 'parcel_shares' => []]],
            'transferor_landowner_id' => $transferor->id, 'transferee_landowner_id' => $transferee->id,
            'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan', 'status' => LandTransferApplication::STATUS_FOR_RELEASING,
            'encoded_by' => $staff->id, 'payment_order_reference' => 'OP-'.$code, 'payment_order_issued_at' => now(),
            'or_number' => 'OR-'.$code, 'or_date' => today()->toDateString(), 'amount_paid' => config('dar_ltc.filing_fee', 2000),
            'csw_reference' => 'CSW-'.$code, 'csw_completed_at' => now(), 'csw_prepared_by' => $staff->id,
        ]);
        $link = ApplicationParcel::create([
            'land_transfer_application_id' => $application->id, 'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code, 'title_no' => $parcel->title_no, 'tax_decl_no' => $parcel->tax_decl_no,
            'lot_number' => $parcel->lot_number, 'area_hectares' => 1, 'area_square_meters' => 10000,
        ]);
        $application->forceFill([
            'transferors' => [['name' => $transferor->full_name, 'landowner_id' => $transferor->id, 'parcel_shares' => [(string) $link->id => 1]]],
            'transferees' => [['name' => $transferee->full_name, 'landowner_id' => $transferee->id, 'parcel_shares' => [(string) $link->id => 1]]],
            'ltc_form4_subject_land_findings' => ['ra6657_not_covered_not_tenanted_retained_area'],
            'ltc_form4_recommendation_findings' => ['application_complete'], 'ltc_form4_recommendation_decision' => 'approval',
            'ltc_form4_certified_at' => today()->toDateString(), 'ltc_form4_certifying_officer_name' => 'Atomic Review Officer',
        ])->save();

        return $application->fresh();
    }

    private function approvalPayload(LandTransferApplication $application): array
    {
        $application = $application->fresh();
        return [
            'expected_status' => $application->status, 'expected_workflow_revision' => $application->workflow_revision,
            'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application),
            'final_decision_confirmation' => '1', 'decision_officer_name' => 'Atomic PARPO II', 'decision_date' => today()->toDateString(),
        ];
    }
}
