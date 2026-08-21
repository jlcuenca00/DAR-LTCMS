<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Monitoring/report date ranges and application recency.
        DB::statement('CREATE INDEX IF NOT EXISTS ltc_apps_application_date_idx ON land_transfer_applications (date_of_application)');
        DB::statement('CREATE INDEX IF NOT EXISTS ltc_apps_created_at_idx ON land_transfer_applications (created_at DESC)');

        // Landowner portal linkage: legacy FK columns plus current JSONB party rows.
        DB::statement('CREATE INDEX IF NOT EXISTS ltc_apps_transferor_owner_idx ON land_transfer_applications (transferor_landowner_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS ltc_apps_transferee_owner_idx ON land_transfer_applications (transferee_landowner_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS ltc_apps_transferors_gin_idx ON land_transfer_applications USING GIN (transferors)');
        DB::statement('CREATE INDEX IF NOT EXISTS ltc_apps_transferees_gin_idx ON land_transfer_applications USING GIN (transferees)');

        // Landowner dashboards, parcel owner labels and parcel-first integrity/reference lookups.
        DB::statement('CREATE INDEX IF NOT EXISTS landholdings_owner_created_idx ON landholdings (landowner_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS landholdings_parcel_status_idx ON landholdings (parcel_id, status)');

        // Full parcel-map pages and the Geodetic awaiting-geometry queue use these
        // partial predicates repeatedly. They retain the complete result set while
        // avoiding scans of unrelated inactive/unmapped rows.
        DB::statement("CREATE INDEX IF NOT EXISTS parcels_active_mapped_location_idx ON parcels (municipality, barangay, parcel_code) WHERE status = 'active' AND geometry_geojson IS NOT NULL");
        DB::statement("CREATE INDEX IF NOT EXISTS parcels_unmapped_created_idx ON parcels (created_at) WHERE geometry_geojson IS NULL AND status <> 'inactive'");

        // Dashboard 'generated today' range and clearance recency.
        DB::statement('CREATE INDEX IF NOT EXISTS clearances_generated_at_idx ON application_clearances (generated_at DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS clearances_generated_at_idx');
        DB::statement('DROP INDEX IF EXISTS parcels_unmapped_created_idx');
        DB::statement('DROP INDEX IF EXISTS parcels_active_mapped_location_idx');
        DB::statement('DROP INDEX IF EXISTS landholdings_parcel_status_idx');
        DB::statement('DROP INDEX IF EXISTS landholdings_owner_created_idx');
        DB::statement('DROP INDEX IF EXISTS ltc_apps_transferees_gin_idx');
        DB::statement('DROP INDEX IF EXISTS ltc_apps_transferors_gin_idx');
        DB::statement('DROP INDEX IF EXISTS ltc_apps_transferee_owner_idx');
        DB::statement('DROP INDEX IF EXISTS ltc_apps_transferor_owner_idx');
        DB::statement('DROP INDEX IF EXISTS ltc_apps_created_at_idx');
        DB::statement('DROP INDEX IF EXISTS ltc_apps_application_date_idx');
    }
};
