<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeodeticParcelDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_finds_unlinked_parcels_beyond_first_page_without_loading_geometry(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);
        foreach (range(1, 25) as $n) {
            $this->parcel(sprintf('DIRECTORY-%03d', $n));
        }
        $this->actingAs($geodetic)->get(route('geodetic.parcels.directory'))
            ->assertOk()->assertViewHas('parcels', function ($parcels) {
                return $parcels->total() === 25 && $parcels->count() === 20
                    && ! array_key_exists('geometry_geojson', $parcels->first()->getAttributes());
            });
        $this->get(route('geodetic.parcels.directory', ['page' => 2]))
            ->assertOk()->assertSee('DIRECTORY-025')
            ->assertViewHas('parcels', fn ($parcels) => $parcels->count() === 5);
        $this->get(route('geodetic.parcels.directory', ['q' => 'directory-025']))
            ->assertOk()->assertSee('DIRECTORY-025')
            ->assertViewHas('parcels', fn ($parcels) => $parcels->total() === 1);
    }

    public function test_search_matches_full_owner_name_and_keeps_preview_bounded(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);
        $parcel = $this->parcel('OWNER-REFERENCE');
        foreach (range(1, 6) as $n) {
            $owner = Landowner::create([
                'first_name' => 'Owner', 'last_name' => 'Needle'.$n,
                'province' => 'Negros Oriental', 'contact_number' => '09171234567',
            ]);
            Landholding::create([
                'parcel_id' => $parcel->id, 'landowner_id' => $owner->id,
                'area_hectares' => 0.1, 'status' => 'active',
            ]);
        }
        $this->actingAs($geodetic)->get(route('geodetic.parcels.directory', ['q' => 'Owner Needle6']))
            ->assertOk()->assertSee('OWNER-REFERENCE')
            ->assertViewHas('parcels', function ($parcels) {
                $parcel = $parcels->first();
                return $parcels->total() === 1 && $parcel->landholdings_count === 6
                    && $parcel->landholdings->count() === 4
                    && ! array_key_exists('contact_number', $parcel->landholdings->first()->landowner->getAttributes());
            });
    }

    public function test_geometry_filters_and_archive_actions_are_consistent(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);
        $this->parcel('NO-GEOMETRY');
        $mapped = $this->parcel('HAS-GEOMETRY', [
            'geometry_geojson' => ['type' => 'Polygon', 'coordinates' => [[
                [123.30, 9.30], [123.31, 9.30], [123.31, 9.31], [123.30, 9.30],
            ]]],
        ]);
        $archived = $this->parcel('ARCHIVED-PARCEL', ['status' => 'inactive']);
        $this->actingAs($geodetic)->get(route('geodetic.parcels.directory', ['geometry' => 'unmapped']))
            ->assertOk()->assertSee('NO-GEOMETRY')->assertDontSee('HAS-GEOMETRY')->assertDontSee('ARCHIVED-PARCEL');
        $this->get(route('geodetic.parcels.directory', ['geometry' => 'mapped']))
            ->assertOk()->assertSee('HAS-GEOMETRY')->assertDontSee('NO-GEOMETRY');
        $this->get(route('geodetic.parcels.directory', ['status' => 'inactive']))
            ->assertOk()->assertSee('ARCHIVED-PARCEL')
            ->assertDontSee(route('geodetic.parcels.geometry.edit', $archived));
        $this->get(route('geodetic.parcels.directory', ['status' => 'all']))
            ->assertOk()->assertViewHas('parcels', fn ($parcels) => $parcels->total() === 3);
        // A legacy record can have stored geometry but unavailable derived bounds.
        \Illuminate\Support\Facades\DB::table('parcels')->where('id', $mapped->id)->update(['map_min_longitude' => null]);
        $this->get(route('geodetic.parcels.directory', ['geometry' => 'unavailable']))
            ->assertOk()->assertSee('HAS-GEOMETRY')
            ->assertViewHas('parcels', fn ($parcels) => $parcels->total() === 1);
    }

    public function test_literal_wildcard_search_and_invalid_filters(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);
        $this->parcel('LITERAL%_REF');
        $this->parcel('LITERAL-OTHER');
        $this->actingAs($geodetic)->get(route('geodetic.parcels.directory', ['q' => '%_']))
            ->assertOk()->assertSee('LITERAL%_REF')
            ->assertViewHas('parcels', fn ($parcels) => $parcels->total() === 1);
        $this->getJson(route('geodetic.parcels.directory', ['geometry' => 'invalid']))->assertUnprocessable();
        $this->getJson(route('geodetic.parcels.directory', ['page' => 0]))->assertUnprocessable();
        $this->getJson(route('geodetic.parcels.directory', ['q' => ['unexpected']]))->assertUnprocessable();
    }

    public function test_mapping_queue_reaches_records_after_first_eight_and_excludes_archived(): void
    {
        $geodetic = User::factory()->create(['role' => 'geodetic']);
        foreach (range(1, 11) as $n) {
            $this->parcel(sprintf('QUEUE-%03d', $n));
        }
        $this->parcel('QUEUE-ARCHIVED', ['status' => 'inactive']);
        $this->actingAs($geodetic)->getJson(route('geodetic.parcels.awaiting-geometry'))
            ->assertOk()->assertJsonCount(8, 'parcels')->assertJsonPath('count', 11)->assertJsonPath('last_page', 2)
            ->assertJsonPath('directory_url', route('geodetic.parcels.directory', ['geometry' => 'unmapped', 'status' => 'active']));
        $this->getJson(route('geodetic.parcels.awaiting-geometry', ['page' => 2]))
            ->assertOk()->assertJsonCount(3, 'parcels')->assertJsonPath('parcels.2.parcel_code', 'QUEUE-011');
        $this->getJson(route('geodetic.parcels.awaiting-geometry', ['page' => 0]))->assertUnprocessable();
    }

    public function test_other_roles_cannot_access_directory_or_queue(): void
    {
        foreach (['staff', 'landowner'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('geodetic.parcels.directory'))->assertForbidden();
            $this->getJson(route('geodetic.parcels.awaiting-geometry'))->assertForbidden();
        }
    }

    private function parcel(string $code, array $extra = []): Parcel
    {
        return Parcel::create(array_merge([
            'parcel_code' => $code, 'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan',
            'province' => 'Negros Oriental', 'area_hectares' => 1, 'status' => 'active',
        ], $extra));
    }
}
