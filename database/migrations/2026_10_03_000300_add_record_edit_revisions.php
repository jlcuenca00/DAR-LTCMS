<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['landowners', 'landholdings', 'parcels'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unsignedBigInteger('record_revision')->default(1));
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION dar_ltcms_bump_record_revision() RETURNS trigger AS $$
            BEGIN
                IF (to_jsonb(NEW) - ARRAY['record_revision', 'updated_at'])
                    IS DISTINCT FROM (to_jsonb(OLD) - ARRAY['record_revision', 'updated_at']) THEN
                    NEW.record_revision := OLD.record_revision + 1;
                ELSE
                    NEW.record_revision := OLD.record_revision;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        foreach (['landowners', 'landholdings', 'parcels'] as $table) {
            DB::unprepared("CREATE TRIGGER record_edit_revision BEFORE UPDATE ON {$table}
                FOR EACH ROW EXECUTE FUNCTION dar_ltcms_bump_record_revision();");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            foreach (['landowners', 'landholdings', 'parcels'] as $table) {
                DB::unprepared("DROP TRIGGER IF EXISTS record_edit_revision ON {$table};");
            }
            DB::unprepared('DROP FUNCTION IF EXISTS dar_ltcms_bump_record_revision();');
        }
        foreach (['landowners', 'landholdings', 'parcels'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('record_revision'));
        }
    }
};
