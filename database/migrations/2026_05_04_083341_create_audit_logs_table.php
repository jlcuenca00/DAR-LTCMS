<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create audit logs table.
     *
     * This table stores trace records for important staff-side actions.
     * It supports auditability without changing the legal meaning of clearance decisions.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('land_transfer_application_id')
                ->nullable()
                ->constrained('land_transfer_applications')
                ->nullOnDelete();

            $table->nullableMorphs('auditable');

            $table->string('action', 100);
            $table->json('metadata')->nullable();

            $table->string('ip_address', 100)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Drop audit logs table.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};