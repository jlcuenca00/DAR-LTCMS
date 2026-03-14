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

            if (!in_array($application->status, ['approved', 'not_approved'], true)) {
                throw new \RuntimeException('Clearance can only be generated for finalized decisions.');
            }

            $totalArea = '0.0000';
            $parcelSnapshot = [];

            foreach ($application->applicationParcels as $applicationParcel) {
                $parcelArea = (string) $applicationParcel->area_hectares;
                $totalArea = bcadd($totalArea, $parcelArea, 4);

                $parcelSnapshot[] = [
                    'parcel_id' => $applicationParcel->parcel_id,
                    'parcel_number' => $applicationParcel->parcel?->parcel_number,
                    'lot_number' => $applicationParcel->parcel?->lot_number,
                    'title_number' => $applicationParcel->parcel?->title_number,
                    'area_hectares' => $parcelArea,
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

            return ApplicationClearance::updateOrCreate(
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
        });
    }
}