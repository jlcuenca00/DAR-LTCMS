<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('legacy_records', 'source_record_package_id')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->foreignId('source_record_package_id')
                    ->nullable()
                    ->after('legacy_record_import_batch_id')
                    ->constrained('source_record_packages')
                    ->nullOnDelete();

                $table->index('source_record_package_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('legacy_records', 'source_record_package_id')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->dropConstrainedForeignId('source_record_package_id');
            });
        }
    }
};