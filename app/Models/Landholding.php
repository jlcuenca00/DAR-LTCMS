<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Landholding extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_HISTORICAL = 'historical';
    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_HISTORICAL,
        self::STATUS_TRANSFERRED,
    ];

    protected $fillable = [
        'landowner_id',
        'parcel_id',
        'area_hectares',
        'status',
        'date_acquired',
        'date_transferred',
        'source_application_id',
        'source_reference_number',
        'remarks',
    ];

    protected $casts = [
        'area_hectares' => 'decimal:4',
        'date_acquired' => 'date',
        'date_transferred' => 'date',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function landowner()
    {
        return $this->belongsTo(Landowner::class);
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function sourceApplication()
    {
        return $this->belongsTo(LandTransferApplication::class, 'source_application_id');
    }
}
