<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize legacy workflow/status values to the revised DAR office flow.
     *
     * This does not transfer ownership, does not change parcel ownership linkage,
     * and does not mutate Registry of Deeds records. It only updates stored
     * administrative status/notification vocabulary.
     */
    public function up(): void
    {
        DB::table('land_transfer_applications')
            ->whereIn('status', ['draft', 'pending_review'])
            ->update(['status' => 'pending_legal_review']);

        DB::table('land_transfer_applications')
            ->where('status', 'approved')
            ->update(['status' => 'released']);

        DB::table('land_transfer_applications')
            ->where('status', 'not_approved')
            ->update(['status' => 'denied']);

        if ($this->tableExists('system_notifications')) {
            DB::table('system_notifications')
                ->where('type', 'application_approved')
                ->update(['type' => 'application_released']);

            DB::table('system_notifications')
                ->where('type', 'application_not_approved')
                ->update(['type' => 'application_denied']);

            DB::table('system_notifications')
                ->where('title', 'Approved Clearance')
                ->update(['title' => 'Released Clearance']);

            DB::table('system_notifications')
                ->where('title', 'Application approved')
                ->update(['title' => 'Clearance released']);

            DB::table('system_notifications')
                ->where('title', 'Application not approved')
                ->update(['title' => 'Application denied']);
        }

        if ($this->tableExists('audit_logs')) {
            DB::table('audit_logs')
                ->where('action', 'application_submitted')
                ->update(['action' => 'application_status_advanced']);

            DB::table('audit_logs')
                ->where('action', 'application_approved')
                ->update(['action' => 'application_released']);

            DB::table('audit_logs')
                ->where('action', 'application_not_approved')
                ->update(['action' => 'application_denied']);
        }
    }

    /**
     * Roll back normalized status vocabulary to the previous labels.
     *
     * This is intended only for local development rollback.
     */
    public function down(): void
    {
        DB::table('land_transfer_applications')
            ->where('status', 'pending_legal_review')
            ->update(['status' => 'pending_review']);

        DB::table('land_transfer_applications')
            ->where('status', 'released')
            ->update(['status' => 'approved']);

        DB::table('land_transfer_applications')
            ->where('status', 'denied')
            ->update(['status' => 'not_approved']);

        if ($this->tableExists('system_notifications')) {
            DB::table('system_notifications')
                ->where('type', 'application_released')
                ->update(['type' => 'application_approved']);

            DB::table('system_notifications')
                ->where('type', 'application_denied')
                ->update(['type' => 'application_not_approved']);
        }

        if ($this->tableExists('audit_logs')) {
            DB::table('audit_logs')
                ->where('action', 'application_status_advanced')
                ->update(['action' => 'application_submitted']);

            DB::table('audit_logs')
                ->where('action', 'application_released')
                ->update(['action' => 'application_approved']);

            DB::table('audit_logs')
                ->where('action', 'application_denied')
                ->update(['action' => 'application_not_approved']);
        }
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
};
