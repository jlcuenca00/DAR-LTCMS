<?php

namespace App\Services;

use App\Models\Parcel;
use App\Models\ParcelGeometryRevision;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ParcelGeometryService
{
    public function decodePolygon(?string $value, bool $required = false, string $field = 'geometry_geojson'): ?array
    {
        if (! filled($value)) {
            if ($required) {
                throw ValidationException::withMessages([
                    $field => 'Parcel geometry is required.',
                ]);
            }

            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => 'The geometry must be valid GeoJSON.',
            ]);
        }

        $this->validatePolygon($decoded, $field);

        return $decoded;
    }

    public function validatePolygon(array $geometry, string $field = 'geometry_geojson'): void
    {
        if (($geometry['type'] ?? null) !== 'Polygon') {
            throw ValidationException::withMessages([
                $field => 'Only GeoJSON Polygon geometry is supported for parcel records.',
            ]);
        }

        $rings = $geometry['coordinates'] ?? null;

        if (! is_array($rings) || ! array_is_list($rings) || $rings === []) {
            $this->invalidPolygon($field);
        }

        foreach ($rings as $ring) {
            if (! is_array($ring) || ! array_is_list($ring) || count($ring) < 4) {
                $this->invalidPolygon($field);
            }

            $distinct = [];
            $dimension = is_array($ring[0] ?? null) ? count($ring[0]) : 0;
            foreach ($ring as $position) {
                if (! is_array($position) || ! array_is_list($position)
                    || ! in_array(count($position), [2, 3], true) || count($position) !== $dimension) {
                    $this->invalidPolygon($field);
                }

                $longitude = $position[0];
                $latitude = $position[1];

                if ((! is_int($longitude) && ! is_float($longitude)) || (! is_int($latitude) && ! is_float($latitude))) {
                    $this->invalidPolygon($field);
                }

                foreach ($position as $ordinate) {
                    if ((! is_int($ordinate) && ! is_float($ordinate)) || ! is_finite((float) $ordinate)) {
                        $this->invalidPolygon($field);
                    }
                }
                $distinct[json_encode([(float) $longitude, (float) $latitude])] = true;
                $longitude = (float) $longitude;
                $latitude = (float) $latitude;

                if (! is_finite($longitude)
                    || ! is_finite($latitude)
                    || $longitude < -180
                    || $longitude > 180
                    || $latitude < -90
                    || $latitude > 90) {
                    $this->invalidPolygon($field);
                }
            }

            $first = $ring[0];
            $last = $ring[count($ring) - 1];

            if (array_map('floatval', $first) !== array_map('floatval', $last)) {
                throw ValidationException::withMessages([
                    $field => 'Each GeoJSON Polygon ring must be closed by repeating its first coordinate as the last coordinate.',
                ]);
            }

            // Translation avoids cancellation from large geographic coordinates.
            // This is a structural check, not a survey/topology certification.
            $twiceArea = 0.0;
            for ($i = 0; $i < count($ring) - 1; $i++) {
                $twiceArea += ((float) $ring[$i][0] - (float) $first[0]) * ((float) $ring[$i + 1][1] - (float) $first[1])
                    - ((float) $ring[$i + 1][0] - (float) $first[0]) * ((float) $ring[$i][1] - (float) $first[1]);
            }
            if (count($distinct) < 3 || abs($twiceArea) <= 1.0e-14) {
                throw ValidationException::withMessages([
                    $field => 'Each polygon ring needs at least three distinct vertices and a non-zero area.',
                ]);
            }
        }
    }

    public function recordRevision(
        Parcel $parcel,
        ?array $previousGeometry,
        int $previousVersion,
        ?User $actor,
        string $source
    ): ParcelGeometryRevision {
        if ($previousGeometry !== null) {
            ParcelGeometryRevision::query()->firstOrCreate(
                [
                    'parcel_id' => $parcel->id,
                    'geometry_version' => $previousVersion,
                ],
                [
                    'geometry_geojson' => $previousGeometry,
                    'actor_user_id' => null,
                    'source' => 'baseline_snapshot',
                ]
            );
        }

        return ParcelGeometryRevision::query()->firstOrCreate(
            [
                'parcel_id' => $parcel->id,
                'geometry_version' => (int) $parcel->geometry_version,
            ],
            [
                'geometry_geojson' => $parcel->geometry_geojson,
                'actor_user_id' => $actor?->id,
                'source' => $source,
            ]
        );
    }

    private function invalidPolygon(string $field): never
    {
        throw ValidationException::withMessages([
            $field => 'The GeoJSON Polygon must contain closed coordinate rings with at least four valid numeric longitude/latitude positions in coordinate lists.',
        ]);
    }
}
