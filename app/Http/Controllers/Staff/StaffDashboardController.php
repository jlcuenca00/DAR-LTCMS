<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationClearance;
use App\Models\LandTransferApplication;
use Illuminate\Support\Facades\DB;

class StaffDashboardController extends Controller
{
    public function __invoke()
    {
        $statusCounts = LandTransferApplication::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $countStatuses = function (array $statuses) use ($statusCounts): int {
            return collect($statuses)
                ->sum(fn ($status) => (int) ($statusCounts[$status] ?? 0));
        };

        $pendingLegalReview = (int) (
            $statusCounts[LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW] ?? 0
        );

        $activeWorkflow = $countStatuses([
            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            LandTransferApplication::STATUS_ENDORSED_LTI,
            LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            LandTransferApplication::STATUS_FOR_RELEASING,
            LandTransferApplication::STATUS_DRAFT,
            LandTransferApplication::STATUS_PENDING_REVIEW,
        ]);

        $forReleasing = (int) (
            $statusCounts[LandTransferApplication::STATUS_FOR_RELEASING] ?? 0
        );

        $workQueue = [
            [
                'label' => 'Pending Legal Review',
                'description' => 'Awaiting initial legal action',
                'value' => $pendingLegalReview,
                'icon' => 'fa-scale-balanced',
                'filter' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            ],
            [
                'label' => 'Active Workflow',
                'description' => 'Applications still being processed',
                'value' => $activeWorkflow,
                'icon' => 'fa-arrows-rotate',
                'filter' => 'all',
            ],
            [
                'label' => 'For Releasing',
                'description' => 'Ready for clearance release',
                'value' => $forReleasing,
                'icon' => 'fa-file-export',
                'filter' => LandTransferApplication::STATUS_FOR_RELEASING,
            ],
        ];

        $activeStatuses = array_values(array_unique(array_merge(
            LandTransferApplication::ACTIVE_STATUSES,
            [
                LandTransferApplication::STATUS_DRAFT,
                LandTransferApplication::STATUS_PENDING_REVIEW,
            ]
        )));

        $actionApplications = LandTransferApplication::query()
            ->whereIn('status', $activeStatuses)
            ->orderByRaw(
                'CASE
                    WHEN status = ? THEN 0
                    WHEN status = ? THEN 1
                    WHEN status = ? THEN 2
                    WHEN status = ? THEN 3
                    WHEN status = ? THEN 4
                    ELSE 5
                END',
                [
                    LandTransferApplication::STATUS_FOR_RELEASING,
                    LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                    LandTransferApplication::STATUS_ENDORSED_PARPO,
                    LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
                    LandTransferApplication::STATUS_ENDORSED_LTI,
                ]
            )
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $todaySummary = [
            [
                'label' => 'Encoded Today',
                'value' => LandTransferApplication::query()
                    ->whereDate('created_at', today())
                    ->count(),
                'icon' => 'fa-file-circle-plus',
            ],
            [
                'label' => 'Final Decisions Today',
                'value' => LandTransferApplication::query()
                    ->whereIn('status', array_merge(
                        LandTransferApplication::FINAL_STATUSES,
                        LandTransferApplication::LEGACY_FINAL_STATUSES
                    ))
                    ->whereDate('updated_at', today())
                    ->count(),
                'icon' => 'fa-gavel',
            ],
            [
                'label' => 'Clearances Generated Today',
                'value' => ApplicationClearance::query()
                    ->whereDate('generated_at', today())
                    ->count(),
                'icon' => 'fa-file-circle-check',
            ],
        ];

        $oldestPendingReview = LandTransferApplication::query()
            ->where('status', LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW)
            ->oldest('updated_at')
            ->first();

        $oldestForReleasing = LandTransferApplication::query()
            ->where('status', LandTransferApplication::STATUS_FOR_RELEASING)
            ->oldest('updated_at')
            ->first();

        $staleActiveCount = LandTransferApplication::query()
            ->whereIn('status', $activeStatuses)
            ->where('updated_at', '<', now()->subDays(7))
            ->count();

        $attentionItems = [
            [
                'label' => 'Oldest Legal Review',
                'application' => $oldestPendingReview,
                'empty' => 'No applications waiting for legal review.',
            ],
            [
                'label' => 'Oldest For Releasing',
                'application' => $oldestForReleasing,
                'empty' => 'No applications currently for releasing.',
            ],
        ];

        return view('dashboards.staff', compact(
            'workQueue',
            'actionApplications',
            'todaySummary',
            'attentionItems',
            'staleActiveCount'
        ));
    }
}
