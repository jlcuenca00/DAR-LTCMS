<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ParcelGeometryRevision extends Model
{
    protected $fillable = [
        'parcel_id',
        'geometry_version',
        'geometry_geojson',
        'actor_user_id',
        'source',
    ];

    protected $casts = [
        'geometry_version' => 'integer',
        'geometry_geojson' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Parcel geometry revisions are append-only and cannot be modified.');
        });

        static::deleting(function () {
            throw new LogicException('Parcel geometry revisions are append-only and cannot be deleted.');
        });
    }


    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
