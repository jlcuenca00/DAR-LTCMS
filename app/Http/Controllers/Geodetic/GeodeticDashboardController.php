<?php

namespace App\Http\Controllers\Geodetic;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Parcel;

class GeodeticDashboardController extends Controller
{
    public function __invoke()
    {
        $totalParcels = Parcel::count();
        $mappedParcels = Parcel::query()
            ->whereNotNull('geometry_geojson')
            ->count();

        $landholdingReferences = Landholding::count();

        $dashboardCards = [
            [
                'label' => 'Parcel References',
                'value' => $totalParcels,
                'description' => 'Main parcel records available for geodetic review.',
                'icon' => 'fa-map-location-dot',
                'tone' => 'green',
            ],
            [
                'label' => 'Mapped Parcels',
                'value' => $mappedParcels,
                'description' => 'Parcel records with stored map geometry.',
                'icon' => 'fa-draw-polygon',
                'tone' => 'blue',
            ],
            [
                'label' => 'Landholding References',
                'value' => $landholdingReferences,
                'description' => 'Landholding records available for read-only checking.',
                'icon' => 'fa-layer-group',
                'tone' => 'slate',
            ],
        ];

        $municipalityBreakdown = Parcel::query()
            ->selectRaw('municipality, COUNT(*) as total')
            ->groupBy('municipality')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'municipality' => $row->municipality ?: 'Unspecified',
                    'total' => (int) $row->total,
                ];
            });

        $recentParcels = Parcel::query()
            ->latest()
            ->limit(6)
            ->get();

        return view('dashboards.geodetic', compact(
            'dashboardCards',
            'municipalityBreakdown',
            'recentParcels'
        ));
    }
}
