<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landholding extends Model
{
    protected $fillable = [
        'landowner_id',
        'parcel_id',
        'area_hectares',
        'status',
        'date_acquired',
        'date_transferred',
        'source_application_id',
        'remarks',
    ];

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