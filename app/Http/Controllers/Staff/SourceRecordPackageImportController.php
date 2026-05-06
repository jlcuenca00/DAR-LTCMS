<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LegacyRecord;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use App\Models\SourceRecordPackageImportBatch;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SourceRecordPackageImportController extends Controller
{
    private array $headers = [
        'include_title',
        'include_landholding',
        'include_parcel_source',
        'include_historical_clearance',
        'source_record_scope',
        'landowner_name',
        'parcel_code',
        'title_number',
        'landholding_reference_number',
        'control_number',
        'transferor_name',
        'transferee_name',
        'lot_number',
        'survey_number',
        'area_hectares',
        'crop_or_land_use',
        'barangay',
        'municipality',
        'province',
        'source_geometry_geojson',
        'boundary_description',
        'source_book',
        'page_number',
        'transcribed_by',
        'transcription_date',
        'remarks',
        'source_notes',
    ];

    public function create()
    {
        return view('staff.source-record-packages.import');
    }

    public function template()
    {
        $sample = [
            'yes',
            'yes',
            'yes',
            'no',
            'current_active',
            'Sample Landowner',
            'SRC-IMPORT-PCL-001',
            'TCT-IMPORT-001',
            'LH-IMPORT-001',
            '',
            '',
            '',
            'LOT-IMPORT-001',
            'SUR-IMPORT-001',
            '2.5000',
            'Agricultural',
            'Bantayan',
            'Dumaguete City',
            'Negros Oriental',
            '{"type":"Polygon","coordinates":[[[123.3080,9.3064],[123.3090,9.3064],[123.3090,9.3072],[123.3080,9.3072],[123.3080,9.3064]]]}',
            'Sample technical description.',
            'Import Source File A',
            '1',
            'Encoder Name',
            now()->toDateString(),
            'Sample imported package.',
            'Sample source notes.',
        ];

        $callback = function () use ($sample) {
            $file = fopen('php://output', 'w');

            fputcsv($file, $this->headers);
            fputcsv($file, $sample);

            fclose($file);
        };

        return response()->streamDownload($callback, 'source-record-package-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $path = $request->file('import_file')->getRealPath();

        $rows = $this->readCsv($path);

        if (count($rows) === 0) {
            throw ValidationException::withMessages([
                'import_file' => 'The uploaded CSV has no rows.',
            ]);
        }

        $previewRows = [];
        $validCount = 0;
        $errorCount = 0;
        $duplicateCount = 0;

        foreach ($rows as $index => $row) {
            $analysis = $this->analyzeRow($row, $index + 2);

            if ($analysis['status'] === 'valid') {
                $validCount++;
            }

            if ($analysis['status'] === 'error') {
                $errorCount++;
            }

            if ($analysis['possible_duplicate']) {
                $duplicateCount++;
            }

            $previewRows[] = $analysis;
        }

        $batch = SourceRecordPackageImportBatch::create([
            'original_filename' => $request->file('import_file')->getClientOriginalName(),
            'status' => 'previewed',
            'total_rows' => count($previewRows),
            'valid_rows' => $validCount,
            'error_rows' => $errorCount,
            'duplicate_rows' => $duplicateCount,
            'uploaded_by_user_id' => $request->user()->id,
            'preview_rows' => $previewRows,
            'summary' => [
                'valid_rows' => $validCount,
                'error_rows' => $errorCount,
                'duplicate_rows' => $duplicateCount,
            ],
        ]);

        AuditLogger::record(
            'source_record_package_import_previewed',
            null,
            $batch,
            [
                'batch_id' => $batch->id,
                'filename' => $batch->original_filename,
                'total_rows' => $batch->total_rows,
                'valid_rows' => $batch->valid_rows,
                'error_rows' => $batch->error_rows,
                'duplicate_rows' => $batch->duplicate_rows,
            ]
        );

        return redirect()
            ->route('staff.source-record-package-imports.preview', $batch)
            ->with('success', 'Import preview generated.');
    }

    public function showPreview(SourceRecordPackageImportBatch $batch)
    {
        return view('staff.source-record-packages.import-preview', [
            'batch' => $batch,
            'rows' => $batch->preview_rows,
        ]);
    }

    public function commit(Request $request, SourceRecordPackageImportBatch $batch)
    {
        if ($batch->status === 'committed') {
            return back()->with('success', 'This import batch has already been committed.');
        }

        $selectedRows = collect($request->input('selected_rows', []))
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();

        if (count($selectedRows) === 0) {
            throw ValidationException::withMessages([
                'selected_rows' => 'Select at least one valid row to commit.',
            ]);
        }

        $previewRows = collect($batch->preview_rows);
        $committed = 0;

        DB::transaction(function () use ($batch, $previewRows, $selectedRows, &$committed, $request) {
            foreach ($selectedRows as $rowIndex) {
                $preview = $previewRows->firstWhere('row_index', $rowIndex);

                if (! $preview || $preview['status'] !== 'valid') {
                    continue;
                }

                $data = $preview['data'];

                $package = SourceRecordPackage::create([
                    'package_code' => $this->generatePackageCode(),
                    'status' => SourceRecordPackage::STATUS_ENCODED,
                    'source_record_scope' => $data['source_record_scope'],
                    'encoded_by_user_id' => $request->user()->id,

                    'parcel_code' => $data['parcel_code'] ?: null,
                    'title_number' => $data['title_number'] ?: null,
                    'landholding_reference_number' => $data['landholding_reference_number'] ?: null,
                    'control_number' => $data['control_number'] ?: null,

                    'landowner_name' => $data['landowner_name'] ?: null,
                    'transferor_name' => $data['transferor_name'] ?: null,
                    'transferee_name' => $data['transferee_name'] ?: null,

                    'lot_number' => $data['lot_number'] ?: null,
                    'survey_number' => $data['survey_number'] ?: null,
                    'area_hectares' => $data['area_hectares'] ?: null,
                    'crop_or_land_use' => $data['crop_or_land_use'] ?: null,

                    'barangay' => $data['barangay'] ?: null,
                    'municipality' => $data['municipality'] ?: null,
                    'province' => $data['province'] ?: 'Negros Oriental',

                    'source_geometry_geojson' => $data['source_geometry_geojson_decoded'],
                    'boundary_description' => $data['boundary_description'] ?: null,

                    'source_book' => $data['source_book'],
                    'page_number' => $data['page_number'] ?: null,
                    'transcribed_by' => $data['transcribed_by'],
                    'transcription_date' => $data['transcription_date'],
                    'remarks' => $data['remarks'] ?: null,
                    'source_notes' => $data['source_notes'] ?: null,
                ]);

                if ($data['include_title']) {
                    $this->createRecordFromPackage($package, LegacyRecord::TYPE_TITLE, $request->user()->id);
                }

                if ($data['include_landholding']) {
                    $this->createRecordFromPackage($package, LegacyRecord::TYPE_LANDHOLDING, $request->user()->id);
                }

                if ($data['include_parcel_source']) {
                    $this->createRecordFromPackage($package, LegacyRecord::TYPE_PARCEL_SOURCE, $request->user()->id);
                }

                if ($data['include_historical_clearance']) {
                    $this->createRecordFromPackage($package, LegacyRecord::TYPE_HISTORICAL_CLEARANCE, $request->user()->id);
                }

                $committed++;
            }

            $batch->update([
                'status' => 'committed',
                'committed_rows' => $committed,
                'committed_by_user_id' => $request->user()->id,
                'committed_at' => now(),
            ]);

            AuditLogger::record(
                'source_record_package_import_committed',
                null,
                $batch,
                [
                    'batch_id' => $batch->id,
                    'filename' => $batch->original_filename,
                    'committed_rows' => $committed,
                    'selected_rows' => $selectedRows,
                ]
            );
        });

        return redirect()
            ->route('staff.legacy-records.index')
            ->with('success', $committed . ' source package row(s) imported successfully.');
    }

    private function readCsv(string $path): array
    {
        $file = fopen($path, 'r');

        $header = fgetcsv($file);

        if (! $header) {
            fclose($file);

            throw ValidationException::withMessages([
                'import_file' => 'The uploaded CSV has no header row.',
            ]);
        }

        $header = array_map(fn ($value) => trim((string) $value), $header);

        $missingHeaders = array_diff($this->headers, $header);

        if (count($missingHeaders) > 0) {
            fclose($file);

            throw ValidationException::withMessages([
                'import_file' => 'Missing required columns: ' . implode(', ', $missingHeaders),
            ]);
        }

        $rows = [];

        while (($line = fgetcsv($file)) !== false) {
            if ($this->isEmptyCsvLine($line)) {
                continue;
            }

            $row = [];

            foreach ($header as $index => $column) {
                $row[$column] = isset($line[$index]) ? trim((string) $line[$index]) : '';
            }

            $rows[] = $row;
        }

        fclose($file);

        return $rows;
    }

    private function analyzeRow(array $row, int $rowIndex): array
    {
        $errors = [];
        $warnings = [];

        $data = [];

        foreach ($this->headers as $header) {
            $data[$header] = $row[$header] ?? '';
        }

        $data['include_title'] = $this->truthy($data['include_title']);
        $data['include_landholding'] = $this->truthy($data['include_landholding']);
        $data['include_parcel_source'] = $this->truthy($data['include_parcel_source']);
        $data['include_historical_clearance'] = $this->truthy($data['include_historical_clearance']);

        if (! $data['include_title'] && ! $data['include_landholding'] && ! $data['include_parcel_source'] && ! $data['include_historical_clearance']) {
            $errors[] = 'Select at least one included source section.';
        }

        if (! in_array($data['source_record_scope'], array_keys(LegacyRecord::SOURCE_SCOPES), true)) {
            $errors[] = 'Invalid source_record_scope. Use current_active, historical, or reference_only.';
        }

        if ($data['landowner_name'] === '') {
            $errors[] = 'landowner_name is required.';
        }

        if ($data['source_book'] === '') {
            $errors[] = 'source_book is required.';
        }

        if ($data['transcribed_by'] === '') {
            $errors[] = 'transcribed_by is required.';
        }

        if ($data['transcription_date'] === '') {
            $errors[] = 'transcription_date is required.';
        } elseif (! $this->validDate($data['transcription_date'])) {
            $errors[] = 'transcription_date must be a valid date in YYYY-MM-DD format.';
        }

        if ($data['include_title'] && $data['title_number'] === '') {
            $errors[] = 'title_number is required when include_title is yes.';
        }

        if ($data['include_landholding'] && $data['landholding_reference_number'] === '') {
            $errors[] = 'landholding_reference_number is required when include_landholding is yes.';
        }

        if ($data['include_parcel_source'] && $data['parcel_code'] === '') {
            $errors[] = 'parcel_code is required when include_parcel_source is yes.';
        }

        if ($data['include_historical_clearance'] && $data['control_number'] === '') {
            $errors[] = 'control_number is required when include_historical_clearance is yes.';
        }

        if ($data['area_hectares'] !== '' && ! is_numeric($data['area_hectares'])) {
            $errors[] = 'area_hectares must be numeric.';
        }

        $data['source_geometry_geojson_decoded'] = null;

        if ($data['source_geometry_geojson'] !== '') {
            $decoded = json_decode($data['source_geometry_geojson'], true);

            if (
                json_last_error() !== JSON_ERROR_NONE ||
                ! is_array($decoded) ||
                empty($decoded['type']) ||
                empty($decoded['coordinates'])
            ) {
                $errors[] = 'source_geometry_geojson must be valid GeoJSON with type and coordinates.';
            } else {
                $data['source_geometry_geojson_decoded'] = $decoded;
            }
        }

        $possibleDuplicate = false;

        if ($data['title_number'] !== '') {
            $exists = LegacyRecord::query()
                ->where('record_type', LegacyRecord::TYPE_TITLE)
                ->whereRaw('lower(title_number) = ?', [mb_strtolower($data['title_number'])])
                ->exists();

            if ($exists) {
                $possibleDuplicate = true;
                $errors[] = 'Duplicate title_number already exists in source records.';
            }
        }

        if ($data['control_number'] !== '') {
            $exists = LegacyRecord::query()
                ->where('record_type', LegacyRecord::TYPE_HISTORICAL_CLEARANCE)
                ->whereRaw('lower(control_number) = ?', [mb_strtolower($data['control_number'])])
                ->exists();

            if ($exists) {
                $possibleDuplicate = true;
                $errors[] = 'Duplicate control_number already exists in source records.';
            }
        }

        if ($data['parcel_code'] !== '') {
            $parcelExists = Parcel::query()
                ->whereRaw('lower(parcel_code) = ?', [mb_strtolower($data['parcel_code'])])
                ->exists();

            if ($parcelExists) {
                $possibleDuplicate = true;
                $warnings[] = 'parcel_code already exists as a main parcel record. Consider linking instead of creating a new parcel later.';
            }
        }

        return [
            'row_index' => $rowIndex,
            'status' => count($errors) > 0 ? 'error' : 'valid',
            'possible_duplicate' => $possibleDuplicate,
            'errors' => $errors,
            'warnings' => $warnings,
            'data' => $data,
        ];
    }

    private function createRecordFromPackage(SourceRecordPackage $package, string $recordType, int $userId): LegacyRecord
    {
        return LegacyRecord::create([
            'source_record_package_id' => $package->id,
            'record_type' => $recordType,
            'origin' => LegacyRecord::ORIGIN_IMPORTED,
            'source_record_scope' => $package->source_record_scope,
            'parcel_id' => $package->parcel_id,
            'encoded_by_user_id' => $userId,

            'parcel_code' => $package->parcel_code,
            'title_number' => $package->title_number,
            'control_number' => $recordType === LegacyRecord::TYPE_HISTORICAL_CLEARANCE ? $package->control_number : null,
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

            'landholding_reference_number' => $recordType === LegacyRecord::TYPE_LANDHOLDING
                ? $package->landholding_reference_number
                : null,

            'boundary_description' => $recordType === LegacyRecord::TYPE_PARCEL_SOURCE
                ? $package->boundary_description
                : null,

            'remarks' => $package->remarks,

            'source_book' => $package->source_book,
            'page_number' => $package->page_number,
            'transcribed_by' => $package->transcribed_by,
            'transcription_date' => $package->transcription_date,
            'source_notes' => $package->source_notes,
        ]);
    }

    private function truthy(string $value): bool
    {
        return in_array(Str::lower(trim($value)), ['1', 'yes', 'y', 'true', 'included'], true);
    }

    private function validDate(string $value): bool
    {
        $timestamp = strtotime($value);

        if (! $timestamp) {
            return false;
        }

        return date('Y-m-d', $timestamp) === $value;
    }

    private function isEmptyCsvLine(array $line): bool
    {
        foreach ($line as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function generatePackageCode(): string
    {
        do {
            $code = 'SRC-PKG-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        } while (SourceRecordPackage::where('package_code', $code)->exists());

        return $code;
    }
}