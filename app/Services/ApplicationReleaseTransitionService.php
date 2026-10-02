<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationReleaseTransitionService
{
    public function markReady(
        LandTransferApplication $application,
        int $actorId,
        int $expectedWorkflowRevision
    ): LandTransferApplication
    {
        return DB::transaction(function () use ($application, $actorId) {
            $application = LandTransferApplication::query()
                ->lockForUpdate()
                ->findOrFail($application->id);

            app(ApplicationWorkflowRevisionService::class)->assertExpected(
                $application,
                $expectedWorkflowRevision
            );

            $this->assertReleaseIntegrity($application);

            if ($application->isReleasedToClient()) {
                throw ValidationException::withMessages([
                    'release' => 'This decision output has already been released to the client.',
                ]);
            }

            if ($application->release_status === LandTransferApplication::RELEASE_READY) {
                throw ValidationException::withMessages([
                    'release' => 'This decision output is already marked Ready for Release.',
                ]);
            }

            $oldReleaseStatus = $application->release_status ?: LandTransferApplication::RELEASE_NOT_READY;

            $application->runAuthorizedReleaseMutation(function () use ($application) {
                $application->release_status = LandTransferApplication::RELEASE_READY;
                $application->ready_for_release_at = $application->ready_for_release_at ?: now();
                $application->save();
            });

            AuditLogger::record(
                'application_ready_for_release',
                $application,
                $application,
                [
                    'old_release_status' => $oldReleaseStatus,
                    'new_release_status' => $application->release_status,
                    'release_status' => $application->release_status,
                    'ready_for_release_at' => optional($application->ready_for_release_at)->toDateTimeString(),
                    'scope_note' => 'Administrative delivery readiness only. The final decision remains immutable.',
                ],
                $actorId
            );

            app(NotificationService::class)->notifyStaffApplicationReadyForRelease($application);
            app(NotificationService::class)->notifyLinkedLandownersReadyForRelease($application);

            return $application;
        });
    }

    public function recordRelease(
        LandTransferApplication $application,
        int $actorId,
        string $recipientName,
        ?string $logbookReference,
        string $csmStatus,
        int $expectedWorkflowRevision
    ): LandTransferApplication {
        return DB::transaction(function () use ($application, $actorId, $recipientName, $logbookReference, $csmStatus, $expectedWorkflowRevision) {
            $application = LandTransferApplication::query()
                ->lockForUpdate()
                ->findOrFail($application->id);

            app(ApplicationWorkflowRevisionService::class)->assertExpected(
                $application,
                $expectedWorkflowRevision
            );

            $this->assertReleaseIntegrity($application);

            if (! $application->isReleaseReady()) {
                throw ValidationException::withMessages([
                    'release' => 'Mark the signed decision output Ready for Release before recording client release.',
                ]);
            }

            $oldReleaseStatus = $application->release_status;

            $application->runAuthorizedReleaseMutation(function () use ($application, $actorId, $recipientName, $logbookReference, $csmStatus) {
                $application->release_status = LandTransferApplication::RELEASED_TO_CLIENT;
                $application->released_at = now();
                $application->released_by = $actorId;
                $application->release_recipient_name = $recipientName;
                $application->release_logbook_reference = $logbookReference;
                $application->csm_status = $csmStatus;
                $application->date_of_clearance_release = now()->toDateString();
                $application->save();
            });

            AuditLogger::record(
                'application_released_to_client',
                $application,
                $application,
                [
                    'final_decision_status' => $application->status,
                    'old_release_status' => $oldReleaseStatus,
                    'new_release_status' => $application->release_status,
                    'release_status' => $application->release_status,
                    'released_at' => optional($application->released_at)->toDateTimeString(),
                    'release_recipient_name' => $application->release_recipient_name,
                    'release_logbook_reference' => $application->release_logbook_reference,
                    'csm_status' => $application->csm_status,
                    'ownership_transfer_performed' => false,
                    'registry_mutation_performed' => false,
                    'scope_note' => 'Administrative release of the signed clearance decision only. No ownership transfer or registry mutation was performed.',
                ],
                $actorId
            );

            app(NotificationService::class)->notifyStaffApplicationReleasedToClient($application);
            app(NotificationService::class)->notifyLinkedLandownersReleasedToClient($application);

            return $application;
        });
    }

    private function assertReleaseIntegrity(LandTransferApplication $application): void
    {
        if (! in_array($application->status, LandTransferApplication::FINAL_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Only a current Approved decision may enter release tracking.',
            ]);
        }

        $businessState = app(ApplicationBusinessStateIntegrityService::class)->inspect($application);
        if (! $businessState['valid']) {
            throw ValidationException::withMessages([
                'release_integrity' => 'The application release state is internally inconsistent.',
            ]);
        }

        if (! $application->clearance()->exists()) {
            throw ValidationException::withMessages([
                'clearance' => 'The final LTC Form No. 5 output must exist before release tracking continues.',
            ]);
        }

        $clearanceIntegrity = app(ApplicationClearanceIntegrityService::class)->inspect($application);
        if (! $clearanceIntegrity['valid']) {
            throw ValidationException::withMessages([
                'clearance' => 'The final decision output failed its integrity check.',
            ]);
        }
    }
}
