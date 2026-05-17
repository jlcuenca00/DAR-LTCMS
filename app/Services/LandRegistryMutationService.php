<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use LogicException;

class LandRegistryMutationService
{
    /**
     * Deprecated by DAR-LTCMS scope freeze.
     *
     * This system is a clearance generation, processing, monitoring, and
     * records-management platform only. Approval of a clearance application
     * must never automatically transfer land ownership, create transferee
     * landholdings, reduce transferor holdings, or mutate registry records.
     */
    public function mutate(LandTransferApplication $application, int $userId): void
    {
        throw new LogicException(
            'Automatic land registry mutation is disabled. Clearance approval records/generates the clearance result only and does not transfer ownership or mutate registry records.'
        );
    }
}
