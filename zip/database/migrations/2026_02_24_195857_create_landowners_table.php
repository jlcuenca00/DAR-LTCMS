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
    Schema::create('landowners', function (Blueprint $table) {
        $table->id();

        // Basic identity fields (DAR registry-friendly)
        $table->string('first_name');
        $table->string('middle_name')->nullable();
        $table->string('last_name');
        $table->string('suffix')->nullable();

        // Optional contact/location
        $table->string('contact_number')->nullable();
        $table->string('address_line')->nullable();
        $table->string('barangay')->nullable();
        $table->string('municipality')->nullable();
        $table->string('province')->default('Negros Oriental');

        // Optional link to login account (for Landowner role)
        $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

        $table->timestamps();

        $table->index(['last_name', 'first_name']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landowners');
    }
};
