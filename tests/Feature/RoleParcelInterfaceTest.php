<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use App\Services\ParcelMapBounds;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleParcelInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private function parcel(string $code, array $extra = []): Parcel
    {
        return Parcel::create(array_merge([
            'parcel_code' => $code, 'status' => 'active', 'area_hectares' => 1,
            'municipality' => 'Dumaguete City', 'barangay' => 'Calindagan',
            'geometry_geojson' => ['type' => 'Polygon', 'coordinates' => [[
                [123.3, 9.3], [123.31, 9.3], [123.31, 9.31], [123.3, 9.31], [123.3, 9.3],
            ]]],
        ], $extra));
    }

    public function test_geodetic_details_offer_geometry_actions_and_announce_escaped_save_feedback(): void
    {
        $user = User::factory()->create(['role' => 'geodetic']);
        $parcel = $this->parcel('GEO-ACTION');
        $owner = Landowner::create(['first_name' => 'Linked', 'last_name' => 'Reference']);
        Landholding::create(['parcel_id' => $parcel->id, 'landowner_id' => $owner->id, 'area_hectares' => .5, 'status' => 'active']);
        $this->actingAs($user)->withSession(['success' => 'Saved version 2. <script>unsafe</script>'])
            ->get(route('geodetic.parcels.show', $parcel))->assertOk()
            ->assertSee('Review Geometry')->assertSee(route('geodetic.parcels.geometry.edit', $parcel), false)
            ->assertSee('Saved version 2. &lt;script&gt;unsafe&lt;/script&gt;', false)
            ->assertDontSee('<script>unsafe</script>', false)->assertSee('role="status"', false)
            ->assertDontSee('Generate Clearance')->assertSee('Geometry stored');
        $this->get(route('geodetic.parcels.index'))->assertOk()->assertSee('Geometry stored');
        $empty = $this->parcel('GEO-EMPTY', ['geometry_geojson' => null]);
        $this->get(route('geodetic.parcels.show', $empty))->assertOk()->assertSee('Add Geometry')->assertSee('No geometry');
    }

    public function test_archived_geometry_has_no_edit_action_and_shows_rejected_save_reason(): void
    {
        $user = User::factory()->create(['role' => 'geodetic']);
        $parcel = $this->parcel('GEO-ARCHIVED', ['status' => 'inactive']);
        $this->actingAs($user)->withSession(['error' => 'Parcel was archived; geometry was not changed.'])
            ->get(route('geodetic.parcels.show', $parcel))->assertOk()
            ->assertSee('Archived reference')->assertSee('Archived parcel geometry is read-only.')
            ->assertSee('Parcel was archived; geometry was not changed.')->assertSee('role="alert"', false)
            ->assertDontSee(route('geodetic.parcels.geometry.edit', $parcel), false);
        $this->get(route('geodetic.parcels.geometry.edit', $parcel))->assertForbidden();
    }

    public function test_missing_display_bounds_are_not_reported_as_map_available_in_role_views(): void
    {
        $user = User::factory()->create(['role' => 'geodetic']);
        $parcel = $this->parcel('GEO-REVIEW');
        Parcel::query()->whereKey($parcel->id)->update(array_fill_keys(ParcelMapBounds::COLUMNS, null));
        $this->actingAs($user)->get(route('geodetic.parcels.show', $parcel))->assertOk()->assertSee('Geometry needs review');
        $this->get(route('geodetic.dashboard'))->assertOk()->assertSee('Geometry needs review')->assertSee('Parcels with geometry');
        $this->get(route('geodetic.parcel-map.index'))->assertOk()->assertViewHas('mapConfig', fn ($config) => $config['total'] === 0);
    }

    public function test_landowner_historical_references_remain_visible_without_geometry_edit_actions(): void
    {
        $user = User::factory()->create(['role' => 'landowner']);
        $owner = Landowner::create(['user_id' => $user->id, 'first_name' => 'Own', 'last_name' => 'Reference']);
        $parcel = $this->parcel('OWN-ARCHIVED', ['status' => 'inactive']);
        Landholding::create(['parcel_id' => $parcel->id, 'landowner_id' => $owner->id, 'area_hectares' => .5, 'status' => 'transferred']);
        $this->actingAs($user)->get(route('landowner.parcels.show', $parcel))->assertOk()
            ->assertSee('Archived reference')->assertDontSee('Review Geometry')->assertDontSee('Add Geometry');
        $this->get(route('landowner.parcels.index'))->assertOk()->assertSee('Archived reference');
        $this->get(route('landowner.dashboard'))->assertOk()->assertSee('Archived reference')->assertSee('Parcels with geometry');
        $this->get(route('landowner.parcel-map.index'))->assertOk()->assertViewHas('mapConfig', fn ($config) => $config['total'] === 0);
        $other = $this->parcel('OTHER-PARCEL');
        $this->get(route('landowner.parcels.show', $other))->assertForbidden();
        $current = $this->parcel('OWN-CURRENT');
        Landholding::create(['parcel_id' => $current->id, 'landowner_id' => $owner->id, 'area_hectares' => .5, 'status' => 'active']);
        $this->get(route('landowner.dashboard'))->assertOk()->assertSee('Geometry stored');
    }
}
