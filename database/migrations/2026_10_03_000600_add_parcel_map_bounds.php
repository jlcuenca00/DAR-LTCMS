<?php

use App\Services\ParcelMapBounds;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            foreach (ParcelMapBounds::COLUMNS as $column) {
                $table->double($column)->nullable();
            }
            $table->index(['status', 'map_min_longitude'], 'parcels_map_bounds_index');
        });

        // Backfill without rewriting geometry, edit-session versions or timestamps.
        DB::table('parcels')->select('id', 'geometry_geojson')->whereNotNull('geometry_geojson')
            ->chunkById(100, function ($parcels) {
                foreach ($parcels as $parcel) {
                    $geometry = is_string($parcel->geometry_geojson)
                        ? json_decode($parcel->geometry_geojson, true) : $parcel->geometry_geojson;
                    $bounds = ParcelMapBounds::fromGeometry($geometry);
                    if ($bounds !== null) {
                        DB::table('parcels')->where('id', $parcel->id)->update($bounds);
                    }
                }
            });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX parcels_map_viewport_gist ON parcels USING gist
                (box(point(map_min_longitude, map_min_latitude), point(map_max_longitude, map_max_latitude)))
                WHERE status = \'active\' AND map_min_longitude IS NOT NULL AND map_min_latitude IS NOT NULL
                AND map_max_longitude IS NOT NULL AND map_max_latitude IS NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS parcels_map_viewport_gist');
        }
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropIndex('parcels_map_bounds_index');
            $table->dropColumn(ParcelMapBounds::COLUMNS);
        });
    }
};
