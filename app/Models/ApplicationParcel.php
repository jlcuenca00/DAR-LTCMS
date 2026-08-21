<?php

namespace App\Models;

use App\Services\ApplicationPartyShareIntegrityService;
use App\Services\ParcelAreaIntegrityService;
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

    protected static function booted(): void
    {
        static::saving(function (ApplicationParcel $applicationParcel) {
            app(ParcelAreaIntegrityService::class)->assertApplicationParcelArea($applicationParcel);

            if ($applicationParcel->exists && $applicationParcel->land_transfer_application_id) {
                $application = LandTransferApplication::query()->find($applicationParcel->land_transfer_application_id);
                if ($application) {
                    app(ApplicationPartyShareIntegrityService::class)->assertValid($application, $applicationParcel);
                }
            }
        });

        static::deleting(function (ApplicationParcel $applicationParcel) {
            $application = $applicationParcel->application;
            if ($application) {
                app(ApplicationPartyShareIntegrityService::class)
                    ->removeParcelShareReferences($application, (int) $applicationParcel->id);
            }
        });
    }

    public function application()
    {
        return $this->belongsTo(LandTransferApplication::class, 'land_transfer_application_id');
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }
}
