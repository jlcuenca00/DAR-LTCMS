<?php

namespace Tests\Feature;

use App\Http\Controllers\Staff\ParcelMapController;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_postgresql_release_performance_indexes_are_present(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Release performance indexes are PostgreSQL-specific.');
        }

        $expected = [
            'ltc_apps_application_date_idx',
            'ltc_apps_created_at_idx',
            'ltc_apps_transferor_owner_idx',
            'ltc_apps_transferee_owner_idx',
            'ltc_apps_transferors_gin_idx',
            'ltc_apps_transferees_gin_idx',
            'landholdings_owner_created_idx',
            'landholdings_parcel_status_idx',
            'parcels_active_mapped_location_idx',
            'parcels_map_viewport_gist',
            'parcels_map_bounds_index',
            'parcels_unmapped_created_idx',
            'clearances_generated_at_idx',
            'ltc_apps_search_trgm_idx',
            'ltc_apps_application_code_trgm_idx',
            'app_docs_reference_trgm_idx',
            'landowners_search_trgm_idx',
            'parcels_search_trgm_idx',
            'users_search_trgm_idx',
            'source_packages_search_trgm_idx',
            'legacy_records_search_trgm_idx',
            'legacy_records_parcel_ref_lower_idx',
            'legacy_records_landholding_ref_lower_idx',
        ];

        $actual = DB::table('pg_indexes')
            ->where('schemaname', 'public')
            ->whereIn('indexname', $expected)
            ->pluck('indexname')
            ->all();

        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
        $this->assertTrue(
            DB::table('pg_extension')->where('extname', 'pg_trgm')->exists(),
            'Expected pg_trgm to support indexed contains-searches.'
        );
    }

    public function test_staff_map_query_count_stays_bounded_as_parcel_rows_grow(): void
    {
        $geometry = [
            'type' => 'Polygon',
            'coordinates' => [[
                [123.3000, 9.3000],
                [123.3010, 9.3000],
                [123.3010, 9.3010],
                [123.3000, 9.3010],
                [123.3000, 9.3000],
            ]],
        ];

        foreach (range(1, 6) as $sequence) {
            $landowner = Landowner::create([
                'first_name' => 'Map',
                'last_name' => 'Owner '.$sequence,
                'province' => 'Negros Oriental',
            ]);

            $parcel = Parcel::create([
                'parcel_code' => sprintf('PAR-PERF-%03d', $sequence),
                'municipality' => 'Dumaguete City',
                'barangay' => 'Bantayan',
                'area_hectares' => 1.0000,
                'status' => 'active',
                'geometry_geojson' => $geometry,
            ]);

            Landholding::create([
                'landowner_id' => $landowner->id,
                'parcel_id' => $parcel->id,
                'area_hectares' => 1.0000,
                'status' => Landholding::STATUS_ACTIVE,
            ]);
        }

        $this->actingAs(User::factory()->create(['role' => 'staff']));
        DB::flushQueryLog();
        DB::enableQueryLog();

        $view = app()->call([app(ParcelMapController::class), 'index']);
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        $mapConfig = $view->getData()['mapConfig'];

        $this->assertCount(6, $mapConfig['initial_search']['items']);
        $this->assertSame(6, $mapConfig['total']);
        $this->assertLessThanOrEqual(
            10,
            $queryCount,
            'Staff parcel map should use bounded eager-load queries instead of per-parcel lookups.'
        );
    }
}
