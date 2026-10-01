<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_compliance_notices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('land_transfer_application_id')
                ->constrained('land_transfer_applications')
                ->restrictOnDelete();
            $table->string('category', 80);
            $table->string('other_category', 150)->nullable();
            $table->text('details');
            $table->text('requested_items')->nullable();
            $table->string('resume_status', 80);
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('requested_by_name_snapshot', 255);
            $table->timestamp('requested_at');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('resolved_by_name_snapshot', 255)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['land_transfer_application_id', 'requested_at'], 'compliance_notice_app_requested_idx');
            $table->index(['land_transfer_application_id', 'resolved_at'], 'compliance_notice_app_resolved_idx');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX application_compliance_one_active_idx
                 ON application_compliance_notices (land_transfer_application_id)
                 WHERE resolved_at IS NULL'
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS application_compliance_one_active_idx');
        }

        Schema::dropIfExists('application_compliance_notices');
    }
};
