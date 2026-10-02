<?php

namespace Tests\Feature;

use App\Models\ApplicationParcel;
use App\Models\LandTransferApplication;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use App\Services\ApplicationWorkflowDependencyService;
use App\Services\LandholdingAreaValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HectareValidationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_time_transferee_record_can_be_created_without_creating_landholding(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'FIRST-TIME-TRANSFEREE-001',
            'transferor_name' => 'Existing Transferor',
            'transferee_name' => 'First Recipient',
            'municipality' => 'Bayawan City',
            'barangay' => 'Banga',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $this->actingAs($staffUser)
            ->post(route('staff.applications.landowner-records.create', $application), [
                'party' => 'transferee',
            ])
            ->assertRedirect();

        $application->refresh();

        $this->assertNotNull($application->transferee_landowner_id);
        $this->assertDatabaseHas('landowners', [
            'id' => $application->transferee_landowner_id,
            'first_name' => 'First',
            'last_name' => 'Recipient',
        ]);
        $this->assertDatabaseMissing('landholdings', [
            'landowner_id' => $application->transferee_landowner_id,
        ]);
    }

    public function test_five_hectare_checker_blocks_over_limit_approval(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $transferor = Landowner::create([
            'first_name' => 'Transferor',
            'last_name' => 'Person',
            'province' => 'Negros Oriental',
        ]);

        $transferee = Landowner::create([
            'first_name' => 'Over',
            'last_name' => 'Limit',
            'province' => 'Negros Oriental',
        ]);

        $existingParcel = Parcel::create([
            'parcel_code' => 'EXISTING-AREA-001',
            'area_hectares' => 4.0000,
            'status' => 'active',
        ]);

        $applicationParcel = Parcel::create([
            'parcel_code' => 'APPLICATION-AREA-001',
            'area_hectares' => 2.0000,
            'status' => 'active',
        ]);

        Landholding::create([
            'landowner_id' => $transferee->id,
            'parcel_id' => $existingParcel->id,
            'area_hectares' => 4.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'OVER-LIMIT-APP-001',
            'transferor_name' => 'Transferor Person',
            'transferee_name' => 'Over Limit',
            'transferor_landowner_id' => $transferor->id,
            'transferee_landowner_id' => $transferee->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_FOR_RELEASING,
            'encoded_by' => $staffUser->id,
        ]);

        ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $applicationParcel->id,
            'area_hectares' => 2.0000,
            'parcel_code' => $applicationParcel->parcel_code,
        ]);

        $this->actingAs($staffUser)
            ->post(route('staff.applications.approve', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'expected_workflow_dependency' => app(ApplicationWorkflowDependencyService::class)->fingerprint($application->fresh()),
                'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Test Signatory',
                'decision_date' => now()->toDateString(),
                'decision_reason' => 'Test approval',
            ])
            ->assertSessionHasErrors('validation');

        $this->assertDatabaseHas('land_transfer_applications', [
            'id' => $application->id,
            'status' => LandTransferApplication::STATUS_FOR_RELEASING,
        ]);
    }
    public function test_approved_and_released_clearances_remain_in_potential_incoming_exposure(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $transferee = Landowner::create([
            'first_name' => 'Exposure',
            'last_name' => 'Transferee',
            'province' => 'Negros Oriental',
        ]);

        foreach ([
            LandTransferApplication::STATUS_APPROVED,
            LandTransferApplication::STATUS_RELEASED,
        ] as $index => $finalStatus) {
            $parcel = Parcel::create([
                'parcel_code' => 'EXPOSURE-FINAL-' . $index,
                'area_hectares' => 1.5000,
                'status' => 'active',
            ]);

            $application = LandTransferApplication::create([
                'application_code' => 'EXPOSURE-FINAL-APP-' . $index,
                'transferor_name' => 'Historical Transferor ' . $index,
                'transferee_name' => $transferee->full_name,
                'transferees' => [[
                    'name' => $transferee->full_name,
                    'landowner_id' => $transferee->id,
                    'parcel_shares' => [],
                ]],
                'transferee_landowner_id' => $transferee->id,
                'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                'encoded_by' => $staff->id,
            ]);

            ApplicationParcel::create([
                'land_transfer_application_id' => $application->id,
                'parcel_id' => $parcel->id,
                'area_hectares' => 1.5000,
                'parcel_code' => $parcel->parcel_code,
            ]);

            DB::table('land_transfer_applications')
                ->where('id', $application->id)
                ->update(['status' => $finalStatus]);
        }

        $currentParcel = Parcel::create([
            'parcel_code' => 'EXPOSURE-CURRENT',
            'area_hectares' => 2.5000,
            'status' => 'active',
        ]);

        $current = LandTransferApplication::create([
            'application_code' => 'EXPOSURE-CURRENT-APP',
            'transferor_name' => 'Current Transferor',
            'transferee_name' => $transferee->full_name,
            'transferees' => [[
                'name' => $transferee->full_name,
                'landowner_id' => $transferee->id,
                'parcel_shares' => [],
            ]],
            'transferee_landowner_id' => $transferee->id,
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        ApplicationParcel::create([
            'land_transfer_application_id' => $current->id,
            'parcel_id' => $currentParcel->id,
            'area_hectares' => 2.5000,
            'parcel_code' => $currentParcel->parcel_code,
        ]);

        $result = app(LandholdingAreaValidationService::class)->forApplication($current->fresh());

        $this->assertSame(3.0, (float) $result['pending_incoming_total']);
        $this->assertSame(2.5, (float) $result['this_application_total']);
        $this->assertSame(5.5, (float) $result['projected_total']);
        $this->assertTrue($result['exceeds_limit']);
    }

    public function test_legacy_pending_review_application_cannot_be_released_directly(): void
{
    $staffUser = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $application = LandTransferApplication::create([
        'application_code' => 'LEGACY-PENDING-001',
        'transferor_name' => 'Transferor',
        'transferee_name' => 'Transferee',
        'municipality' => 'Dumaguete City',
        'barangay' => 'Bantayan',
        'status' => LandTransferApplication::STATUS_PENDING_REVIEW,
        'encoded_by' => $staffUser->id,
    ]);

    $this->actingAs($staffUser)
        ->post(route('staff.applications.approve', $application), [
            'expected_status' => $application->fresh()->status,
            'expected_workflow_revision' => $application->fresh()->workflow_revision,
            'final_decision_confirmation' => '1',
                'decision_officer_name' => 'PARPO II Test Signatory',
                'decision_date' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('land_transfer_applications', [
        'id' => $application->id,
        'status' => LandTransferApplication::STATUS_PENDING_REVIEW,
    ]);
}
}
