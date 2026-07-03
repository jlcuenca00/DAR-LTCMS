<?php

namespace Tests\Feature;

use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelAgriculturalStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_update_preserves_internal_agricultural_status_when_land_type_field_is_not_on_form(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'AGR-SCOPE-001',
            'title_no' => 'TCT-AGR-001',
            'tax_decl_no' => 'TD-AGR-001',
            'province' => 'Negros Oriental',
            'municipality' => 'Bayawan City',
            'barangay' => 'Banga',
            'area_hectares' => 2.4000,
            'status' => 'active',
            'agricultural_status' => 'not_yet_determined',
            'remarks' => 'Initial internal classification test parcel.',
        ]);

        $response = $this->actingAs($staffUser)
            ->patch(route('staff.records.parcels.update', $parcel), [
                'parcel_code' => 'AGR-SCOPE-001',
                'title_no' => 'TCT-AGR-001',
                'tax_decl_no' => 'TD-AGR-001',
                'province' => 'Negros Oriental',
                'municipality' => 'Bayawan City',
                'barangay' => 'Banga',
                'area_hectares' => '2.4000',
                'status' => 'active',
                'remarks' => 'Updated parcel without visible land type selector.',
            ]);

        $response->assertRedirect(route('staff.records.parcels.show', $parcel));

        $this->assertDatabaseHas('parcels', [
            'id' => $parcel->id,
            'agricultural_status' => 'not_yet_determined',
        ]);
    }

    public function test_staff_parcel_details_display_dar_clearance_scope_instead_of_agricultural_status_label(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'AGR-DETAILS-001',
            'title_no' => 'TCT-AGR-DETAILS-001',
            'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'agricultural_status' => 'private_agricultural',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.parcels.show', $parcel));

        $response->assertOk();
        $response->assertSee('DAR Clearance Scope');
        $response->assertSee('Agricultural land record');
        $response->assertDontSee('Agricultural Status');
        $response->assertDontSee('Private Agricultural Land');
    }

    public function test_staff_parcel_list_no_longer_filters_by_agricultural_status(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        Parcel::create([
            'parcel_code' => 'VISIBLE-AGRI-FILTER',
            'title_no' => 'TCT-VISIBLE-AGRI',
            'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'agricultural_status' => 'awarded_cloa',
        ]);

        Parcel::create([
            'parcel_code' => 'ALSO-VISIBLE-AGRI-FILTER',
            'title_no' => 'TCT-HIDDEN-AGRI',
            'province' => 'Negros Oriental',
            'municipality' => 'Bayawan City',
            'barangay' => 'Banga',
            'area_hectares' => 2.0000,
            'status' => 'active',
            'agricultural_status' => 'private_agricultural',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.parcels.index', [
                'agricultural_status' => 'awarded_cloa',
            ]));

        $response->assertOk();
        $response->assertSee('VISIBLE-AGRI-FILTER');
        $response->assertSee('ALSO-VISIBLE-AGRI-FILTER');
        $response->assertDontSee('Agricultural Status');
    }
}
