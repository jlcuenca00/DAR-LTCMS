<?php

namespace Database\Seeders;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\ParcelListing;
use App\Services\AuditLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ParcelListingDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing', 'staging')) {
            throw new RuntimeException('Parcel listing demo records are restricted to local, testing, and staging databases.');
        }

        DB::transaction(function () {
            for ($index = 0; $index < 15; $index++) {
                $sequence = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                $code = 'DEMO-LISTING-'.$sequence;
                $parcel = Parcel::query()->firstOrNew(['parcel_code' => $code]);
                if ($parcel->exists) {
                    $holding = $parcel->landholdings()->first();
                    if ($parcel->remarks !== 'Fictional parcel listing demo'
                        || ! $holding?->parcelListing?->is_demo) {
                        throw new RuntimeException('Demo parcel code is already in use: '.$code);
                    }

                    continue;
                }

                $longitude = round(123.263 + ($index % 5) * 0.004, 6);
                $latitude = round(9.282 + intdiv($index, 5) * 0.005, 6);
                $east = round($longitude + 0.00055 + $index * 0.000025, 6);
                $north = round($latitude + 0.00065, 6);
                $hectares = round(($east - $longitude) * ($north - $latitude) * 1220000, 4);

                $owner = Landowner::create([
                    'first_name' => 'Demo Landowner',
                    'last_name' => $sequence,
                ]);
                $parcel->fill([
                    'municipality' => 'Dumaguete City',
                    'province' => 'Negros Oriental',
                    'area_hectares' => $hectares,
                    'status' => 'active',
                    'remarks' => 'Fictional parcel listing demo',
                    'geometry_geojson' => ['type' => 'Polygon', 'coordinates' => [[
                        [$longitude, $latitude],
                        [$east, $latitude],
                        [$east, $north],
                        [$longitude, $north],
                        [$longitude, $latitude],
                    ]]],
                ])->save();

                $holding = Landholding::create([
                    'parcel_id' => $parcel->id,
                    'landowner_id' => $owner->id,
                    'area_hectares' => $hectares,
                    'status' => Landholding::STATUS_ACTIVE,
                    'remarks' => 'Fictional parcel listing demo',
                ]);
                $listing = $holding->parcelListing()->create([
                    'display_name' => $owner->full_name,
                    'status' => ParcelListing::PUBLISHED,
                    'parcel_revision' => $parcel->record_revision,
                    'holding_revision' => $holding->record_revision,
                    'is_demo' => true,
                ]);
                AuditLogger::record('parcel_listing.demo_seeded', auditable: $listing);
            }
        }, 3);
    }
}
