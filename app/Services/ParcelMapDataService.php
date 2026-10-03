<?php

namespace App\Services;

use App\Models\Parcel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ParcelMapDataService
{
    public const FEATURE_LIMIT = 50;
    public const SEARCH_LIMIT = 15;
    public const GEOMETRY_BYTE_LIMIT = 200000;
    public const RESPONSE_BYTE_LIMIT = 1000000;

    private function visible(User $user): Builder
    {
        $query = Parcel::query()->where('status', 'active')->whereNotNull('geometry_geojson');
        if ($user->role === 'landowner') {
            $ownerId = $user->landowner?->id;
            $query->whereHas('landholdings', fn ($q) => $q->where('landowner_id', $ownerId ?? 0));
        } else {
            abort_unless(in_array($user->role, ['staff', 'geodetic'], true), 403);
        }

        return $query;
    }

    public function scoped(User $user): Builder
    {
        $query = $this->visible($user);
        foreach (ParcelMapBounds::COLUMNS as $column) {
            $query->whereNotNull($column);
        }

        return $query;
    }

    private function metadataQuery(User $user): Builder
    {
        $query = $this->scoped($user)->select([
            'id', 'parcel_code', 'title_no', 'tax_decl_no', 'municipality', 'barangay',
            'area_hectares', 'status', 'is_flagged', 'flag_reason', ...ParcelMapBounds::COLUMNS,
        ]);
        $ownerId = $user->role === 'landowner' ? ($user->landowner?->id ?? 0) : null;
        $scope = function ($q) use ($ownerId) {
            if ($ownerId !== null) {
                $q->where('landowner_id', $ownerId);
            }
        };
        $query->withCount([
            'landholdings as active_holding_count' => function ($q) use ($scope) { $scope($q); $q->where('status', 'active'); },
            'landholdings as historical_holding_count' => function ($q) use ($scope) { $scope($q); $q->where('status', '!=', 'active'); },
        ])->withSum([
            'landholdings as active_linked_area' => function ($q) use ($scope) { $scope($q); $q->where('status', 'active'); },
        ], 'area_hectares');

        // Eager-load only a bounded preview of owner names per parcel.
        $relations = [
            'landholdings' => function ($q) use ($scope) {
                $scope($q);
                $q->select('id', 'parcel_id', 'landowner_id', 'status')
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")->orderBy('id')->limit(4);
            },
            'landholdings.landowner:id,first_name,middle_name,last_name,suffix',
        ];
        if ($user->role !== 'landowner') {
            $query->withCount(['legacyRecords', 'sourceRecordPackages']);
            $relations = array_merge($relations, [
            'legacyRecords' => fn ($q) => $q->select('id', 'parcel_id', 'landowner_id')->orderBy('id')->limit(4),
            'legacyRecords.landowner:id,first_name,middle_name,last_name,suffix',
            'sourceRecordPackages' => fn ($q) => $q->select('id', 'parcel_id', 'landowner_id')->orderBy('id')->limit(4),
            'sourceRecordPackages.landowner:id,first_name,middle_name,last_name,suffix',
            ]);
        }

        return $query->with($relations);
    }

    public function config(User $user): array
    {
        $extent = $this->scoped($user)->toBase()->selectRaw(
            'COUNT(*) as total, MIN(map_min_longitude) as west, MIN(map_min_latitude) as south,
             MAX(map_max_longitude) as east, MAX(map_max_latitude) as north'
        )->first();
        $role = $user->role;

        return [
            'role' => $role,
            'total' => (int) $extent->total,
            'unavailable_total' => $this->visible($user)->count() - (int) $extent->total,
            'bounds' => $extent->total ? [(float) $extent->west, (float) $extent->south, (float) $extent->east, (float) $extent->north] : null,
            'features_url' => route($role.'.parcel-map.features'),
            'search_url' => route($role.'.parcel-map.search'),
            'focus_url' => route($role.'.parcel-map.feature', ['parcel' => '__ID__']),
            'initial_search' => $this->search($user, '', 1),
        ];
    }

    public function search(User $user, string $text, int $page): array
    {
        $query = $this->metadataQuery($user);
        if ($text !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text).'%';
            $query->where(function ($q) use ($like, $user) {
                foreach (['parcel_code', 'title_no', 'tax_decl_no', 'municipality', 'barangay'] as $column) {
                    $q->orWhereRaw("LOWER(".$column.") LIKE LOWER(?) ESCAPE '!'", [$like]);
                }
                if ($user->role !== 'landowner') {
                    foreach (['landholdings.landowner', 'legacyRecords.landowner', 'sourceRecordPackages.landowner'] as $relation) {
                        $q->orWhereHas($relation, function ($owner) use ($like) {
                            $owner->where(function ($names) use ($like) {
                                foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $column) {
                                    $names->orWhereRaw("LOWER(".$column.") LIKE LOWER(?) ESCAPE '!'", [$like]);
                                }
                                $names->orWhereRaw(
                                    "LOWER(TRIM(COALESCE(first_name, '') || ' ' ||
                                        COALESCE(NULLIF(middle_name, '') || ' ', '') || COALESCE(last_name, '') ||
                                        COALESCE(' ' || NULLIF(suffix, ''), ''))) LIKE LOWER(?) ESCAPE '!'", [$like]
                                );
                            });
                        });
                    }
                }
            });
        }
        $results = $query->orderBy('parcel_code')->orderBy('id')->paginate(self::SEARCH_LIMIT, ['*'], 'page', $page);

        return [
            'items' => $results->getCollection()->map(fn ($p) => $this->properties($p, $user))->values()->all(),
            'total' => $results->total(), 'page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
        ];
    }

    public function features(User $user, array $bounds): array
    {
        $query = $this->metadataQuery($user);
        [$west, $south, $east, $north] = $bounds;
        if (DB::getDriverName() === 'pgsql') {
            $query->whereRaw(
                'box(point(map_min_longitude, map_min_latitude), point(map_max_longitude, map_max_latitude))
                 && box(point(?, ?), point(?, ?))', [$west, $south, $east, $north]
            );
        } else {
            $query->where('map_min_longitude', '<=', $east)->where('map_max_longitude', '>=', $west)
                ->where('map_min_latitude', '<=', $north)->where('map_max_latitude', '>=', $south);
        }
        $total = (clone $query)->toBase()->count();
        // SQL excludes oversized legacy geometry before materializing it in PHP.
        $safe = clone $query;
        $this->withinGeometryBudget($safe);
        $parcels = $safe->addSelect('geometry_geojson')->orderBy('id')->limit(self::FEATURE_LIMIT)->get();
        $features = [];
        $bytes = 0;
        $skipped = $total - (clone $safe)->toBase()->count();
        foreach ($parcels as $parcel) {
            $feature = $this->feature($parcel, $user);
            $size = strlen(json_encode($feature));
            if ($feature === null || $bytes + $size > self::RESPONSE_BYTE_LIMIT) {
                $skipped++;
                continue;
            }
            $features[] = $feature;
            $bytes += $size;
        }

        return ['type' => 'FeatureCollection', 'features' => $features, 'total' => $total,
            'returned' => count($features), 'limited' => count($features) < $total, 'skipped' => $skipped,
            'limit' => self::FEATURE_LIMIT];
    }

    private function withinGeometryBudget(Builder $query): void
    {
        $length = DB::getDriverName() === 'pgsql' ? 'octet_length(CAST(geometry_geojson AS text))' : 'length(CAST(geometry_geojson AS BLOB))';
        $query->whereRaw($length.' <= ?', [self::GEOMETRY_BYTE_LIMIT]);
    }

    public function focus(User $user, int $id): array
    {
        $query = $this->metadataQuery($user)->whereKey($id);
        $this->withinGeometryBudget($query);
        $parcel = $query->addSelect('geometry_geojson')->firstOrFail();
        $feature = $this->feature($parcel, $user);
        abort_if($feature === null, 422, 'This parcel geometry cannot be displayed. Open its record for review.');

        return $feature;
    }

    private function feature(Parcel $parcel, User $user): ?array
    {
        if (ParcelMapBounds::fromGeometry($parcel->geometry_geojson) === null) {
            return null;
        }

        return ['type' => 'Feature', 'properties' => $this->properties($parcel, $user), 'geometry' => $parcel->geometry_geojson];
    }

    private function properties(Parcel $parcel, User $user): array
    {
        $active = $parcel->landholdings->where('status', 'active');
        $preview = $parcel->active_holding_count ? $active : $parcel->landholdings;
        $names = $preview->map(fn ($holding) => $holding->landowner?->full_name)->filter()->unique()->implode(', ');
        $label = $parcel->active_holding_count ? 'Active landholding reference' : 'Historical/non-active landholding reference';
        if ($user->role === 'landowner') {
            $names = $user->landowner?->full_name ?: 'Your landowner account';
        } elseif ($names === '') {
            $names = $parcel->legacyRecords->merge($parcel->sourceRecordPackages)
                ->map(fn ($record) => $record->landowner?->full_name)->filter()->unique()->implode(', ');
            $label = $names !== '' ? 'Source-linked reference' : 'No linked landowner record';
            if ($parcel->legacy_records_count + $parcel->source_record_packages_count > $parcel->legacyRecords->count() + $parcel->sourceRecordPackages->count()) {
                $names .= ' (additional source records)';
            }
        }
        $count = $parcel->active_holding_count ?: $parcel->historical_holding_count;
        if ($count > $preview->count()) {
            $names .= ' (additional holding records)';
        }
        $route = $user->role === 'staff' ? 'staff.records.parcels.show' : $user->role.'.parcels.show';

        return [
            'id' => $parcel->id, 'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no ?: 'N/A', 'tax_decl_no' => $parcel->tax_decl_no ?: 'N/A',
            'municipality' => $parcel->municipality ?: 'N/A', 'barangay' => $parcel->barangay ?: 'N/A',
            'landowner' => $names ?: 'No linked landowner record', 'reference_scope' => $label,
            'area_hectares' => $parcel->area_hectares,
            'active_linked_area_hectares' => number_format((float) ($parcel->active_linked_area ?? 0), 4, '.', ''),
            'historical_holding_count' => (int) $parcel->historical_holding_count,
            'details_url' => route($route, $parcel),
            'bounds' => array_map(fn ($column) => (float) $parcel->getAttribute($column), ParcelMapBounds::COLUMNS),
            'status' => $parcel->is_flagged ? 'flagged' : 'active',
            'is_flagged' => (bool) $parcel->is_flagged,
            'flag_reason' => $parcel->is_flagged ? $parcel->flag_reason_label : null,
        ];
    }
}
