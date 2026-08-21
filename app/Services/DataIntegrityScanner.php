<?php

namespace App\Services;

use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\LegacyRecord;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DataIntegrityScanner
{
    public function scan(): array
    {
        $issues = [];

        $this->addQueryIssue(
            $issues,
            'parcel_area_mismatch',
            'Parcel hectare and square-meter values are inconsistent.',
            DB::table('parcels')
                ->whereNotNull('area_hectares')
                ->whereNotNull('area_square_meters')
                ->whereRaw('ABS(area_square_meters - (area_hectares * 10000)) > 0.01'),
            ['id', 'parcel_code', 'area_hectares', 'area_square_meters']
        );

        $this->addQueryIssue(
            $issues,
            'application_parcel_area_exceeds_master',
            'Application transfer area exceeds the linked Parcel master area.',
            DB::table('application_parcels as ap')
                ->join('parcels as p', 'p.id', '=', 'ap.parcel_id')
                ->whereNotNull('ap.area_hectares')
                ->whereNotNull('p.area_hectares')
                ->whereRaw('ap.area_hectares > p.area_hectares + 0.0001'),
            ['ap.id', 'ap.land_transfer_application_id', 'ap.parcel_id', 'ap.area_hectares', 'p.area_hectares as parcel_area_hectares']
        );

        $duplicateApplicationParcels = DB::table('application_parcels')
            ->whereNotNull('parcel_id')
            ->select('land_transfer_application_id', 'parcel_id', DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('land_transfer_application_id', 'parcel_id')
            ->havingRaw('COUNT(*) > 1');
        $this->addQueryIssue(
            $issues,
            'duplicate_application_parcel',
            'The same Parcel is linked more than once to an application.',
            $duplicateApplicationParcels,
            ['land_transfer_application_id', 'parcel_id', 'duplicate_count']
        );

        $overAllocated = Parcel::query()
            ->withSum(['landholdings as active_landholding_area' => fn ($query) => $query->where('status', 'active')], 'area_hectares')
            ->get(['id', 'parcel_code', 'area_hectares'])
            ->filter(fn (Parcel $parcel) => (float) ($parcel->active_landholding_area ?? 0) - (float) ($parcel->area_hectares ?? 0) > 0.0001)
            ->values();
        $this->addCollectionIssue(
            $issues,
            'active_landholding_overallocation',
            'Active Landholding shares exceed the Parcel area.',
            $overAllocated->map(fn (Parcel $parcel) => [
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
                'parcel_area_hectares' => (float) $parcel->area_hectares,
                'active_landholding_area_hectares' => (float) $parcel->active_landholding_area,
            ])->all()
        );

        $this->addQueryIssue(
            $issues,
            'landowner_user_role_mismatch',
            'A Landowner record is linked to a non-Landowner user account.',
            DB::table('landowners as l')
                ->join('users as u', 'u.id', '=', 'l.user_id')
                ->whereNotNull('l.user_id')
                ->where('u.role', '<>', 'landowner'),
            ['l.id as landowner_id', 'l.user_id', 'u.role']
        );

        $this->scanLocations($issues);
        $this->scanOrphans($issues);
        $this->scanPartyShares($issues);
        $this->scanSourceReferenceDuplicates($issues);

        return [
            'clean' => empty($issues),
            'issue_groups' => count($issues),
            'issue_count' => collect($issues)->sum('count'),
            'generated_at' => now()->toIso8601String(),
            'read_only' => true,
            'issues' => $issues,
        ];
    }

    private function scanLocations(array &$issues): void
    {
        $service = app(DarLocationService::class);
        $models = [
            'parcel' => Parcel::class,
            'landowner' => Landowner::class,
            'application' => LandTransferApplication::class,
            'source_record_package' => SourceRecordPackage::class,
            'source_record' => LegacyRecord::class,
        ];

        foreach ($models as $label => $modelClass) {
            $invalid = [];

            $modelClass::query()
                ->select(array_values(array_filter([
                    'id',
                    'municipality',
                    'barangay',
                    in_array($label, ['application'], true) ? null : 'province',
                ])))
                ->orderBy('id')
                ->chunkById(500, function ($records) use (&$invalid, $service, $label) {
                    foreach ($records as $record) {
                        if (! filled($record->municipality) && ! filled($record->barangay)) {
                            continue;
                        }

                        $inspection = $service->inspect(
                            $record->municipality,
                            $record->barangay,
                            $label === 'application' ? null : $record->province
                        );

                        if (! $inspection['valid']) {
                            $invalid[] = [
                                'id' => $record->id,
                                'municipality' => $record->municipality,
                                'barangay' => $record->barangay,
                                'errors' => $inspection['errors'],
                            ];
                        }
                    }
                });

            $this->addCollectionIssue(
                $issues,
                'invalid_'.$label.'_location',
                ucfirst(str_replace('_', ' ', $label)).' contains an invalid municipality/barangay pairing.',
                $invalid
            );
        }
    }

    private function scanOrphans(array &$issues): void
    {
        $checks = [
            ['orphan_landholding_landowner', 'Landholding references a missing Landowner.', 'landholdings as h', 'landowners as l', 'l.id', 'h.landowner_id', ['h.id', 'h.landowner_id', 'h.parcel_id']],
            ['orphan_landholding_parcel', 'Landholding references a missing Parcel.', 'landholdings as h', 'parcels as p', 'p.id', 'h.parcel_id', ['h.id', 'h.landowner_id', 'h.parcel_id']],
            ['orphan_application_parcel_application', 'ApplicationParcel references a missing application.', 'application_parcels as ap', 'land_transfer_applications as a', 'a.id', 'ap.land_transfer_application_id', ['ap.id', 'ap.land_transfer_application_id', 'ap.parcel_id']],
        ];

        foreach ($checks as [$code, $message, $from, $join, $joinId, $foreignKey, $columns]) {
            $query = DB::table($from)
                ->leftJoin($join, $joinId, '=', $foreignKey)
                ->whereNull($joinId);

            $this->addQueryIssue($issues, $code, $message, $query, $columns);
        }

        $this->addQueryIssue(
            $issues,
            'application_parcel_missing_master',
            'ApplicationParcel snapshot no longer has a linked Parcel master record; review for archival consistency.',
            DB::table('application_parcels as ap')
                ->leftJoin('parcels as p', 'p.id', '=', 'ap.parcel_id')
                ->whereNotNull('ap.parcel_id')
                ->whereNull('p.id'),
            ['ap.id', 'ap.land_transfer_application_id', 'ap.parcel_id', 'ap.parcel_code']
        );
    }

    private function scanPartyShares(array &$issues): void
    {
        $invalid = [];
        $service = app(ApplicationPartyShareIntegrityService::class);

        LandTransferApplication::query()
            ->with('applicationParcels')
            ->orderBy('id')
            ->chunkById(200, function ($applications) use (&$invalid, $service) {
                foreach ($applications as $application) {
                    $inspection = $service->inspect($application);
                    if (! $inspection['valid']) {
                        $invalid[] = [
                            'application_id' => $application->id,
                            'application_code' => $application->application_code,
                            'issues' => $inspection['issues'],
                        ];
                    }
                }
            });

        $this->addCollectionIssue(
            $issues,
            'invalid_transferee_parcel_shares',
            'Explicit transferee shares are incomplete, stale, negative, or do not equal the transferred area.',
            $invalid
        );
    }

    private function scanSourceReferenceDuplicates(array &$issues): void
    {
        $checks = [
            [LegacyRecord::TYPE_TITLE, 'title_number'],
            [LegacyRecord::TYPE_LANDHOLDING, 'landholding_reference_number'],
            [LegacyRecord::TYPE_PARCEL_SOURCE, 'parcel_code'],
            [LegacyRecord::TYPE_HISTORICAL_CLEARANCE, 'control_number'],
        ];

        foreach ($checks as [$type, $field]) {
            $duplicates = DB::table('legacy_records')
                ->where('record_type', $type)
                ->whereNotNull($field)
                ->where($field, '<>', '')
                ->selectRaw('LOWER('.$field.') as normalized_reference, COUNT(*) as duplicate_count')
                ->groupByRaw('LOWER('.$field.')')
                ->havingRaw('COUNT(*) > 1');

            $this->addQueryIssue(
                $issues,
                'duplicate_source_'.$field,
                'Duplicate '.$field.' values exist in '.$type.' source records.',
                $duplicates,
                ['normalized_reference', 'duplicate_count']
            );
        }
    }

    private function addQueryIssue(array &$issues, string $code, string $message, $query, array $columns): void
    {
        $count = (clone $query)->count();
        if ($count === 0) {
            return;
        }

        $samples = (clone $query)->limit(25)->get($columns)->map(fn ($row) => (array) $row)->all();
        $issues[] = compact('code', 'message', 'count', 'samples');
    }

    private function addCollectionIssue(array &$issues, string $code, string $message, array $rows): void
    {
        $count = count($rows);
        if ($count === 0) {
            return;
        }

        $samples = array_slice($rows, 0, 25);
        $issues[] = compact('code', 'message', 'count', 'samples');
    }
}
