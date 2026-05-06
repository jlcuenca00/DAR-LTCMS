<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcel extends Model
{
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
        'remarks',
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