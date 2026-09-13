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
     * Staff notifications stay intentionally narrow: intake, final decision,
     * release readiness, and actual client release. Routine internal stage
     * endorsements remain visible in the audit trail without creating noise.
     */
    private const STAFF_ALLOWED_TYPES = [
        'application_created',
        'application_submitted',
        'application_approved',
        'application_denied',
        'application_ready_for_release',
        'application_released',
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

    public function notifyStaffApplicationApproved(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_approved',
            'PARPO II approval recorded',
            'A final Approved clearance decision was recorded for application ' . $application->application_code . '. Client release is tracked separately.',
            $application,
            $this->staffApplicationData($application)
        );
    }

    /**
     * Historical compatibility wrapper. New code should use
     * notifyStaffApplicationReleasedToClient() for an actual release event.
     */
    public function notifyStaffApplicationReleased(LandTransferApplication $application): void
    {
        $this->notifyStaffApplicationReleasedToClient($application);
    }

    public function notifyStaffApplicationDenied(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_denied',
            'PARPO II denial recorded',
            'A final Denied clearance decision was recorded for application ' . $application->application_code . '.',
            $application,
            $this->staffApplicationData($application)
        );
    }

    public function notifyStaffApplicationReadyForRelease(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_ready_for_release',
            'Decision output ready for release',
            'The signed LTC Form No. 5 for application ' . $application->application_code . ' is ready for client release.',
            $application,
            $this->staffApplicationData($application)
        );
    }

    public function notifyStaffApplicationReleasedToClient(LandTransferApplication $application): void
    {
        $this->notifyActiveStaff(
            'application_released',
            'Decision output released to client',
            'The signed clearance decision for application ' . $application->application_code . ' was released to the client or authorized representative.',
            $application,
            array_merge($this->staffApplicationData($application), [
                'release_status' => $application->release_status,
                'released_at' => optional($application->released_at)->toDateTimeString(),
            ])
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
            'A final clearance decision has been recorded for application ' . $application->application_code . '. Decision status: ' . $statusLabel . '. Release of the signed output is tracked separately.',
            $application,
            $this->landownerApplicationData($application)
        );
    }

    public function notifyLinkedLandownersReleasedToClient(LandTransferApplication $application): void
    {
        $users = $this->linkedLandownerUsers($application);

        $this->notifyUsers(
            $users,
            'landowner_clearance_released',
            'Decision output released',
            'The signed clearance decision for application ' . $application->application_code . ' has been recorded as Released to Client.',
            $application,
            array_merge($this->landownerApplicationData($application), [
                'release_status' => $application->release_status,
                'released_at' => optional($application->released_at)->toDateTimeString(),
            ])
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

        $packageNoun = $committedRows === 1 ? 'package' : 'packages';
        $importVerb = $committedRows === 1 ? 'was' : 'were';
        $availabilityVerb = $committedRows === 1 ? 'is' : 'are';

        $this->notifyActiveGeodetic(
            'geodetic_reference_imported',
            'Source references imported',
            $committedRows . ' source ' . $packageNoun . ' ' . $importVerb . ' imported and ' . $availabilityVerb . ' available for parcel/reference review.',
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

    public function notifyGeodeticParcelGeometryUpdated(Parcel $parcel, ?User $actor): void
    {
        $actorName = $actor?->name ?: 'A Geodetic user';

        User::query()
            ->where('role', User::ROLE_GEODETIC)
            ->where('is_active', true)
            ->when($actor, fn ($query) => $query->whereKeyNot($actor->id))
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($parcel, $actorName, $actor) {
                $this->notifyUsers(
                    $users,
                    'geodetic_geometry_updated',
                    'Parcel geometry updated',
                    $actorName . ' successfully updated the parcel geometry for ' . $parcel->parcel_code . '.',
                    $parcel,
                    [
                        'parcel_id' => $parcel->id,
                        'parcel_code' => $parcel->parcel_code,
                        'geometry_version' => (int) $parcel->geometry_version,
                        'actor_user_id' => $actor?->id,
                        'actor_name' => $actorName,
                        'municipality' => $parcel->municipality,
                        'barangay' => $parcel->barangay,
                    ]
                );
            });
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

            // Legacy draft/pending records are normalized into the current Legal
            // intake workflow. The first action may immediately land at Awaiting
            // Payment after a successful completeness check, but it is still the
            // one-time submission into the current review process.
            $isSubmissionIntoReview = in_array($oldStatus, [
                LandTransferApplication::STATUS_DRAFT,
                LandTransferApplication::STATUS_PENDING_REVIEW,
            ], true) && in_array($newStatus, [
                LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                LandTransferApplication::STATUS_AWAITING_PAYMENT,
            ], true);

            if (! $isSubmissionIntoReview) {
                return null;
            }

            $applicationCode = $related instanceof LandTransferApplication
                ? $related->application_code
                : ($data['application_code'] ?? 'the application');
            $newStatusLabel = LandTransferApplication::statusLabels()[$newStatus]
                ?? ucwords(str_replace('_', ' ', (string) $newStatus));

            $type = 'application_submitted';
            $title = 'Application submitted for review';
            $message = 'Application ' . $applicationCode . ' entered the current review workflow and is now ' . $newStatusLabel . '.';
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
            'release_status' => $application->release_status,
            'release_status_label' => method_exists($application, 'releaseStatusLabel') ? $application->releaseStatusLabel() : null,
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
            'release_status' => $application->release_status,
            'release_status_label' => method_exists($application, 'releaseStatusLabel') ? $application->releaseStatusLabel() : null,
        ];
    }

    private function finalDecisionLabel(LandTransferApplication $application): string
    {
        return match ($application->status) {
            LandTransferApplication::STATUS_APPROVED => 'Approved',
            LandTransferApplication::STATUS_DENIED,
            LandTransferApplication::STATUS_NOT_APPROVED => 'Denied',
            LandTransferApplication::STATUS_RELEASED => 'Released (legacy record)',
            default => $application->statusLabel(),
        };
    }
}
