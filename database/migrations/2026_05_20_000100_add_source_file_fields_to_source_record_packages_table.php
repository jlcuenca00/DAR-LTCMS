<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('source_record_packages', function (Blueprint $table) {
            $table->string('source_file_path')->nullable()->after('boundary_description');
            $table->string('source_file_original_filename')->nullable()->after('source_file_path');
            $table->string('source_file_mime_type')->nullable()->after('source_file_original_filename');
            $table->foreignId('source_file_uploaded_by_user_id')
                ->nullable()
                ->after('source_file_mime_type')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('source_file_uploaded_at')->nullable()->after('source_file_uploaded_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('source_record_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_file_uploaded_by_user_id');
            $table->dropColumn([
                'source_file_path',
                'source_file_original_filename',
                'source_file_mime_type',
                'source_file_uploaded_at',
            ]);
        });
    }
};
