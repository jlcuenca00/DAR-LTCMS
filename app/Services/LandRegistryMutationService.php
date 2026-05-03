<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use App\Models\Landholding;
use App\Models\LandholdingMutation;
use Illuminate\Support\Facades\DB;

class LandRegistryMutationService
{
    public function mutate(LandTransferApplication $application, int $userId): void
    {
        DB::transaction(function () use ($application, $userId) {

            // Lock the application row to prevent double approval races
            $application = LandTransferApplication::where('id', $application->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency guard (must be inside the lock)
            if ($application->registry_mutated_at) {
                return;
            }

            $parcels = $application->applicationParcels()->get();

            foreach ($parcels as $parcel) {
                $transferArea = $parcel->area_hectares;

                $transferorHolding = Landholding::where([
                    'landowner_id' => $application->transferor_landowner_id,
                    'parcel_id' => $parcel->parcel_id,
                    'status' => 'active',
                ])->lockForUpdate()->first();

                if (!$transferorHolding) {
                    throw new \Exception("Transferor holding not found for parcel {$parcel->parcel_id}");
                }

                // Check sufficient area
                if (bccomp($transferorHolding->area_hectares, $transferArea, 4) < 0) {
                    throw new \Exception('Transfer area exceeds transferor holding.');
                }

                $beforeArea = $transferorHolding->area_hectares;
                $afterArea = bcsub($beforeArea, $transferArea, 4);

                $transferorHolding->area_hectares = $afterArea;

                // Policy: if 0 area → inactive
                if (bccomp($afterArea, '0.0000', 4) === 0) {
                    $transferorHolding->status = 'inactive';
                    $transferorHolding->date_transferred = now();
                }

                $transferorHolding->save();

                // Create / update transferee holding
                $transfereeHolding = Landholding::firstOrCreate(
                    [
                        'landowner_id' => $application->transferee_landowner_id,
                        'parcel_id' => $parcel->parcel_id,
                    ],
                    [
                        'area_hectares' => '0.0000',
                        'status' => 'active',
                        'source_application_id' => $application->id,
                    ]
                );

                $beforeTransferee = $transfereeHolding->area_hectares;

                $transfereeHolding->area_hectares = bcadd(
                    $transfereeHolding->area_hectares,
                    $transferArea,
                    4
                );

                $transfereeHolding->status = 'active';

                // Preserve source trace if missing
                if (!$transfereeHolding->source_application_id) {
                    $transfereeHolding->source_application_id = $application->id;
                }

                $transfereeHolding->save();

                // Mutation ledger
                LandholdingMutation::create([
                    'land_transfer_application_id' => $application->id,
                    'parcel_id' => $parcel->parcel_id,
                    'transferor_landowner_id' => $application->transferor_landowner_id,
                    'transferee_landowner_id' => $application->transferee_landowner_id,
                    'transferred_area_hectares' => $transferArea,
                    'transferor_before_area' => $beforeArea,
                    'transferor_after_area' => $afterArea,
                    'transferee_before_area' => $beforeTransferee,
                    'transferee_after_area' => $transfereeHolding->area_hectares,
                    'mutated_by' => $userId,
                    'mutated_at' => now(),
                ]);
            }

            // Mark registry mutated
            $application->update([
                'registry_mutated_at' => now(),
                'registry_mutated_by' => $userId,
            ]);
        });
    }
}