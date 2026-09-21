<?php

namespace Database\Seeders\Support;

final class RealisticAgriculturalParcelDataset
{
    /**
     * Fictional demonstration parcels positioned in documented crop-producing
     * barangays around Dumaguete City. Coordinates are reference/demo geometry,
     * not cadastral or legal boundary data.
     *
     * Centers were intentionally offset from known barangay-hall, school, and
     * major-road anchors used during the map review. Each polygon is kept small
     * (sub-2-hectare scale) so it reads like a field parcel rather than a large
     * artificial block spanning roads and neighborhoods.
     */
    public static function parcels(): array
    {
        $rows = [
            ['code' => 'AGRI-DEMO-DGT-001', 'barangay' => 'Balugo',      'lat' => 9.30420, 'lon' => 123.25710, 'ha' => 0.7200, 'rotation' => 12,  'crop' => 'Corn / banana',       'title_type' => 'tct'],
            ['code' => 'AGRI-DEMO-DGT-002', 'barangay' => 'Balugo',      'lat' => 9.30250, 'lon' => 123.25720, 'ha' => 1.1000, 'rotation' => -8,  'crop' => 'Corn / root crops',   'title_type' => 'untitled'],
            ['code' => 'AGRI-DEMO-DGT-003', 'barangay' => 'Candau-ay',   'lat' => 9.31020, 'lon' => 123.25380, 'ha' => 0.8600, 'rotation' => 19,  'crop' => 'Rice / vegetables',   'title_type' => 'tct'],
            ['code' => 'AGRI-DEMO-DGT-004', 'barangay' => 'Candau-ay',   'lat' => 9.30855, 'lon' => 123.25435, 'ha' => 1.2800, 'rotation' => -14, 'crop' => 'Corn / coconut',       'title_type' => 'tct'],
            ['code' => 'AGRI-DEMO-DGT-005', 'barangay' => 'Cadawinonan', 'lat' => 9.30710, 'lon' => 123.26610, 'ha' => 0.6400, 'rotation' => 7,   'crop' => 'Corn / vegetables',   'title_type' => 'cloa'],
            ['code' => 'AGRI-DEMO-DGT-006', 'barangay' => 'Cadawinonan', 'lat' => 9.30535, 'lon' => 123.26540, 'ha' => 0.9300, 'rotation' => -21, 'crop' => 'Banana / coconut',      'title_type' => 'tct'],
            ['code' => 'AGRI-DEMO-DGT-007', 'barangay' => 'Batinguel',   'lat' => 9.31505, 'lon' => 123.27805, 'ha' => 1.2200, 'rotation' => 16,  'crop' => 'Rice / corn',          'title_type' => 'tct'],
            ['code' => 'AGRI-DEMO-DGT-008', 'barangay' => 'Batinguel',   'lat' => 9.31660, 'lon' => 123.27920, 'ha' => 0.5800, 'rotation' => -5,  'crop' => 'Vegetables / banana', 'title_type' => 'ep'],
            ['code' => 'AGRI-DEMO-DGT-009', 'barangay' => 'Camanjac',    'lat' => 9.32935, 'lon' => 123.27655, 'ha' => 0.7600, 'rotation' => 23,  'crop' => 'Rice / vegetables',   'title_type' => 'tct'],
            ['code' => 'AGRI-DEMO-DGT-010', 'barangay' => 'Camanjac',    'lat' => 9.32780, 'lon' => 123.27800, 'ha' => 1.1500, 'rotation' => -17, 'crop' => 'Corn / coconut',       'title_type' => 'untitled'],
            ['code' => 'AGRI-DEMO-DGT-011', 'barangay' => 'Junob',       'lat' => 9.29020, 'lon' => 123.28720, 'ha' => 0.6700, 'rotation' => 10,  'crop' => 'Rice / banana',        'title_type' => 'tct'],
            ['code' => 'AGRI-DEMO-DGT-012', 'barangay' => 'Junob',       'lat' => 9.28855, 'lon' => 123.28860, 'ha' => 1.3600, 'rotation' => -11, 'crop' => 'Corn / root crops',    'title_type' => 'tct'],
        ];

        return array_map(function (array $row, int $index): array {
            $number = $index + 1;
            $titleType = $row['title_type'];
            $hasTitle = $titleType !== 'untitled';

            return [
                'parcel_code' => $row['code'],
                'title_no' => $hasTitle ? sprintf('T-2026-DGT-%04d', $number) : null,
                'tax_decl_no' => sprintf('TD-2026-DGT-%04d', $number),
                'lot_number' => sprintf('Lot %d-A', 300 + $number),
                'survey_plan_number' => sprintf('PSD-07-2026-%03d', $number),
                'title_type' => $titleType,
                'rod_office' => $hasTitle ? 'Negros Oriental Province' : null,
                'municipality' => 'Dumaguete City',
                'barangay' => $row['barangay'],
                'province' => 'Negros Oriental',
                'area_hectares' => $row['ha'],
                'area_square_meters' => round($row['ha'] * 10000, 2),
                'geometry_geojson' => self::geometryFor(
                    $row['lat'],
                    $row['lon'],
                    $row['ha'],
                    $row['rotation'],
                    $number
                ),
                'status' => 'active',
                'agricultural_status' => match ($titleType) {
                    'cloa' => 'awarded_cloa',
                    'ep' => 'emancipation_patent',
                    default => 'private_agricultural',
                },
                'is_flagged' => false,
                'remarks' => 'Fictional agricultural demo parcel for DAR-LTCMS map, workflow, privacy, and reporting tests. Geometry is reference-only and is not a legal cadastral boundary.',
                'crop_or_land_use' => $row['crop'],
                'demo_center' => ['lat' => $row['lat'], 'lon' => $row['lon']],
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * Build a compact irregular six-point field polygon with an approximate
     * ground area equal to the requested hectares.
     */
    private static function geometryFor(
        float $latitude,
        float $longitude,
        float $hectares,
        float $rotationDegrees,
        int $variant
    ): array {
        $shapes = [
            [[-0.55, -0.42], [0.35, -0.52], [0.58, -0.10], [0.46, 0.47], [-0.18, 0.55], [-0.60, 0.14]],
            [[-0.62, -0.28], [0.15, -0.55], [0.57, -0.30], [0.52, 0.38], [0.05, 0.56], [-0.50, 0.35]],
            [[-0.46, -0.55], [0.44, -0.38], [0.61, 0.18], [0.28, 0.56], [-0.37, 0.48], [-0.58, -0.02]],
        ];

        $points = $shapes[($variant - 1) % count($shapes)];
        $normalizedArea = abs(self::shoelaceArea($points));
        $targetSquareMeters = $hectares * 10000;
        $scale = sqrt($targetSquareMeters / max($normalizedArea, 0.000001));
        $angle = deg2rad($rotationDegrees);
        $cos = cos($angle);
        $sin = sin($angle);

        $metersPerDegreeLat = 110574.0;
        $metersPerDegreeLon = 111320.0 * cos(deg2rad($latitude));

        $ring = [];
        foreach ($points as [$x, $y]) {
            $mx = $x * $scale;
            $my = $y * $scale;
            $rx = ($mx * $cos) - ($my * $sin);
            $ry = ($mx * $sin) + ($my * $cos);

            $ring[] = [
                round($longitude + ($rx / $metersPerDegreeLon), 7),
                round($latitude + ($ry / $metersPerDegreeLat), 7),
            ];
        }

        $ring[] = $ring[0];

        return [
            'type' => 'Polygon',
            'coordinates' => [$ring],
        ];
    }

    private static function shoelaceArea(array $points): float
    {
        $sum = 0.0;
        $count = count($points);

        for ($i = 0; $i < $count; $i++) {
            [$x1, $y1] = $points[$i];
            [$x2, $y2] = $points[($i + 1) % $count];
            $sum += ($x1 * $y2) - ($x2 * $y1);
        }

        return $sum / 2.0;
    }
}
