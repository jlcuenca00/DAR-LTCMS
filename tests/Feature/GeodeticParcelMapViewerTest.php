<?php

namespace Tests\Feature;

use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeodeticParcelMapViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_geodetic_user_can_view_parcel_map_with_database_geometry(): void
    {
        $geodetic = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'TEST-GEO-MAP-001',
            'title_no' => 'T-GEO-001',
            'tax_decl_no' => 'TD-GEO-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Calindagan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.7500,
            'status' => 'active',
            'remarks' => 'Geodetic test mapped parcel.',
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

        $response = $this->actingAs($geodetic)
            ->get(route('geodetic.parcel-map.index'));

        $response->assertOk();
        $response->assertSee('Parcel Map Viewer');
        $response->assertSee('Map Tools');
        $response->assertSee('Reset View');
        $response->assertSee('Parcel List');
        $response->assertSee($parcel->parcel_code);
        $response->assertSee('T-GEO-001');
        $response->assertSee('TD-GEO-001');
        $response->assertSee('Read-only');
    }

    public function test_geodetic_user_can_view_read_only_parcel_details(): void
    {
        $geodetic = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'TEST-GEO-MAP-002',
            'title_no' => 'T-GEO-002',
            'tax_decl_no' => 'TD-GEO-002',
            'municipality' => 'Sibulan',
            'barangay' => 'Boloc-boloc',
            'province' => 'Negros Oriental',
            'area_hectares' => 2.2500,
            'status' => 'active',
            'remarks' => 'Geodetic parcel details test.',
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

        $response = $this->actingAs($geodetic)
            ->get(route('geodetic.parcels.show', $parcel));

        $response->assertOk();
        $response->assertSee('Parcel Reference Details');
        $response->assertSee('TEST-GEO-MAP-002');
        $response->assertSee('T-GEO-002');
        $response->assertSee('TD-GEO-002');
        $response->assertSee('Sibulan');
        $response->assertSee('Boloc-boloc');
        $response->assertSee('Geometry Reference');
        $response->assertDontSee('Approve');
        $response->assertDontSee('Generate Clearance');
    }

    public function test_guest_cannot_view_geodetic_parcel_map(): void
    {
        $response = $this->get(route('geodetic.parcel-map.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_geodetic_dashboard_links_to_parcel_map_viewer(): void
    {
        $geodetic = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $response = $this->actingAs($geodetic)
            ->get(route('geodetic.dashboard'));

        $response->assertOk();
        $response->assertSee('Open Map');
        $response->assertSee(route('geodetic.parcel-map.index'));
        $response->assertDontSee('/geodetic/applications');
    }
}
