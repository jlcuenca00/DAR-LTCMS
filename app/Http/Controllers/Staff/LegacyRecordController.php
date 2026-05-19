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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LegacyRecordController extends Controller
{
    public function index(Request $request)
    {
        $archiveView = $request->query('view') === 'packages' ? 'packages' : 'individual';

        $sourcePackages = SourceRecordPackage::query()
            ->with(['parcel', 'landowner'])
            ->withCount('records')
            ->when($request->filled('municipality'), function ($query) use ($request) {
                $query->where('municipality', 'ILIKE', '%' . $request->municipality . '%');
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->search . '%';

                $query->where(function ($inner) use ($search) {
                    $inner->where('package_code', 'ILIKE', $search)
                        ->orWhere('title_number', 'ILIKE', $search)
                        ->orWhere('control_number', 'ILIKE', $search)
                        ->orWhere('parcel_code', 'ILIKE', $search)
                        ->orWhere('lot_number', 'ILIKE', $search)
                        ->orWhere('survey_number', 'ILIKE', $search)
                        ->orWhere('landowner_name', 'ILIKE', $search)
                        ->orWhere('transferor_name', 'ILIKE', $search)
                        ->orWhere('transferee_name', 'ILIKE', $search)
                        ->orWhere('landholding_reference_number', 'ILIKE', $search);
                });
            })
            ->latest()
            ->limit($archiveView === 'packages' ? 60 : 6)
            ->get();

        $records = LegacyRecord::query()
            ->with('parcel')
            ->when($request->filled('record_type'), function ($query) use ($request) {
                $query->where('record_type', $request->record_type);
            })
            ->when($request->filled('origin'), function ($query) use ($request) {
                $query->where('origin', $request->origin);
            })
            ->when($request->filled('municipality'), function ($query) use ($request) {
                $query->where('municipality', 'ILIKE', '%' . $request->municipality . '%');
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->search . '%';

                $query->where(function ($inner) use ($search) {
                    $inner->where('title_number', 'ILIKE', $search)
                        ->orWhere('control_number', 'ILIKE', $search)
                        ->orWhere('application_reference_number', 'ILIKE', $search)
                        ->orWhere('parcel_code', 'ILIKE', $search)
                        ->orWhere('lot_number', 'ILIKE', $search)
                        ->orWhere('survey_number', 'ILIKE', $search)
                        ->orWhere('landowner_name', 'ILIKE', $search)
                        ->orWhere('transferor_name', 'ILIKE', $search)
                        ->orWhere('transferee_name', 'ILIKE', $search)
                        ->orWhere('previous_dar_reference_number', 'ILIKE', $search)
                        ->orWhere('landholding_reference_number', 'ILIKE', $search);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('staff.legacy-records.index', [
            'records' => $records,
            'sourcePackages' => $sourcePackages,
            'archiveView' => $archiveView,
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

        return view('staff.legacy-records.create', [
            'recordType' => $recordType,
            'recordTypes' => LegacyRecord::RECORD_TYPES,
            'sourceScopes' => LegacyRecord::SOURCE_SCOPES,
            'parcels' => $parcels,
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

        return redirect()
            ->route('staff.legacy-records.show', $record)
            ->with('success', 'Source record encoded successfully.');
    }

    public function show(LegacyRecord $legacyRecord)
    {
        $legacyRecord->load(['parcel', 'package']);

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

        return view('staff.legacy-records.show', [
            'record' => $legacyRecord,
            'parcels' => $parcels,
            'landowners' => $landowners,
        ]);
    }

    public function linkParcel(Request $request, LegacyRecord $legacyRecord)
    {
        $data = $request->validate([
            'parcel_id' => ['required', 'exists:parcels,id'],
        ]);

        $parcel = Parcel::findOrFail($data['parcel_id']);

        $legacyRecord->update([
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
        ]);

        AuditLogger::record(
            'source_record_linked_to_parcel',
            null,
            $legacyRecord,
            [
                'source_record_id' => $legacyRecord->id,
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
            ]
        );

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

        $geometry = null;

        if (! empty($data['geometry_geojson'])) {
            $geometry = $this->decodeGeoJson($data['geometry_geojson']);
        }

        DB::transaction(function () use ($data, $geometry, $legacyRecord) {
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