<?php

namespace App\Services;

use App\Models\Landowner;
use App\Models\LandTransferApplication;

class LandholdingAreaValidationService
{
    public const FIVE_HECTARE_LIMIT = 5.0000;
    public const NEAR_LIMIT_THRESHOLD = 4.5000;

    public function forApplication(LandTransferApplication $application): array
    {
        $application->loadMissing('applicationParcels.parcel');

        $linkedTransfereeIds = $application->linkedLandownerIds('transferee');
        $landowners = Landowner::query()
            ->whereIn('id', $linkedTransfereeIds)
            ->get()
            ->keyBy('id');

        if ($linkedTransfereeIds->isEmpty()) {
            $result = $this->calculate(null, $application);
            $result['per_landowner'] = [];

            return $result;
        }

        $perLandowner = $linkedTransfereeIds
            ->map(function ($landownerId) use ($landowners, $application) {
                return $this->calculate($landowners->get($landownerId), $application);
            })
            ->values();

        $representative = $perLandowner
            ->sortByDesc(fn ($row) => ((int) $row['blocks_release'] * 1000000) + (float) $row['projected_total'])
            ->first();

        $representative['blocks_release'] = $perLandowner->contains(fn ($row) => $row['blocks_release']);
        $representative['exceeds_limit'] = $perLandowner->contains(fn ($row) => $row['exceeds_limit']);
        $representative['near_limit'] = $perLandowner->contains(fn ($row) => $row['near_limit']);
        $representative['per_landowner'] = $perLandowner->all();
        $representative['scope_note'] = 'Computed separately for every linked transferee using encoded active landholding records and the transferee hectare share recorded for each linked parcel. The result is assistive only and does not make a final legal determination or execute ownership transfer.';

        return $representative;
    }

    public function forLandowner(Landowner $landowner): array
    {
        $result = $this->calculate($landowner, null);
        $result['per_landowner'] = [$result];

        return $result;
    }

    private function calculate(?Landowner $landowner, ?LandTransferApplication $application): array
    {
        $currentActiveTotal = 0.0;
        $pendingIncomingTotal = 0.0;
        $thisApplicationTotal = 0.0;

        if ($landowner) {
            $currentActiveTotal = (float) $landowner->landholdings()
                ->where('status', 'active')
                ->sum('area_hectares');

            $pendingApplications = LandTransferApplication::query()
                ->with('applicationParcels.parcel')
                ->where(function ($query) use ($landowner) {
                    $query->where('transferee_landowner_id', $landowner->id)
                        ->orWhereJsonContains('transferees', [['landowner_id' => $landowner->id]]);
                })
                ->whereIn('status', array_merge(
                    LandTransferApplication::ACTIVE_STATUSES,
                    [LandTransferApplication::STATUS_DRAFT, LandTransferApplication::STATUS_PENDING_REVIEW]
                ))
                ->when($application, fn ($query) => $query->where('id', '!=', $application->id))
                ->get();

            $pendingIncomingTotal = (float) $pendingApplications
                ->sum(fn (LandTransferApplication $pendingApplication) => $this->incomingAreaForLandowner($pendingApplication, $landowner->id));
        }

        if ($application) {
            $thisApplicationTotal = $landowner
                ? $this->incomingAreaForLandowner($application, $landowner->id)
                : (float) $application->applicationParcels()->sum('area_hectares');
        }

        $projectedTotal = $currentActiveTotal + $pendingIncomingTotal + $thisApplicationTotal;
        $remainingAfterProjection = max(0, self::FIVE_HECTARE_LIMIT - $projectedTotal);
        $exceedsLimit = $projectedTotal > self::FIVE_HECTARE_LIMIT;
        $nearLimit = ! $exceedsLimit && $projectedTotal >= self::NEAR_LIMIT_THRESHOLD;

        $successionExceptionClaimed = (bool) ($application?->is_succession_case ?? false);
        $retentionCertificateRequired = (bool) ($application?->retention_certificate_required ?? false);
        $retentionCertificateReference = trim((string) ($application?->retention_certificate_reference ?? ''));
        $retentionCertificateMissing = $retentionCertificateRequired && $retentionCertificateReference === '';

        $blocksRelease = ($exceedsLimit && ! $successionExceptionClaimed) || $retentionCertificateMissing;

        $status = match (true) {
            $retentionCertificateMissing => 'retention_certificate_missing',
            $exceedsLimit && $successionExceptionClaimed => 'succession_exception_for_manual_review',
            $exceedsLimit => 'over_limit',
            $nearLimit => 'near_limit',
            default => 'within_limit',
        };

        return [
            'landowner_id' => $landowner?->id,
            'landowner_name' => $landowner?->full_name,
            'limit' => self::FIVE_HECTARE_LIMIT,
            'near_limit_threshold' => self::NEAR_LIMIT_THRESHOLD,
            'current_active_total' => round($currentActiveTotal, 4),
            'pending_incoming_total' => round($pendingIncomingTotal, 4),
            'this_application_total' => round($thisApplicationTotal, 4),
            'projected_total' => round($projectedTotal, 4),
            'remaining_after_projection' => round($remainingAfterProjection, 4),
            'exceeds_limit' => $exceedsLimit,
            'near_limit' => $nearLimit,
            'succession_exception_claimed' => $successionExceptionClaimed,
            'retention_certificate_required' => $retentionCertificateRequired,
            'retention_certificate_reference' => $retentionCertificateReference ?: null,
            'retention_certificate_missing' => $retentionCertificateMissing,
            'blocks_release' => $blocksRelease,
            'status' => $status,
            'status_label' => match ($status) {
                'retention_certificate_missing' => 'Retention Certificate reference needed',
                'succession_exception_for_manual_review' => 'Over limit with succession exception noted',
                'over_limit' => 'Over 5-hectare reference limit',
                'near_limit' => 'Near 5-hectare reference limit',
                default => 'Within 5-hectare reference limit',
            },
            'scope_note' => 'Computed from encoded active landholding records and pending/current clearance application areas only. Succession and retention-certificate entries are staff review context, not automatic legal determinations.',
        ];
    }

    private function incomingAreaForLandowner(LandTransferApplication $application, int $landownerId): float
    {
        $application->loadMissing('applicationParcels.parcel');

        return round((float) $application->applicationParcels->sum(function ($applicationParcel) use ($application, $landownerId) {
            $parcelArea = (float) ($applicationParcel->area_hectares ?? $applicationParcel->parcel?->area_hectares ?? 0);

            return $application->partyAreaForParcel(
                'transferee',
                $landownerId,
                (int) $applicationParcel->id,
                $parcelArea
            );
        }), 4);
    }
}
