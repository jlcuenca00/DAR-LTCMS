<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
