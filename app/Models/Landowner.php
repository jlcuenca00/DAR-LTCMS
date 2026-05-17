<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landowner extends Model
{
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'contact_number',
        'address_line',
        'barangay',
        'municipality',
        'province',
        'user_id',
    ];

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
