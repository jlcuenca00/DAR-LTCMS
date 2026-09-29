<?php

namespace App\Services;

use App\Models\ApplicationParcel;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use Illuminate\Validation\ValidationException;

class ApplicationParcelIntegrityService
{
    public const AREA_TOLERANCE = 0.0001;

    public function inspect(ApplicationParcel $applicationParcel, ?LandTransferApplication $application = null): array
    {
        $issues = [];
        $application ??= $applicationParcel->application;

        // Final/historical snapshots stay readable. The stricter rules below
        // govern records that are still participating in the current workflow.
        if (! $application || $application->isFinalized()) {
            return ['valid' => true, 'issues' => []];
        }

        if (! $applicationParcel->parcel_id) {
            $issues[] = 'The application Parcel row is not linked to a current Parcel record.';

            return ['valid' => false, 'issues' => $issues];
        }

        $parcel = $applicationParcel->relationLoaded('parcel')
            ? $applicationParcel->parcel
            : Parcel::query()->find($applicationParcel->parcel_id);

        if (! $parcel) {
            $issues[] = 'The linked Parcel record no longer exists.';

            return ['valid' => false, 'issues' => $issues];
        }

        if ($parcel->status !== 'active') {
            $issues[] = 'The linked Parcel record is inactive and cannot be used by an open clearance application.';
        }

        $area = $applicationParcel->area_hectares;
        if ($area === null || $area === '' || (float) $area <= self::AREA_TOLERANCE) {
            $issues[] = 'The application Parcel must record a positive transferred area.';
        }

        if ($parcel->area_hectares === null || (float) $parcel->area_hectares <= self::AREA_TOLERANCE) {
            $issues[] = 'The linked Parcel master record must have a positive recorded area.';
        }

        return [
            'valid' => empty($issues),
            'issues' => array_values(array_unique($issues)),
        ];
    }

    public function assertCurrentWorkflowValid(
        ApplicationParcel $applicationParcel,
        ?LandTransferApplication $application = null
    ): void {
        $inspection = $this->inspect($applicationParcel, $application);

        if (! $inspection['valid']) {
            throw ValidationException::withMessages([
                'parcel_id' => $inspection['issues'],
            ]);
        }
    }

    public function inspectApplication(LandTransferApplication $application): array
    {
        $application->loadMissing('applicationParcels.parcel');

        if ($application->isFinalized()) {
            return [
                'valid' => true,
                'total_count' => $application->applicationParcels->count(),
                'valid_count' => $application->applicationParcels->count(),
                'issues' => [],
            ];
        }

        $issues = [];
        $validCount = 0;

        foreach ($application->applicationParcels as $applicationParcel) {
            $inspection = $this->inspect($applicationParcel, $application);

            if ($inspection['valid']) {
                $validCount++;
                continue;
            }

            foreach ($inspection['issues'] as $issue) {
                $issues[] = 'ApplicationParcel #'.$applicationParcel->id.': '.$issue;
            }
        }

        return [
            'valid' => empty($issues),
            'total_count' => $application->applicationParcels->count(),
            'valid_count' => $validCount,
            'issues' => array_values(array_unique($issues)),
        ];
    }
}
