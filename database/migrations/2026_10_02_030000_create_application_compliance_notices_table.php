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
            $table->foreignId('requested_by')->nullable()->constrained('users')->restrictOnDelete();
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

        DB::table('land_transfer_applications')
            ->where('status', 'returned_for_compliance')
            ->orderBy('id')
            ->get()
            ->each(function ($application): void {
                $recorderName = $application->returned_for_compliance_by
                    ? DB::table('users')->where('id', $application->returned_for_compliance_by)->value('name')
                    : null;

                DB::table('application_compliance_notices')->insert([
                    'land_transfer_application_id' => $application->id,
                    'category' => 'other',
                    'other_category' => 'Historical compliance request',
                    'details' => $application->latest_compliance_reason
                        ?: 'Historical compliance request recorded before structured compliance notices were introduced.',
                    'requested_items' => null,
                    'resume_status' => 'pending_legal_review',
                    'requested_by' => $application->returned_for_compliance_by,
                    'requested_by_name_snapshot' => $recorderName ?: 'Historical recorder unavailable',
                    'requested_at' => $application->returned_for_compliance_at
                        ?: $application->updated_at
                        ?: now(),
                    'resolved_by' => null,
                    'resolved_by_name_snapshot' => null,
                    'resolved_at' => null,
                    'resolution_note' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
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
