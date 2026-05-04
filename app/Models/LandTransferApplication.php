<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandTransferApplication extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_NOT_APPROVED = 'not_approved';

    public const FINAL_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_NOT_APPROVED,
    ];

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

    public function isFinalized(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }

    public function isEditable(): bool
    {
        return ! $this->isFinalized();
    }

    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class, 'land_transfer_application_id');
    }

    public function applicationParcels()
    {
        return $this->hasMany(ApplicationParcel::class, 'land_transfer_application_id');
    }

    public function transferorLandowner()
    {
        return $this->belongsTo(Landowner::class, 'transferor_landowner_id');
    }

    public function transfereeLandowner()
    {
        return $this->belongsTo(Landowner::class, 'transferee_landowner_id');
    }

    public function clearance()
    {
        return $this->hasOne(ApplicationClearance::class, 'land_transfer_application_id');
    }
}