<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table): void {
            $table->string('decision_authority', 100)->nullable()->after('decision_notes');
            $table->string('decision_officer_name')->nullable()->after('decision_authority');
            $table->date('decision_date')->nullable()->after('decision_officer_name');
            $table->foreignId('decision_recorded_by')->nullable()->after('decision_date')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('decision_recorded_at')->nullable()->after('decision_recorded_by');
        });

        Schema::table('application_clearances', function (Blueprint $table): void {
            $table->string('decision_authority', 100)->nullable()->after('decision_status');
            $table->string('decision_officer_name')->nullable()->after('decision_authority');
            $table->date('decision_date')->nullable()->after('decision_officer_name');
            $table->foreignId('decision_recorded_by')->nullable()->after('decision_date')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('decision_recorded_at')->nullable()->after('decision_recorded_by');
        });

        DB::table('land_transfer_applications')
            ->whereIn('status', ['approved', 'not_approved', 'released', 'denied'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $reviewedAt = $row->reviewed_at ?? null;

                    DB::table('land_transfer_applications')
                        ->where('id', $row->id)
                        ->update([
                            'decision_authority' => 'PARPO II',
                            'decision_date' => $reviewedAt ? date('Y-m-d', strtotime((string) $reviewedAt)) : null,
                            'decision_recorded_by' => $row->reviewed_by ?? null,
                            'decision_recorded_at' => $reviewedAt,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('application_clearances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('decision_recorded_by');
            $table->dropColumn([
                'decision_authority',
                'decision_officer_name',
                'decision_date',
                'decision_recorded_at',
            ]);
        });

        Schema::table('land_transfer_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('decision_recorded_by');
            $table->dropColumn([
                'decision_authority',
                'decision_officer_name',
                'decision_date',
                'decision_recorded_at',
            ]);
        });
    }
};
