<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationParcel extends Model
{
    protected $table = 'application_parcels';

    protected $fillable = [
        'land_transfer_application_id',
        'parcel_id',
        'area_hectares',
        'area_square_meters',
        'parcel_code',
        'title_no',
        'tax_decl_no',
        'lot_number',
        'survey_plan_number',
        'title_type',
        'rod_office',
    ];

    protected $casts = [
        'area_hectares' => 'decimal:4',
        'area_square_meters' => 'decimal:2',
    ];

    public function application()
    {
        return $this->belongsTo(LandTransferApplication::class, 'land_transfer_application_id');
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }
}
