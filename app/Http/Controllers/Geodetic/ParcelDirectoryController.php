<?php

namespace App\Http\Controllers\Geodetic;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Services\ParcelMapBounds;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParcelDirectoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'geometry' => ['nullable', Rule::in(['all', 'mapped', 'unmapped', 'unavailable'])],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $filters = [
            'q' => trim($filters['q'] ?? ''),
            'geometry' => $filters['geometry'] ?? 'all',
            'status' => $filters['status'] ?? 'active',
        ];

        $query = Parcel::query()->select([
            'id', 'parcel_code', 'title_no', 'tax_decl_no', 'survey_plan_number',
            'municipality', 'barangay', 'province', 'area_hectares', 'status',
            ...ParcelMapBounds::COLUMNS,
        ])->selectRaw('CASE WHEN geometry_geojson IS NULL THEN 0 ELSE 1 END as has_stored_geometry')
            ->withCount('landholdings')
            ->with([
                'landholdings' => fn ($q) => $q->select('id', 'parcel_id', 'landowner_id', 'status')
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                    ->orderBy('id')->limit(4),
                'landholdings.landowner:id,first_name,middle_name,last_name,suffix',
            ]);

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if ($filters['geometry'] === 'unmapped') {
            $query->whereNull('geometry_geojson');
        } elseif ($filters['geometry'] !== 'all') {
            $query->whereNotNull('geometry_geojson');
            if ($filters['geometry'] === 'mapped') {
                foreach (ParcelMapBounds::COLUMNS as $column) {
                    $query->whereNotNull($column);
                }
            } else {
                $query->where(function ($q) {
                    foreach (ParcelMapBounds::COLUMNS as $column) {
                        $q->orWhereNull($column);
                    }
                });
            }
        }
        if ($filters['q'] !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']).'%';
            $query->where(function ($q) use ($like) {
                foreach (['parcel_code', 'title_no', 'tax_decl_no', 'survey_plan_number', 'municipality', 'barangay'] as $column) {
                    $q->orWhereRaw("LOWER(".$column.") LIKE LOWER(?) ESCAPE '!'", [$like]);
                }
                foreach (['landholdings.landowner', 'legacyRecords.landowner', 'sourceRecordPackages.landowner'] as $relation) {
                    $q->orWhereHas($relation, function ($owner) use ($like) {
                        $owner->whereRaw(
                            "LOWER(TRIM(COALESCE(first_name, '') || ' ' ||
                                COALESCE(NULLIF(middle_name, '') || ' ', '') || COALESCE(last_name, '') ||
                                COALESCE(' ' || NULLIF(suffix, ''), ''))) LIKE LOWER(?) ESCAPE '!'",
                            [$like]
                        );
                    });
                }
            });
        }

        $parcels = $query->orderBy('parcel_code')->orderBy('id')->paginate(20)->withQueryString();

        return view('geodetic.parcels.directory', compact('parcels', 'filters'));
    }
}
