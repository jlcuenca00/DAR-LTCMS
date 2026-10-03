<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use App\Services\ParcelMapBounds;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BoundedParcelMapTest extends TestCase
{
    use RefreshDatabase;

    private function parcel(string $code, float $longitude = 123.3): Parcel
    {
        return Parcel::create([
            'parcel_code' => $code, 'status' => 'active', 'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City', 'barangay' => 'Calindagan', 'area_hectares' => 10,
            'geometry_geojson' => ['type' => 'Polygon', 'coordinates' => [[
                [$longitude, 9.3], [$longitude + 0.01, 9.3], [$longitude + 0.01, 9.31],
                [$longitude, 9.31], [$longitude, 9.3],
            ]]],
        ]);
    }

    private function viewport(string $role): string
    {
        return route($role.'.parcel-map.features', ['west' => 123.2, 'east' => 123.5, 'south' => 9.2, 'north' => 9.5]);
    }

    public function test_viewport_is_bounded_but_search_and_focus_find_parcels_beyond_the_limit(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        for ($i = 0; $i < 55; $i++) {
            $last = $this->parcel(sprintf('BOUNDED-%03d', $i));
        }
        $outside = $this->parcel('OUTSIDE-VIEW', 124.0);
        $inactive = $this->parcel('INACTIVE-VIEW');
        $inactive->update(['status' => 'inactive']);
        $this->actingAs($staff)->getJson($this->viewport('staff'))
            ->assertOk()->assertJsonCount(50, 'features')->assertJsonPath('total', 55)
            ->assertJsonPath('limited', true);
        $this->getJson(route('staff.parcel-map.search', ['q' => $last->parcel_code]))
            ->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $last->id)
            ->assertJsonMissingPath('items.0.geometry');
        $this->getJson(route('staff.parcel-map.feature', $last->id))
            ->assertOk()->assertJsonPath('properties.id', $last->id);
        $this->getJson(route('staff.parcel-map.search', ['page' => 2]))
            ->assertOk()->assertJsonCount(15, 'items')->assertJsonPath('page', 2)->assertJsonPath('total', 56);
        $this->getJson(route('staff.parcel-map.search', ['q' => $outside->parcel_code]))->assertJsonPath('total', 1);
        $this->getJson(route('staff.parcel-map.feature', $inactive->id))->assertNotFound();
        $config = $this->get(route('staff.parcel-map.index'))->assertOk()->viewData('mapConfig');
        $this->assertCount(15, $config['initial_search']['items']);
        $this->assertArrayNotHasKey('geometry', $config['initial_search']['items'][0]);
    }

    public function test_landowner_endpoints_preserve_historical_access_without_other_owner_data_or_active_area_inflation(): void
    {
        $user = User::factory()->create(['role' => 'landowner']);
        $owner = Landowner::create(['user_id' => $user->id, 'first_name' => 'Map', 'last_name' => 'Owner', 'province' => 'Negros Oriental']);
        $other = Landowner::create(['first_name' => 'OtherPrivate', 'last_name' => 'Owner', 'province' => 'Negros Oriental']);
        $parcel = $this->parcel('OWN-HISTORICAL-MAP');
        $unlinked = $this->parcel('OTHER-PRIVATE-MAP');
        $ownHolding = Landholding::create(['parcel_id' => $parcel->id, 'landowner_id' => $owner->id, 'status' => 'historical', 'area_hectares' => 7]);
        Landholding::create(['parcel_id' => $parcel->id, 'landowner_id' => $other->id, 'status' => 'active', 'area_hectares' => 3]);

        $this->actingAs($user)->getJson($this->viewport('landowner'))->assertOk()
            ->assertJsonCount(1, 'features')->assertJsonPath('features.0.properties.active_linked_area_hectares', '0.0000')
            ->assertJsonPath('features.0.properties.area_hectares', '10.0000')
            ->assertJsonPath('features.0.properties.historical_holding_count', 1)->assertDontSee('OtherPrivate');
        $this->getJson(route('landowner.parcel-map.search', ['q' => 'OTHER-PRIVATE']))->assertJsonPath('total', 0);
        $this->getJson(route('landowner.parcel-map.feature', $unlinked->id))->assertNotFound();
        $this->get(route('landowner.parcels.show', $parcel))->assertOk()
            ->assertSee('Current active linked area')->assertSee('0.0000 ha')->assertSee('7.0000 ha');
        $this->getJson(route('landowner.parcel-map.feature', $parcel->id))->assertOk()
            ->assertJsonPath('properties.active_linked_area_hectares', '0.0000')
            ->assertJsonPath('properties.reference_scope', 'Historical/non-active landholding reference');
        $ownHolding->update(['status' => 'active', 'area_hectares' => 2]);
        $this->getJson(route('landowner.parcel-map.feature', $parcel->id))->assertOk()
            ->assertJsonPath('properties.active_linked_area_hectares', '2.0000')
            ->assertJsonPath('properties.reference_scope', 'Active landholding reference');
    }

    public function test_endpoint_role_and_parameter_validation_is_enforced(): void
    {
        $this->getJson($this->viewport('staff'))->assertUnauthorized();
        $geodetic = User::factory()->create(['role' => 'geodetic']);
        $this->actingAs($geodetic)->getJson($this->viewport('staff'))->assertForbidden();
        $this->getJson(route('geodetic.parcel-map.features', ['west' => 123, 'east' => 122, 'south' => 9, 'north' => 10]))
            ->assertUnprocessable()->assertJsonValidationErrors('east');
        $this->getJson(route('geodetic.parcel-map.features', ['west' => 123, 'east' => 124, 'south' => 9, 'north' => 100]))
            ->assertUnprocessable()->assertJsonValidationErrors('north');
        $this->getJson(route('geodetic.parcel-map.search', ['q' => str_repeat('x', 101), 'page' => -1]))
            ->assertUnprocessable()->assertJsonValidationErrors(['q', 'page']);
        $this->parcel('LITERAL_%_MAP');
        $this->parcel('ORDINARY-MAP');
        $this->getJson(route('geodetic.parcel-map.search', ['q' => '_%_']))->assertOk()->assertJsonPath('total', 1);
        $this->actingAs(User::factory()->create(['role' => 'landowner']))
            ->getJson($this->viewport('landowner'))->assertOk()->assertJsonPath('total', 0);
    }

    public function test_geodetic_owner_reference_is_qualified_and_full_name_search_finds_it(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);
        $owner = Landowner::create(['first_name' => 'Current', 'last_name' => 'Owner', 'province' => 'Negros Oriental']);
        $former = Landowner::create(['first_name' => 'Former', 'last_name' => 'Owner', 'province' => 'Negros Oriental']);
        $parcel = $this->parcel('QUALIFIED-REFERENCE');
        Landholding::create(['parcel_id' => $parcel->id, 'landowner_id' => $owner->id, 'status' => 'active', 'area_hectares' => 2]);
        Landholding::create(['parcel_id' => $parcel->id, 'landowner_id' => $former->id, 'status' => 'historical', 'area_hectares' => 7]);
        $this->actingAs($geodetic)->getJson(route('geodetic.parcel-map.feature', $parcel->id))
            ->assertOk()->assertJsonPath('properties.reference_scope', 'Active landholding reference')
            ->assertJsonPath('properties.landowner', 'Current Owner')->assertJsonPath('properties.active_linked_area_hectares', '2.0000');
        $this->getJson(route('geodetic.parcel-map.search', ['q' => 'Current Owner']))->assertOk()->assertJsonPath('total', 1);
        $this->getJson(route('geodetic.parcel-map.search', ['q' => 'Former Owner']))->assertOk()->assertJsonPath('total', 1);
    }

    public function test_total_viewport_payload_budget_is_enforced_independently_of_feature_count(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        for ($i = 0; $i < 8; $i++) {
            $parcel = $this->parcel('PAYLOAD-BUDGET-'.$i);
            $geometry = $parcel->geometry_geojson;
            $geometry['metadata'] = str_repeat('x', 190000);
            $parcel->update(['geometry_geojson' => $geometry]);
        }
        $response = $this->actingAs($staff)->getJson($this->viewport('staff'))->assertOk()
            ->assertJsonPath('total', 8)->assertJsonPath('limited', true);
        $this->assertLessThanOrEqual(1000000, strlen(json_encode($response->json('features'))));
        $this->assertLessThan(8, $response->json('returned'));
    }

    public function test_geometry_budget_and_invalid_legacy_rows_do_not_break_viewport(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $good = $this->parcel('BUDGET-GOOD');
        $large = $this->parcel('BUDGET-LARGE');
        $geometry = $large->geometry_geojson;
        $geometry['metadata'] = str_repeat('x', 210000);
        $large->update(['geometry_geojson' => $geometry]);
        $bad = $this->parcel('BUDGET-BAD');
        // Simulate a pre-existing malformed legacy record with stale display bounds.
        DB::table('parcels')->where('id', $bad->id)->update(['geometry_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => []])]);
        $this->actingAs($staff)->getJson($this->viewport('staff'))->assertOk()
            ->assertJsonCount(1, 'features')->assertJsonPath('features.0.properties.id', $good->id)
            ->assertJsonPath('total', 3)->assertJsonPath('skipped', 2)->assertJsonPath('limited', true);
        $this->getJson(route('staff.parcel-map.feature', $large->id))->assertNotFound();
        $this->getJson(route('staff.parcel-map.search', ['q' => 'BUDGET-LARGE']))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonMissingPath('items.0.geometry');
    }

    public function test_display_bounds_follow_geometry_edits_and_clear_without_extra_version_changes(): void
    {
        $parcel = $this->parcel('BOUNDS-SYNC');
        $this->assertEquals(123.3, $parcel->map_min_longitude);
        $version = $parcel->geometry_version;
        $geometry = $parcel->geometry_geojson;
        $geometry['coordinates'] = [[[124.0, 9.3], [124.1, 9.3], [124.1, 9.4], [124.0, 9.4], [124.0, 9.3]]];
        $parcel->update(['geometry_geojson' => $geometry]);
        $this->assertEquals(124, $parcel->fresh()->map_min_longitude);
        $this->assertSame($version + 1, $parcel->fresh()->geometry_version);
        $parcel->update(['remarks' => 'Reference only']);
        $this->assertSame($version + 1, $parcel->fresh()->geometry_version);
        $parcel->update(['geometry_geojson' => null]);
        foreach (ParcelMapBounds::COLUMNS as $column) {
            $this->assertNull($parcel->fresh()->getAttribute($column));
        }
    }

    public function test_bounds_migration_backfills_existing_geometry_without_rewriting_it(): void
    {
        $parcel = $this->parcel('BOUNDS-BACKFILL');
        $before = DB::table('parcels')->where('id', $parcel->id)->first();
        $migration = require database_path('migrations/2026_10_03_000600_add_parcel_map_bounds.php');
        try {
            $migration->down();
            $migration->up();
            $after = DB::table('parcels')->where('id', $parcel->id)->first();
            $this->assertEquals(123.3, $after->map_min_longitude);
            $this->assertSame($before->geometry_geojson, $after->geometry_geojson);
            $this->assertSame($before->geometry_version, $after->geometry_version);
            $this->assertSame($before->updated_at, $after->updated_at);
        } finally {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('parcels', 'map_min_longitude')) {
                $migration->up();
            }
        }
    }
}
