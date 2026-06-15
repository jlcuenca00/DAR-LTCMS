<?php

namespace App\Services;

use App\Models\ApplicationClearance;
use App\Models\LandTransferApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogger;

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

            $decisionYear = ($application->reviewed_at ?? now())->format('Y');
            $clearanceNumber = sprintf(
                'DAR-CLR-%s-%06d',
                $decisionYear,
                $application->id
            );

                        $clearance = ApplicationClearance::updateOrCreate(
                [
                    'land_transfer_application_id' => $application->id,
                ],
                [
                    'clearance_number' => $clearanceNumber,
                    'decision_status' => $application->status,
                    'application_code' => $application->application_code,
                    'transferor_name' => $application->transferor_name,
                    'transferee_name' => $application->transferee_name,
                    'municipality' => $application->municipality,
                    'barangay' => $application->barangay,
                    'total_area_hectares' => $totalArea,
                    'parcel_snapshot' => $parcelSnapshot,
                    'review_officer_name' => $reviewOfficerName,
                    'reviewed_at' => $application->reviewed_at,
                    'generated_by' => $userId,
                    'generated_at' => now(),
                ]
            );

            AuditLogger::record(
                'clearance_generated',
                $application,
                $clearance,
                [
                    'clearance_number' => $clearance->clearance_number,
                    'decision_status' => $clearance->decision_status,
                    'total_area_hectares' => $clearance->total_area_hectares,
                    'parcel_count' => count($parcelSnapshot),
                ],
                $userId
            );

            return $clearance;
        });
    }
}