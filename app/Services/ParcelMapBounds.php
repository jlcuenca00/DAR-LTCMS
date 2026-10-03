<?php

namespace App\Services;

class ParcelMapBounds
{
    public const COLUMNS = ['map_min_longitude', 'map_min_latitude', 'map_max_longitude', 'map_max_latitude'];

    // Derived display bounds only: this does not validate survey/legal boundaries.
    public static function fromGeometry(mixed $geometry): ?array
    {
        if (! is_array($geometry) || ($geometry['type'] ?? null) !== 'Polygon'
            || ! is_array($geometry['coordinates'] ?? null) || ! array_is_list($geometry['coordinates'])
            || count($geometry['coordinates']) === 0) {
            return null;
        }

        $west = 180.0;
        $south = 90.0;
        $east = -180.0;
        $north = -90.0;
        foreach ($geometry['coordinates'] as $ring) {
            if (! is_array($ring) || ! array_is_list($ring) || count($ring) < 4 || $ring[0] !== $ring[count($ring) - 1]) {
                return null;
            }
            foreach ($ring as $point) {
                if (! is_array($point) || ! array_is_list($point) || count($point) < 2
                    || ! is_numeric($point[0]) || ! is_numeric($point[1])
                    || ! is_finite((float) $point[0]) || ! is_finite((float) $point[1])
                    || abs((float) $point[0]) > 180 || abs((float) $point[1]) > 90) {
                    return null;
                }
                $west = min($west, (float) $point[0]);
                $south = min($south, (float) $point[1]);
                $east = max($east, (float) $point[0]);
                $north = max($north, (float) $point[1]);
            }
        }

        return array_combine(self::COLUMNS, [$west, $south, $east, $north]);
    }
}
