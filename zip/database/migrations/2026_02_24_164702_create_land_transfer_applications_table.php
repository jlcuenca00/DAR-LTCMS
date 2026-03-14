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
    Schema::create('land_transfer_applications', function (Blueprint $table) {
        $table->id();

        // System-generated application id (human-friendly)
        $table->string('application_code')->unique(); // e.g., LTC-2026-000001

        // Parties (we will normalize later if needed)
        $table->string('transferor_name');
        $table->string('transferee_name');

        // Location summary (for filtering/list views)
        $table->string('municipality')->nullable();
        $table->string('barangay')->nullable();

        // Dates (assist validation later; still savable even if inconsistent)
        $table->date('date_filed')->nullable();
        $table->date('date_of_transfer')->nullable();

        // Status lifecycle (still save Not Approved)
        $table->string('status', 30)->default('draft'); 
        // draft | pending_review | approved | not_approved

        $table->text('remarks')->nullable(); // required when not_approved (enforced in app layer)

        // Audit fields
        $table->foreignId('encoded_by')->constrained('users')->cascadeOnDelete();
        $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('reviewed_at')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('land_transfer_applications');
    }
};
