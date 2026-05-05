<?php

namespace Tests\Feature;

use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordSearchPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_landowner_records_page(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        Landowner::create([
            'first_name' => 'Visible',
            'middle_name' => 'Demo',
            'last_name' => 'Landowner',
            'contact_number' => '09123456789',
            'address_line' => 'Sample Address',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.landowners.index'));

        $response->assertOk();
        $response->assertSee('Landowner Records');
        $response->assertSee('Staff Landowner Record Search');
        $response->assertSee('Visible Demo Landowner');
        $response->assertSee('Dumaguete City');
    }

    public function test_staff_can_search_landowner_records_by_name(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        Landowner::create([
            'first_name' => 'Searchable',
            'middle_name' => 'Demo',
            'last_name' => 'Owner',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
        ]);

        Landowner::create([
            'first_name' => 'Hidden',
            'middle_name' => 'Demo',
            'last_name' => 'Owner',
            'municipality' => 'Bayawan City',
            'barangay' => 'Villareal',
            'province' => 'Negros Oriental',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.landowners.index', [
                'search' => 'Searchable',
            ]));

        $response->assertOk();
        $response->assertSee('Searchable Demo Owner');
        $response->assertDontSee('Hidden Demo Owner');
    }

    public function test_staff_can_filter_landowners_by_account_link_status(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $linkedUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        Landowner::create([
            'first_name' => 'Linked',
            'middle_name' => 'Demo',
            'last_name' => 'Owner',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'user_id' => $linkedUser->id,
        ]);

        Landowner::create([
            'first_name' => 'Unlinked',
            'middle_name' => 'Demo',
            'last_name' => 'Owner',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.landowners.index', [
                'linked_status' => 'linked',
            ]));

        $response->assertOk();
        $response->assertSee('Linked Demo Owner');
        $response->assertDontSee('Unlinked Demo Owner');
    }

    public function test_staff_can_view_parcel_records_page(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        Parcel::create([
            'parcel_code' => 'PARCEL-VISIBLE-001',
            'title_no' => 'TCT-001',
            'tax_decl_no' => 'TD-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'remarks' => 'Visible parcel record',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.parcels.index'));

        $response->assertOk();
        $response->assertSee('Parcel Records');
        $response->assertSee('Staff Parcel Record Search');
        $response->assertSee('PARCEL-VISIBLE-001');
        $response->assertSee('TCT-001');
    }

    public function test_staff_can_search_parcel_records_by_code_or_title(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        Parcel::create([
            'parcel_code' => 'VISIBLE-PARCEL-001',
            'title_no' => 'TCT-VISIBLE-001',
            'tax_decl_no' => 'TD-VISIBLE-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'remarks' => 'Visible parcel record',
        ]);

        Parcel::create([
            'parcel_code' => 'HIDDEN-PARCEL-001',
            'title_no' => 'TCT-HIDDEN-001',
            'tax_decl_no' => 'TD-HIDDEN-001',
            'municipality' => 'Bayawan City',
            'barangay' => 'Villareal',
            'area_hectares' => 2.5000,
            'status' => 'active',
            'remarks' => 'Hidden parcel record',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.parcels.index', [
                'search' => 'VISIBLE',
            ]));

        $response->assertOk();
        $response->assertSee('VISIBLE-PARCEL-001');
        $response->assertDontSee('HIDDEN-PARCEL-001');
    }

    public function test_staff_can_filter_parcels_by_location_and_status(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        Parcel::create([
            'parcel_code' => 'VISIBLE-LOCATION-PARCEL',
            'title_no' => 'TCT-LOCATION-001',
            'tax_decl_no' => 'TD-LOCATION-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'remarks' => 'Visible location parcel',
        ]);

        Parcel::create([
            'parcel_code' => 'HIDDEN-LOCATION-PARCEL',
            'title_no' => 'TCT-LOCATION-002',
            'tax_decl_no' => 'TD-LOCATION-002',
            'municipality' => 'Bayawan City',
            'barangay' => 'Villareal',
            'area_hectares' => 2.5000,
            'status' => 'inactive',
            'remarks' => 'Hidden location parcel',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.parcels.index', [
                'municipality' => 'Dumaguete City',
                'barangay' => 'Bantayan',
                'status' => 'active',
            ]));

        $response->assertOk();
        $response->assertSee('VISIBLE-LOCATION-PARCEL');
        $response->assertDontSee('HIDDEN-LOCATION-PARCEL');
    }

    public function test_landowner_cannot_view_staff_record_search_pages(): void
    {
        $landownerUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $this->actingAs($landownerUser)
            ->get(route('staff.records.landowners.index'))
            ->assertForbidden();

        $this->actingAs($landownerUser)
            ->get(route('staff.records.parcels.index'))
            ->assertForbidden();
    }

    public function test_geodetic_cannot_view_staff_record_search_pages(): void
    {
        $geodeticUser = User::factory()->create([
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $this->actingAs($geodeticUser)
            ->get(route('staff.records.landowners.index'))
            ->assertForbidden();

        $this->actingAs($geodeticUser)
            ->get(route('staff.records.parcels.index'))
            ->assertForbidden();
    }
}