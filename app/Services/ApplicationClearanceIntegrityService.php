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
