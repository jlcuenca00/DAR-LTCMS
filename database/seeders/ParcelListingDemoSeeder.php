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
    private const EXAMPLES = [
        ['Mariel', 'Abellanosa', 123.2534, 9.2833, 650, 1050],
        ['Rafael', 'Villamor', 123.2618, 9.2872, 980, 720],
        ['Elena', 'Montero', 123.2705, 9.2811, 730, 890],
        ['Celia', 'Villarin', 123.2743, 9.2962, 1140, 630],
        ['Leonardo', 'Cabrera', 123.2665, 9.3003, 800, 1150],
        ['Teresa', 'Almendras', 123.2777, 9.3096, 560, 870],
        ['Victor', 'Dalisay', 123.2838, 9.2998, 1090, 810],
        ['Lucia', 'Rosales', 123.2805, 9.2828, 750, 550],
        ['Nestor', 'Labrador', 123.2579, 9.2927, 1320, 830],
        ['Isabel', 'Carandang', 123.2691, 9.2914, 690, 970],
        ['Felipe', 'Mercado', 123.2657, 9.2764, 720, 1240],
        ['Amelia', 'Navarro', 123.2773, 9.2898, 1020, 590],
        ['Gabriel', 'Aguilar', 123.2874, 9.2914, 890, 720],
        ['Lourdes', 'Mendoza', 123.2735, 9.3048, 610, 1280],
        ['Daniel', 'Alcantara', 123.2612, 9.3073, 1110, 680],
    ];

    public function run(bool $allowProduction = false, bool $refreshExamples = false): void
    {
        if (! app()->environment('local', 'testing', 'staging') && ! ($allowProduction && app()->isProduction())) {
            throw new RuntimeException('Production demo seeding requires the dedicated data migration.');
        }

        DB::transaction(function () use ($allowProduction, $refreshExamples) {
            for ($index = 0; $index < 15; $index++) {
                $sequence = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                $code = 'DEMO-LISTING-'.$sequence;
                $parcel = Parcel::query()->firstOrNew(['parcel_code' => $code]);
                $holding = null;
                if ($parcel->exists) {
                    $holding = $parcel->landholdings()->first();
                    if ($parcel->remarks !== 'Fictional parcel listing demo'
                        || ! $holding?->parcelListing?->is_demo) {
                        throw new RuntimeException('Demo parcel code is already in use: '.$code);
                    }

                    if ($allowProduction && $holding->parcelListing->status === ParcelListing::WITHDRAWN) {
                        $holding->parcelListing->update(['status' => ParcelListing::PUBLISHED]);
                        AuditLogger::record('parcel_listing.demo_republished', auditable: $holding->parcelListing);
                    }

                    if (! $refreshExamples || $holding->landowner->first_name !== 'Demo Landowner'
                        || $holding->landowner->last_name !== $sequence
                        || $holding->landowner->user_id !== null
                        || $holding->remarks !== 'Fictional parcel listing demo') {
                        continue;
                    }
                    if ((float) $holding->area_hectares > 0.01) {
                        $holding->update(['area_hectares' => 0.01]);
                    }
                }

                [$name, $surname, $longitude, $latitude, $width, $height] = self::EXAMPLES[$index];
                $offsets = $index % 2 === 0
                    ? [[0, 0.15], [0.72, 0], [1, 0.38], [0.82, 0.95], [0.23, 1]]
                    : [[0.08, 0], [0.92, 0.12], [1, 0.55], [0.65, 0.57], [0.53, 1], [0, 0.82]];
                $twiceArea = 0;
                foreach ($offsets as $pointIndex => [$x, $y]) {
                    [$nextX, $nextY] = $offsets[($pointIndex + 1) % count($offsets)];
                    $twiceArea += $x * $nextY - $nextX * $y;
                }
                $hectares = round($width * $height * 0.00000122 * abs($twiceArea) / 2, 4);
                $ring = array_map(fn ($point) => [
                    round($longitude + $width * $point[0] / 1000000, 6),
                    round($latitude + $height * $point[1] / 1000000, 6),
                ], $offsets);
                $ring[] = $ring[0];

                $owner = $holding?->landowner ?? new Landowner;
                $owner->fill(['first_name' => $name, 'last_name' => $surname])->save();
                $parcel->fill([
                    'municipality' => 'Dumaguete City',
                    'province' => 'Negros Oriental',
                    'area_hectares' => $hectares,
                    'status' => 'active',
                    'remarks' => 'Fictional parcel listing demo',
                    'geometry_geojson' => ['type' => 'Polygon', 'coordinates' => [$ring]],
                ])->save();

                $holding ??= new Landholding;
                $holding->fill([
                    'parcel_id' => $parcel->id,
                    'landowner_id' => $owner->id,
                    'area_hectares' => $hectares,
                    'status' => Landholding::STATUS_ACTIVE,
                    'remarks' => 'Fictional parcel listing demo',
                ])->save();
                $listing = $holding->parcelListing()->firstOrNew();
                $listing->fill([
                    'display_name' => $owner->full_name,
                    'status' => $listing->status ?? ParcelListing::PUBLISHED,
                    'parcel_revision' => $parcel->refresh()->record_revision,
                    'holding_revision' => $holding->refresh()->record_revision,
                    'is_demo' => true,
                ])->save();
                AuditLogger::record('parcel_listing.demo_seeded', auditable: $listing);
            }
        }, 3);
    }
}
