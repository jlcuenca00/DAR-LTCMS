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
}