<?php

namespace App\Services;

use App\Models\LandTransferApplication;

class ApplicationBusinessStateIntegrityService
{
    public function inspect(LandTransferApplication $application): array
    {
        $issues = [];
        $isCurrentFinal = in_array($application->status, LandTransferApplication::FINAL_STATUSES, true);
        $isLegacyFinal = in_array($application->status, LandTransferApplication::LEGACY_FINAL_STATUSES, true);
        $isFinal = $isCurrentFinal || $isLegacyFinal;
        $releaseStatus = $application->release_status ?: LandTransferApplication::RELEASE_NOT_READY;
        $activeComplianceNoticeCount = $application->complianceNotices()
            ->whereNull('resolved_at')
            ->count();

        if ($application->status === LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE) {
            if ($activeComplianceNoticeCount !== 1) {
                $issues[] = 'Compliance Required status must have exactly one unresolved compliance notice.';
            }
        } elseif ($activeComplianceNoticeCount > 0) {
            $issues[] = 'An unresolved compliance notice exists while the application is not in Compliance Required status.';
        }

        if ($isCurrentFinal) {
            if (! $application->reviewed_by) {
                $issues[] = 'Current final decision is missing reviewed_by.';
            }
            if (! $application->reviewed_at) {
                $issues[] = 'Current final decision is missing reviewed_at.';
            }
            if (! $application->validated_at) {
                $issues[] = 'Current final decision is missing validated_at.';
            }
            if (! is_array($application->validation_snapshot) || empty($application->validation_snapshot)) {
                $issues[] = 'Current final decision is missing its validation snapshot.';
            }
        }

        $releaseFields = [
            'ready_for_release_at' => $application->ready_for_release_at,
            'released_at' => $application->released_at,
            'released_by' => $application->released_by,
            'release_recipient_name' => $application->release_recipient_name,
            'release_logbook_reference' => $application->release_logbook_reference,
            'date_of_clearance_release' => $application->date_of_clearance_release,
        ];

        if (! $isFinal) {
            if ($releaseStatus !== LandTransferApplication::RELEASE_NOT_READY) {
                $issues[] = 'A non-final application has a release state other than not_ready.';
            }

            if (collect($releaseFields)->contains(fn ($value) => filled($value))) {
                $issues[] = 'A non-final application contains final-output release metadata.';
            }

            return [
                'valid' => empty($issues),
                'issues' => array_values(array_unique($issues)),
            ];
        }

        // Legacy final records remain readable and are not forced into the
        // current release workflow. Historical migration anomalies are reported
        // separately by the integrity scanner for manual review.
        if ($isLegacyFinal) {
            return [
                'valid' => empty($issues),
                'issues' => array_values(array_unique($issues)),
            ];
        }

        if (! in_array($releaseStatus, [
            LandTransferApplication::RELEASE_NOT_READY,
            LandTransferApplication::RELEASE_READY,
            LandTransferApplication::RELEASED_TO_CLIENT,
        ], true)) {
            $issues[] = 'Current final decision has an unknown release_status.';
        }

        if ($releaseStatus === LandTransferApplication::RELEASE_NOT_READY) {
            if (collect($releaseFields)->contains(fn ($value) => filled($value))) {
                $issues[] = 'A not_ready final decision contains release metadata that belongs to a later release stage.';
            }
        }

        if ($releaseStatus === LandTransferApplication::RELEASE_READY) {
            if (! $application->ready_for_release_at) {
                $issues[] = 'A ready_for_release decision is missing ready_for_release_at.';
            }

            if (collect([
                $application->released_at,
                $application->released_by,
                $application->release_recipient_name,
                $application->date_of_clearance_release,
            ])->contains(fn ($value) => filled($value))) {
                $issues[] = 'A ready_for_release decision already contains client-release metadata.';
            }
        }

        if ($releaseStatus === LandTransferApplication::RELEASED_TO_CLIENT) {
            foreach ([
                'ready_for_release_at' => $application->ready_for_release_at,
                'released_at' => $application->released_at,
                'released_by' => $application->released_by,
                'release_recipient_name' => $application->release_recipient_name,
                'date_of_clearance_release' => $application->date_of_clearance_release,
            ] as $field => $value) {
                if (blank($value)) {
                    $issues[] = "A released decision is missing {$field}.";
                }
            }

            if (
                $application->ready_for_release_at
                && $application->released_at
                && $application->released_at->lt($application->ready_for_release_at)
            ) {
                $issues[] = 'released_at is earlier than ready_for_release_at.';
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => array_values(array_unique($issues)),
        ];
    }
}
