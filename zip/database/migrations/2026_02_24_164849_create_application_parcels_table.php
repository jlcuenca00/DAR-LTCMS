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
    Schema::create('application_parcels', function (Blueprint $table) {
        $table->id();

        $table->foreignId('land_transfer_application_id')
            ->constrained('land_transfer_applications')
            ->cascadeOnDelete();

        $table->unsignedBigInteger('parcel_id')->nullable();

        $table->decimal('area_hectares', 12, 4)->nullable();

        $table->string('parcel_code')->nullable();
        $table->string('title_no')->nullable();
        $table->string('tax_decl_no')->nullable();

        $table->timestamps();

        $table->index('parcel_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_parcels');
    }
};
