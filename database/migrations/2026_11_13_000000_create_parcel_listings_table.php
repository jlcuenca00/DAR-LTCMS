<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcel_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landholding_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name', 100);
            $table->string('mobile', 13)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->string('status', 20)->index();
            $table->unsignedBigInteger('parcel_revision');
            $table->unsignedBigInteger('holding_revision');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_listings');
    }
};
