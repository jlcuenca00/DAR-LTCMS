<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationClearance;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffDashboardController extends Controller
{
    public function __invoke(Request $request)
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

        // This broader set is used for operational attention and stale-record monitoring.
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

        // Use the exact same deduplicated review requirement set shown on the
        // Application Review page so dashboard counts cannot drift from the checklist.
        $transferorRequirements = RequiredDocument::deduplicateForApplicationReview(
            RequiredDocument::query()
                ->where('applies_to', 'transferor')
                ->orderBy('blocks_acceptance', 'desc')
                ->orderBy('requirement_classification')
                ->orderBy('name')
                ->get()
        );
        $transfereeRequirements = RequiredDocument::deduplicateForApplicationReview(
            RequiredDocument::query()
                ->where('applies_to', 'transferee')
                ->orderBy('blocks_acceptance', 'desc')
                ->orderBy('requirement_classification')
                ->orderBy('name')
                ->get()
        );

        $blockingRequirementIds = $transferorRequirements
            ->concat($transfereeRequirements)
            ->filter(fn (RequiredDocument $requirement) => $requirement->blocksAcceptance())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $blockingRequirementTotal = $blockingRequirementIds->count();

        $completeRequirementApplicationIds = function () use ($blockingRequirementIds, $blockingRequirementTotal) {
            return DB::table('application_documents')
                ->select('land_transfer_application_id')
                ->whereIn('required_document_id', $blockingRequirementIds)
                ->groupBy('land_transfer_application_id')
                ->havingRaw('COUNT(DISTINCT required_document_id) >= ?', [$blockingRequirementTotal]);
        };

        $activeApplicationCount = LandTransferApplication::query()
            ->whereIn('status', $activeStatuses)
            ->count();

        if ($blockingRequirementTotal > 0) {
            $incompleteRequirementsCount = LandTransferApplication::query()
                ->whereIn('status', $activeStatuses)
                ->whereNotIn('id', $completeRequirementApplicationIds())
                ->count();

            $requirementsCompleteCount = LandTransferApplication::query()
                ->whereIn('status', $activeStatuses)
                ->whereIn('id', $completeRequirementApplicationIds())
                ->count();
        } else {
            $incompleteRequirementsCount = 0;
            $requirementsCompleteCount = $activeApplicationCount;
        }

        $staleActiveCount = LandTransferApplication::query()
            ->whereIn('status', $activeStatuses)
            ->where('updated_at', '<', now()->subDays(7))
            ->count();

        $attentionFilter = (string) $request->query('attention', '');
        $allowedAttentionFilters = ['missing_requirements', 'requirements_complete', 'stale'];
        if (! in_array($attentionFilter, $allowedAttentionFilters, true)) {
            $attentionFilter = '';
        }

        $attentionItems = [
            [
                'key' => 'missing_requirements',
                'label' => 'Incomplete Requirements',
                'description' => 'Active applications with required entries still missing.',
                'value' => $incompleteRequirementsCount,
                'icon' => 'fa-file-circle-exclamation',
                'action' => 'Review requirements',
                'tone' => 'warning',
                'href' => route('staff.dashboard', ['attention' => 'missing_requirements']),
            ],
            [
                'key' => 'requirements_complete',
                'label' => 'Requirements Complete',
                'description' => 'Required entries are encoded; continue the current stage review.',
                'value' => $requirementsCompleteCount,
                'icon' => 'fa-list-check',
                'action' => 'View applications',
                'tone' => 'success',
                'href' => route('staff.dashboard', ['attention' => 'requirements_complete']),
            ],
            [
                'key' => 'stale',
                'label' => 'No Update for More Than 7 Days',
                'description' => 'Active records that may require staff follow-up.',
                'value' => $staleActiveCount,
                'icon' => 'fa-clock-rotate-left',
                'action' => 'Review follow-up',
                'tone' => 'warning',
                'href' => route('staff.dashboard', ['attention' => 'stale']),
            ],
        ];

        $attentionFocusLabel = null;

        if ($attentionFilter !== '') {
            $attentionQuery = LandTransferApplication::query()
                ->whereIn('status', $activeStatuses);

            if ($attentionFilter === 'missing_requirements') {
                if ($blockingRequirementTotal > 0) {
                    $attentionQuery->whereNotIn('id', $completeRequirementApplicationIds());
                } else {
                    $attentionQuery->whereRaw('1 = 0');
                }
            } elseif ($attentionFilter === 'requirements_complete') {
                if ($blockingRequirementTotal > 0) {
                    $attentionQuery->whereIn('id', $completeRequirementApplicationIds());
                }
            } elseif ($attentionFilter === 'stale') {
                $attentionQuery->where('updated_at', '<', now()->subDays(7));
            }

            $actionApplications = $attentionQuery
                ->oldest('updated_at')
                ->limit(12)
                ->get();

            $attentionFocusLabel = collect($attentionItems)
                ->firstWhere('key', $attentionFilter)['label'] ?? null;
        }

        return view('dashboards.staff', compact(
            'workQueue',
            'actionApplications',
            'todaySummary',
            'attentionItems',
            'attentionFilter',
            'attentionFocusLabel'
        ));
    }
}
