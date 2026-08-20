<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->index();
            $table->string('flag_reason')->nullable();
            $table->text('flag_notes')->nullable();
            $table->foreignId('flagged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('flagged_at')->nullable();
            $table->foreignId('flag_resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('flag_resolved_at')->nullable();
            $table->text('flag_resolution_notes')->nullable();
        });

        // Preserve the intent of legacy parcel statuses while separating
        // parcel lifecycle (active/inactive) from review conditions.
        DB::table('parcels')
            ->where('status', 'flagged')
            ->update([
                'status' => 'active',
                'is_flagged' => true,
                'flag_reason' => 'legacy_status_migration',
                'flag_notes' => 'Migrated from the former parcel status "flagged".',
                'flagged_at' => now(),
            ]);

        DB::table('parcels')
            ->whereIn('status', ['linked_application', 'pending_legal_review'])
            ->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropForeign(['flagged_by']);
            $table->dropForeign(['flag_resolved_by']);
            $table->dropColumn([
                'is_flagged',
                'flag_reason',
                'flag_notes',
                'flagged_by',
                'flagged_at',
                'flag_resolved_by',
                'flag_resolved_at',
                'flag_resolution_notes',
            ]);
        });
    }
};
