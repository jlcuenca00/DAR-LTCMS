<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandholdingMutation extends Model
{
    protected $fillable = [
        'land_transfer_application_id',
        'parcel_id',
        'transferor_landowner_id',
        'transferee_landowner_id',
        'transferred_area_hectares',
        'transferor_before_area',
        'transferor_after_area',
        'transferee_before_area',
        'transferee_after_area',
        'mutated_by',
        'mutated_at',
    ];

    protected $casts = [
        'mutated_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(LandTransferApplication::class, 'land_transfer_application_id');
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function transferor()
    {
        return $this->belongsTo(Landowner::class, 'transferor_landowner_id');
    }

    public function transferee()
    {
        return $this->belongsTo(Landowner::class, 'transferee_landowner_id');
    }

    public function mutatedBy()
    {
        return $this->belongsTo(User::class, 'mutated_by');
    }
}