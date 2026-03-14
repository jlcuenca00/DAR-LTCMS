<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landholding_mutations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('land_transfer_application_id')
                ->constrained('land_transfer_applications')
                ->cascadeOnDelete();

            $table->foreignId('parcel_id')
                ->constrained('parcels')
                ->cascadeOnDelete();

            $table->foreignId('transferor_landowner_id')
                ->constrained('landowners')
                ->cascadeOnDelete();

            $table->foreignId('transferee_landowner_id')
                ->constrained('landowners')
                ->cascadeOnDelete();

            // how much area moved
            $table->decimal('transferred_area_hectares', 12, 4);

            // audit: before/after values (nullable if record didn’t exist)
            $table->decimal('transferor_before_area', 12, 4)->nullable();
            $table->decimal('transferor_after_area', 12, 4)->nullable();
            $table->decimal('transferee_before_area', 12, 4)->nullable();
            $table->decimal('transferee_after_area', 12, 4)->nullable();

            // who executed the mutation (staff)
            $table->foreignId('mutated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('mutated_at')->nullable();

            $table->timestamps();

            // Prevent duplicate per application+parcel mutation entry
            $table->unique(['land_transfer_application_id', 'parcel_id']);
            $table->index(['transferor_landowner_id', 'transferee_landowner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landholding_mutations');
    }
};