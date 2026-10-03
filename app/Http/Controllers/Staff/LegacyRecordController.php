<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LegacyRecord;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use App\Services\AuditLogger;
use App\Services\LandownerConcurrencyService;
use App\Services\ParcelGeometryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LegacyRecordController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['individual', 'packages'])],
            'search' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'record_type' => ['nullable', Rule::in(array_keys(LegacyRecord::RECORD_TYPES))],
            'origin' => ['nullable', Rule::in(array_keys(LegacyRecord::ORIGINS))],
        ]);

        $archiveView = ($filters['view'] ?? 'individual') === 'packages'
            ? 'packages'
            : 'individual';

        $search = trim((string) ($filters['search'] ?? ''));
        $municipality = trim((string) ($filters['municipality'] ?? ''));
        $recordType = $filters['record_type'] ?? null;
        $origin = $filters['origin'] ?? null;

        $sourcePackages = SourceRecordPackage::query()
            ->with(['parcel', 'landowner'])
            ->withCount('records')
            ->when($municipality !== '', function ($query) use ($municipality) {
                $query->where('municipality', $municipality);
            })
            ->when($search !== '', function ($query) use ($search) {
                $pattern = '%' . mb_strtolower($search) . '%';

                $query->whereRaw(
                    "LOWER(COALESCE(package_code, '') || ' ' || COALESCE(title_number, '') || ' ' || COALESCE(control_number, '') || ' ' || COALESCE(parcel_code, '') || ' ' || COALESCE(lot_number, '') || ' ' || COALESCE(survey_number, '') || ' ' || COALESCE(landowner_name, '') || ' ' || COALESCE(transferor_name, '') || ' ' || COALESCE(transferee_name, '') || ' ' || COALESCE(landholding_reference_number, '')) LIKE ?",
                    [$pattern]
                );
            })
            ->latest()
            ->paginate($archiveView === 'packages' ? 15 : 6, ['*'], 'packages_page')
            ->withQueryString();

        $records = LegacyRecord::query()
            ->with('parcel')
            ->when($recordType, function ($query) use ($recordType) {
                $query->where('record_type', $recordType);
            })
            ->when($origin, function ($query) use ($origin) {
                $query->where('origin', $origin);
            })
            ->when($municipality !== '', function ($query) use ($municipality) {
                $query->where('municipality', $municipality);
            })
            ->when($search !== '', function ($query) use ($search) {
                $pattern = '%' . mb_strtolower($search) . '%';

                $query->whereRaw(
                    "LOWER(COALESCE(title_number, '') || ' ' || COALESCE(control_number, '') || ' ' || COALESCE(application_reference_number, '') || ' ' || COALESCE(parcel_code, '') || ' ' || COALESCE(lot_number, '') || ' ' || COALESCE(survey_number, '') || ' ' || COALESCE(landowner_name, '') || ' ' || COALESCE(transferor_name, '') || ' ' || COALESCE(transferee_name, '') || ' ' || COALESCE(previous_dar_reference_number, '') || ' ' || COALESCE(landholding_reference_number, '')) LIKE ?",
                    [$pattern]
                );
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $filters = array_merge($filters, [
            'view' => $archiveView,
            'search' => $search,
            'municipality' => $municipality,
        ]);

        return view('staff.legacy-records.index', [
            'records' => $records,
            'sourcePackages' => $sourcePackages,
            'archiveView' => $archiveView,
            'filters' => $filters,
            'recordTypes' => LegacyRecord::RECORD_TYPES,
            'origins' => LegacyRecord::ORIGINS,
        ]);
    }

    public function create(Request $request)
    {
        $recordType = $request->query('record_type', LegacyRecord::TYPE_TITLE);

        if (! array_key_exists($recordType, LegacyRecord::RECORD_TYPES)) {
            $recordType = LegacyRecord::TYPE_TITLE;
        }

        return view('staff.legacy-records.create', [
            'recordType' => $recordType,
            'recordTypes' => LegacyRecord::RECORD_TYPES,
            'sourceScopes' => LegacyRecord::SOURCE_SCOPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request));

        $this->ensureNoDuplicate($data);

        if (! empty($data['source_geometry_geojson'])) {
            $data['source_geometry_geojson'] = $this->decodeGeoJson($data['source_geometry_geojson']);
        }

        if (empty($data['parcel_code']) && ! empty($data['parcel_id'])) {
            $linkedParcel = Parcel::find($data['parcel_id']);
            $data['parcel_code'] = $linkedParcel?->parcel_code;
        }

        $record = DB::transaction(function () use ($data, $request) {
            $record = LegacyRecord::create(array_merge($data, [
                'origin' => LegacyRecord::ORIGIN_ENCODED,
                'encoded_by_user_id' => $request->user()->id,
                'province' => $data['province'] ?? 'Negros Oriental',
            ]));

            AuditLogger::record(
                'source_record_encoded',
                null,
                $record,
                [
                    'record_type' => $record->record_type,
                    'origin' => $record->origin,
                    'source_record_scope' => $record->source_record_scope,
                    'parcel_id' => $record->parcel_id,
                    'parcel_code' => $record->parcel_code,
                    'title_number' => $record->title_number,
                    'control_number' => $record->control_number,
                    'landowner_name' => $record->landowner_name,
                    'source_book' => $record->source_book,
                    'page_number' => $record->page_number,
                ]
            );

            return $record;
        });

        return redirect()
            ->route('staff.legacy-records.show', $record)
            ->with('success', 'Source record encoded successfully.');
    }

    public function show(LegacyRecord $legacyRecord)
    {
        $legacyRecord->load(['parcel', 'package', 'landowner']);

        $selectedParcelId = request()->session()->getOldInput('parcel_id', $legacyRecord->parcel_id);
        $selectedParcel = $selectedParcelId
            ? Parcel::query()->find($selectedParcelId)
            : null;

        $selectedLandownerId = request()->session()->getOldInput('landowner_id', $legacyRecord->landowner_id);
        $selectedLandowner = $selectedLandownerId
            ? Landowner::query()->find($selectedLandownerId)
            : null;

        return view('staff.legacy-records.show', [
            'record' => $legacyRecord,
            'selectedParcel' => $selectedParcel,
            'selectedLandowner' => $selectedLandowner,
        ]);
    }

    public function linkParcel(Request $request, LegacyRecord $legacyRecord)
    {
        $data = $request->validate([
            'parcel_id' => ['required', 'exists:parcels,id'],
        ]);

        $parcel = Parcel::findOrFail($data['parcel_id']);

        DB::transaction(function () use ($legacyRecord, $parcel) {
            $lockedRecord = LegacyRecord::query()
                ->whereKey($legacyRecord->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRecord->update([
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
            ]);

            AuditLogger::record(
                'source_record_linked_to_parcel',
                null,
                $lockedRecord,
                [
                    'source_record_id' => $lockedRecord->id,
                    'parcel_id' => $parcel->id,
                    'parcel_code' => $parcel->parcel_code,
                ]
            );
        });

        return back()->with('success', 'Source record linked to parcel successfully.');
    }

    public function createParcel(Request $request, LegacyRecord $legacyRecord)
    {
        if ($legacyRecord->parcel_id) {
            throw ValidationException::withMessages([
                'parcel_code' => 'This source record is already linked to a parcel.',
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

        $geometry = app(ParcelGeometryService::class)->decodePolygon(
            $data['geometry_geojson'] ?? null
        );

        DB::transaction(function () use ($data, $geometry, $legacyRecord, $request) {
            if (! empty($data['landowner_id'])) {
                app(LandownerConcurrencyService::class)->lockLandowner((int) $data['landowner_id']);
            }

            $parcel = Parcel::create([
                'parcel_code' => $data['parcel_code'],
                'title_no' => $data['title_no'] ?: $legacyRecord->title_number,
                'municipality' => $data['municipality'] ?: $legacyRecord->municipality,
                'barangay' => $data['barangay'] ?: $legacyRecord->barangay,
                'province' => $data['province'] ?: ($legacyRecord->province ?? 'Negros Oriental'),
                'area_hectares' => $data['area_hectares'] ?: $legacyRecord->area_hectares,
                'geometry_geojson' => $geometry,
                'status' => $data['status'],
                'remarks' => $data['remarks'] ?: 'Created from source record #' . $legacyRecord->id . '.',
            ]);

            if ($parcel->geometry_geojson !== null) {
                app(ParcelGeometryService::class)->recordRevision(
                    $parcel,
                    null,
                    0,
                    $request->user(),
                    'staff_create_from_source_record'
                );
            }

            if (! empty($data['landowner_id'])) {
                Landholding::create([
                    'landowner_id' => $data['landowner_id'],
                    'parcel_id' => $parcel->id,
                    'area_hectares' => $parcel->area_hectares ?? 0,
                    'status' => 'active',
                    'date_acquired' => $data['date_acquired'] ?? null,
                    'remarks' => 'Created from source record #' . $legacyRecord->id . '.',
                ]);
            }

            $legacyRecord->update([
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
            ]);

            AuditLogger::record(
                'parcel_created_from_source_record',
                null,
                $parcel,
                [
                    'source_record_id' => $legacyRecord->id,
                    'parcel_id' => $parcel->id,
                    'parcel_code' => $parcel->parcel_code,
                    'linked_landowner_id' => $data['landowner_id'] ?? null,
                ]
            );

            AuditLogger::record(
                'source_record_linked_to_created_parcel',
                null,
                $legacyRecord,
                [
                    'source_record_id' => $legacyRecord->id,
                    'parcel_id' => $parcel->id,
                    'parcel_code' => $parcel->parcel_code,
                ]
            );
        });

        return redirect()
            ->route('staff.legacy-records.show', $legacyRecord)
            ->with('success', 'Parcel record created from source record and linked successfully.');
    }

    private function rules(Request $request): array
    {
        $rules = [
            'record_type' => ['required', Rule::in(array_keys(LegacyRecord::RECORD_TYPES))],
            'source_record_scope' => ['required', Rule::in(array_keys(LegacyRecord::SOURCE_SCOPES))],
            'parcel_id' => ['nullable', 'exists:parcels,id'],

            'parcel_code' => ['nullable', 'string', 'max:255'],
            'title_number' => ['nullable', 'string', 'max:255'],
            'control_number' => ['nullable', 'string', 'max:255'],
            'application_reference_number' => ['nullable', 'string', 'max:255'],
            'tax_declaration_number' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:255'],
            'survey_number' => ['nullable', 'string', 'max:255'],

            'landowner_name' => ['nullable', 'string', 'max:255'],
            'transferor_name' => ['nullable', 'string', 'max:255'],
            'transferee_name' => ['nullable', 'string', 'max:255'],

            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'crop_or_land_use' => ['nullable', 'string', 'max:255'],

            'barangay' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'source_geometry_geojson' => ['nullable', 'string', 'max:200000'],

            'record_date' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'decision_status' => ['nullable', 'string', 'max:255'],
            'previous_dar_reference_number' => ['nullable', 'string', 'max:255'],
            'landholding_reference_number' => ['nullable', 'string', 'max:255'],

            'remarks' => ['nullable', 'string', 'max:5000'],
            'boundary_description' => ['nullable', 'string', 'max:5000'],

            'source_book' => ['required', 'string', 'max:255'],
            'page_number' => ['nullable', 'string', 'max:100'],
            'transcribed_by' => ['required', 'string', 'max:255'],
            'transcription_date' => ['required', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'source_notes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($request->record_type === LegacyRecord::TYPE_TITLE) {
            $rules['title_number'] = ['required', 'string', 'max:255'];
            $rules['landowner_name'] = ['required', 'string', 'max:255'];
        }

        if ($request->record_type === LegacyRecord::TYPE_LANDHOLDING) {
            $rules['landowner_name'] = ['required', 'string', 'max:255'];
            $rules['landholding_reference_number'] = ['required', 'string', 'max:255'];
        }

        if ($request->record_type === LegacyRecord::TYPE_PARCEL_SOURCE) {
            $rules['parcel_code'] = ['required', 'string', 'max:255'];
            $rules['landowner_name'] = ['required', 'string', 'max:255'];
            $rules['lot_number'] = ['required', 'string', 'max:255'];
        }

        if ($request->record_type === LegacyRecord::TYPE_HISTORICAL_CLEARANCE) {
            $rules['control_number'] = ['required', 'string', 'max:255'];
            $rules['transferor_name'] = ['required', 'string', 'max:255'];
            $rules['transferee_name'] = ['required', 'string', 'max:255'];
            $rules['record_date'] = ['required', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'];
        }

        return $rules;
    }

    private function ensureNoDuplicate(array $data): void
    {
        if (($data['record_type'] ?? null) === LegacyRecord::TYPE_TITLE) {
            $exists = LegacyRecord::query()
                ->where('record_type', LegacyRecord::TYPE_TITLE)
                ->whereRaw('lower(title_number) = ?', [mb_strtolower($data['title_number'])])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'title_number' => 'A source title record with this title number already exists.',
                ]);
            }
        }

        if (($data['record_type'] ?? null) === LegacyRecord::TYPE_HISTORICAL_CLEARANCE) {
            $exists = LegacyRecord::query()
                ->where('record_type', LegacyRecord::TYPE_HISTORICAL_CLEARANCE)
                ->whereRaw('lower(control_number) = ?', [mb_strtolower($data['control_number'])])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'control_number' => 'A historical clearance record with this control number already exists.',
                ]);
            }
        }
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
}