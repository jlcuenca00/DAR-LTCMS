<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            // Link transferor to registry (critical for mutation integrity)
            $table->foreignId('transferor_landowner_id')
                ->nullable()
                ->after('transferee_landowner_id')
                ->constrained('landowners')
                ->nullOnDelete();

            // Idempotency + audit: mutation ran exactly once
            $table->timestamp('registry_mutated_at')->nullable()->after('validation_snapshot');
            $table->foreignId('registry_mutated_by')
                ->nullable()
                ->after('registry_mutated_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->dropForeign(['transferor_landowner_id']);
            $table->dropColumn('transferor_landowner_id');

            $table->dropForeign(['registry_mutated_by']);
            $table->dropColumn(['registry_mutated_at', 'registry_mutated_by']);
        });
    }
};