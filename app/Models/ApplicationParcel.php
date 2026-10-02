<?php

namespace App\Models;

use App\Services\ApplicationParcelIntegrityService;
use App\Services\ApplicationPartyShareIntegrityService;
use App\Services\ApplicationWorkflowRevisionService;
use App\Services\ParcelAreaIntegrityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ApplicationParcel extends Model
{
    use \App\Models\Concerns\RequiresModelEvents;

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
            if ($applicationParcel->exists && $applicationParcel->isDirty('land_transfer_application_id')) {
                throw ValidationException::withMessages([
                    'application' => 'Persisted application child records cannot be reassigned to another application.',
                ]);
            }

            app(ParcelAreaIntegrityService::class)->assertApplicationParcelArea($applicationParcel);

            $application = $applicationParcel->land_transfer_application_id
                ? LandTransferApplication::query()->find($applicationParcel->land_transfer_application_id)
                : null;

            if ($application && $application->isFinalized()) {
                throw ValidationException::withMessages([
                    'application_parcel' => 'Linked parcel snapshots are immutable after the application is finalized.',
                ]);
            }

            if ($application) {
                app(ApplicationParcelIntegrityService::class)
                    ->assertCurrentWorkflowValid($applicationParcel, $application);
            }

            if ($applicationParcel->exists && $application) {
                app(ApplicationPartyShareIntegrityService::class)->assertValid($application, $applicationParcel);
            }
        });

        static::saved(function (ApplicationParcel $applicationParcel) {
            if ($applicationParcel->land_transfer_application_id) {
                app(ApplicationWorkflowRevisionService::class)
                    ->bump((int) $applicationParcel->land_transfer_application_id);
            }
        });

        static::deleting(function (ApplicationParcel $applicationParcel) {
            $application = $applicationParcel->application;

            if ($application && $application->isFinalized()) {
                throw ValidationException::withMessages([
                    'application_parcel' => 'Linked parcel snapshots are immutable after the application is finalized.',
                ]);
            }

            if ($application) {
                app(ApplicationPartyShareIntegrityService::class)
                    ->removeParcelShareReferences($application, (int) $applicationParcel->id);
            }
        });

        static::deleted(function (ApplicationParcel $applicationParcel) {
            if ($applicationParcel->land_transfer_application_id) {
                app(ApplicationWorkflowRevisionService::class)
                    ->bump((int) $applicationParcel->land_transfer_application_id);
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
