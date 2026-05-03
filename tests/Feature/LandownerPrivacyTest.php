<?php

namespace Tests\Feature;

use App\Models\Landowner;
use App\Models\Landholding;
use App\Models\Parcel;
use App\Models\LandTransferApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandownerPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_landowner_can_only_see_own_parcel_records(): void
    {
        $userA = User::factory()->create(['role' => 'landowner']);
        $userB = User::factory()->create(['role' => 'landowner']);

        $ownerA = Landowner::create([
            'user_id' => $userA->id,
            'first_name' => 'Owner',
            'last_name' => 'Alpha',
            'province' => 'Negros Oriental',
        ]);

        $ownerB = Landowner::create([
            'user_id' => $userB->id,
            'first_name' => 'Owner',
            'last_name' => 'Bravo',
            'province' => 'Negros Oriental',
        ]);

        $parcelA = Parcel::create([
            'parcel_code' => 'PARCEL-ALPHA',
            'title_no' => 'TITLE-ALPHA',
            'tax_decl_no' => 'TD-ALPHA',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.2500,
            'status' => 'active',
        ]);

        $parcelB = Parcel::create([
            'parcel_code' => 'PARCEL-BRAVO',
            'title_no' => 'TITLE-BRAVO',
            'tax_decl_no' => 'TD-BRAVO',
            'municipality' => 'Sibulan',
            'barangay' => 'Poblacion',
            'province' => 'Negros Oriental',
            'area_hectares' => 2.5000,
            'status' => 'active',
        ]);

        Landholding::create([
            'landowner_id' => $ownerA->id,
            'parcel_id' => $parcelA->id,
            'area_hectares' => 1.2500,
            'status' => 'active',
        ]);

        Landholding::create([
            'landowner_id' => $ownerB->id,
            'parcel_id' => $parcelB->id,
            'area_hectares' => 2.5000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($userA)->get(route('landowner.parcels.index'));

        $response->assertOk();
        $response->assertSee('PARCEL-ALPHA');
        $response->assertDontSee('PARCEL-BRAVO');
    }

    public function test_landowner_can_only_see_own_application_status(): void
    {
        $staffUser = User::factory()->create(['role' => 'staff']);

        $userA = User::factory()->create(['role' => 'landowner']);
        $userB = User::factory()->create(['role' => 'landowner']);

        $ownerA = Landowner::create([
            'user_id' => $userA->id,
            'first_name' => 'Owner',
            'last_name' => 'Alpha',
            'province' => 'Negros Oriental',
        ]);

        $ownerB = Landowner::create([
            'user_id' => $userB->id,
            'first_name' => 'Owner',
            'last_name' => 'Bravo',
            'province' => 'Negros Oriental',
        ]);

        LandTransferApplication::create([
            'application_code' => 'APP-ALPHA',
            'transferor_name' => 'Owner Alpha',
            'transferee_name' => 'Owner Alpha',
            'transferor_landowner_id' => $ownerA->id,
            'transferee_landowner_id' => $ownerA->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'pending_review',
            'encoded_by' => $staffUser->id,
        ]);

        LandTransferApplication::create([
            'application_code' => 'APP-BRAVO',
            'transferor_name' => 'Owner Bravo',
            'transferee_name' => 'Owner Bravo',
            'transferor_landowner_id' => $ownerB->id,
            'transferee_landowner_id' => $ownerB->id,
            'municipality' => 'Sibulan',
            'barangay' => 'Poblacion',
            'status' => 'pending_review',
            'encoded_by' => $staffUser->id,
        ]);

        $response = $this->actingAs($userA)->get(route('landowner.applications.index'));

        $response->assertOk();
        $response->assertSee('APP-ALPHA');
        $response->assertDontSee('APP-BRAVO');
    }
}