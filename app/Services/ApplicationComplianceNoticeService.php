<?php

namespace App\Services;

use App\Models\ApplicationComplianceNotice;
use App\Models\LandTransferApplication;
use Illuminate\Validation\ValidationException;

class ApplicationComplianceNoticeService
{
    public function create(LandTransferApplication $application, array $attributes): ApplicationComplianceNotice
    {
        if ($application->isFinalized()) {
            throw ValidationException::withMessages([
                'compliance' => 'Finalized applications cannot receive new compliance notices.',
            ]);
        }

        $notice = new ApplicationComplianceNotice(array_merge(
            $attributes,
            ['land_transfer_application_id' => $application->id]
        ));

        $notice->runAuthorizedCreation(function () use ($notice) {
            $notice->save();
        });

        return $notice;
    }

    public function resolve(ApplicationComplianceNotice $notice, array $attributes): ApplicationComplianceNotice
    {
        $application = $notice->application()->first();

        if (! $application || $application->isFinalized()) {
            throw ValidationException::withMessages([
                'compliance' => 'Compliance history cannot be changed after the application is finalized.',
            ]);
        }

        if ($notice->isResolved()) {
            throw ValidationException::withMessages([
                'compliance' => 'This compliance notice has already been resolved and is immutable.',
            ]);
        }

        $notice->runAuthorizedResolution(function () use ($notice, $attributes) {
            $notice->forceFill($attributes);
            $notice->save();
        });

        return $notice;
    }
}
