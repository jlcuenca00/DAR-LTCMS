<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use App\Models\Landowner;
use App\Models\LegacyRecord;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use Illuminate\Support\Facades\DB;

class StaffDashboardController extends Controller
{
    public function __invoke()
    {
        $statusCounts = LandTransferApplication::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalApplications = LandTransferApplication::count();

        $statusCards = [
            [
                'label' => 'Total Applications',
                'value' => $totalApplications,
                'description' => 'All encoded clearance applications',
                'border' => 'border-slate-200',
                'accent' => 'bg-slate-800',
            ],
            [
                'label' => 'Pending Review',
                'value' => (int) ($statusCounts[LandTransferApplication::STATUS_PENDING_REVIEW] ?? 0),
                'description' => 'Applications awaiting staff decision',
                'border' => 'border-amber-200',
                'accent' => 'bg-amber-500',
            ],
            [
                'label' => 'Approved Clearances',
                'value' => (int) ($statusCounts[LandTransferApplication::STATUS_APPROVED] ?? 0),
                'description' => 'Finalized clearance approvals only',
                'border' => 'border-green-200',
                'accent' => 'bg-green-600',
            ],
            [
                'label' => 'Not Approved',
                'value' => (int) ($statusCounts[LandTransferApplication::STATUS_NOT_APPROVED] ?? 0),
                'description' => 'Finalized non-approval decisions',
                'border' => 'border-red-200',
                'accent' => 'bg-red-600',
            ],
        ];

        $statusDistribution = collect([
            LandTransferApplication::STATUS_DRAFT => 'Draft',
            LandTransferApplication::STATUS_PENDING_REVIEW => 'Pending Review',
            LandTransferApplication::STATUS_APPROVED => 'Approved Clearances',
            LandTransferApplication::STATUS_NOT_APPROVED => 'Not Approved',
        ])->map(function ($label, $status) use ($statusCounts, $totalApplications) {
            $count = (int) ($statusCounts[$status] ?? 0);

            return [
                'status' => $status,
                'label' => $label,
                'count' => $count,
                'percentage' => $totalApplications > 0
                    ? round(($count / $totalApplications) * 100)
                    : 0,
            ];
        })->values();

        $monthlyApplications = collect(range(5, 0))
            ->map(function ($monthsBack) {
                $date = now()->startOfMonth()->subMonths($monthsBack);

                $count = LandTransferApplication::query()
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();

                return [
                    'label' => $date->format('M'),
                    'count' => $count,
                ];
            });

        $maxMonthlyCount = max((int) $monthlyApplications->max('count'), 1);

        $municipalityBreakdown = LandTransferApplication::query()
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

        $recentApplications = LandTransferApplication::query()
            ->latest()
            ->limit(6)
            ->get();

        $recentAuditLogs = AuditLog::query()
            ->with('actor')
            ->latest()
            ->limit(5)
            ->get();

        $recordsSummary = [
            'landowners' => Landowner::count(),
            'parcels' => Parcel::count(),
            'source_packages' => SourceRecordPackage::count(),
            'legacy_records' => LegacyRecord::count(),
        ];

        return view('dashboards.staff', compact(
            'statusCards',
            'statusDistribution',
            'monthlyApplications',
            'maxMonthlyCount',
            'municipalityBreakdown',
            'recentApplications',
            'recentAuditLogs',
            'recordsSummary'
        ));
    }
}