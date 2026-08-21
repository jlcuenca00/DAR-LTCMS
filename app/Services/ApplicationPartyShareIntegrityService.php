<?php

namespace App\Services;

use App\Models\ApplicationParcel;
use App\Models\LandTransferApplication;
use Illuminate\Validation\ValidationException;

class ApplicationPartyShareIntegrityService
{
    public const TOLERANCE = 0.0001;

    public function inspect(LandTransferApplication $application, ?ApplicationParcel $prospectiveParcel = null): array
    {
        $application->loadMissing('applicationParcels');
        $parcels = $application->applicationParcels->keyBy('id');

        if ($prospectiveParcel && $prospectiveParcel->getKey()) {
            $parcels->put($prospectiveParcel->getKey(), $prospectiveParcel);
        }

        $rows = collect($application->partyRows('transferee'));
        $validParcelIds = $parcels->keys()->map(fn ($id) => (string) $id)->all();
        $issues = [];
        $parcelResults = [];

        foreach ($rows as $index => $row) {
            foreach (array_keys((array) ($row['parcel_shares'] ?? [])) as $shareParcelId) {
                if (! in_array((string) $shareParcelId, $validParcelIds, true)) {
                    $issues[] = 'Transferee '.($index + 1).' contains a stale share for removed ApplicationParcel #'.$shareParcelId.'.';
                }
            }
        }

        foreach ($parcels as $applicationParcel) {
            $parcelId = (string) $applicationParcel->id;
            $transferredArea = round((float) $applicationParcel->area_hectares, 4);
            $explicitRows = $rows->filter(fn ($row) => array_key_exists($parcelId, (array) ($row['parcel_shares'] ?? [])));

            if ($explicitRows->isEmpty()) {
                $parcelResults[$parcelId] = [
                    'mode' => 'equal_share_fallback',
                    'valid' => true,
                    'transferred_area' => $transferredArea,
                ];
                continue;
            }

            $missingCount = $rows->count() - $explicitRows->count();
            if ($missingCount > 0) {
                $issues[] = 'ApplicationParcel #'.$parcelId.' mixes explicit and fallback transferee shares. Enter every transferee share or leave all shares blank.';
            }

            $shares = $rows->map(fn ($row) => data_get($row, 'parcel_shares.'.$parcelId));
            if ($shares->contains(fn ($value) => $value !== null && (float) $value < 0)) {
                $issues[] = 'ApplicationParcel #'.$parcelId.' contains a negative transferee share.';
            }

            $total = round((float) $shares->filter(fn ($value) => $value !== null && $value !== '')->sum(fn ($value) => (float) $value), 4);
            if (abs($total - $transferredArea) > self::TOLERANCE) {
                $issues[] = 'ApplicationParcel #'.$parcelId.' transferee shares total '.number_format($total, 4).' ha but the transferred area is '.number_format($transferredArea, 4).' ha.';
            }

            $parcelResults[$parcelId] = [
                'mode' => 'explicit',
                'valid' => $missingCount === 0 && abs($total - $transferredArea) <= self::TOLERANCE,
                'transferred_area' => $transferredArea,
                'share_total' => $total,
            ];
        }

        return [
            'valid' => empty($issues),
            'issues' => array_values(array_unique($issues)),
            'parcels' => $parcelResults,
        ];
    }

    public function assertValid(LandTransferApplication $application, ?ApplicationParcel $prospectiveParcel = null): void
    {
        $result = $this->inspect($application, $prospectiveParcel);

        if (! $result['valid']) {
            throw ValidationException::withMessages([
                'transferees' => $result['issues'],
            ]);
        }
    }

    public function removeParcelShareReferences(LandTransferApplication $application, int $applicationParcelId): void
    {
        $key = (string) $applicationParcelId;
        $changed = false;

        foreach (['transferors', 'transferees'] as $attribute) {
            $rows = collect($application->{$attribute} ?? [])->map(function ($row) use ($key, &$changed) {
                $shares = (array) data_get($row, 'parcel_shares', []);

                if (array_key_exists($key, $shares)) {
                    unset($shares[$key]);
                    $row['parcel_shares'] = $shares;
                    $changed = true;
                }

                return $row;
            })->values()->all();

            $application->setAttribute($attribute, $rows);
        }

        if ($changed) {
            $application->saveQuietly();
        }
    }
}
