<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['source_record_packages', 'legacy_records'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unsignedBigInteger('record_revision')->default(1));
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }


        foreach (['source_record_packages', 'legacy_records'] as $table) {
            DB::unprepared("CREATE TRIGGER record_edit_revision BEFORE UPDATE ON {$table}
                FOR EACH ROW EXECUTE FUNCTION dar_ltcms_bump_record_revision();");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            foreach (['source_record_packages', 'legacy_records'] as $table) {
                DB::unprepared("DROP TRIGGER IF EXISTS record_edit_revision ON {$table};");
            }
        }
        foreach (['source_record_packages', 'legacy_records'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('record_revision'));
        }
    }
};
