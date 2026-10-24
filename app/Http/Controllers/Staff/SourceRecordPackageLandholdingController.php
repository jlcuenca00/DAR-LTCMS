<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\LegacyRecord;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use App\Services\AuditLogger;
use App\Services\LandownerConcurrencyService;
use App\Services\LandholdingAreaValidationService;
use App\Services\ParcelConcurrencyService;
use App\Services\RecordEditRevisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SourceRecordPackageLandholdingController extends Controller
{
    public function review(SourceRecordPackage $sourceRecordPackage)
    {
        $sourceRecordPackage->load(['parcel', 'landowner', 'landholding']);

        $hasHoldingEvidence = $sourceRecordPackage->records()
            ->where('record_type', LegacyRecord::TYPE_LANDHOLDING)
            ->exists();

        $existingPair = $sourceRecordPackage->parcel_id && $sourceRecordPackage->landowner_id
            ? Landholding::query()
                ->where('landowner_id', $sourceRecordPackage->landowner_id)
                ->where('parcel_id', $sourceRecordPackage->parcel_id)
                ->first()
            : null;

        $allocatedArea = $sourceRecordPackage->parcel_id
            ? (float) Landholding::query()
                ->where('parcel_id', $sourceRecordPackage->parcel_id)
                ->where('status', Landholding::STATUS_ACTIVE)
                ->sum('area_hectares')
            : 0.0;

        $overlappingExposure = $sourceRecordPackage->parcel && $sourceRecordPackage->landowner
            ? $this->hasIncomingClearanceExposure($sourceRecordPackage->landowner, $sourceRecordPackage->parcel)
            : false;

        $eligible = $hasHoldingEvidence
            && $sourceRecordPackage->parcel !== null
            && $sourceRecordPackage->landowner !== null
            && $sourceRecordPackage->landholding === null
            && $existingPair === null
            && in_array($sourceRecordPackage->source_record_scope, [
                LegacyRecord::SOURCE_SCOPE_CURRENT_ACTIVE,
                LegacyRecord::SOURCE_SCOPE_HISTORICAL,
            ], true);

        return view('staff.source-record-packages.landholding-review', [
            'package' => $sourceRecordPackage,
            'hasHoldingEvidence' => $hasHoldingEvidence,
            'existingPair' => $existingPair,
            'allocatedArea' => $allocatedArea,
            'overlappingExposure' => $overlappingExposure,
            'eligible' => $eligible,
            'proposedStatus' => $sourceRecordPackage->source_record_scope === LegacyRecord::SOURCE_SCOPE_CURRENT_ACTIVE
                ? Landholding::STATUS_ACTIVE
                : Landholding::STATUS_HISTORICAL,
        ]);
    }

    public function store(Request $request, SourceRecordPackage $sourceRecordPackage)
    {
        $validated = $request->validate([
            'expected_record_revision' => ['required', 'integer', 'min:1'],
            'area_hectares' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'date_acquired' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'verified_source' => ['required', 'accepted'],
        ]);

        DB::transaction(function () use ($sourceRecordPackage, $validated, $request): void {
            $package = SourceRecordPackage::query()
                ->whereKey($sourceRecordPackage->id)
                ->lockForUpdate()
                ->firstOrFail();

            app(RecordEditRevisionService::class)->assertExpected(
                $package,
                (int) $validated['expected_record_revision']
            );

            if (! $package->parcel_id || ! $package->landowner_id) {
                throw ValidationException::withMessages([
                    'conversion' => 'Link the source package to its verified Landowner and Parcel records first.',
                ]);
            }

            if (! $package->records()->where('record_type', LegacyRecord::TYPE_LANDHOLDING)->exists()) {
                throw ValidationException::withMessages([
                    'conversion' => 'This package has no landholding source record. Review its source sections before creating a Landholding.',
                ]);
            }

            if (! in_array($package->source_record_scope, [
                LegacyRecord::SOURCE_SCOPE_CURRENT_ACTIVE,
                LegacyRecord::SOURCE_SCOPE_HISTORICAL,
            ], true)) {
                throw ValidationException::withMessages([
                    'conversion' => 'Reference-only source packages cannot establish a master Landholding record.',
                ]);
            }

            $landowner = app(LandownerConcurrencyService::class)->lockLandowner((int) $package->landowner_id);
            $parcel = app(ParcelConcurrencyService::class)->lockParcel((int) $package->parcel_id);

            if ($package->landholding()->exists()) {
                throw ValidationException::withMessages([
                    'conversion' => 'This source package has already created a Landholding record.',
                ]);
            }

            if (Landholding::query()
                ->where('landowner_id', $landowner->id)
                ->where('parcel_id', $parcel->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'conversion' => 'This Landowner and Parcel already have a Landholding record. Review it instead of duplicating it.',
                ]);
            }

            $status = $package->source_record_scope === LegacyRecord::SOURCE_SCOPE_CURRENT_ACTIVE
                ? Landholding::STATUS_ACTIVE
                : Landholding::STATUS_HISTORICAL;

            if ($status === Landholding::STATUS_ACTIVE && $this->hasIncomingClearanceExposure($landowner, $parcel)) {
                throw ValidationException::withMessages([
                    'conversion' => 'This parcel has an existing incoming clearance exposure for this landowner. Reconcile the application before recording the area as an active holding to avoid double-counting.',
                ]);
            }

            $landholding = Landholding::create([
                'landowner_id' => $landowner->id,
                'parcel_id' => $parcel->id,
                'source_record_package_id' => $package->id,
                'source_reference_number' => $package->landholding_reference_number,
                'area_hectares' => $validated['area_hectares'],
                'status' => $status,
                'date_acquired' => $validated['date_acquired'] ?? null,
                'remarks' => 'Staff-verified administrative record from source package '.$package->package_code.'. No legal ownership transfer or registry mutation.',
            ]);

            AuditLogger::record('landholding_created_from_source_package', null, $landholding, [
                'source_record_package_id' => $package->id,
                'package_code' => $package->package_code,
                'landowner_id' => $landowner->id,
                'parcel_id' => $parcel->id,
                'area_hectares' => $landholding->area_hectares,
                'status' => $status,
                'source_reference_number' => $package->landholding_reference_number,
                'verified_by_user_id' => $request->user()->id,
                'verified_at' => now()->toIso8601String(),
                'date_acquired' => $landholding->date_acquired?->toDateString(),
                'source_record_revision' => (int) $package->record_revision,
                'source_snapshot' => \Illuminate\Support\Arr::only($package->attributesToArray(), array_diff($package->getFillable(), ['source_geometry_geojson'])),
                'source_geometry_sha256' => $package->source_geometry_geojson === null
                    ? null : hash('sha256', json_encode($package->source_geometry_geojson, JSON_THROW_ON_ERROR)),
                'source_records' => $package->records()->orderBy('id')->get()
                    ->map(fn ($record) => $record->only(['id', 'record_revision', 'record_type', 'source_record_scope']))->all(),
                'scope_note' => 'Administrative record and assistive monitoring only. No land ownership transfer or registry mutation was executed.',
            ]);
        });

        return redirect()
            ->route('staff.source-record-packages.show', $sourceRecordPackage)
            ->with('success', 'Verified Landholding created and linked to this source package.');
    }

    private function hasIncomingClearanceExposure(Landowner $landowner, Parcel $parcel): bool
    {
        return ApplicationParcel::query()
            ->where('parcel_id', $parcel->id)
            ->whereHas('application', function ($query) use ($landowner) {
                $query->whereIn('status', LandholdingAreaValidationService::potentialIncomingExposureStatuses())
                    ->where(function ($query) use ($landowner) {
                        $query->where('transferee_landowner_id', $landowner->id)
                            ->orWhereJsonContains('transferees', [['landowner_id' => $landowner->id]]);
                    });
            })
            ->exists();
    }
}
