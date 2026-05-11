<?php

namespace App\Http\Controllers\Landowner;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LandownerDashboardController extends Controller
{
    public function __invoke()
    {
        $landownerIds = Landowner::query()
            ->where('user_id', Auth::id())
            ->pluck('id');

        $landowner = Landowner::query()
            ->where('user_id', Auth::id())
            ->first();

        $landholdingsQuery = Landholding::query()
            ->with(['parcel', 'landowner'])
            ->whereIn('landowner_id', $landownerIds);

        $applicationQuery = LandTransferApplication::query()
            ->with([
                'transferorLandowner',
                'transfereeLandowner',
                'applicationParcels.parcel',
                'clearance',
            ])
            ->where(function ($query) use ($landownerIds) {
                $query->whereIn('transferor_landowner_id', $landownerIds)
                    ->orWhereIn('transferee_landowner_id', $landownerIds);
            });

        $statusCounts = (clone $applicationQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $linkedParcelCount = (clone $landholdingsQuery)
            ->whereNotNull('parcel_id')
            ->distinct('parcel_id')
            ->count('parcel_id');

        $mappedParcelCount = (clone $landholdingsQuery)
            ->whereHas('parcel', function ($query) {
                $query->whereNotNull('geometry_geojson');
            })
            ->distinct('parcel_id')
            ->count('parcel_id');

        $landholdingCount = (clone $landholdingsQuery)->count();
        $applicationCount = (clone $applicationQuery)->count();

        $finalDecisionCount = (clone $applicationQuery)
            ->whereIn('status', LandTransferApplication::FINAL_STATUSES)
            ->count();

        $statusSummary = collect([
            LandTransferApplication::STATUS_DRAFT => 'Draft',
            LandTransferApplication::STATUS_PENDING_REVIEW => 'Pending Review',
            LandTransferApplication::STATUS_APPROVED => 'Approved Clearance',
            LandTransferApplication::STATUS_NOT_APPROVED => 'Not Approved',
        ])->map(function ($label, $status) use ($statusCounts) {
            return [
                'status' => $status,
                'label' => $label,
                'count' => (int) ($statusCounts[$status] ?? 0),
            ];
        })->values();

        $recentApplications = (clone $applicationQuery)
            ->latest()
            ->limit(5)
            ->get();

        $recentLandholdings = (clone $landholdingsQuery)
            ->latest()
            ->limit(5)
            ->get();

        $dashboardCards = [
            [
                'label' => 'Linked Parcels',
                'value' => $linkedParcelCount,
                'description' => 'Parcel records connected to your landowner account',
                'icon' => 'fa-map-location-dot',
                'tone' => 'green',
            ],
            [
                'label' => 'Landholding Records',
                'value' => $landholdingCount,
                'description' => 'Read-only landholding references linked to you',
                'icon' => 'fa-layer-group',
                'tone' => 'slate',
            ],
            [
                'label' => 'My Applications',
                'value' => $applicationCount,
                'description' => 'Clearance applications where you are linked',
                'icon' => 'fa-file-lines',
                'tone' => 'amber',
            ],
            [
                'label' => 'Mapped Parcels',
                'value' => $mappedParcelCount,
                'description' => 'Linked parcels with available map geometry',
                'icon' => 'fa-map',
                'tone' => 'blue',
            ],
        ];

        return view('dashboards.landowner', compact(
            'landowner',
            'dashboardCards',
            'statusSummary',
            'recentApplications',
            'recentLandholdings',
            'finalDecisionCount'
        ));
    }
}
