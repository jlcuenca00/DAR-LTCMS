<?php

namespace App\Services;

use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use Illuminate\Validation\ValidationException;

class ParcelAreaIntegrityService
{
    public const HECTARE_TOLERANCE = 0.0001;

    public function canonicalizeParcel(Parcel $parcel): void
    {
        $hectares = $parcel->getAttribute('area_hectares');
        $squareMeters = $parcel->getAttribute('area_square_meters');

        if ($hectares !== null && $hectares !== '') {
            $hectares = round((float) $hectares, 4);
            $parcel->setAttribute('area_hectares', $hectares);
            $parcel->setAttribute('area_square_meters', round($hectares * 10000, 2));

            return;
        }

        if ($squareMeters !== null && $squareMeters !== '') {
            $hectares = round(((float) $squareMeters) / 10000, 4);
            $parcel->setAttribute('area_hectares', $hectares);
            $parcel->setAttribute('area_square_meters', round($hectares * 10000, 2));
        }
    }

    public function assertParcelLandholdingCompatibility(Parcel $parcel): void
    {
        if (! $parcel->exists) {
            return;
        }

        $activeArea = round((float) Landholding::query()
            ->where('parcel_id', $parcel->getKey())
            ->where('status', Landholding::STATUS_ACTIVE)
            ->sum('area_hectares'), 4);

        if ($activeArea <= self::HECTARE_TOLERANCE) {
            return;
        }

        if ($parcel->isDirty('area_hectares')) {
            $parcelArea = $parcel->getAttribute('area_hectares');

            if ($parcelArea === null || $parcelArea === '') {
                throw ValidationException::withMessages([
                    'area_hectares' => 'The Parcel area cannot be cleared while active Landholding records are linked to it.',
                ]);
            }

            if ($activeArea - (float) $parcelArea > self::HECTARE_TOLERANCE) {
                throw ValidationException::withMessages([
                    'area_hectares' => 'The Parcel area cannot be reduced below the currently allocated active Landholding area of '.number_format($activeArea, 4).' ha.',
                ]);
            }
        }

        if ($parcel->isDirty('status') && $parcel->status === 'inactive') {
            throw ValidationException::withMessages([
                'status' => 'Resolve or deactivate the Parcel\'s active Landholding records before archiving this Parcel.',
            ]);
        }

        if ($parcel->isDirty('status') && $parcel->status === 'inactive') {
            $finalStatuses = array_values(array_unique(array_merge(
                LandTransferApplication::FINAL_STATUSES,
                LandTransferApplication::LEGACY_FINAL_STATUSES
            )));

            $hasOpenApplication = ApplicationParcel::query()
                ->where('parcel_id', $parcel->getKey())
                ->whereHas('application', fn ($query) => $query->whereNotIn('status', $finalStatuses))
                ->exists();

            if ($hasOpenApplication) {
                throw ValidationException::withMessages([
                    'status' => 'This Parcel is still linked to an open clearance application and cannot be archived until that application is finalized or the Parcel link is resolved.',
                ]);
            }
        }
    }

    public function assertApplicationParcelArea(ApplicationParcel $applicationParcel): void
    {
        $area = $applicationParcel->getAttribute('area_hectares');
        $parcelId = $applicationParcel->getAttribute('parcel_id');

        if ($area === null || $area === '') {
            return;
        }

        $area = round((float) $area, 4);
        if ($area <= 0) {
            throw ValidationException::withMessages([
                'area_hectares' => 'The application transfer area must be greater than zero.',
            ]);
        }

        $applicationParcel->setAttribute('area_hectares', $area);
        $applicationParcel->setAttribute('area_square_meters', round($area * 10000, 2));

        if (! $parcelId) {
            return;
        }

        $parcel = Parcel::query()->find($parcelId);
        if (! $parcel || $parcel->area_hectares === null) {
            throw ValidationException::withMessages([
                'area_hectares' => 'The linked Parcel must have a recorded area before a transfer area can be encoded.',
            ]);
        }

        if ($area - (float) $parcel->area_hectares > self::HECTARE_TOLERANCE) {
            throw ValidationException::withMessages([
                'area_hectares' => 'The application transfer area cannot exceed the linked Parcel area of '.number_format((float) $parcel->area_hectares, 4).' ha.',
            ]);
        }
    }

    public function assertLandholdingCapacity(Landholding $landholding): void
    {
        if ($landholding->status !== Landholding::STATUS_ACTIVE) {
            return;
        }

        $area = round((float) $landholding->area_hectares, 4);
        if ($area <= 0) {
            throw ValidationException::withMessages([
                'area_hectares' => 'An active landholding must have an area greater than zero.',
            ]);
        }

        $parcel = Parcel::query()->find($landholding->parcel_id);
        if (! $parcel || $parcel->area_hectares === null) {
            throw ValidationException::withMessages([
                'parcel_id' => 'The Parcel must have a recorded area before an active landholding can be saved.',
            ]);
        }

        if ($parcel->status !== 'active') {
            throw ValidationException::withMessages([
                'parcel_id' => 'An active Landholding cannot be linked to an inactive Parcel. Reactivate the Parcel or save the Landholding as a non-active historical record.',
            ]);
        }

        $otherActiveArea = (float) Landholding::query()
            ->where('parcel_id', $landholding->parcel_id)
            ->where('status', Landholding::STATUS_ACTIVE)
            ->when($landholding->exists, fn ($query) => $query->whereKeyNot($landholding->getKey()))
            ->sum('area_hectares');

        $allocatedArea = round($otherActiveArea + $area, 4);
        if ($allocatedArea - (float) $parcel->area_hectares > self::HECTARE_TOLERANCE) {
            throw ValidationException::withMessages([
                'area_hectares' => 'Active landholding shares would total '.number_format($allocatedArea, 4).' ha, exceeding the Parcel area of '.number_format((float) $parcel->area_hectares, 4).' ha.',
            ]);
        }
    }
}
