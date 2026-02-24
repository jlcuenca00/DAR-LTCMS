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
    Schema::create('landholdings', function (Blueprint $table) {
        $table->id();

        // Who owns it
        $table->foreignId('landowner_id')
            ->constrained('landowners')
            ->cascadeOnDelete();

        // What parcel
        $table->foreignId('parcel_id')
            ->constrained('parcels')
            ->cascadeOnDelete();

        // Official size used for validation
        $table->decimal('area_hectares', 12, 4);

        // Ownership status for validation
        $table->string('status', 30)->default('active'); 
        // active | inactive | transferred

        // Ownership period (assistive validation + audit trail)
        $table->date('date_acquired')->nullable();
        $table->date('date_transferred')->nullable();

        // Link to the application that created/changed this record (optional)
        $table->foreignId('source_application_id')
            ->nullable()
            ->constrained('land_transfer_applications')
            ->nullOnDelete();

        $table->text('remarks')->nullable();

        $table->timestamps();

        // Prevent duplicate active holding of same parcel for same owner
        $table->unique(['landowner_id', 'parcel_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landholdings');
    }
};
