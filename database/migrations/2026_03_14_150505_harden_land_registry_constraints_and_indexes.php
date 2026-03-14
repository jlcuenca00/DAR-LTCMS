<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * INDEXES (for frequent filters + sums)
         */
        Schema::table('landholdings', function (Blueprint $table) {
            // common: where landowner_id + status then sum(area_hectares)
            $table->index(['landowner_id', 'status'], 'landholdings_landowner_status_idx');
            $table->index(['parcel_id'], 'landholdings_parcel_idx');
        });

        Schema::table('application_parcels', function (Blueprint $table) {
            $table->index(['land_transfer_application_id'], 'app_parcels_application_idx');
            $table->index(['parcel_id'], 'app_parcels_parcel_idx');
        });

        Schema::table('application_documents', function (Blueprint $table) {
            $table->index(['land_transfer_application_id'], 'app_docs_application_idx');
        });

        /**
         * CHECK CONSTRAINTS (PostgreSQL numeric integrity)
         * Note: Laravel doesn't have native check constraints; we use raw SQL.
         */
        DB::statement("
            ALTER TABLE landholdings
            ADD CONSTRAINT landholdings_area_nonnegative_chk
            CHECK (area_hectares >= 0)
        ");

        DB::statement("
            ALTER TABLE application_parcels
            ADD CONSTRAINT application_parcels_area_positive_chk
            CHECK (area_hectares > 0)
        ");

        DB::statement("
            ALTER TABLE landholding_mutations
            ADD CONSTRAINT landholding_mutations_transferred_positive_chk
            CHECK (transferred_area_hectares > 0)
        ");
    }

    public function down(): void
    {
        // Drop check constraints
        DB::statement("ALTER TABLE landholding_mutations DROP CONSTRAINT IF EXISTS landholding_mutations_transferred_positive_chk");
        DB::statement("ALTER TABLE application_parcels DROP CONSTRAINT IF EXISTS application_parcels_area_positive_chk");
        DB::statement("ALTER TABLE landholdings DROP CONSTRAINT IF EXISTS landholdings_area_nonnegative_chk");

        // Drop indexes
        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropIndex('app_docs_application_idx');
        });

        Schema::table('application_parcels', function (Blueprint $table) {
            $table->dropIndex('app_parcels_application_idx');
            $table->dropIndex('app_parcels_parcel_idx');
        });

        Schema::table('landholdings', function (Blueprint $table) {
            $table->dropIndex('landholdings_landowner_status_idx');
            $table->dropIndex('landholdings_parcel_idx');
        });
    }
};