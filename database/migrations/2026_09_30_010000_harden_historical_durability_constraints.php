<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertNoUserOrphans('csw_prepared_by');
        $this->assertNoUserOrphans('released_by');

        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->dropForeign(['encoded_by']);
            $table->foreign('encoded_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('csw_prepared_by', 'land_transfer_applications_csw_prepared_by_foreign')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('released_by', 'land_transfer_applications_released_by_foreign')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropForeign(['land_transfer_application_id']);
            $table->dropForeign(['required_document_id']);
            $table->dropForeign(['uploaded_by']);

            $table->foreign('land_transfer_application_id')
                ->references('id')
                ->on('land_transfer_applications')
                ->restrictOnDelete();

            $table->foreign('required_document_id')
                ->references('id')
                ->on('required_documents')
                ->restrictOnDelete();

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('application_clearances', function (Blueprint $table) {
            $table->dropForeign(['land_transfer_application_id']);
            $table->foreign('land_transfer_application_id')
                ->references('id')
                ->on('land_transfer_applications')
                ->restrictOnDelete();
        });

        Schema::table('parcel_geometry_revisions', function (Blueprint $table) {
            $table->dropForeign(['parcel_id']);
            $table->dropForeign(['actor_user_id']);

            $table->foreign('parcel_id')
                ->references('id')
                ->on('parcels')
                ->restrictOnDelete();

            $table->foreign('actor_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['actor_user_id']);
            $table->dropForeign(['land_transfer_application_id']);

            $table->foreign('actor_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('land_transfer_application_id')
                ->references('id')
                ->on('land_transfer_applications')
                ->restrictOnDelete();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION dar_ltcms_prevent_application_clearance_mutation()
                RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'DAR-LTCMS final clearance snapshots are append-only and cannot be updated or deleted.';
                END;
                $$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS application_clearances_append_only ON application_clearances;
                CREATE TRIGGER application_clearances_append_only
                BEFORE UPDATE OR DELETE ON application_clearances
                FOR EACH ROW
                EXECUTE FUNCTION dar_ltcms_prevent_application_clearance_mutation();

                CREATE OR REPLACE FUNCTION dar_ltcms_prevent_geometry_revision_mutation()
                RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'DAR-LTCMS parcel geometry revisions are append-only and cannot be updated or deleted.';
                END;
                $$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS parcel_geometry_revisions_append_only ON parcel_geometry_revisions;
                CREATE TRIGGER parcel_geometry_revisions_append_only
                BEFORE UPDATE OR DELETE ON parcel_geometry_revisions
                FOR EACH ROW
                EXECUTE FUNCTION dar_ltcms_prevent_geometry_revision_mutation();
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP TRIGGER IF EXISTS application_clearances_append_only ON application_clearances;
                DROP FUNCTION IF EXISTS dar_ltcms_prevent_application_clearance_mutation();

                DROP TRIGGER IF EXISTS parcel_geometry_revisions_append_only ON parcel_geometry_revisions;
                DROP FUNCTION IF EXISTS dar_ltcms_prevent_geometry_revision_mutation();
            SQL);
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['actor_user_id']);
            $table->dropForeign(['land_transfer_application_id']);

            $table->foreign('actor_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('land_transfer_application_id')
                ->references('id')
                ->on('land_transfer_applications')
                ->nullOnDelete();
        });

        Schema::table('parcel_geometry_revisions', function (Blueprint $table) {
            $table->dropForeign(['parcel_id']);
            $table->dropForeign(['actor_user_id']);

            $table->foreign('parcel_id')
                ->references('id')
                ->on('parcels')
                ->cascadeOnDelete();

            $table->foreign('actor_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('application_clearances', function (Blueprint $table) {
            $table->dropForeign(['land_transfer_application_id']);
            $table->foreign('land_transfer_application_id')
                ->references('id')
                ->on('land_transfer_applications')
                ->cascadeOnDelete();
        });

        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropForeign(['land_transfer_application_id']);
            $table->dropForeign(['required_document_id']);
            $table->dropForeign(['uploaded_by']);

            $table->foreign('land_transfer_application_id')
                ->references('id')
                ->on('land_transfer_applications')
                ->cascadeOnDelete();

            $table->foreign('required_document_id')
                ->references('id')
                ->on('required_documents')
                ->cascadeOnDelete();

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->dropForeign('land_transfer_applications_csw_prepared_by_foreign');
            $table->dropForeign('land_transfer_applications_released_by_foreign');
            $table->dropForeign(['encoded_by']);

            $table->foreign('encoded_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    private function assertNoUserOrphans(string $column): void
    {
        $orphanCount = DB::table('land_transfer_applications as applications')
            ->whereNotNull("applications.{$column}")
            ->whereNotExists(function ($query) use ($column) {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', "applications.{$column}");
            })
            ->count();

        if ($orphanCount > 0) {
            throw new RuntimeException(
                "Cannot add {$column} user foreign key: {$orphanCount} orphaned application row(s) require manual integrity review first."
            );
        }
    }
};
