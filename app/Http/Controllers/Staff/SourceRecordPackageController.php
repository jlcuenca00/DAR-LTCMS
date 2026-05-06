<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LegacyRecord;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SourceRecordPackageController extends Controller
{
    public function create()
    {
        $parcels = Parcel::query()
            ->orderBy('parcel_code')
            ->limit(500)
            ->get([
                'id',
                'parcel_code',
                'title_no',
                'municipality',
                'barangay',
            ]);

        return view('staff.source-record-packages.create', [
            'sourceScopes' => LegacyRecord::SOURCE_SCOPES,
            'parcels' => $parcels,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        if (
            ! $request->boolean('include_title') &&
            ! $request->boolean('include_landholding') &&
            ! $request->boolean('include_parcel_source') &&
            ! $request->boolean('include_historical_clearance')
        ) {
            throw ValidationException::withMessages([
                'include_title' => 'Select at least one source record section to save.',
            ]);
        }

        if ($request->boolean('include_title') && empty($data['title_number'])) {
            throw ValidationException::withMessages([
                'title_number' => 'Title number is required when including a title source record.',
            ]);
        }

        if ($request->boolean('include_landholding') && empty($data['landholding_reference_number'])) {
            throw ValidationException::withMessages([
                'landholding_reference_number' => 'Landholding reference number is required when including a landholding source record.',
            ]);
        }

        if ($request->boolean('include_parcel_source') && empty($data['parcel_code'])) {
            throw ValidationException::withMessages([
                'parcel_code' => 'Parcel reference code is required when including a parcel source record.',
            ]);
        }

        if ($request->boolean('include_historical_clearance') && empty($data['control_number'])) {
            throw ValidationException::withMessages([
                'control_number' => 'Clearance control number is required when including a historical clearance source record.',
            ]);
        }

        if (! empty($data['source_geometry_geojson'])) {
            $data['source_geometry_geojson'] = $this->decodeGeoJson($data['source_geometry_geojson']);
        }

        if (empty($data['parcel_code']) && ! empty($data['parcel_id'])) {
            $linkedParcel = Parcel::find($data['parcel_id']);
            $data['parcel_code'] = $linkedParcel?->parcel_code;
        }

        $package = DB::transaction(function () use ($request, $data) {
            $package = SourceRecordPackage::create(array_merge($data, [
                'package_code' => $this->generatePackageCode(),
                'status' => ! empty($data['parcel_id'])
                    ? SourceRecordPackage::STATUS_LINKED
                    : SourceRecordPackage::STATUS_ENCODED,
                'encoded_by_user_id' => $request->user()->id,
                'province' => $data['province'] ?? 'Negros Oriental',
            ]));

            if ($request->boolean('include_title')) {
                $this->createRecordFromPackage($package, LegacyRecord::TYPE_TITLE, $request->user()->id);
            }

            if ($request->boolean('include_landholding')) {
                $this->createRecordFromPackage($package, LegacyRecord::TYPE_LANDHOLDING, $request->user()->id);
            }

            if ($request->boolean('include_parcel_source')) {
                $this->createRecordFromPackage($package, LegacyRecord::TYPE_PARCEL_SOURCE, $request->user()->id);
            }

            if ($request->boolean('include_historical_clearance')) {
                $this->createRecordFromPackage($package, LegacyRecord::TYPE_HISTORICAL_CLEARANCE, $request->user()->id);
            }

            AuditLogger::record(
                'source_record_package_encoded',
                null,
                $package,
                [
                    'package_code' => $package->package_code,
                    'source_record_scope' => $package->source_record_scope,
                    'parcel_id' => $package->parcel_id,
                    'parcel_code' => $package->parcel_code,
                    'records_created' => $package->records()->count(),
                ]
            );

            return $package;
        });

        return redirect()
            ->route('staff.source-record-packages.show', $package)
            ->with('success', 'Source record package encoded successfully.');
    }

    public function show(SourceRecordPackage $sourceRecordPackage)
    {
        $sourceRecordPackage->load(['records', 'parcel']);

        $parcels = Parcel::query()
            ->orderBy('parcel_code')
            ->limit(500)
            ->get([
                'id',
                'parcel_code',
                'title_no',
                'municipality',
                'barangay',
            ]);

        $landowners = Landowner::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(500)
            ->get([
                'id',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'municipality',
                'barangay',
            ]);

        return view('staff.source-record-packages.show', [
            'package' => $sourceRecordPackage,
            'parcels' => $parcels,
            'landowners' => $landowners,
        ]);
    }

    public function linkParcel(Request $request, SourceRecordPackage $sourceRecordPackage)
    {
        $data = $request->validate([
            'parcel_id' => ['required', 'exists:parcels,id'],
        ]);

        $parcel = Parcel::findOrFail($data['parcel_id']);

        DB::transaction(function () use ($sourceRecordPackage, $parcel) {
            $sourceRecordPackage->update([
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
                'status' => SourceRecordPackage::STATUS_LINKED,
            ]);

            $sourceRecordPackage->records()->update([
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
            ]);

            AuditLogger::record(
                'source_record_package_linked_to_parcel',
                null,
                $sourceRecordPackage,
                [
                    'package_code' => $sourceRecordPackage->package_code,
                    'parcel_id' => $parcel->id,
                    'parcel_code' => $parcel->parcel_code,
                    'records_linked' => $sourceRecordPackage->records()->count(),
                ]
            );
        });

        return back()->with('success', 'Source record package linked to parcel successfully.');
    }

    public function createParcel(Request $request, SourceRecordPackage $sourceRecordPackage)
    {
        if ($sourceRecordPackage->parcel_id) {
            throw ValidationException::withMessages([
                'parcel_code' => 'This package is already linked to a parcel.',
            ]);
        }

        $data = $request->validate([
            'parcel_code' => ['required', 'string', 'max:255', 'unique:parcels,parcel_code'],
            'title_no' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'geometry_geojson' => ['nullable', 'string', 'max:200000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'remarks' => ['nullable', 'string', 'max:5000'],

            'landowner_id' => ['nullable', 'exists:landowners,id'],
            'date_acquired' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
        ]);

        $geometry = null;

        if (! empty($data['geometry_geojson'])) {
            $geometry = $this->decodeGeoJson($data['geometry_geojson']);
        }

        DB::transaction(function () use ($data, $geometry, $sourceRecordPackage) {
            $parcel = Parcel::create([
                'parcel_code' => $data['parcel_code'],
                'title_no' => $data['title_no'] ?: $sourceRecordPackage->title_number,
                'municipality' => $data['municipality'] ?: $sourceRecordPackage->municipality,
                'barangay' => $data['barangay'] ?: $sourceRecordPackage->barangay,
                'province' => $data['province'] ?: ($sourceRecordPackage->province ?? 'Negros Oriental'),
                'area_hectares' => $data['area_hectares'] ?: $sourceRecordPackage->area_hectares,
                'geometry_geojson' => $geometry,
                'status' => $data['status'],
                'remarks' => $data['remarks'] ?: 'Created from source package ' . $sourceRecordPackage->package_code . '.',
            ]);

            if (! empty($data['landowner_id'])) {
                Landholding::create([
                    'landowner_id' => $data['landowner_id'],
                    'parcel_id' => $parcel->id,
                    'area_hectares' => $parcel->area_hectares ?? 0,
                    'status' => 'active',
                    'date_acquired' => $data['date_acquired'] ?? null,
                    'remarks' => 'Created from source package ' . $sourceRecordPackage->package_code . '.',
                ]);
            }

            $sourceRecordPackage->update([
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
                'status' => SourceRecordPackage::STATUS_PARCEL_CREATED,
            ]);

            $sourceRecordPackage->records()->update([
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
            ]);

            AuditLogger::record(
                'parcel_created_from_source_record_package',
                null,
                $parcel,
                [
                    'package_code' => $sourceRecordPackage->package_code,
                    'source_record_package_id' => $sourceRecordPackage->id,
                    'parcel_id' => $parcel->id,
                    'parcel_code' => $parcel->parcel_code,
                    'linked_landowner_id' => $data['landowner_id'] ?? null,
                    'source_records_linked' => $sourceRecordPackage->records()->count(),
                ]
            );
        });

        return redirect()
            ->route('staff.source-record-packages.show', $sourceRecordPackage)
            ->with('success', 'Parcel record created from source package and linked successfully.');
    }

    private function createRecordFromPackage(SourceRecordPackage $package, string $recordType, int $userId): LegacyRecord
    {
        return LegacyRecord::create([
            'source_record_package_id' => $package->id,
            'record_type' => $recordType,
            'origin' => LegacyRecord::ORIGIN_ENCODED,
            'source_record_scope' => $package->source_record_scope,
            'parcel_id' => $package->parcel_id,
            'encoded_by_user_id' => $userId,

            'parcel_code' => $package->parcel_code,
            'title_number' => $package->title_number,
            'control_number' => $recordType === LegacyRecord::TYPE_HISTORICAL_CLEARANCE ? $package->control_number : null,
            'application_reference_number' => null,
            'lot_number' => $package->lot_number,
            'survey_number' => $package->survey_number,

            'landowner_name' => $package->landowner_name,
            'transferor_name' => $recordType === LegacyRecord::TYPE_HISTORICAL_CLEARANCE ? $package->transferor_name : null,
            'transferee_name' => $recordType === LegacyRecord::TYPE_HISTORICAL_CLEARANCE ? $package->transferee_name : null,

            'area_hectares' => $package->area_hectares,
            'crop_or_land_use' => $package->crop_or_land_use,

            'barangay' => $package->barangay,
            'municipality' => $package->municipality,
            'province' => $package->province,
            'source_geometry_geojson' => $recordType === LegacyRecord::TYPE_PARCEL_SOURCE
                ? $package->source_geometry_geojson
                : null,

            'record_date' => null,
            'decision_status' => null,
            'previous_dar_reference_number' => null,
            'landholding_reference_number' => $recordType === LegacyRecord::TYPE_LANDHOLDING
                ? $package->landholding_reference_number
                : null,

            'remarks' => $package->remarks,
            'boundary_description' => $recordType === LegacyRecord::TYPE_PARCEL_SOURCE
                ? $package->boundary_description
                : null,

            'source_book' => $package->source_book,
            'page_number' => $package->page_number,
            'transcribed_by' => $package->transcribed_by,
            'transcription_date' => $package->transcription_date,
            'source_notes' => $package->source_notes,
        ]);
    }

    private function rules(): array
    {
        return [
            'source_record_scope' => ['required', Rule::in(array_keys(LegacyRecord::SOURCE_SCOPES))],
            'parcel_id' => ['nullable', 'exists:parcels,id'],

            'include_title' => ['nullable', 'boolean'],
            'include_landholding' => ['nullable', 'boolean'],
            'include_parcel_source' => ['nullable', 'boolean'],
            'include_historical_clearance' => ['nullable', 'boolean'],

            'parcel_code' => ['nullable', 'string', 'max:255'],
            'title_number' => ['nullable', 'string', 'max:255'],
            'landholding_reference_number' => ['nullable', 'string', 'max:255'],
            'control_number' => ['nullable', 'string', 'max:255'],

            'landowner_name' => ['required', 'string', 'max:255'],
            'transferor_name' => ['nullable', 'string', 'max:255'],
            'transferee_name' => ['nullable', 'string', 'max:255'],

            'lot_number' => ['nullable', 'string', 'max:255'],
            'survey_number' => ['nullable', 'string', 'max:255'],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'crop_or_land_use' => ['nullable', 'string', 'max:255'],

            'barangay' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],

            'source_geometry_geojson' => ['nullable', 'string', 'max:200000'],
            'boundary_description' => ['nullable', 'string', 'max:5000'],

            'source_book' => ['required', 'string', 'max:255'],
            'page_number' => ['nullable', 'string', 'max:100'],
            'transcribed_by' => ['required', 'string', 'max:255'],
            'transcription_date' => ['required', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'source_notes' => ['nullable', 'string', 'max:5000'],
            'remarks' => ['nullable', 'string', 'max:5000'],

            'date_acquired' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
        ];
    }

    private function decodeGeoJson(string $value): array
    {
        $decoded = json_decode($value, true);

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            ! is_array($decoded) ||
            empty($decoded['type']) ||
            empty($decoded['coordinates'])
        ) {
            throw ValidationException::withMessages([
                'source_geometry_geojson' => 'The geometry must be valid GeoJSON with type and coordinates.',
                'geometry_geojson' => 'The geometry must be valid GeoJSON with type and coordinates.',
            ]);
        }

        return $decoded;
    }

    private function generatePackageCode(): string
    {
        do {
            $code = 'SRC-PKG-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        } while (SourceRecordPackage::where('package_code', $code)->exists());

        return $code;
    }
}