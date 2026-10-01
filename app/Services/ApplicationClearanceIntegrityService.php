<?php

namespace App\Services;

use App\Models\LandTransferApplication;

class ApplicationClearanceIntegrityService
{
    public const AREA_TOLERANCE = 0.0001;

    public function inspect(LandTransferApplication $application): array
    {
        $application->loadMissing('clearance');
        $issues = [];
        $clearance = $application->clearance;
        $isCurrentFinal = in_array($application->status, LandTransferApplication::FINAL_STATUSES, true);

        if ($isCurrentFinal && ! $clearance) {
            $issues[] = 'Current final decision does not have an immutable LTC Form No. 5 clearance snapshot.';

            return ['valid' => false, 'issues' => $issues];
        }

        if (! $application->isFinalized() && $clearance) {
            $issues[] = 'A non-final application already has an immutable clearance snapshot.';
        }

        if (! $clearance) {
            return [
                'valid' => empty($issues),
                'issues' => array_values(array_unique($issues)),
            ];
        }

        if ((string) $clearance->decision_status !== (string) $application->status) {
            $issues[] = 'Clearance decision_status does not match the application final status.';
        }

        if ((string) $clearance->application_code !== (string) $application->application_code) {
            $issues[] = 'Clearance application_code does not match its parent application.';
        }

        $allowedDecisionStatuses = array_merge(
            LandTransferApplication::FINAL_STATUSES,
            LandTransferApplication::LEGACY_FINAL_STATUSES
        );

        if (! in_array((string) $clearance->decision_status, $allowedDecisionStatuses, true)) {
            $issues[] = 'Clearance decision_status is not a recognized final decision state.';
        }

        $hasVersionedFormSnapshot = is_array($clearance->form_snapshot)
            && filled(data_get($clearance->form_snapshot, 'snapshot_version'));

        if ($hasVersionedFormSnapshot && $isCurrentFinal) {
            $decisionComparisons = [
                'decision_authority' => [
                    (string) ($clearance->decision_authority ?? ''),
                    (string) ($application->decision_authority ?? ''),
                ],
                'decision_officer_name' => [
                    (string) ($clearance->decision_officer_name ?? ''),
                    (string) ($application->decision_officer_name ?? ''),
                ],
                'decision_date' => [
                    optional($clearance->decision_date)->toDateString(),
                    optional($application->decision_date)->toDateString(),
                ],
                'decision_recorded_by' => [
                    $clearance->decision_recorded_by !== null ? (int) $clearance->decision_recorded_by : null,
                    $application->decision_recorded_by !== null ? (int) $application->decision_recorded_by : null,
                ],
                'decision_recorded_at' => [
                    optional($clearance->decision_recorded_at)->toDateTimeString(),
                    optional($application->decision_recorded_at)->toDateTimeString(),
                ],
            ];

            foreach ($decisionComparisons as $field => [$snapshotValue, $applicationValue]) {
                if ($snapshotValue === null || $snapshotValue === '') {
                    $issues[] = "Clearance {$field} is missing from the immutable final-decision snapshot.";
                    continue;
                }

                if ((string) $snapshotValue !== (string) $applicationValue) {
                    $issues[] = "Clearance {$field} does not match the frozen application final-decision record.";
                }
            }
        } else {
            // Historical compatibility: pre-versioned snapshots remain readable.
            // When identity fields are present, still detect contradictions.
            foreach ([
                'decision_authority',
                'decision_officer_name',
                'decision_recorded_by',
            ] as $field) {
                $snapshotValue = $clearance->{$field};
                $applicationValue = $application->{$field};

                if (filled($snapshotValue) && filled($applicationValue)
                    && (string) $snapshotValue !== (string) $applicationValue) {
                    $issues[] = "Clearance {$field} does not match the application final-decision record.";
                }
            }

            if ($clearance->decision_date && $application->decision_date
                && $clearance->decision_date->toDateString() !== $application->decision_date->toDateString()) {
                $issues[] = 'Clearance decision_date does not match the application final-decision record.';
            }

            if ($clearance->decision_recorded_at && $application->decision_recorded_at
                && $clearance->decision_recorded_at->toDateTimeString() !== $application->decision_recorded_at->toDateTimeString()) {
                $issues[] = 'Clearance decision_recorded_at does not match the application final-decision record.';
            }
        }

        $snapshotArea = round((float) collect((array) $clearance->parcel_snapshot)
            ->sum(fn ($row) => is_array($row) ? (float) ($row['area_hectares'] ?? 0) : 0), 4);
        $recordedArea = round((float) $clearance->total_area_hectares, 4);

        if (abs($recordedArea - $snapshotArea) > self::AREA_TOLERANCE) {
            $issues[] = 'Clearance total_area_hectares does not equal the sum of its immutable parcel_snapshot.';
        }

        return [
            'valid' => empty($issues),
            'issues' => array_values(array_unique($issues)),
        ];
    }
}
