<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class DarLocationService
{
    public function normalize(?string $municipality, ?string $barangay, ?string $province = null): array
    {
        $municipality = $this->clean($municipality);
        $barangay = $this->clean($barangay);
        $province = $this->clean($province);
        $configuredProvince = (string) config('dar_locations.province', 'Negros Oriental');

        if ($province !== null && ! $this->same($province, $configuredProvince)) {
            throw ValidationException::withMessages([
                'province' => 'The province must be '.$configuredProvince.'.',
            ]);
        }

        if ($municipality === null) {
            if ($barangay !== null) {
                throw ValidationException::withMessages([
                    'municipality' => 'Select a municipality or city before selecting a barangay.',
                ]);
            }

            return [
                'municipality' => null,
                'barangay' => null,
                'province' => $province === null ? null : $configuredProvince,
            ];
        }

        $municipalities = (array) config('dar_locations.municipalities', []);
        $canonicalMunicipality = $this->canonicalMatch($municipality, array_keys($municipalities));

        if ($canonicalMunicipality === null) {
            throw ValidationException::withMessages([
                'municipality' => 'Select a valid Negros Oriental municipality or city.',
            ]);
        }

        $canonicalBarangay = null;
        if ($barangay !== null) {
            $canonicalBarangay = $this->canonicalMatch($barangay, (array) $municipalities[$canonicalMunicipality]);

            if ($canonicalBarangay === null) {
                throw ValidationException::withMessages([
                    'barangay' => 'The selected barangay does not belong to '.$canonicalMunicipality.'.',
                ]);
            }
        }

        return [
            'municipality' => $canonicalMunicipality,
            'barangay' => $canonicalBarangay,
            'province' => $configuredProvince,
        ];
    }

    public function inspect(?string $municipality, ?string $barangay, ?string $province = null): array
    {
        try {
            return ['valid' => true, 'normalized' => $this->normalize($municipality, $barangay, $province), 'errors' => []];
        } catch (ValidationException $exception) {
            return ['valid' => false, 'normalized' => null, 'errors' => $exception->errors()];
        }
    }

    private function canonicalMatch(string $value, array $options): ?string
    {
        foreach ($options as $option) {
            if ($this->same($value, (string) $option)) {
                return (string) $option;
            }
        }

        return null;
    }

    private function same(string $left, string $right): bool
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $left))) === mb_strtolower(trim(preg_replace('/\s+/u', ' ', $right)));
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value));

        return $value === '' ? null : $value;
    }
}
