<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcel_geometry_revisions', function (Blueprint $table) {
            $table->json('geometry_geojson')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Preserve cleared-geometry revisions as JSON null before restoring the
        // historical NOT NULL constraint.
        DB::table('parcel_geometry_revisions')
            ->whereNull('geometry_geojson')
            ->update(['geometry_geojson' => 'null']);

        Schema::table('parcel_geometry_revisions', function (Blueprint $table) {
            $table->json('geometry_geojson')->nullable(false)->change();
        });
    }
};
