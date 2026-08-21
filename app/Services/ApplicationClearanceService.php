<?php

namespace App\Services;

use App\Models\ApplicationClearance;
use App\Models\LandTransferApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApplicationClearanceService
{
    public function generateForDecision(LandTransferApplication $application, int $userId): ApplicationClearance
    {
        return DB::transaction(function () use ($application, $userId) {
            $application = LandTransferApplication::with(['applicationParcels.parcel'])
                ->lockForUpdate()
                ->findOrFail($application->id);

            if (! $application->isFinalized()) {
                throw new \RuntimeException('Clearance can only be generated for finalized clearance decisions.');
            }

            $allowedDecisionStatuses = [
                LandTransferApplication::STATUS_RELEASED,
                LandTransferApplication::STATUS_DENIED,
                LandTransferApplication::STATUS_APPROVED,
                LandTransferApplication::STATUS_NOT_APPROVED,
            ];

            if (! in_array($application->status, $allowedDecisionStatuses, true)) {
                throw new \RuntimeException('Clearance can only be generated for released/denied decisions.');
            }

            /*
             * Final decision outputs are immutable snapshots. If one already
             * exists for this application, return it exactly as recorded rather
             * than refreshing names, parcel data, timestamps, or decision data.
             */
            $existingClearance = ApplicationClearance::query()
                ->where('land_transfer_application_id', $application->id)
                ->lockForUpdate()
                ->first();

            if ($existingClearance) {
                return $existingClearance;
            }

            $totalArea = '0.0000';
            $parcelSnapshot = [];

            foreach ($application->applicationParcels as $applicationParcel) {
                $parcelArea = (string) $applicationParcel->area_hectares;
                $totalArea = bcadd($totalArea, $parcelArea, 4);

                $linkedParcel = $applicationParcel->parcel;
                $areaSquareMeters = $applicationParcel->area_square_meters
                    ?? $linkedParcel?->area_square_meters
                    ?? (filled($parcelArea) ? bcmul($parcelArea, '10000', 2) : null);

                $parcelSnapshot[] = [
                    'parcel_id' => $applicationParcel->parcel_id,
                    'parcel_code' => $applicationParcel->parcel_code ?? $linkedParcel?->parcel_code,
                    'parcel_number' => $applicationParcel->parcel_code ?? $linkedParcel?->parcel_code,
                    'title_no' => $applicationParcel->title_no ?? $linkedParcel?->title_no,
                    'title_number' => $applicationParcel->title_no ?? $linkedParcel?->title_no,
                    'tax_decl_no' => $applicationParcel->tax_decl_no ?? $linkedParcel?->tax_decl_no,
                    'lot_number' => $applicationParcel->lot_number ?? $linkedParcel?->lot_number,
                    'survey_plan_number' => $applicationParcel->survey_plan_number ?? $linkedParcel?->survey_plan_number,
                    'title_type' => $applicationParcel->title_type ?? $linkedParcel?->title_type,
                    'rod_office' => $applicationParcel->rod_office ?? $linkedParcel?->rod_office,
                    'area_hectares' => $parcelArea,
                    'area_square_meters' => $areaSquareMeters,
                ];
            }

            $reviewOfficer = User::find($application->reviewed_by);

            $reviewOfficerName = $reviewOfficer?->name
                ?? ('User #' . ($application->reviewed_by ?? $userId));

            $decisionYear = ($application->date_of_clearance_release ?? $application->reviewed_at ?? now())->format('Y');
            $pageNumber = max(1, (int) ($application->ltc_page_number ?: 1));

            /*
             * LTC numbers are issued as 1803-YEAR-XXXX (page). Serialize
             * number allocation on PostgreSQL so two releases cannot receive
             * the same annual sequence. Existing final clearances are never
             * renumbered because of the create-once rule above.
             */
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('LOCK TABLE application_clearances IN SHARE ROW EXCLUSIVE MODE');
            }

            $existingSequences = ApplicationClearance::query()
                ->where('clearance_number', 'LIKE', '1803-' . $decisionYear . '-%')
                ->pluck('clearance_number')
                ->map(function ($number) use ($decisionYear): ?int {
                    $pattern = '/^1803-' . preg_quote($decisionYear, '/') . '-(\d+)\s*\(/';

                    return preg_match($pattern, (string) $number, $matches)
                        ? (int) $matches[1]
                        : null;
                })
                ->filter(fn ($sequence) => $sequence !== null);

            $sequence = max(1, ((int) $existingSequences->max()) + 1);

            do {
                $clearanceNumber = sprintf('1803-%s-%04d (%d)', $decisionYear, $sequence, $pageNumber);
                $sequence++;
            } while (ApplicationClearance::where('clearance_number', $clearanceNumber)->exists());

            $clearance = ApplicationClearance::create([
                'land_transfer_application_id' => $application->id,
                'clearance_number' => $clearanceNumber,
                'decision_status' => $application->status,
                'application_code' => $application->application_code,
                'transferor_name' => $application->transferorDisplayName(),
                'transferee_name' => $application->transfereeDisplayName(),
                'municipality' => $application->municipality,
                'barangay' => $application->barangay,
                'total_area_hectares' => $totalArea,
                'parcel_snapshot' => $parcelSnapshot,
                'review_officer_name' => $reviewOfficerName,
                'reviewed_at' => $application->reviewed_at,
                'generated_by' => $userId,
                'generated_at' => now(),
            ]);

            AuditLogger::record(
                'clearance_generated',
                $application,
                $clearance,
                [
                    'clearance_number' => $clearance->clearance_number,
                    'decision_status' => $clearance->decision_status,
                    'total_area_hectares' => $clearance->total_area_hectares,
                    'parcel_count' => count($parcelSnapshot),
                    'scope_note' => 'Immutable final clearance snapshot only. No ownership transfer or registry mutation was performed.',
                ],
                $userId
            );

            return $clearance;
        });
    }
}
