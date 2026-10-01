<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationClearance extends Model
{
    protected $fillable = [
        'land_transfer_application_id',
        'clearance_number',
        'decision_status',
        'decision_authority',
        'decision_officer_name',
        'decision_date',
        'decision_recorded_by',
        'decision_recorded_at',
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
        'decision_date' => 'date',
        'decision_recorded_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'generated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \LogicException('Final clearance snapshots are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new \LogicException('Final clearance snapshots are immutable and cannot be deleted.');
        });
    }

    public function application()
    {
        return $this->belongsTo(LandTransferApplication::class, 'land_transfer_application_id');
    }

    public function decisionRecordedBy()
    {
        return $this->belongsTo(User::class, 'decision_recorded_by');
    }

    public function generatedByUser()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}