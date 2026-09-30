<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // High-value contains-searches use one expression index per list instead
        // of maintaining a separate trigram index for every searchable column.
        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS ltc_apps_search_trgm_idx
            ON land_transfer_applications USING GIN (
                (lower(
                    coalesce(application_code, '') || ' ' ||
                    coalesce(transferor_name, '') || ' ' ||
                    coalesce(transferee_name, '')
                )) gin_trgm_ops
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS ltc_apps_application_code_trgm_idx
            ON land_transfer_applications USING GIN ((lower(application_code)) gin_trgm_ops)
            WHERE application_code IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS app_docs_reference_trgm_idx
            ON application_documents USING GIN ((lower(document_reference_number)) gin_trgm_ops)
            WHERE document_reference_number IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS landowners_search_trgm_idx
            ON landowners USING GIN (
                (lower(
                    coalesce(first_name, '') || ' ' ||
                    coalesce(middle_name, '') || ' ' ||
                    coalesce(last_name, '') || ' ' ||
                    coalesce(registered_owner_status, '') || ' ' ||
                    coalesce(spouse_name, '') || ' ' ||
                    coalesce(contact_number, '') || ' ' ||
                    coalesce(address_line, '')
                )) gin_trgm_ops
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS parcels_search_trgm_idx
            ON parcels USING GIN (
                (lower(
                    coalesce(parcel_code, '') || ' ' ||
                    coalesce(title_no, '') || ' ' ||
                    coalesce(tax_decl_no, '') || ' ' ||
                    coalesce(lot_number, '') || ' ' ||
                    coalesce(survey_plan_number, '') || ' ' ||
                    coalesce(rod_office, '') || ' ' ||
                    coalesce(remarks, '')
                )) gin_trgm_ops
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS users_search_trgm_idx
            ON users USING GIN (
                (lower(
                    coalesce(name, '') || ' ' ||
                    coalesce(username, '') || ' ' ||
                    coalesce(email, '')
                )) gin_trgm_ops
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS source_packages_search_trgm_idx
            ON source_record_packages USING GIN (
                (lower(
                    coalesce(package_code, '') || ' ' ||
                    coalesce(title_number, '') || ' ' ||
                    coalesce(control_number, '') || ' ' ||
                    coalesce(parcel_code, '') || ' ' ||
                    coalesce(lot_number, '') || ' ' ||
                    coalesce(survey_number, '') || ' ' ||
                    coalesce(landowner_name, '') || ' ' ||
                    coalesce(transferor_name, '') || ' ' ||
                    coalesce(transferee_name, '') || ' ' ||
                    coalesce(landholding_reference_number, '')
                )) gin_trgm_ops
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS legacy_records_search_trgm_idx
            ON legacy_records USING GIN (
                (lower(
                    coalesce(title_number, '') || ' ' ||
                    coalesce(control_number, '') || ' ' ||
                    coalesce(application_reference_number, '') || ' ' ||
                    coalesce(parcel_code, '') || ' ' ||
                    coalesce(lot_number, '') || ' ' ||
                    coalesce(survey_number, '') || ' ' ||
                    coalesce(landowner_name, '') || ' ' ||
                    coalesce(transferor_name, '') || ' ' ||
                    coalesce(transferee_name, '') || ' ' ||
                    coalesce(previous_dar_reference_number, '') || ' ' ||
                    coalesce(landholding_reference_number, '')
                )) gin_trgm_ops
            )
        SQL);

        // Exact case-insensitive duplicate/reference checks use B-tree support.
        // These are intentionally non-unique until the production duplicate
        // preflight required by DB-031 is completed.
        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS legacy_records_parcel_ref_lower_idx
            ON legacy_records (record_type, lower(parcel_code))
            WHERE parcel_code IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS legacy_records_landholding_ref_lower_idx
            ON legacy_records (record_type, lower(landholding_reference_number))
            WHERE landholding_reference_number IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS legacy_records_landholding_ref_lower_idx');
        DB::statement('DROP INDEX IF EXISTS legacy_records_parcel_ref_lower_idx');
        DB::statement('DROP INDEX IF EXISTS legacy_records_search_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS source_packages_search_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS users_search_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS parcels_search_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS landowners_search_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS app_docs_reference_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS ltc_apps_application_code_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS ltc_apps_search_trgm_idx');

        // pg_trgm may be shared by other database objects; do not drop it.
    }
};
