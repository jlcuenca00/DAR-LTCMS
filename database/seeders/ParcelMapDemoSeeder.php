<?php

namespace Database\Seeders;

use App\Models\Parcel;
use Database\Seeders\Support\RealisticAgriculturalParcelDataset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ParcelMapDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RealisticAgriculturalParcelDataset::parcels() as $parcelData) {
            Parcel::updateOrCreate(
                ['parcel_code' => $parcelData['parcel_code']],
                Arr::except($parcelData, ['crop_or_land_use', 'demo_center'])
            );
        }
    }
}
