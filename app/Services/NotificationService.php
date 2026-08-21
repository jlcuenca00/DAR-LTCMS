<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use App\Models\SourceRecordPackageImportBatch;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Approved Staff notification policy.
     *
     * Staff should only receive notices for application encoding, submission
     * into review, and final released/denied decisions. Internal endorsement
     * stage changes, document activity, metadata/indexing changes, and automatic
     * clearance generation are intentionally excluded to avoid notification noise.
     */
    private const STAFF_ALLOWED_TYPES = [
        'application_created',
        'application_submitted',
        'application_released',
        'application_denied',
    ];

    public function notifyUser(
        User|int|null $user,
        string $type,
        string $title,
        string $message,
        ?Model $related = null,
        array $data = []
    ): ?SystemNotification {
        $recipient = $user instanceof User
            ? $user
            : ($user ? User::query()->find($user) : null);

        if (! $recipient || ! $recipient->is_active) {
            return null;
        }

        return SystemNotification::create([
            'user_id' => $recipient->id,
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
        collect($users)
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->each(fn (User $user) => $this->notifyUser($user, $type, $title, $message, $related, $data));
    }

    public function notifyActiveStaff(string $type, string $title, string $message, ?Model $related = null, array $data = []): void
    {
        $normalized = $this->normalizeStaffNotification($type, $title, $message, $related, $data);

        if ($normalized === null) {
            return;
        }

        [$type, $title, $message, $data] = $normalized;

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
            $this->staffApplicationData($application)
        );
    }

    public function notifyStaffApplicationSubmitted(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_submitted',
            'Application submitted for review',
            'Application ' . $application->application_code . ' was submitted for review and is now ' . $application->statusLabel() . '.',
            $application,
            $this->staffApplicationData($application)
        );
    }

    public function notifyStaffApplicationReleased(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_released',
            'Clearance released',
            'A final released clearance decision was recorded for application ' . $application->application_code . '.',
            $application,
            $this->staffApplicationData($application)
        );
    }

    public function notifyStaffApplicationDenied(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_denied',
            'Application denied',
            'A final denied clearance decision was recorded for application ' . $application->application_code . '.',
            $application,
            $this->staffApplicationData($application)
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
            $this->landownerApplicationData($application)
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
            $this->landownerApplicationData($application)
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

    public function notifyGeodeticSourceImportCommitted(SourceRecordPackageImportBatch $batch): void
    {
        $committedRows = max(0, (int) $batch->committed_rows);

        if ($committedRows === 0) {
            return;
        }

        $packageLabel = $committedRows === 1 ? 'package was' : 'packages were';

        $this->notifyActiveGeodetic(
            'geodetic_reference_imported',
            'Source references imported',
            $committedRows . ' source ' . $packageLabel . ' imported and are available for parcel/reference review.',
            null,
            [
                'committed_rows' => $committedRows,
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
        $landownerIds = $application->linkedLandownerIds();

        if ($landownerIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->where('role', User::ROLE_LANDOWNER)
            ->where('is_active', true)
            ->whereHas('landowner', fn ($query) => $query->whereIn('id', $landownerIds))
            ->get()
            ->unique('id')
            ->values();
    }

    private function normalizeStaffNotification(
        string $type,
        string $title,
        string $message,
        ?Model $related,
        array $data
    ): ?array {
        if ($type === 'application_status_updated') {
            $oldStatus = $data['old_status'] ?? null;
            $newStatus = $data['new_status'] ?? null;

            $isSubmissionIntoReview = in_array($oldStatus, [
                LandTransferApplication::STATUS_DRAFT,
                LandTransferApplication::STATUS_PENDING_REVIEW,
            ], true) && $newStatus === LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW;

            if (! $isSubmissionIntoReview) {
                return null;
            }

            $applicationCode = $related instanceof LandTransferApplication
                ? $related->application_code
                : ($data['application_code'] ?? 'the application');

            $type = 'application_submitted';
            $title = 'Application submitted for review';
            $message = 'Application ' . $applicationCode . ' was submitted for review and is now Pending Review by Legal Officer.';
        }

        if (! in_array($type, self::STAFF_ALLOWED_TYPES, true)) {
            return null;
        }

        return [$type, $title, $message, $data];
    }

    private function staffApplicationData(LandTransferApplication $application): array
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

    private function landownerApplicationData(LandTransferApplication $application): array
    {
        return [
            'application_id' => $application->id,
            'application_code' => $application->application_code,
            'status' => $application->status,
            'status_label' => $application->statusLabel(),
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
