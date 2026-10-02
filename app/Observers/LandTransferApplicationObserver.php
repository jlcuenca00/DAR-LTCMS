<?php

namespace App\Observers;

use App\Models\LandTransferApplication;
use App\Services\ApplicationPartyIntegrityService;
use App\Services\ApplicationPartyShareIntegrityService;
use App\Services\DarLocationService;
use Illuminate\Validation\ValidationException;

class LandTransferApplicationObserver
{
    public function saving(LandTransferApplication $application): void
    {
        if (
            $application->isDirty('status')
            && in_array($application->status, [
                LandTransferApplication::STATUS_NOT_APPROVED,
                LandTransferApplication::STATUS_DENIED,
            ], true)
        ) {
            throw ValidationException::withMessages([
                'status' => 'Not Approved / Denied statuses are historical-only and cannot be created by the current compliance-first workflow.',
            ]);
        }

        if ($application->exists) {
            $originalStatus = (string) $application->getRawOriginal('status');
            $originalReleaseStatus = (string) (
                $application->getRawOriginal('release_status')
                ?: LandTransferApplication::RELEASE_NOT_READY
            );
            $dirtyFields = array_keys($application->getDirty());

            if (
                in_array($originalStatus, LandTransferApplication::LEGACY_FINAL_STATUSES, true)
                && ! empty($dirtyFields)
            ) {
                throw ValidationException::withMessages([
                    'application' => 'Historical finalized application records are immutable and cannot be edited.',
                ]);
            }

            if (
                in_array($originalStatus, LandTransferApplication::FINAL_STATUSES, true)
                && ! empty($dirtyFields)
            ) {
                $releaseFields = [
                    'release_status',
                    'ready_for_release_at',
                    'released_at',
                    'released_by',
                    'release_recipient_name',
                    'release_logbook_reference',
                    'csm_status',
                    'date_of_clearance_release',
                ];

                $disallowedFields = array_values(array_diff($dirtyFields, $releaseFields));

                if (! empty($disallowedFields)) {
                    throw ValidationException::withMessages([
                        'application' => 'Approved application records are frozen. Only authorized release-tracking fields may change after the final decision.',
                    ]);
                }

                if ($originalReleaseStatus === LandTransferApplication::RELEASED_TO_CLIENT) {
                    throw ValidationException::withMessages([
                        'release' => 'Released application records are immutable. Release metadata can no longer be changed.',
                    ]);
                }

                $nextReleaseStatus = (string) (
                    $application->release_status
                    ?: LandTransferApplication::RELEASE_NOT_READY
                );

                $allowedReleaseTransitions = [
                    LandTransferApplication::RELEASE_NOT_READY => [
                        LandTransferApplication::RELEASE_NOT_READY,
                        LandTransferApplication::RELEASE_READY,
                    ],
                    LandTransferApplication::RELEASE_READY => [
                        LandTransferApplication::RELEASE_READY,
                        LandTransferApplication::RELEASED_TO_CLIENT,
                    ],
                ];

                if (! in_array(
                    $nextReleaseStatus,
                    $allowedReleaseTransitions[$originalReleaseStatus] ?? [],
                    true
                )) {
                    throw ValidationException::withMessages([
                        'release' => 'Release tracking must progress from Not Ready to Ready for Release to Released to Client without skipping or reversing stages.',
                    ]);
                }
            }
        }

        if (! $application->exists || $application->isDirty(['municipality', 'barangay'])) {
            $normalized = app(DarLocationService::class)->normalize(
                $application->municipality,
                $application->barangay,
                null
            );

            $application->municipality = $normalized['municipality'];
            $application->barangay = $normalized['barangay'];
        }

        if (! $application->exists || $application->isDirty([
            'transferors',
            'transferees',
            'transferor_name',
            'transferee_name',
            'transferor_landowner_id',
            'transferee_landowner_id',
        ])) {
            $partyIntegrity = app(ApplicationPartyIntegrityService::class);
            $partyIntegrity->synchronizeCompatibilityFields($application);
            $partyIntegrity->assertValid($application);
        }

        if ($application->exists && $application->isDirty('transferees')) {
            app(ApplicationPartyShareIntegrityService::class)->assertValid($application);
        }
    }
}
