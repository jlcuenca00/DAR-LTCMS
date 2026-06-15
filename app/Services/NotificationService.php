<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use App\Models\SourceRecordPackage;
use App\Models\Parcel;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notifyUser(
        User|int|null $user,
        string $type,
        string $title,
        string $message,
        ?Model $related = null,
        array $data = []
    ): ?SystemNotification {
        $userId = $user instanceof User ? $user->id : $user;

        if (! $userId) {
            return null;
        }

        return SystemNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_type' => $related ? $related::class : null,
            'related_id' => $related?->getKey(),
            'data' => $data ?: null,
        ]);
    }

    public function notifyUsers(iterable $users, string $type, string $title, string $message, ?Model $related = null, array $data = []): void
    {
        foreach ($users as $user) {
            $this->notifyUser($user, $type, $title, $message, $related, $data);
        }
    }

    public function notifyActiveStaff(string $type, string $title, string $message, ?Model $related = null, array $data = []): void
    {
        User::query()
            ->where('role', User::ROLE_STAFF)
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($type, $title, $message, $related, $data) {
                $this->notifyUsers($users, $type, $title, $message, $related, $data);
            });
    }

    public function notifyActiveGeodetic(string $type, string $title, string $message, ?Model $related = null, array $data = []): void
    {
        User::query()
            ->where('role', User::ROLE_GEODETIC)
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($type, $title, $message, $related, $data) {
                $this->notifyUsers($users, $type, $title, $message, $related, $data);
            });
    }

    public function notifyStaffApplicationEncoded(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_created',
            'Clearance application encoded',
            'Application ' . $application->application_code . ' was encoded and placed under ' . $application->statusLabel() . '.',
            $application,
            $this->applicationData($application)
        );
    }

    public function notifyStaffApplicationSubmitted(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_status_updated',
            'Application status updated',
            'Application ' . $application->application_code . ' is now ' . $application->statusLabel() . '.',
            $application,
            $this->applicationData($application)
        );
    }

    public function notifyStaffApplicationReleased(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_released',
            'Clearance released',
            'A final released clearance decision was recorded for application ' . $application->application_code . '.',
            $application,
            $this->applicationData($application)
        );
    }

    public function notifyStaffApplicationDenied(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_denied',
            'Application denied',
            'A final denied clearance decision was recorded for application ' . $application->application_code . '.',
            $application,
            $this->applicationData($application)
        );
    }

    public function notifyLinkedLandownersStatusChanged(LandTransferApplication $application, string $statusLabel): void
    {
        $users = $this->linkedLandownerUsers($application);

        $this->notifyUsers(
            $users,
            'landowner_application_status',
            'Application status updated',
            'Your clearance application ' . $application->application_code . ' is now ' . $statusLabel . '.',
            $application,
            $this->applicationData($application)
        );
    }

    public function notifyLinkedLandownersFinalDecision(LandTransferApplication $application): void
    {
        $users = $this->linkedLandownerUsers($application);
        $statusLabel = $this->finalDecisionLabel($application);

        $this->notifyUsers(
            $users,
            'landowner_final_decision',
            'Final clearance decision recorded',
            'A final clearance decision has been recorded for application ' . $application->application_code . '. Decision status: ' . $statusLabel . '.',
            $application,
            $this->applicationData($application)
        );
    }

    public function notifyGeodeticSourcePackageAvailable(SourceRecordPackage $package): void
    {
        $this->notifyActiveGeodetic(
            'geodetic_reference_available',
            'Source reference available for review',
            'Source package ' . $package->package_code . ' is available for parcel/reference review.',
            $package,
            [
                'package_code' => $package->package_code,
                'parcel_code' => $package->parcel_code,
                'status' => $package->status,
            ]
        );
    }

    public function notifyGeodeticParcelReferenceUpdated(Parcel $parcel): void
    {
        $this->notifyActiveGeodetic(
            'geodetic_reference_updated',
            'Parcel reference updated',
            'Parcel reference ' . $parcel->parcel_code . ' was updated and is available for review.',
            $parcel,
            [
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
                'municipality' => $parcel->municipality,
                'barangay' => $parcel->barangay,
            ]
        );
    }

    private function linkedLandownerUsers(LandTransferApplication $application): Collection
    {
        $application->loadMissing(['transferorLandowner.user', 'transfereeLandowner.user']);

        return collect([
            $application->transferorLandowner?->user,
            $application->transfereeLandowner?->user,
        ])
            ->filter(fn ($user) => $user instanceof User && $user->is_active)
            ->unique('id')
            ->values();
    }

    private function applicationData(LandTransferApplication $application): array
    {
        return [
            'application_id' => $application->id,
            'application_code' => $application->application_code,
            'status' => $application->status,
            'status_label' => $application->statusLabel(),
            'transferor_name' => $application->transferor_name,
            'transferee_name' => $application->transferee_name,
            'municipality' => $application->municipality,
            'barangay' => $application->barangay,
        ];
    }

    private function finalDecisionLabel(LandTransferApplication $application): string
    {
        return match ($application->status) {
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_APPROVED => 'Released',

            LandTransferApplication::STATUS_DENIED,
            LandTransferApplication::STATUS_NOT_APPROVED => 'Denied',

            default => $application->statusLabel(),
        };
    }
}
