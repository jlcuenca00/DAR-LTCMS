<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandownerParcelMapViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_landowner_can_only_view_own_mapped_parcels_on_map(): void
    {
        $userA = User::factory()->create([
            'role' => 'landowner',
        ]);

        $userB = User::factory()->create([
            'role' => 'landowner',
        ]);

        $landownerA = Landowner::create([
            'first_name' => 'Alpha',
            'last_name' => 'Owner',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Calindagan',
            'province' => 'Negros Oriental',
            'user_id' => $userA->id,
        ]);

        $landownerB = Landowner::create([
            'first_name' => 'Bravo',
            'last_name' => 'Owner',
            'municipality' => 'Sibulan',
            'barangay' => 'Boloc-boloc',
            'province' => 'Negros Oriental',
            'user_id' => $userB->id,
        ]);

        $parcelA = Parcel::create([
            'parcel_code' => 'LANDOWNER-ALPHA-PARCEL',
            'title_no' => 'T-ALPHA-001',
            'tax_decl_no' => 'TD-ALPHA-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Calindagan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'geometry_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [123.2865, 9.3185],
                    [123.2920, 9.3182],
                    [123.2924, 9.3134],
                    [123.2870, 9.3129],
                    [123.2865, 9.3185],
                ]],
            ],
        ]);

        $parcelB = Parcel::create([
            'parcel_code' => 'LANDOWNER-BRAVO-PARCEL',
            'title_no' => 'T-BRAVO-001',
            'tax_decl_no' => 'TD-BRAVO-001',
            'municipality' => 'Sibulan',
            'barangay' => 'Boloc-boloc',
            'province' => 'Negros Oriental',
            'area_hectares' => 2.5000,
            'status' => 'active',
            'geometry_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [123.2780, 9.3562],
                    [123.2844, 9.3560],
                    [123.2851, 9.3507],
                    [123.2785, 9.3503],
                    [123.2780, 9.3562],
                ]],
            ],
        ]);

        Landholding::create([
            'landowner_id' => $landownerA->id,
            'parcel_id' => $parcelA->id,
            'area_hectares' => 1.2500,
            'status' => 'active',
            'remarks' => 'Alpha owner mapped parcel.',
        ]);

        Landholding::create([
            'landowner_id' => $landownerB->id,
            'parcel_id' => $parcelB->id,
            'area_hectares' => 2.5000,
            'status' => 'active',
            'remarks' => 'Bravo owner mapped parcel.',
        ]);

        $response = $this->actingAs($userA)
            ->get(route('landowner.parcel-map.index'));

        $response->assertOk();
        $response->assertSee('My Parcel Map');
        $response->assertSee('LANDOWNER-ALPHA-PARCEL');
        $response->assertSee('T-ALPHA-001');
        $response->assertDontSee('LANDOWNER-BRAVO-PARCEL');
        $response->assertDontSee('T-BRAVO-001');
    }

    public function test_landowner_can_view_own_parcel_details(): void
    {
        $user = User::factory()->create([
            'role' => 'landowner',
        ]);

        $landowner = Landowner::create([
            'first_name' => 'Alpha',
            'last_name' => 'Owner',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Calindagan',
            'province' => 'Negros Oriental',
            'user_id' => $user->id,
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'LANDOWNER-DETAILS-PARCEL',
            'title_no' => 'T-DETAILS-001',
            'tax_decl_no' => 'TD-DETAILS-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Calindagan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.7500,
            'status' => 'active',
            'geometry_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [123.2865, 9.3185],
                    [123.2920, 9.3182],
                    [123.2924, 9.3134],
                    [123.2870, 9.3129],
                    [123.2865, 9.3185],
                ]],
            ],
        ]);

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 1.7500,
            'status' => 'active',
            'remarks' => 'Own linked parcel.',
        ]);

        $response = $this->actingAs($user)
            ->get(route('landowner.parcels.show', $parcel));

        $response->assertOk();
        $response->assertSee('My Parcel Details');
        $response->assertSee('LANDOWNER-DETAILS-PARCEL');
        $response->assertSee('T-DETAILS-001');
        $response->assertSee('TD-DETAILS-001');
        $response->assertSee('Calindagan');
    }

    public function test_landowner_cannot_directly_access_another_landowners_parcel_details(): void
    {
        $userA = User::factory()->create([
            'role' => 'landowner',
        ]);

        $userB = User::factory()->create([
            'role' => 'landowner',
        ]);

        $landownerA = Landowner::create([
            'first_name' => 'Alpha',
            'last_name' => 'Owner',
            'province' => 'Negros Oriental',
            'user_id' => $userA->id,
        ]);

        $landownerB = Landowner::create([
            'first_name' => 'Bravo',
            'last_name' => 'Owner',
            'province' => 'Negros Oriental',
            'user_id' => $userB->id,
        ]);

        $parcelA = Parcel::create([
            'parcel_code' => 'PRIVATE-ALPHA-PARCEL',
            'title_no' => 'T-PRIVATE-ALPHA',
            'tax_decl_no' => 'TD-PRIVATE-ALPHA',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Calindagan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.0000,
            'status' => 'active',
            'geometry_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [123.2865, 9.3185],
                    [123.2920, 9.3182],
                    [123.2924, 9.3134],
                    [123.2870, 9.3129],
                    [123.2865, 9.3185],
                ]],
            ],
        ]);

        $parcelB = Parcel::create([
            'parcel_code' => 'PRIVATE-BRAVO-PARCEL',
            'title_no' => 'T-PRIVATE-BRAVO',
            'tax_decl_no' => 'TD-PRIVATE-BRAVO',
            'municipality' => 'Sibulan',
            'barangay' => 'Boloc-boloc',
            'province' => 'Negros Oriental',
            'area_hectares' => 2.0000,
            'status' => 'active',
            'geometry_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [123.2780, 9.3562],
                    [123.2844, 9.3560],
                    [123.2851, 9.3507],
                    [123.2785, 9.3503],
                    [123.2780, 9.3562],
                ]],
            ],
        ]);

        Landholding::create([
            'landowner_id' => $landownerA->id,
            'parcel_id' => $parcelA->id,
            'area_hectares' => 1.0000,
            'status' => 'active',
        ]);

        Landholding::create([
            'landowner_id' => $landownerB->id,
            'parcel_id' => $parcelB->id,
            'area_hectares' => 2.0000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($userA)
            ->get(route('landowner.parcels.show', $parcelB));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_landowner_parcel_map(): void
    {
        $response = $this->get(route('landowner.parcel-map.index'));

        $response->assertRedirect(route('login'));
    }
    public function test_landowner_dashboard_links_to_parcel_map_viewer(): void
{
    $user = User::factory()->create([
        'role' => 'landowner',
    ]);

    Landowner::create([
        'first_name' => 'Alpha',
        'last_name' => 'Owner',
        'municipality' => 'Dumaguete City',
        'barangay' => 'Calindagan',
        'province' => 'Negros Oriental',
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('landowner.dashboard'));

    $response->assertOk();
    $response->assertSee('My Parcel Map');
    $response->assertSee(route('landowner.parcel-map.index'));
}
}