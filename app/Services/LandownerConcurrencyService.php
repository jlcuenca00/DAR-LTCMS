<?php

namespace App\Services;

use App\Models\Landowner;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class LandownerConcurrencyService
{
    /**
     * Lock Landowner rows in deterministic order before reading or mutating
     * hectare-allocation state that is shared across applications.
     *
     * @param  array<int|string|null>  $landownerIds
     * @return Collection<int, Landowner>
     */
    public function lockLandowners(array $landownerIds): Collection
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Landowner concurrency locks require an active database transaction.');
        }

        $ids = collect($landownerIds)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Landowner::query()
            ->whereIn('id', $ids->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    public function lockLandowner(int $landownerId): Landowner
    {
        $landowner = $this->lockLandowners([$landownerId])->get($landownerId);

        if (! $landowner) {
            throw (new ModelNotFoundException)->setModel(Landowner::class, [$landownerId]);
        }

        return $landowner;
    }
}
