<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParcelListing extends Model
{
    public const PENDING = 'pending';
    public const PUBLISHED = 'published';
    public const WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'landholding_id', 'display_name', 'mobile', 'consented_at', 'status',
        'parcel_revision', 'holding_revision', 'is_demo',
    ];

    protected $casts = ['is_demo' => 'boolean'];

    public function landholding(): BelongsTo
    {
        return $this->belongsTo(Landholding::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereHas('landholding', fn (Builder $holding) => $holding
            ->active()
            ->whereNotExists(fn ($others) => $others->selectRaw('1')
                ->from('landholdings as other_holdings')
                ->whereColumn('other_holdings.parcel_id', 'landholdings.parcel_id')
                ->whereColumn('other_holdings.id', '<>', 'landholdings.id')
                ->where('other_holdings.status', Landholding::STATUS_ACTIVE))
            ->whereColumn('landholdings.record_revision', 'parcel_listings.holding_revision')
            ->whereHas('parcel', fn (Builder $parcel) => $parcel
                ->where('status', 'active')
                ->where('is_flagged', false)
                ->whereColumn('parcels.record_revision', 'parcel_listings.parcel_revision')));
    }

    public static function publicGeometry(Parcel $parcel): ?array
    {
        $geometry = $parcel->geometry_geojson;
        if (($geometry['type'] ?? null) !== 'Polygon'
            || empty($geometry['coordinates']) || $parcel->geometryReferenceLabel() !== 'Geometry stored') {
            return null;
        }

        return ['type' => $geometry['type'], 'coordinates' => $geometry['coordinates']];
    }
}
