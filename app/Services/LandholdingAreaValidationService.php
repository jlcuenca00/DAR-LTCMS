<?php

namespace App\Services;

use App\Models\ApplicationParcel;
use App\Models\Landowner;
use App\Models\LandTransferApplication;

class LandholdingAreaValidationService
{
    public const FIVE_HECTARE_LIMIT = 5.0000;
    public const NEAR_LIMIT_THRESHOLD = 4.5000;

    public function forApplication(LandTransferApplication $application): array
    {
        $application->loadMissing('transfereeLandowner');

        return $this->calculate(
            $application->transfereeLandowner,
            $application
        );
    }

    public function forLandowner(Landowner $landowner): array
    {
        return $this->calculate($landowner, null);
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

            $pendingIncomingQuery = ApplicationParcel::query()
                ->whereHas('application', function ($query) use ($landowner, $application) {
                    $query->where('transferee_landowner_id', $landowner->id)
                        ->whereIn('status', array_merge(
                            LandTransferApplication::ACTIVE_STATUSES,
                            [LandTransferApplication::STATUS_DRAFT, LandTransferApplication::STATUS_PENDING_REVIEW]
                        ));

                    if ($application) {
                        $query->where('id', '!=', $application->id);
                    }
                });

            $pendingIncomingTotal = (float) $pendingIncomingQuery->sum('area_hectares');
        }

        if ($application) {
            $thisApplicationTotal = (float) $application->applicationParcels()
                ->sum('area_hectares');
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
}
