<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Parcel;

class ParcelMapController extends Controller
{
    public function index()
    {
        $parcelFeatures = Parcel::query()
            ->with(['landholdings.landowner', 'legacyRecords.landowner', 'sourceRecordPackages.landowner'])
            ->whereNotNull('geometry_geojson')
            ->orderBy('municipality')
            ->orderBy('barangay')
            ->orderBy('parcel_code')
            ->get()
            ->map(function (Parcel $parcel) {
                $geometry = $parcel->geometry_geojson;

                if (! is_array($geometry)) {
                    return null;
                }

                if (empty($geometry['type']) || empty($geometry['coordinates'])) {
                    return null;
                }

                $activeLandholdingOwners = $parcel->landholdings
                    ->filter(fn ($landholding) => strtolower((string) $landholding->status) === 'active')
                    ->map(fn ($landholding) => $landholding->landowner?->full_name)
                    ->filter()
                    ->unique()
                    ->values();

                $anyLandholdingOwners = $parcel->landholdings
                    ->map(fn ($landholding) => $landholding->landowner?->full_name)
                    ->filter()
                    ->unique()
                    ->values();

                $sourceLinkedOwners = collect()
                    ->merge($parcel->legacyRecords->map(fn ($record) => $record->landowner?->full_name))
                    ->merge($parcel->sourceRecordPackages->map(fn ($package) => $package->landowner?->full_name))
                    ->filter()
                    ->unique()
                    ->values();

                $landownerNames = $activeLandholdingOwners->isNotEmpty()
                    ? $activeLandholdingOwners->implode(', ')
                    : ($anyLandholdingOwners->isNotEmpty()
                        ? $anyLandholdingOwners->implode(', ') . ' (non-active landholding)'
                        : ($sourceLinkedOwners->isNotEmpty()
                            ? $sourceLinkedOwners->implode(', ') . ' (source-linked)'
                            : 'No linked landowner record'));

                return [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $parcel->id,
                        'details_url' => route('staff.records.parcels.show', $parcel),
                        'parcel_code' => $parcel->parcel_code,
                        'title_no' => $parcel->title_no ?: 'N/A',
                        'tax_decl_no' => $parcel->tax_decl_no ?: 'N/A',
                        'landowner' => $landownerNames,
                        'municipality' => $parcel->municipality ?: 'N/A',
                        'barangay' => $parcel->barangay ?: 'N/A',
                        'area_hectares' => $parcel->area_hectares ?: 'N/A',
                        'status' => $parcel->status ?: 'active',
                    ],
                    'geometry' => $geometry,
                ];
            })
            ->filter()
            ->values();

        $parcelGeoJson = [
            'type' => 'FeatureCollection',
            'features' => $parcelFeatures,
        ];

        return view('staff.maps.parcel-map', compact('parcelGeoJson'));
    }
}