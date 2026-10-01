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
