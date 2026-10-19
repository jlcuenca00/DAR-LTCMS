<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserAccountDeletionService
{
    /** Preserve every official attribution, including nullable foreign keys. */
    private const RECORD_REFERENCES = [
        'land_transfer_applications' => ['encoded_by', 'reviewed_by', 'csw_prepared_by', 'released_by', 'decision_recorded_by', 'returned_for_compliance_by'],
        'application_documents' => ['uploaded_by', 'metadata_encoded_by'],
        'application_clearances' => ['generated_by', 'decision_recorded_by'],
        'application_compliance_notices' => ['requested_by', 'resolved_by'],
        'parcel_geometry_revisions' => ['actor_user_id'],
        'parcels' => ['flagged_by', 'flag_resolved_by'],
        'legacy_records' => ['encoded_by_user_id'],
        'legacy_record_import_batches' => ['uploaded_by_user_id', 'committed_by_user_id'],
        'source_record_packages' => ['encoded_by_user_id', 'source_file_uploaded_by_user_id'],
        'source_record_package_import_batches' => ['uploaded_by_user_id', 'committed_by_user_id'],
        'users' => ['registration_reviewed_by_user_id'],
    ];

    public function blockedReason(User $user, User $actor): ?string
    {
        if ($user->id === $actor->id) {
            return 'You cannot delete your own account.';
        }
        if ($user->account_deleted_at !== null) {
            return 'This account has already been deleted.';
        }
        if (! $user->isLandowner() && ($user->landowner()->exists() || $this->hasOfficialReferences($user))) {
            return 'This account is linked to official records or recorded staff actions. Suspend access instead to preserve those records.';
        }

        return null;
    }

    public function hasOfficialReferences(User $user): bool
    {
        foreach (self::RECORD_REFERENCES as $table => $columns) {
            if (DB::table($table)->where(function ($query) use ($columns, $user) {
                foreach ($columns as $column) {
                    $query->orWhere($column, $user->id);
                }
            })->exists()) {
                return true;
            }
        }

        return false;
    }

    public function revokeSessions(User $user): void
    {
        // Remove database sessions even when a different driver is used by this request.
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        $user->forceFill(['remember_token' => null, 'session_version' => $user->session_version + 1])->save();
    }
}
