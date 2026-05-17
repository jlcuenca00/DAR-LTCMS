<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('source_record_packages', 'landowner_id')) {
            Schema::table('source_record_packages', function (Blueprint $table) {
                $table->foreignId('landowner_id')
                    ->nullable()
                    ->after('parcel_id')
                    ->constrained('landowners')
                    ->nullOnDelete();

                $table->index('landowner_id');
            });
        }

        if (! Schema::hasColumn('legacy_records', 'landowner_id')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->foreignId('landowner_id')
                    ->nullable()
                    ->after('parcel_id')
                    ->constrained('landowners')
                    ->nullOnDelete();

                $table->index('landowner_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('legacy_records', 'landowner_id')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->dropConstrainedForeignId('landowner_id');
            });
        }

        if (Schema::hasColumn('source_record_packages', 'landowner_id')) {
            Schema::table('source_record_packages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('landowner_id');
            });
        }
    }
};
