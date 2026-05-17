<?php

namespace Tests\Feature;

use App\Models\ApplicationParcel;
use App\Models\LandTransferApplication;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'status' => LandTransferApplication::STATUS_PENDING_REVIEW,
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
                'decision_reason' => 'Test approval',
            ])
            ->assertSessionHasErrors('validation');

        $this->assertDatabaseHas('land_transfer_applications', [
            'id' => $application->id,
            'status' => LandTransferApplication::STATUS_PENDING_REVIEW,
            'registry_mutated_at' => null,
        ]);
    }
}
