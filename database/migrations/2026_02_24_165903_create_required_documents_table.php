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
    Schema::create('required_documents', function (Blueprint $table) {
        $table->id();

        // Name of the document requirement
        $table->string('name');

        // Who it applies to
        $table->enum('applies_to', ['transferor', 'transferee']);

        // Mandatory or conditional
        $table->boolean('is_mandatory')->default(true);

        // Legal reference (DAR AO 4)
        $table->string('legal_basis')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('required_documents');
    }
};
