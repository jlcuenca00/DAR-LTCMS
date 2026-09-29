<?php

namespace App\Observers;

use App\Models\LandTransferApplication;
use App\Services\ApplicationPartyIntegrityService;
use App\Services\ApplicationPartyShareIntegrityService;
use App\Services\DarLocationService;

class LandTransferApplicationObserver
{
    public function saving(LandTransferApplication $application): void
    {
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
