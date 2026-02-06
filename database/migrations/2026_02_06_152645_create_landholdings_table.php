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

    // Owner (can be null for now)
    $table->foreignId('landowner_user_id')
          ->nullable()
          ->constrained('users')
          ->nullOnDelete();

    // Parcel identifier
    $table->string('parcel_code')->unique();

    // Land size in hectares
    $table->decimal('area_hectares', 10, 2);

    // Location details
    $table->string('barangay');
    $table->string('municipality');

    $table->timestamps();
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
