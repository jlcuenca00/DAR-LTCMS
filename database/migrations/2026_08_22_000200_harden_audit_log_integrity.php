<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->uuid('event_uuid')->nullable()->unique();
            $table->uuid('request_id')->nullable()->index();
            $table->string('actor_name_snapshot')->nullable();
            $table->string('actor_username_snapshot', 100)->nullable();
            $table->string('actor_role_snapshot', 50)->nullable();
            $table->string('application_code_snapshot', 100)->nullable()->index();
            $table->string('route_name', 150)->nullable()->index();
            $table->string('http_method', 10)->nullable();
        });

        DB::table('audit_logs')
            ->select('id')
            ->orderBy('id')
            ->chunkById(250, function ($logs): void {
                foreach ($logs as $log) {
                    DB::table('audit_logs')
                        ->where('id', $log->id)
                        ->update(['event_uuid' => (string) Str::uuid()]);
                }
            });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                UPDATE audit_logs AS audit
                SET actor_name_snapshot = users.name,
                    actor_username_snapshot = users.username,
                    actor_role_snapshot = users.role
                FROM users
                WHERE audit.actor_user_id = users.id
                  AND audit.actor_name_snapshot IS NULL
            SQL);

            DB::statement(<<<'SQL'
                UPDATE audit_logs AS audit
                SET application_code_snapshot = applications.application_code
                FROM land_transfer_applications AS applications
                WHERE audit.land_transfer_application_id = applications.id
                  AND audit.application_code_snapshot IS NULL
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION dar_ltcms_prevent_audit_log_mutation()
                RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'DAR-LTCMS audit logs are append-only and cannot be updated or deleted.';
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER audit_logs_append_only
                BEFORE UPDATE OR DELETE ON audit_logs
                FOR EACH ROW
                EXECUTE FUNCTION dar_ltcms_prevent_audit_log_mutation();
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP TRIGGER IF EXISTS audit_logs_append_only ON audit_logs;
                DROP FUNCTION IF EXISTS dar_ltcms_prevent_audit_log_mutation();
            SQL);
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropUnique(['event_uuid']);
            $table->dropIndex(['request_id']);
            $table->dropIndex(['application_code_snapshot']);
            $table->dropIndex(['route_name']);
            $table->dropColumn([
                'event_uuid',
                'request_id',
                'actor_name_snapshot',
                'actor_username_snapshot',
                'actor_role_snapshot',
                'application_code_snapshot',
                'route_name',
                'http_method',
            ]);
        });
    }
};
