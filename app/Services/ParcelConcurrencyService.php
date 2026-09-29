<?php

namespace App\Services;

use App\Models\Parcel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class ParcelConcurrencyService
{
    /**
     * Lock shared Parcel rows in a deterministic order before validating or
     * mutating allocation-sensitive state.
     *
     * @param  array<int|string|null>  $parcelIds
     * @return Collection<int, Parcel>
     */
    public function lockParcels(array $parcelIds): Collection
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Parcel concurrency locks require an active database transaction.');
        }

        $ids = collect($parcelIds)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Parcel::query()
            ->whereIn('id', $ids->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    public function lockParcel(int $parcelId): Parcel
    {
        return $this->lockParcels([$parcelId])->get($parcelId)
            ?? Parcel::query()->findOrFail($parcelId);
    }
}
