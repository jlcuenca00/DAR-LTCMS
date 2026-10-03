<?php

namespace App\Services;

use App\Models\Landholding;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use Illuminate\Validation\ValidationException;

class ApplicationPartyLinkReviewService
{
    public function fingerprint(LandTransferApplication $application): string
    {
        $parcelIds = $application->applicationParcels()->whereNotNull('parcel_id')->pluck('parcel_id');
        $parcels = Parcel::query()->whereIn('id', $parcelIds)->orderBy('id')->get(['id', 'record_revision']);
        $holdings = Landholding::query()->whereIn('parcel_id', $parcelIds)->orderBy('id')
            ->get(['id', 'landowner_id', 'parcel_id', 'record_revision']);
        return hash('sha256', json_encode([
            'application_id' => $application->id,
            'parcels' => $parcels->toArray(),
            'landholdings' => $holdings->toArray(),
        ], JSON_UNESCAPED_SLASHES));
    }

    public function assertExpected(LandTransferApplication $application, string $expected): void
    {
        if (! hash_equals($expected, $this->fingerprint($application))) {
            throw ValidationException::withMessages([
                'expected_party_dependency' => 'Parcel or Landholding records changed after this form was opened. Reload and review the current shares before synchronizing.',
            ]);
        }
    }
}
