<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use App\Models\Landholding;
use App\Models\LandholdingMutation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class LandRegistryMutationService
{
    public function mutate(LandTransferApplication $application): void
    {
        // 🔒 Idempotency guard
        if ($application->registry_mutated_at !== null) {
            throw new Exception('Registry mutation already executed for this application.');
        }

        DB::transaction(function () use ($application) {

            foreach ($application->applicationParcels as $appParcel) {

                $parcel = $appParcel->parcel;
                $transferArea = $appParcel->area_hectares;

                $transferor = $application->transferor_landowner_id;
                $transferee = $application->transferee_landowner_id;

                // Find transferor holding
                $transferorHolding = Landholding::where('landowner_id', $transferor)
                    ->where('parcel_id', $parcel->id)
                    ->first();

                if (!$transferorHolding) {
                    throw new Exception("Transferor does not own parcel ID {$parcel->id}");
                }

                $transferorBefore = $transferorHolding->area_hectares;

                if ($transferorBefore < $transferArea) {
                    throw new Exception("Transfer area exceeds transferor holding.");
                }

                // Deduct from transferor
                $transferorHolding->area_hectares -= $transferArea;
                $transferorHolding->save();

                $transferorAfter = $transferorHolding->area_hectares;

                // Add to transferee
                $transfereeHolding = Landholding::firstOrCreate(
                    [
                        'landowner_id' => $transferee,
                        'parcel_id' => $parcel->id,
                    ],
                    [
                        'area_hectares' => 0,
                        'status' => 'active',
                    ]
                );

                $transfereeBefore = $transfereeHolding->area_hectares;
                $transfereeHolding->area_hectares += $transferArea;
                $transfereeHolding->source_application_id = $application->id;
                $transfereeHolding->save();
                $transfereeAfter = $transfereeHolding->area_hectares;

                // Create ledger record
                LandholdingMutation::create([
                    'land_transfer_application_id' => $application->id,
                    'parcel_id' => $parcel->id,
                    'transferor_landowner_id' => $transferor,
                    'transferee_landowner_id' => $transferee,
                    'transferred_area_hectares' => $transferArea,
                    'transferor_before_area' => $transferorBefore,
                    'transferor_after_area' => $transferorAfter,
                    'transferee_before_area' => $transfereeBefore,
                    'transferee_after_area' => $transfereeAfter,
                    'mutated_by' => Auth::id(),
                    'mutated_at' => now(),
                ]);
            }

            // Stamp mutation
            $application->update([
                'registry_mutated_at' => now(),
                'registry_mutated_by' => Auth::id(),
            ]);
        });
    }
}