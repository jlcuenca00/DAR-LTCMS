<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParcelGeometryEditSession extends Model
{
    protected $fillable = [
        'parcel_id',
        'user_id',
        'session_token',
        'base_geometry_version',
        'last_seen_at',
    ];

    protected $casts = [
        'base_geometry_version' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
