<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationClearance;
use App\Models\LandTransferApplication;
use App\Services\ApplicationRequirementService;
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

        // Intake/compliance work includes compatibility rows from the previous
        // draft/pending_review workflow plus the current AO4 completeness,
        // compliance-return, and cashier/O.R. stages.
        $intakeStatuses = array_values(array_unique([
            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE,
            LandTransferApplication::STATUS_AWAITING_PAYMENT,
            LandTransferApplication::STATUS_DRAFT,
            LandTransferApplication::STATUS_PENDING_REVIEW,
        ]));

        // Active workflow represents internal verification/evaluation handoffs
        // after intake and before the PARPO II final-decision gate.
        $workflowStatuses = [
            LandTransferApplication::STATUS_ENDORSED_LTI,
            LandTransferApplication::STATUS_RETURNED_TO_LEGAL,
            LandTransferApplication::STATUS_LEGAL_EVALUATION,
            LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
            LandTransferApplication::STATUS_ENDORSED_PARPO,
        ];

        $activeStatuses = array_values(array_unique(array_merge(
            $intakeStatuses,
            $workflowStatuses,
            [LandTransferApplication::STATUS_FOR_RELEASING]
        )));

        $intakeAndCompliance = $countStatuses($intakeStatuses);
        $activeWorkflow = $countStatuses($workflowStatuses);
        $decisionPending = (int) ($statusCounts[LandTransferApplication::STATUS_FOR_RELEASING] ?? 0);

        $workQueue = [
            [
                'label' => 'Intake & Compliance',
                'description' => 'Legal completeness, compliance, and O.R. recording',
                'value' => $intakeAndCompliance,
                'icon' => 'fa-scale-balanced',
                'filter' => 'intake_compliance',
            ],
            [
                'label' => 'Active Workflow',
                'description' => 'LTID, Legal, Chief Legal, and PARPO handoffs',
                'value' => $activeWorkflow,
                'icon' => 'fa-arrows-rotate',
                'filter' => 'active_workflow',
            ],
            [
                'label' => 'PARPO II Decision Pending',
                'description' => 'Complete records awaiting final Approved/Not Approved action',
                'value' => $decisionPending,
                'icon' => 'fa-gavel',
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
                    WHEN status = ? THEN 3
                    WHEN status = ? THEN 4
                    ELSE 5
                END',
                [
                    LandTransferApplication::STATUS_ENDORSED_PARPO,
                    LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
                    LandTransferApplication::STATUS_LEGAL_EVALUATION,
                    LandTransferApplication::STATUS_RETURNED_TO_LEGAL,
                    LandTransferApplication::STATUS_ENDORSED_LTI,
                ]
            )
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $intakePreview = LandTransferApplication::query()
            ->whereIn('status', $intakeStatuses)
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $decisionPendingPreview = LandTransferApplication::query()
            ->where('status', LandTransferApplication::STATUS_FOR_RELEASING)
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $actionApplications = $activeWorkflowPreview
            ->concat($intakePreview)
            ->concat($decisionPendingPreview)
            ->unique('id')
            ->values();

        $todayStart = now()->startOfDay();
        $tomorrowStart = $todayStart->copy()->addDay();

        $todaySummary = [
            [
                'label' => 'Encoded Today',
                'value' => LandTransferApplication::query()
                    ->where('created_at', '>=', $todayStart)
                    ->where('created_at', '<', $tomorrowStart)
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
                    ->where('updated_at', '>=', $todayStart)
                    ->where('updated_at', '<', $tomorrowStart)
                    ->count(),
                'icon' => 'fa-gavel',
            ],
            [
                'label' => 'Clearances Generated Today',
                'value' => ApplicationClearance::query()
                    ->where('generated_at', '>=', $todayStart)
                    ->where('generated_at', '<', $tomorrowStart)
                    ->count(),
                'icon' => 'fa-file-circle-check',
            ],
        ];

        /*
         * Requirement attention must use the same conditional/freshness rules as
         * the actual intake gate. A simple global document-ID count would falsely
         * flag municipal-vs-city, titled-vs-untitled, SPA, and juridical-entity
         * conditions. Evaluate in chunks with eager-loaded relations instead.
         */
        $requirementService = app(ApplicationRequirementService::class);
        $requirementsCompleteIds = [];
        $incompleteRequirementIds = [];

        LandTransferApplication::query()
            ->whereIn('status', $activeStatuses)
            ->with(['documents.requiredDocument', 'applicationParcels.parcel'])
            ->chunkById(100, function ($applications) use (
                $requirementService,
                &$requirementsCompleteIds,
                &$incompleteRequirementIds
            ) {
                foreach ($applications as $application) {
                    $evaluation = $requirementService->evaluate($application);

                    if ($evaluation['complete']) {
                        $requirementsCompleteIds[] = (int) $application->id;
                    } else {
                        $incompleteRequirementIds[] = (int) $application->id;
                    }
                }
            });

        $requirementsCompleteCount = count($requirementsCompleteIds);
        $incompleteRequirementsCount = count($incompleteRequirementIds);

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
                'description' => 'Active applications missing an applicable or still-valid required document.',
                'value' => $incompleteRequirementsCount,
                'icon' => 'fa-file-circle-exclamation',
                'action' => 'Review requirements',
                'tone' => 'warning',
                'href' => route('staff.dashboard', ['attention' => 'missing_requirements']),
            ],
            [
                'key' => 'requirements_complete',
                'label' => 'Requirements Complete',
                'description' => 'Applicable required documents are complete under the assistive intake checks.',
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
                if ($incompleteRequirementIds === []) {
                    $attentionQuery->whereRaw('1 = 0');
                } else {
                    $attentionQuery->whereIn('id', $incompleteRequirementIds);
                }
            } elseif ($attentionFilter === 'requirements_complete') {
                if ($requirementsCompleteIds === []) {
                    $attentionQuery->whereRaw('1 = 0');
                } else {
                    $attentionQuery->whereIn('id', $requirementsCompleteIds);
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
