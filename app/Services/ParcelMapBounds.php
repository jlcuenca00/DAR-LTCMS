<?php

namespace App\Services;

class ParcelMapBounds
{
    public const COLUMNS = ['map_min_longitude', 'map_min_latitude', 'map_max_longitude', 'map_max_latitude'];

    // Derived display bounds only: this does not validate survey/legal boundaries.
    public static function fromGeometry(mixed $geometry): ?array
    {
        if (! is_array($geometry)) {
            return null;
        }
        try {
            (new ParcelGeometryService)->validatePolygon($geometry);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return null;
        }

        $west = 180.0;
        $south = 90.0;
        $east = -180.0;
        $north = -90.0;
        foreach ($geometry['coordinates'] as $ring) {
            foreach ($ring as $point) {
                $west = min($west, (float) $point[0]);
                $south = min($south, (float) $point[1]);
                $east = max($east, (float) $point[0]);
                $north = max($north, (float) $point[1]);
            }
        }

        return array_combine(self::COLUMNS, [$west, $south, $east, $north]);
    }
}
