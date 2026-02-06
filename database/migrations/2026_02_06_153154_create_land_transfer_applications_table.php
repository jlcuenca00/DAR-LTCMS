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

    // System-generated application code
    $table->string('application_code')->unique();

    // Applicant (landowner)
    $table->foreignId('applicant_user_id')
          ->constrained('users')
          ->cascadeOnDelete();

    // Application status
    $table->string('status')->default('Pending');

    // DAR staff remarks (optional)
    $table->text('remarks')->nullable();

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
