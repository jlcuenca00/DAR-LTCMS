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

        // Legacy draft/pending_review records are displayed as Pending Review by
        // Legal Officer, so dashboard counts and previews must treat them the same way.
        $pendingLegalStatuses = array_values(array_unique([
            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            LandTransferApplication::STATUS_DRAFT,
            LandTransferApplication::STATUS_PENDING_REVIEW,
        ]));

        // Keep the three dashboard work queues mutually exclusive. Active Workflow
        // represents only the endorsement stages between initial legal review and release.
        $workflowStatuses = [
            LandTransferApplication::STATUS_ENDORSED_LTI,
            LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
            LandTransferApplication::STATUS_ENDORSED_PARPO,
        ];

        // This broader set is still useful for office-wide stale-record monitoring.
        $activeStatuses = array_values(array_unique(array_merge(
            $pendingLegalStatuses,
            $workflowStatuses,
            [LandTransferApplication::STATUS_FOR_RELEASING]
        )));

        $pendingLegalReview = $countStatuses($pendingLegalStatuses);
        $activeWorkflow = $countStatuses($workflowStatuses);
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
                'description' => 'Applications in endorsement stages',
                'value' => $activeWorkflow,
                'icon' => 'fa-arrows-rotate',
                'filter' => 'active_workflow',
            ],
            [
                'label' => 'For Releasing',
                'description' => 'Ready for clearance release',
                'value' => $forReleasing,
                'icon' => 'fa-file-export',
                'filter' => LandTransferApplication::STATUS_FOR_RELEASING,
            ],
        ];

        $activeWorkflowPreview = LandTransferApplication::query()
            ->whereIn('status', $workflowStatuses)
            ->orderByRaw(
                'CASE
                    WHEN status = ? THEN 0
                    WHEN status = ? THEN 1
                    WHEN status = ? THEN 2
                    ELSE 3
                END',
                [
                    LandTransferApplication::STATUS_ENDORSED_PARPO,
                    LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
                    LandTransferApplication::STATUS_ENDORSED_LTI,
                ]
            )
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $pendingLegalPreview = LandTransferApplication::query()
            ->whereIn('status', $pendingLegalStatuses)
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $forReleasingPreview = LandTransferApplication::query()
            ->where('status', LandTransferApplication::STATUS_FOR_RELEASING)
            ->latest('updated_at')
            ->limit(6)
            ->get();

        // Each queue contributes its own preview candidates. The browser then shows
        // only the selected queue, capped at six visible rows.
        $actionApplications = $activeWorkflowPreview
            ->concat($pendingLegalPreview)
            ->concat($forReleasingPreview)
            ->unique('id')
            ->values();

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
            ->whereIn('status', $pendingLegalStatuses)
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
