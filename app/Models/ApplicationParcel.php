<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationParcel extends Model
{
    protected $table = 'application_parcels';

    protected $fillable = [
        'land_transfer_application_id',
        'parcel_id',
        'area_hectares',
    ];

    public function application()
    {
        return $this->belongsTo(LandTransferApplication::class, 'land_transfer_application_id');
    }

    public function parcel()
{
    return $this->belongsTo(\App\Models\Parcel::class);
}
}