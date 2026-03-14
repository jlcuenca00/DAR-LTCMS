<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandTransferApplication extends Model
{
    protected $fillable = [
        'application_code',
        'transferor_name',
        'transferee_name',
        'barangay',
        'municipality',
        'status',
        'encoded_by',
        'reviewed_by',
        'reviewed_at',
        'decision_reason',
        'decision_notes',
        'validated_at',
        'validation_snapshot',

        // 🔐 Registry mutation + landowner links
        'transferor_landowner_id',
        'transferee_landowner_id',
        'registry_mutated_at',
        'registry_mutated_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'validated_at' => 'datetime',
        'registry_mutated_at' => 'datetime',
        'validation_snapshot' => 'array',
    ];

    public function documents()
    {
        return $this->hasMany(\App\Models\ApplicationDocument::class, 'land_transfer_application_id');
    }

    public function applicationParcels()
    {
        return $this->hasMany(\App\Models\ApplicationParcel::class, 'land_transfer_application_id');
    }

    public function transferorLandowner()
    {
        return $this->belongsTo(\App\Models\Landowner::class, 'transferor_landowner_id');
    }

    public function transfereeLandowner()
    {
        return $this->belongsTo(\App\Models\Landowner::class, 'transferee_landowner_id');
    }

    public function clearance()
    {
    return $this->hasOne(\App\Models\ApplicationClearance::class, 'land_transfer_application_id');
    }

}