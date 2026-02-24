<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('parcels', function (Blueprint $table) {
        $table->id();

        // Internal identifiers
        $table->string('parcel_code')->unique(); // e.g., PAR-NEG-000001
        $table->string('title_no')->nullable();
        $table->string('tax_decl_no')->nullable();

        // Location
        $table->string('municipality')->nullable();
        $table->string('barangay')->nullable();
        $table->string('province')->default('Negros Oriental');

        // Size (master reference; application can override via pivot if needed)
        $table->decimal('area_hectares', 12, 4)->nullable();

        // Spatial data (Phase 1: GeoJSON text storage; PostGIS later if desired)
        $table->longText('geometry_geojson')->nullable();

        // Notes/status
        $table->string('status', 30)->default('active'); // active | inactive
        $table->text('remarks')->nullable();

        $table->timestamps();

        $table->index(['municipality', 'barangay']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcels');
    }
};
