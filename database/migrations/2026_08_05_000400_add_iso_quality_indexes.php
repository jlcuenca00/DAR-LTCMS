<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS ltc_applications_status_updated_idx ON land_transfer_applications (status, updated_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS ltc_applications_location_idx ON land_transfer_applications (municipality, barangay)');
        DB::statement('CREATE INDEX IF NOT EXISTS audit_logs_actor_created_idx ON audit_logs (actor_user_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS audit_logs_application_created_idx ON audit_logs (land_transfer_application_id, created_at DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ltc_applications_status_updated_idx');
        DB::statement('DROP INDEX IF EXISTS ltc_applications_location_idx');
        DB::statement('DROP INDEX IF EXISTS audit_logs_actor_created_idx');
        DB::statement('DROP INDEX IF EXISTS audit_logs_application_created_idx');
    }
};
