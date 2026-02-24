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
    Schema::create('application_documents', function (Blueprint $table) {
        $table->id();

        // Link to application
        $table->foreignId('land_transfer_application_id')
            ->constrained('land_transfer_applications')
            ->cascadeOnDelete();

        // Link to the requirement checklist
        $table->foreignId('required_document_id')
            ->constrained('required_documents')
            ->cascadeOnDelete();

        // File storage
        $table->string('original_filename')->nullable();
        $table->string('file_path'); // storage path

        // Optional: for your AO annex/reference tracking
        $table->string('annex_reference')->nullable(); // e.g., Annex A-1 if needed

        // Staff accountability
        $table->foreignId('uploaded_by')
            ->constrained('users')
            ->cascadeOnDelete();

        $table->text('remarks')->nullable();

        $table->timestamps();

        // Prevent duplicate upload for same requirement (optional but recommended)
        $table->unique(['land_transfer_application_id', 'required_document_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
