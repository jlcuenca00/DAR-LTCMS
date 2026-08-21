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
        // Resolve the user's Landowner records once. Keeping the ID collection
        // preserves support for historical accounts linked to more than one record.
        $landowners = Landowner::query()
            ->where('user_id', Auth::id())
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix']);

        $landownerIds = $landowners->pluck('id');
        $landowner = $landowners->first();

        $landholdingsQuery = Landholding::query()
            ->select([
                'id',
                'landowner_id',
                'parcel_id',
                'area_hectares',
                'status',
                'created_at',
            ])
            ->with(['parcel' => function ($query) {
                $query->select([
                    'id',
                    'parcel_code',
                    'municipality',
                    'barangay',
                    'geometry_geojson',
                ]);
            }])
            ->whereIn('landowner_id', $landownerIds);

        // The dashboard reads application scalar/JSON fields only. Avoid loading
        // parcels, clearance and party relationships that are unused by the view.
        $applicationQuery = LandTransferApplication::query()
            ->linkedToLandownerIds($landownerIds);

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
        $applicationCount = $statusCounts->sum(fn ($count) => (int) $count);

        $statusSummary = collect([
            [
                'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                'label' => 'Pending Review by Legal Officer',
                'statuses' => [
                    LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                    LandTransferApplication::STATUS_DRAFT,
                    LandTransferApplication::STATUS_PENDING_REVIEW,
                ],
            ],
            [
                'status' => LandTransferApplication::STATUS_ENDORSED_LTI,
                'label' => 'Endorsed to LTI Division',
                'statuses' => [LandTransferApplication::STATUS_ENDORSED_LTI],
            ],
            [
                'status' => LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
                'label' => 'Endorsed to Chief Legal',
                'statuses' => [LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL],
            ],
            [
                'status' => LandTransferApplication::STATUS_ENDORSED_PARPO,
                'label' => 'Endorsed to PARPO II',
                'statuses' => [LandTransferApplication::STATUS_ENDORSED_PARPO],
            ],
            [
                'status' => LandTransferApplication::STATUS_FOR_RELEASING,
                'label' => 'For Releasing',
                'statuses' => [LandTransferApplication::STATUS_FOR_RELEASING],
            ],
            [
                'status' => LandTransferApplication::STATUS_RELEASED,
                'label' => 'Released',
                'statuses' => [
                    LandTransferApplication::STATUS_RELEASED,
                    LandTransferApplication::STATUS_APPROVED,
                ],
            ],
            [
                'status' => LandTransferApplication::STATUS_DENIED,
                'label' => 'Denied',
                'statuses' => [
                    LandTransferApplication::STATUS_DENIED,
                    LandTransferApplication::STATUS_NOT_APPROVED,
                ],
            ],
        ])->map(function (array $summary) use ($statusCounts) {
            $summary['count'] = collect($summary['statuses'])
                ->sum(fn (string $status) => (int) ($statusCounts[$status] ?? 0));

            unset($summary['statuses']);

            return $summary;
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
            'recentLandholdings'
        ));
    }
}
