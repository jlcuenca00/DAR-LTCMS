<?php

namespace App\Services;

class EqualAreaAllocationService
{
    /** Allocate in 0.0001 ha units, keeping the final row's rounding remainder. */
    public function allocate(float $area, int $count): array
    {
        if ($count < 1) {
            return [];
        }
        $units = (int) bcmul(number_format(max(0, $area), 4, '.', ''), '10000', 0);
        $baseUnits = intdiv($units, $count);
        $shares = array_fill(0, $count, (float) ($baseUnits / 10000));
        $shares[$count - 1] = (float) (($units - $baseUnits * ($count - 1)) / 10000);
        return $shares;
    }
}
