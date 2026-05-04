<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->string('document_reference_number')->nullable();
            $table->jsonb('document_metadata')->nullable();

            $table->foreignId('metadata_encoded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('metadata_encoded_at')->nullable();

            $table->index(
                'document_reference_number',
                'application_documents_reference_number_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropIndex('application_documents_reference_number_index');

            $table->dropForeign(['metadata_encoded_by']);

            $table->dropColumn([
                'document_reference_number',
                'document_metadata',
                'metadata_encoded_by',
                'metadata_encoded_at',
            ]);
        });
    }
};