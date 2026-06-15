<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelAgriculturalStatusRoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_landowner_can_view_clearance_scope_only_for_own_parcel(): void
    {
        $userA = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $userB = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

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

        $ownParcel = Parcel::create([
            'parcel_code' => 'LANDOWNER-AGRI-OWN',
            'title_no' => 'TCT-OWN-AGRI',
            'tax_decl_no' => 'TD-OWN-AGRI',
            'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'agricultural_status' => 'private_agricultural',
        ]);

        $otherParcel = Parcel::create([
            'parcel_code' => 'LANDOWNER-AGRI-OTHER',
            'title_no' => 'TCT-OTHER-AGRI',
            'tax_decl_no' => 'TD-OTHER-AGRI',
            'province' => 'Negros Oriental',
            'municipality' => 'Sibulan',
            'barangay' => 'Poblacion',
            'area_hectares' => 2.5000,
            'status' => 'active',
            'agricultural_status' => 'non_agricultural',
        ]);

        Landholding::create([
            'landowner_id' => $ownerA->id,
            'parcel_id' => $ownParcel->id,
            'area_hectares' => 1.2500,
            'status' => 'active',
        ]);

        Landholding::create([
            'landowner_id' => $ownerB->id,
            'parcel_id' => $otherParcel->id,
            'area_hectares' => 2.5000,
            'status' => 'active',
        ]);

        $indexResponse = $this->actingAs($userA)->get(route('landowner.parcels.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('LANDOWNER-AGRI-OWN');
        $indexResponse->assertSee('Clearance Scope');
        $indexResponse->assertSee('Agricultural land record');
        $indexResponse->assertDontSee('LANDOWNER-AGRI-OTHER');
        $indexResponse->assertDontSee('Non-Agricultural / Reference Only');

        $detailsResponse = $this->actingAs($userA)->get(route('landowner.parcels.show', $ownParcel));

        $detailsResponse->assertOk();
        $detailsResponse->assertSee('DAR Clearance Scope');
        $detailsResponse->assertSee('Agricultural land record');
        $detailsResponse->assertDontSee('Agricultural Status');
        $detailsResponse->assertDontSee('Private Agricultural Land');
    }

    public function test_landowner_cannot_view_other_landowners_parcel_clearance_scope(): void
    {
        $userA = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $userB = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        Landowner::create([
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

        $otherParcel = Parcel::create([
            'parcel_code' => 'HIDDEN-AGRI-STATUS',
            'title_no' => 'TCT-HIDDEN-AGRI',
            'tax_decl_no' => 'TD-HIDDEN-AGRI',
            'province' => 'Negros Oriental',
            'municipality' => 'Sibulan',
            'barangay' => 'Poblacion',
            'area_hectares' => 2.5000,
            'status' => 'active',
            'agricultural_status' => 'awarded_cloa',
        ]);

        Landholding::create([
            'landowner_id' => $ownerB->id,
            'parcel_id' => $otherParcel->id,
            'area_hectares' => 2.5000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($userA)->get(route('landowner.parcels.show', $otherParcel));

        $response->assertForbidden();
    }

    public function test_geodetic_can_view_clearance_scope_but_cannot_edit_parcels(): void
    {
        $geodetic = User::factory()->create([
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $owner = Landowner::create([
            'first_name' => 'Geo',
            'last_name' => 'Reference',
            'province' => 'Negros Oriental',
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'GEODETIC-AGRI-READONLY',
            'title_no' => 'TCT-GEO-AGRI',
            'tax_decl_no' => 'TD-GEO-AGRI',
            'province' => 'Negros Oriental',
            'municipality' => 'Bayawan City',
            'barangay' => 'Banga',
            'area_hectares' => 2.4000,
            'status' => 'active',
            'agricultural_status' => 'carp_covered',
        ]);

        Landholding::create([
            'landowner_id' => $owner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 2.4000,
            'status' => 'active',
        ]);

        $indexResponse = $this->actingAs($geodetic)->get(route('geodetic.parcels.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('GEODETIC-AGRI-READONLY');
        $indexResponse->assertSee('Clearance Scope');
        $indexResponse->assertSee('Agricultural land record');
        $indexResponse->assertDontSee('CARP-Covered Land');

        $detailsResponse = $this->actingAs($geodetic)->get(route('geodetic.parcels.show', $parcel));

        $detailsResponse->assertOk();
        $detailsResponse->assertSee('DAR Clearance Scope');
        $detailsResponse->assertSee('Agricultural land record');
        $detailsResponse->assertDontSee('Agricultural Status');
        $detailsResponse->assertDontSee('CARP-Covered Land');
        $detailsResponse->assertDontSee('Save Parcel Record');

        $editResponse = $this->actingAs($geodetic)->get(route('staff.records.parcels.edit', $parcel));
        $editResponse->assertForbidden();
    }
}
