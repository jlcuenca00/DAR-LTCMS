<?php

namespace App\Services;

use App\Models\ApplicationParcel;
use App\Models\Landholding;
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
