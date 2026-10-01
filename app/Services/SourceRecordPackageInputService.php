<?php

namespace App\Services;

use App\Models\LegacyRecord;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SourceRecordPackageInputService
{
    public function createRules(): array
    {
        return array_merge($this->commonRules(), [
            'parcel_id' => ['nullable', 'exists:parcels,id'],
            'include_title' => ['nullable', 'boolean'],
            'include_landholding' => ['nullable', 'boolean'],
            'include_parcel_source' => ['nullable', 'boolean'],
            'include_historical_clearance' => ['nullable', 'boolean'],
            'date_acquired' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'source_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
    }

    public function updateRules(): array
    {
        return $this->commonRules();
    }

    public function importRules(): array
    {
        return array_merge($this->commonRules(), [
            'include_title' => ['required', 'boolean'],
            'include_landholding' => ['required', 'boolean'],
            'include_parcel_source' => ['required', 'boolean'],
            'include_historical_clearance' => ['required', 'boolean'],
        ]);
    }

    public function validateImportRow(array $data): array
    {
        $validated = Validator::make($data, $this->importRules())->validate();
        $this->assertIncludedSections($validated);

        $normalizedLocation = app(DarLocationService::class)->normalize(
            $validated['municipality'] ?? null,
            $validated['barangay'] ?? null,
            $validated['province'] ?? null,
        );

        $validated = array_merge($validated, $normalizedLocation);
        $validated['source_geometry_geojson_decoded'] = filled($validated['source_geometry_geojson'] ?? null)
            ? $this->decodeGeoJson((string) $validated['source_geometry_geojson'])
            : null;

        return $validated;
    }

    public function assertIncludedSections(array $data): void
    {
        $includeTitle = (bool) ($data['include_title'] ?? false);
        $includeLandholding = (bool) ($data['include_landholding'] ?? false);
        $includeParcelSource = (bool) ($data['include_parcel_source'] ?? false);
        $includeHistoricalClearance = (bool) ($data['include_historical_clearance'] ?? false);

        if (! $includeTitle && ! $includeLandholding && ! $includeParcelSource && ! $includeHistoricalClearance) {
            throw ValidationException::withMessages([
                'include_title' => 'Select at least one source record section to save.',
            ]);
        }

        $requiredReferences = [
            ['enabled' => $includeTitle, 'field' => 'title_number', 'message' => 'Title number is required when including a title source record.'],
            ['enabled' => $includeLandholding, 'field' => 'landholding_reference_number', 'message' => 'Landholding reference number is required when including a landholding source record.'],
            ['enabled' => $includeParcelSource, 'field' => 'parcel_code', 'message' => 'Parcel reference code is required when including a parcel source record.'],
            ['enabled' => $includeHistoricalClearance, 'field' => 'control_number', 'message' => 'Clearance control number is required when including a historical clearance source record.'],
        ];

        foreach ($requiredReferences as $requirement) {
            if ($requirement['enabled'] && blank($data[$requirement['field']] ?? null)) {
                throw ValidationException::withMessages([
                    $requirement['field'] => $requirement['message'],
                ]);
            }
        }
    }

    public function decodeGeoJson(string $value): array
    {
        $decoded = json_decode($value, true);

        if (
            json_last_error() !== JSON_ERROR_NONE
            || ! is_array($decoded)
            || empty($decoded['type'])
            || empty($decoded['coordinates'])
        ) {
            throw ValidationException::withMessages([
                'source_geometry_geojson' => 'The geometry must be valid GeoJSON with type and coordinates.',
                'geometry_geojson' => 'The geometry must be valid GeoJSON with type and coordinates.',
            ]);
        }

        return $decoded;
    }

    private function commonRules(): array
    {
        return [
            'source_record_scope' => ['required', Rule::in(array_keys(LegacyRecord::SOURCE_SCOPES))],

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
        ];
    }
}
