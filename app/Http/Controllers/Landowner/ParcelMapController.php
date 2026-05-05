<?php

namespace App\Http\Controllers\Landowner;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Parcel;
use Illuminate\Support\Facades\Auth;

class ParcelMapController extends Controller
{
    public function index()
    {
        $landowner = Auth::user()->landowner;

        $landholdings = collect();

        if ($landowner) {
            $landholdings = Landholding::query()
                ->with('parcel')
                ->where('landowner_id', $landowner->id)
                ->whereHas('parcel', function ($query) {
                    $query->whereNotNull('geometry_geojson');
                })
                ->orderByDesc('created_at')
                ->get();
        }

        $parcelFeatures = $landholdings
            ->filter(fn (Landholding $landholding) => $landholding->parcel !== null)
            ->unique('parcel_id')
            ->map(function (Landholding $landholding) use ($landowner) {
                $parcel = $landholding->parcel;
                $geometry = $parcel->geometry_geojson;

                if (! is_array($geometry)) {
                    return null;
                }

                if (empty($geometry['type']) || empty($geometry['coordinates'])) {
                    return null;
                }

                return [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $parcel->id,
                        'details_url' => route('landowner.parcels.show', $parcel),
                        'parcel_code' => $parcel->parcel_code,
                        'title_no' => $parcel->title_no ?: 'N/A',
                        'tax_decl_no' => $parcel->tax_decl_no ?: 'N/A',
                        'landowner' => $landowner?->full_name ?: 'Your landowner account',
                        'municipality' => $parcel->municipality ?: 'N/A',
                        'barangay' => $parcel->barangay ?: 'N/A',
                        'area_hectares' => $landholding->area_hectares ?: $parcel->area_hectares ?: 'N/A',
                        'status' => $landholding->status ?: $parcel->status ?: 'active',
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

        return view('landowner.maps.parcel-map', compact('parcelGeoJson'));
    }

    public function show(Parcel $parcel)
    {
        $landowner = Auth::user()->landowner;

        if (! $landowner) {
            abort(403);
        }

        $landholdings = Landholding::query()
            ->with('parcel')
            ->where('landowner_id', $landowner->id)
            ->where('parcel_id', $parcel->id)
            ->orderByDesc('created_at')
            ->get();

        if ($landholdings->isEmpty()) {
            abort(403);
        }

        return view('landowner.parcels.show', compact('parcel', 'landholdings', 'landowner'));
    }
}