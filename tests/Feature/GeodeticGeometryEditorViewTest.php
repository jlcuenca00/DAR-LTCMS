<?php

namespace Tests\Feature;

use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeodeticGeometryEditorViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_geodetic_geometry_editor_uses_shared_point_based_mapping_helper(): void
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
        $response->assertSee('Parcel boundary helper');
        $response->assertSee('Point 1');
        $response->assertSee('Longitude / X');
        $response->assertSee('Latitude / Y');
        $response->assertSee('Apply Coordinates');
        $response->assertSee('Save Geometry');
    }
}
