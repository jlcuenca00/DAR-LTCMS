<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landowner extends Model
{
    public const STATUS_SINGLE = 'single';
    public const STATUS_MARRIED = 'married';
    public const STATUS_WIDOWED = 'widowed';
    public const STATUS_SEPARATED = 'separated';
    public const STATUS_ANNULLED = 'annulled';
    public const STATUS_JURIDICAL_ENTITY = 'juridical_entity';

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'registered_owner_status',
        'spouse_name',
        'contact_number',
        'address_line',
        'barangay',
        'municipality',
        'province',
        'user_id',
    ];


    public static function registeredOwnerStatusOptions(): array
    {
        return [
            self::STATUS_SINGLE => 'Single',
            self::STATUS_MARRIED => 'Married',
            self::STATUS_WIDOWED => 'Widowed',
            self::STATUS_SEPARATED => 'Separated',
            self::STATUS_ANNULLED => 'Annulled',
            self::STATUS_JURIDICAL_ENTITY => 'Juridical Entity / Organization',
        ];
    }

    public function getRegisteredOwnerStatusLabelAttribute(): string
    {
        return self::registeredOwnerStatusOptions()[$this->registered_owner_status] ?? 'Not specified';
    }

    public function requiresSpouseName(): bool
    {
        return $this->registered_owner_status === self::STATUS_MARRIED;
    }

    public function landholdings()
    {
        return $this->hasMany(Landholding::class);
    }

    public function activeLandholdings()
    {
        return $this->hasMany(Landholding::class)->where('status', Landholding::STATUS_ACTIVE);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sourceRecordPackages()
    {
        return $this->hasMany(SourceRecordPackage::class);
    }

    public function sourceRecords()
    {
        return $this->hasMany(LegacyRecord::class);
    }

    public function transferorApplications()
    {
        return $this->hasMany(LandTransferApplication::class, 'transferor_landowner_id');
    }

    public function transfereeApplications()
    {
        return $this->hasMany(LandTransferApplication::class, 'transferee_landowner_id');
    }

    public function getFullNameAttribute()
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])
            ->filter()
            ->implode(' ');
    }

    public function getCurrentActiveHectaresAttribute(): float
    {
        if (array_key_exists('active_landholding_area_hectares', $this->attributes)) {
            return (float) ($this->attributes['active_landholding_area_hectares'] ?? 0);
        }

        return (float) $this->activeLandholdings()->sum('area_hectares');
    }
}
