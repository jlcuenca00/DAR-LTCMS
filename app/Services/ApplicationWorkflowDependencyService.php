<?php

namespace App\Services;

use App\Models\Landholding;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use Illuminate\Validation\ValidationException;

class ApplicationWorkflowDependencyService
{
    public function fingerprint(LandTransferApplication $application): string
    {
        $transfereeIds = $application->linkedLandownerIds('transferee')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        $parcelIds = $application->applicationParcels()
            ->whereNotNull('parcel_id')
            ->pluck('parcel_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        $parcels = Parcel::query()
            ->whereIn('id', $parcelIds->all())
            ->orderBy('id')
            ->get([
                'id',
                'status',
                'area_hectares',
                'area_square_meters',
                'geometry_version',
                'updated_at',
            ])
            ->map(fn (Parcel $parcel) => [
                'id' => (int) $parcel->id,
                'status' => $parcel->status,
                'area_hectares' => $parcel->area_hectares !== null ? (string) $parcel->area_hectares : null,
                'area_square_meters' => $parcel->area_square_meters !== null ? (string) $parcel->area_square_meters : null,
                'geometry_version' => (int) ($parcel->geometry_version ?? 0),
                'updated_at' => optional($parcel->updated_at)->toIso8601String(),
            ])
            ->values()
            ->all();

        $landholdings = Landholding::query()
            ->whereIn('landowner_id', $transfereeIds->all())
            ->where('status', Landholding::STATUS_ACTIVE)
            ->orderBy('id')
            ->get([
                'id',
                'landowner_id',
                'parcel_id',
                'area_hectares',
                'status',
                'source_application_id',
                'updated_at',
            ])
            ->map(fn (Landholding $landholding) => [
                'id' => (int) $landholding->id,
                'landowner_id' => (int) $landholding->landowner_id,
                'parcel_id' => (int) $landholding->parcel_id,
                'area_hectares' => (string) $landholding->area_hectares,
                'status' => $landholding->status,
                'source_application_id' => $landholding->source_application_id !== null
                    ? (int) $landholding->source_application_id
                    : null,
                'updated_at' => optional($landholding->updated_at)->toIso8601String(),
            ])
            ->values()
            ->all();

        $exposureApplications = collect();

        if ($transfereeIds->isNotEmpty()) {
            $query = LandTransferApplication::query()
                ->where('id', '!=', $application->id)
                ->whereIn('status', LandholdingAreaValidationService::potentialIncomingExposureStatuses())
                ->where(function ($query) use ($transfereeIds) {
                    foreach ($transfereeIds as $landownerId) {
                        $query->orWhere('transferee_landowner_id', $landownerId)
                            ->orWhereJsonContains('transferees', [['landowner_id' => $landownerId]]);
                    }
                })
                ->orderBy('id');

            $exposureApplications = $query
                ->get(['id', 'status', 'workflow_revision', 'updated_at'])
                ->map(function (LandTransferApplication $other) {
                    $activeExposure = in_array(
                        $other->status,
                        array_merge(
                            LandTransferApplication::ACTIVE_STATUSES,
                            [
                                LandTransferApplication::STATUS_DRAFT,
                                LandTransferApplication::STATUS_PENDING_REVIEW,
                            ]
                        ),
                        true
                    );

                    return [
                        'id' => (int) $other->id,
                        'status' => $other->status,
                        // Active applications may still change their linked
                        // parties/parcel shares, so bind to their monotonic
                        // workflow revision. Approved/Released records are
                        // frozen for those exposure inputs; release tracking
                        // should not invalidate another application's review.
                        'workflow_revision' => $activeExposure
                            ? (int) $other->workflow_revision
                            : null,
                    ];
                })
                ->values();
        }

        return hash('sha256', json_encode([
            'application_id' => (int) $application->id,
            'transferee_landowner_ids' => $transfereeIds->all(),
            'linked_master_parcels' => $parcels,
            'active_transferee_landholdings' => $landholdings,
            'potential_incoming_applications' => $exposureApplications->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function assertExpected(LandTransferApplication $application, string $expectedFingerprint): void
    {
        if (! hash_equals($expectedFingerprint, $this->fingerprint($application))) {
            throw ValidationException::withMessages([
                'workflow_dependency' => 'Shared parcel or landholding data changed after this page was opened. Refresh and review the latest validation data before continuing.',
            ]);
        }
    }
}
