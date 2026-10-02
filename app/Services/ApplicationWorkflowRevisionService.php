<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationWorkflowRevisionService
{
    public function assertExpected(LandTransferApplication $application, int $expectedRevision): void
    {
        if ((int) $application->workflow_revision !== $expectedRevision) {
            throw ValidationException::withMessages([
                'workflow_revision' => 'This application changed after the page was opened. Refresh and review the latest record before continuing.',
            ]);
        }
    }

    public function bump(LandTransferApplication|int $application): void
    {
        // PostgreSQL child triggers bump revisions for model and bulk writes.
        if (DB::connection()->getDriverName() === 'pgsql') {
            return;
        }

        $applicationId = $application instanceof LandTransferApplication
            ? (int) $application->getKey()
            : (int) $application;

        if ($applicationId <= 0) {
            return;
        }

        DB::table('land_transfer_applications')
            ->where('id', $applicationId)
            ->increment('workflow_revision');
    }
}
