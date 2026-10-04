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
        $landowners = Landowner::query()
            ->where('user_id', Auth::id())
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix']);

        $landownerIds = $landowners->pluck('id');
        $landowner = $landowners->first();

        $landholdingsQuery = Landholding::query()
            ->select(['id', 'landowner_id', 'parcel_id', 'area_hectares', 'status', 'created_at'])
            ->with(['parcel' => function ($query) {
                $query->select(['id', 'parcel_code', 'municipality', 'barangay', 'geometry_geojson']);
            }])
            ->whereIn('landowner_id', $landownerIds);

        $applicationQuery = LandTransferApplication::query()->linkedToLandownerIds($landownerIds);

        $statusCounts = (clone $applicationQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $linkedParcelCount = (clone $landholdingsQuery)
            ->whereNotNull('parcel_id')->distinct('parcel_id')->count('parcel_id');

        $mappedParcelCount = (clone $landholdingsQuery)
            ->whereHas('parcel', fn ($query) => $query->whereNotNull('geometry_geojson'))
            ->distinct('parcel_id')->count('parcel_id');

        $landholdingCount = (clone $landholdingsQuery)->count();
        $applicationCount = $statusCounts->sum(fn ($count) => (int) $count);

        $statusSummary = collect([
            [
                'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                'label' => 'Legal Completeness Review',
                'statuses' => [
                    LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                    LandTransferApplication::STATUS_DRAFT,
                    LandTransferApplication::STATUS_PENDING_REVIEW,
                ],
            ],
            [
                'status' => LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE,
                'label' => 'Compliance Required',
                'statuses' => [LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE],
            ],
            [
                'status' => LandTransferApplication::STATUS_AWAITING_PAYMENT,
                'label' => 'Awaiting Payment / O.R.',
                'statuses' => [LandTransferApplication::STATUS_AWAITING_PAYMENT],
            ],
            [
                'status' => LandTransferApplication::STATUS_ENDORSED_LTI,
                'label' => 'LTID Verification',
                'statuses' => [LandTransferApplication::STATUS_ENDORSED_LTI],
            ],
            [
                'status' => LandTransferApplication::STATUS_RETURNED_TO_LEGAL,
                'label' => 'Returned to Legal',
                'statuses' => [LandTransferApplication::STATUS_RETURNED_TO_LEGAL],
            ],
            [
                'status' => LandTransferApplication::STATUS_LEGAL_EVALUATION,
                'label' => 'Legal Evaluation / CSW',
                'statuses' => [LandTransferApplication::STATUS_LEGAL_EVALUATION],
            ],
            [
                'status' => LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
                'label' => 'Chief Legal Review',
                'statuses' => [LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL],
            ],
            [
                'status' => LandTransferApplication::STATUS_ENDORSED_PARPO,
                'label' => 'Forwarded to PARPO II',
                'statuses' => [LandTransferApplication::STATUS_ENDORSED_PARPO],
            ],
            [
                'status' => LandTransferApplication::STATUS_FOR_RELEASING,
                'label' => 'PARPO II Decision Pending',
                'statuses' => [LandTransferApplication::STATUS_FOR_RELEASING],
            ],
            [
                'status' => LandTransferApplication::STATUS_APPROVED,
                'label' => 'Approved',
                'statuses' => [LandTransferApplication::STATUS_APPROVED, LandTransferApplication::STATUS_RELEASED],
            ],
            [
                'status' => LandTransferApplication::STATUS_NOT_APPROVED,
                'label' => 'Historical Not Approved / Denied',
                'statuses' => [LandTransferApplication::STATUS_NOT_APPROVED, LandTransferApplication::STATUS_DENIED],
            ],
        ])->map(function (array $summary) use ($statusCounts) {
            $summary['count'] = collect($summary['statuses'])
                ->sum(fn (string $status) => (int) ($statusCounts[$status] ?? 0));
            unset($summary['statuses']);
            return $summary;
        })->filter(fn ($summary) => $summary['count'] > 0)->values();

        $complianceApplications = (clone $applicationQuery)
            ->where('status', LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE)
            ->whereHas('activeComplianceNotice')
            ->with('activeComplianceNotice')
            ->latest('returned_for_compliance_at')
            ->orderByDesc('id')
            ->paginate(5, ['*'], 'compliance_page')
            ->withQueryString()
            ->fragment('compliance-alerts');

        $recentApplications = (clone $applicationQuery)
            ->with('activeComplianceNotice')
            ->latest()
            ->limit(5)
            ->get();
        $recentLandholdings = (clone $landholdingsQuery)->latest()->limit(5)->get();

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
            'complianceApplications',
            'recentApplications',
            'recentLandholdings'
        ));
    }
}
