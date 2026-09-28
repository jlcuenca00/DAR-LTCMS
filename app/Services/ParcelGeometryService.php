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

        if (! is_array($rings) || $rings === []) {
            $this->invalidPolygon($field);
        }

        foreach ($rings as $ring) {
            if (! is_array($ring) || count($ring) < 4) {
                $this->invalidPolygon($field);
            }

            foreach ($ring as $position) {
                if (! is_array($position)
                    || ! array_key_exists(0, $position)
                    || ! array_key_exists(1, $position)) {
                    $this->invalidPolygon($field);
                }

                $longitude = $position[0];
                $latitude = $position[1];

                if (! is_numeric($longitude) || ! is_numeric($latitude)) {
                    $this->invalidPolygon($field);
                }

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

            if ((float) $first[0] !== (float) $last[0]
                || (float) $first[1] !== (float) $last[1]) {
                throw ValidationException::withMessages([
                    $field => 'Each GeoJSON Polygon ring must be closed by repeating its first coordinate as the last coordinate.',
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
    ): ?ParcelGeometryRevision {
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

        if ($parcel->geometry_geojson === null) {
            return null;
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
            $field => 'The GeoJSON Polygon must contain closed coordinate rings with at least four valid longitude/latitude positions.',
        ]);
    }
}
