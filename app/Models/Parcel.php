<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcel extends Model
{
    public const DEFAULT_AGRICULTURAL_STATUS = 'private_agricultural';
        public const AGRICULTURAL_STATUSES = [
        'private_agricultural' => 'Private Agricultural Land',
        'awarded_cloa' => 'Awarded CLOA Land',
        'emancipation_patent' => 'Emancipation Patent Land',
        'carp_covered' => 'CARP-Covered Land',
        'not_yet_determined' => 'Not Yet Determined',
        'non_agricultural' => 'Non-Agricultural / Reference Only',
    ];

    public static function agriculturalStatusOptions(): array
    {
        return self::AGRICULTURAL_STATUSES;
    }

    public static function agriculturalStatusLabel(?string $status): string
    {
        return self::AGRICULTURAL_STATUSES[$status ?: 'not_yet_determined'] ?? self::AGRICULTURAL_STATUSES['not_yet_determined'];
    }

    public function getAgriculturalStatusLabelAttribute(): string
    {
        return self::agriculturalStatusLabel($this->agricultural_status);
    }
protected $fillable = [
        'parcel_code',
        'title_no',
        'tax_decl_no',
        'municipality',
        'barangay',
        'province',
        'area_hectares',
        'geometry_geojson',
        'status',
        'agricultural_status',
        'remarks',
        'reference_photo_path',
    ];

    protected $casts = [
        'geometry_geojson' => 'array',
        'area_hectares' => 'decimal:4',
    ];

    public function landholdings()
    {
        return $this->hasMany(Landholding::class);
    }
    public function legacyRecords()
{
    return $this->hasMany(LegacyRecord::class);
}
public function sourceRecordPackages()
{
    return $this->hasMany(SourceRecordPackage::class);
}
}