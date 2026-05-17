<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('application_documents', 'source_record_id')) {
                $table->foreignId('source_record_id')
                    ->nullable()
                    ->after('uploaded_by')
                    ->constrained('legacy_records')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('application_documents', 'source_record_package_id')) {
                $table->foreignId('source_record_package_id')
                    ->nullable()
                    ->after('source_record_id')
                    ->constrained('source_record_packages')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            if (Schema::hasColumn('application_documents', 'source_record_package_id')) {
                $table->dropConstrainedForeignId('source_record_package_id');
            }

            if (Schema::hasColumn('application_documents', 'source_record_id')) {
                $table->dropConstrainedForeignId('source_record_id');
            }
        });
    }
};
