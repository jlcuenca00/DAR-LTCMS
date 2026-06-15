<?php

namespace Database\Seeders;

use App\Models\Parcel;
use Illuminate\Database\Seeder;

class ParcelMapDemoSeeder extends Seeder
{
    public function run(): void
    {
        $demoParcels = [
            [
                'parcel_code' => 'DEMO-DGT-001',
                'title_no' => 'T-102938',
                'tax_decl_no' => 'TD-2026-001',
                'municipality' => 'Dumaguete City',
                'barangay' => 'Calindagan',
                'province' => 'Negros Oriental',
                'area_hectares' => 1.2500,
                'status' => 'active',
                'remarks' => 'Demo mapped parcel for parcel map viewer testing.',
                'geometry_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [123.2865, 9.3185],
                        [123.2920, 9.3182],
                        [123.2924, 9.3134],
                        [123.2870, 9.3129],
                        [123.2865, 9.3185],
                    ]],
                ],
            ],
            [
                'parcel_code' => 'DEMO-DGT-002',
                'title_no' => 'T-564738',
                'tax_decl_no' => 'TD-2026-002',
                'municipality' => 'Dumaguete City',
                'barangay' => 'Bantayan',
                'province' => 'Negros Oriental',
                'area_hectares' => 2.4000,
                'status' => \App\Models\LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                'remarks' => 'Demo mapped parcel for parcel map viewer testing.',
                'geometry_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [123.2990, 9.3278],
                        [123.3046, 9.3270],
                        [123.3040, 9.3222],
                        [123.2982, 9.3226],
                        [123.2990, 9.3278],
                    ]],
                ],
            ],
            [
                'parcel_code' => 'DEMO-SIB-001',
                'title_no' => 'T-778899',
                'tax_decl_no' => 'TD-2026-003',
                'municipality' => 'Sibulan',
                'barangay' => 'Boloc-boloc',
                'province' => 'Negros Oriental',
                'area_hectares' => 3.1000,
                'status' => 'linked_application',
                'remarks' => 'Demo mapped parcel for parcel map viewer testing.',
                'geometry_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [123.2780, 9.3562],
                        [123.2844, 9.3560],
                        [123.2851, 9.3507],
                        [123.2785, 9.3503],
                        [123.2780, 9.3562],
                    ]],
                ],
            ],
            [
                'parcel_code' => 'DEMO-BAC-001',
                'title_no' => 'T-334455',
                'tax_decl_no' => 'TD-2026-004',
                'municipality' => 'Bacong',
                'barangay' => 'San Miguel',
                'province' => 'Negros Oriental',
                'area_hectares' => 0.9500,
                'status' => 'flagged',
                'remarks' => 'Demo mapped parcel for parcel map viewer testing.',
                'geometry_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [123.2850, 9.2505],
                        [123.2918, 9.2501],
                        [123.2912, 9.2445],
                        [123.2842, 9.2450],
                        [123.2850, 9.2505],
                    ]],
                ],
            ],
        ];

        foreach ($demoParcels as $parcelData) {
            Parcel::updateOrCreate(
                ['parcel_code' => $parcelData['parcel_code']],
                $parcelData
            );
        }
    }
}