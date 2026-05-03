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
        Schema::create('application_clearances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('land_transfer_application_id')
                ->constrained('land_transfer_applications')
                ->cascadeOnDelete();

            $table->string('clearance_number')->unique();

            $table->string('decision_status', 50);

            $table->string('application_code');
            $table->string('transferor_name');
            $table->string('transferee_name');
            $table->string('municipality')->nullable();
            $table->string('barangay')->nullable();

            $table->decimal('total_area_hectares', 12, 4)->default('0.0000');

            $table->json('parcel_snapshot');

            $table->string('review_officer_name');
            $table->timestamp('reviewed_at')->nullable();

            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('generated_at');

            $table->timestamps();

            $table->unique('land_transfer_application_id', 'uq_application_clearances_application');
            $table->index(['decision_status', 'generated_at'], 'idx_application_clearances_status_generated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_clearances');
    }
};