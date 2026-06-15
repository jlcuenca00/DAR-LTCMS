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
        'non_agricultural' => 'Non-Agricultural / Not covered by DAR Clearance',
    ];

    public const TITLE_TYPES = [
        'oct' => 'OCT - Original Certificate of Title',
        'tct' => 'TCT - Transfer Certificate of Title',
        'cloa' => 'CLOA - Certificate of Land Ownership Award',
        'ep' => 'EP - Emancipation Patent',
    ];

    public const ROD_OFFICES = [
        'Canlaon City' => 'Canlaon City',
        'Bais City' => 'Bais City',
        'Negros Oriental Province' => 'Negros Oriental Province',
    ];

    protected $fillable = [
        'parcel_code',
        'title_no',
        'tax_decl_no',
        'lot_number',
        'survey_plan_number',
        'title_type',
        'rod_office',
        'municipality',
        'barangay',
        'province',
        'area_hectares',
        'area_square_meters',
        'geometry_geojson',
        'status',
        'agricultural_status',
        'remarks',
        'reference_photo_path',
    ];

    protected $casts = [
        'geometry_geojson' => 'array',
        'area_hectares' => 'decimal:4',
        'area_square_meters' => 'decimal:2',
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

    public static function titleTypeOptions(): array
    {
        return self::TITLE_TYPES;
    }

    public static function titleTypeLabel(?string $type): string
    {
        return self::TITLE_TYPES[$type ?: ''] ?? 'Not specified';
    }

    public function getTitleTypeLabelAttribute(): string
    {
        return self::titleTypeLabel($this->title_type);
    }

    public static function rodOfficeOptions(): array
    {
        return self::ROD_OFFICES;
    }

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
