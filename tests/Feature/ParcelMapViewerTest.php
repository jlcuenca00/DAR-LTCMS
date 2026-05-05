<?php

namespace Tests\Feature;

use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelMapViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_parcel_map_with_database_geometry(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'TEST-MAP-001',
            'title_no' => 'T-TEST-001',
            'tax_decl_no' => 'TD-TEST-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Calindagan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'remarks' => 'Test mapped parcel.',
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

        $response = $this->actingAs($staff)
            ->get(route('staff.parcel-map.index'));

        $response->assertOk();
        $response->assertSee('Parcel Map Viewer');
        $response->assertSee($parcel->parcel_code);
        $response->assertSee('T-TEST-001');
        $response->assertSee('TD-TEST-001');
    }

    public function test_staff_can_view_parcel_details_page(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'TEST-MAP-002',
            'title_no' => 'T-TEST-002',
            'tax_decl_no' => 'TD-TEST-002',
            'municipality' => 'Sibulan',
            'barangay' => 'Boloc-boloc',
            'province' => 'Negros Oriental',
            'area_hectares' => 2.5000,
            'status' => 'active',
            'remarks' => 'Test parcel details record.',
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

        $response = $this->actingAs($staff)
            ->get(route('staff.records.parcels.show', $parcel));

        $response->assertOk();
        $response->assertSee('Parcel Details');
        $response->assertSee('TEST-MAP-002');
        $response->assertSee('T-TEST-002');
        $response->assertSee('TD-TEST-002');
        $response->assertSee('Sibulan');
        $response->assertSee('Boloc-boloc');
    }

    public function test_guest_cannot_view_staff_parcel_map(): void
    {
        $response = $this->get(route('staff.parcel-map.index'));

        $response->assertRedirect(route('login'));
    }
}