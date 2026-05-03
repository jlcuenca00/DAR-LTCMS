<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationClearance extends Model
{
    protected $fillable = [
        'land_transfer_application_id',
        'clearance_number',
        'decision_status',
        'application_code',
        'transferor_name',
        'transferee_name',
        'municipality',
        'barangay',
        'total_area_hectares',
        'parcel_snapshot',
        'review_officer_name',
        'reviewed_at',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'parcel_snapshot' => 'array',
        'reviewed_at' => 'datetime',
        'generated_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(LandTransferApplication::class, 'land_transfer_application_id');
    }

    public function generatedByUser()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}