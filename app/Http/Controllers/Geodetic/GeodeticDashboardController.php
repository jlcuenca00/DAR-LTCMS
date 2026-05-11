<?php

namespace App\Http\Controllers\Geodetic;

use App\Http\Controllers\Controller;
use App\Models\LandTransferApplication;
use App\Models\Landholding;
use App\Models\Parcel;
use Illuminate\Support\Facades\DB;

class GeodeticDashboardController extends Controller
{
    public function __invoke()
    {
        $totalParcels = Parcel::count();
        $mappedParcels = Parcel::query()
            ->whereNotNull('geometry_geojson')
            ->count();

        $landholdingReferences = Landholding::count();
        $applicationReferences = LandTransferApplication::count();

        $pendingApplications = LandTransferApplication::query()
            ->where('status', LandTransferApplication::STATUS_PENDING_REVIEW)
            ->count();

        $finalizedApplications = LandTransferApplication::query()
            ->whereIn('status', LandTransferApplication::FINAL_STATUSES)
            ->count();

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
            [
                'label' => 'Application References',
                'value' => $applicationReferences,
                'description' => 'Clearance applications visible for reference review only.',
                'icon' => 'fa-file-lines',
                'tone' => 'amber',
            ],
        ];

        $statusCounts = LandTransferApplication::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusDistribution = collect([
            LandTransferApplication::STATUS_DRAFT => 'Draft',
            LandTransferApplication::STATUS_PENDING_REVIEW => 'Pending Review',
            LandTransferApplication::STATUS_APPROVED => 'Approved Clearances',
            LandTransferApplication::STATUS_NOT_APPROVED => 'Not Approved',
        ])->map(function ($label, $status) use ($statusCounts, $applicationReferences) {
            $count = (int) ($statusCounts[$status] ?? 0);

            return [
                'status' => $status,
                'label' => $label,
                'count' => $count,
                'percentage' => $applicationReferences > 0
                    ? round(($count / $applicationReferences) * 100)
                    : 0,
            ];
        })->values();

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

        $recentApplications = LandTransferApplication::query()
            ->with(['applicationParcels.parcel', 'clearance'])
            ->latest()
            ->limit(6)
            ->get();

        return view('dashboards.geodetic', compact(
            'dashboardCards',
            'pendingApplications',
            'finalizedApplications',
            'statusDistribution',
            'municipalityBreakdown',
            'recentParcels',
            'recentApplications'
        ));
    }
}
