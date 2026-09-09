<?php

namespace Tests\Feature;

use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeodeticGeometryEditorViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_geodetic_geometry_editor_uses_prs92_zone4_point_based_mapping_helper(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-POINT-EDITOR-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $response = $this->actingAs($geodetic)
            ->get(route('geodetic.parcels.geometry.edit', $parcel));

        $response->assertOk();
        $response->assertSee('Parcel Boundary Coordinates');
        $response->assertSee('PRS92 / PTM Zone IV parcel boundary');
        $response->assertSee('EPSG:3124');
        $response->assertSee('Point 1');
        $response->assertSee('Easting (m)');
        $response->assertSee('Northing (m)');
        $response->assertSee('Convert &amp; Apply', false);
        $response->assertSee('Generated Web Map Geometry (WGS84)');
        $response->assertSee('Save Geometry');
    }

    public function test_geodetic_geometry_update_preserves_prs92_source_coordinates_in_geometry_metadata(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-PRS92-SOURCE-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'status' => 'active',
        ]);

        $geometry = [
            'type' => 'Polygon',
            'coordinates' => [[
                [122.79500000, 9.35500000],
                [122.80950000, 9.35850000],
                [122.80720000, 9.36920000],
                [122.79500000, 9.35500000],
            ]],
            'dar_source' => [
                'crs' => 'EPSG:3124',
                'name' => 'PRS92 / Philippines zone 4',
                'projection' => 'PTM Zone IV',
                'coordinate_order' => ['easting', 'northing'],
                'unit' => 'metre',
                'coordinates' => [
                    [477318.941, 1034526.171],
                    [478911.914, 1034912.331],
                    [478659.955, 1036095.895],
                ],
            ],
        ];

        $response = $this->actingAs($geodetic)
            ->patch(route('geodetic.parcels.geometry.update', $parcel), [
                'geometry_geojson' => json_encode($geometry),
            ]);

        $response->assertRedirect(route('geodetic.parcels.show', $parcel));

        $parcel->refresh();

        $this->assertSame('EPSG:3124', $parcel->geometry_geojson['dar_source']['crs']);
        $this->assertSame('PTM Zone IV', $parcel->geometry_geojson['dar_source']['projection']);
        $this->assertSame(
            [477318.941, 1034526.171],
            $parcel->geometry_geojson['dar_source']['coordinates'][0]
        );
    }
}
