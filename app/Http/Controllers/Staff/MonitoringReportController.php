<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationClearance;
use App\Models\LandTransferApplication;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MonitoringReportController extends Controller
{
    public function index(Request $request)
    {
        return view('staff.reports.monitoring', $this->buildReportData($request));
    }

    public function print(Request $request)
    {
        return view('staff.reports.monitoring-print', $this->buildReportData($request));
    }

    private function buildReportData(Request $request): array
    {
        $statusOptions = [
            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW => 'Pending Review by Legal Officer',
            LandTransferApplication::STATUS_ENDORSED_LTI => 'Endorsed to LTI Division',
            LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL => 'Endorsed to Chief Legal',
            LandTransferApplication::STATUS_ENDORSED_PARPO => 'Endorsed to PARPO II',
            LandTransferApplication::STATUS_FOR_RELEASING => 'For Releasing',
            LandTransferApplication::STATUS_RELEASED => 'Released',
            LandTransferApplication::STATUS_DENIED => 'Denied',
        ];

        $municipalities = array_keys((array) config('dar_locations.municipalities', []));

        $validated = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(array_keys($statusOptions))],
            'municipality' => ['nullable', Rule::in($municipalities)],
        ]);

        if (filled($validated['date_from'] ?? null)
            && filled($validated['date_to'] ?? null)
            && strcmp($validated['date_to'], $validated['date_from']) < 0) {
            throw ValidationException::withMessages([
                'date_to' => 'Date To must be the same as or later than Date From.',
            ]);
        }

        $filters = [
            'date_from' => filled($validated['date_from'] ?? null) ? $validated['date_from'] : null,
            'date_to' => filled($validated['date_to'] ?? null) ? $validated['date_to'] : null,
            'status' => filled($validated['status'] ?? null) ? $validated['status'] : null,
            'municipality' => filled($validated['municipality'] ?? null) ? $validated['municipality'] : null,
        ];

        $applications = LandTransferApplication::query();
        $this->applyApplicationFilters($applications, $filters);

        $validDecisionStatuses = [
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_DENIED,
            LandTransferApplication::STATUS_APPROVED,
            LandTransferApplication::STATUS_NOT_APPROVED,
        ];

        $clearances = ApplicationClearance::query()
            ->whereIn('decision_status', $validDecisionStatuses)
            ->whereHas('application', function (Builder $query) use ($filters) {
                $this->applyApplicationFilters($query, $filters);
            });

        $statusCounts = (clone $applications)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status');

        $clearanceCounts = (clone $clearances)
            ->selectRaw('decision_status, COUNT(*) as total')
            ->groupBy('decision_status')
            ->orderBy('decision_status')
            ->pluck('total', 'decision_status');

        // These totals are already represented by the grouped queries above, so
        // derive them in memory instead of asking PostgreSQL to recount the same set.
        $totalApplications = $statusCounts->sum(fn ($count) => (int) $count);
        $totalClearances = $clearanceCounts->sum(fn ($count) => (int) $count);

        // Compute all hectare metrics with one scan of the filtered clearance set.
        $clearanceMetrics = (clone $clearances)
            ->selectRaw(
                'COALESCE(SUM(total_area_hectares), 0) as total_area_hectares,
                 COALESCE(SUM(CASE WHEN decision_status IN (?, ?) THEN total_area_hectares ELSE 0 END), 0) as released_area_hectares,
                 COALESCE(SUM(CASE WHEN decision_status IN (?, ?) THEN total_area_hectares ELSE 0 END), 0) as denied_area_hectares',
                [
                    LandTransferApplication::STATUS_RELEASED,
                    LandTransferApplication::STATUS_APPROVED,
                    LandTransferApplication::STATUS_DENIED,
                    LandTransferApplication::STATUS_NOT_APPROVED,
                ]
            )
            ->first();

        $totalClearanceArea = (float) ($clearanceMetrics?->total_area_hectares ?? 0);
        $releasedOutputArea = (float) ($clearanceMetrics?->released_area_hectares ?? 0);
        $deniedOutputArea = (float) ($clearanceMetrics?->denied_area_hectares ?? 0);

        $municipalityBreakdown = (clone $applications)
            ->selectRaw('municipality, COUNT(*) as total')
            ->whereNotNull('municipality')
            ->groupBy('municipality')
            ->orderBy('municipality')
            ->get();

        $recentApplications = (clone $applications)
            ->latest('created_at')
            ->limit(10)
            ->get();

        $recentClearances = (clone $clearances)
            ->latest('generated_at')
            ->limit(10)
            ->get();

        $filterLabels = collect([
            $filters['date_from'] ? 'From '.$filters['date_from'] : null,
            $filters['date_to'] ? 'To '.$filters['date_to'] : null,
            $filters['status'] ? ($statusOptions[$filters['status']] ?? null) : null,
            $filters['municipality'],
        ])->filter()->values();

        return [
            'statusCounts' => $statusCounts,
            'clearanceCounts' => $clearanceCounts,
            'totalApplications' => $totalApplications,
            'totalClearances' => $totalClearances,
            'totalClearanceArea' => $totalClearanceArea,
            'releasedOutputArea' => $releasedOutputArea,
            'deniedOutputArea' => $deniedOutputArea,
            'municipalityBreakdown' => $municipalityBreakdown,
            'recentApplications' => $recentApplications,
            'recentClearances' => $recentClearances,
            'generatedAt' => now(),
            'generatedBy' => Auth::user(),
            'filters' => $filters,
            'filterLabels' => $filterLabels,
            'hasActiveFilters' => $filterLabels->isNotEmpty(),
            'statusOptions' => $statusOptions,
            'municipalities' => $municipalities,
            'scopeNotice' => 'This report is generated for administrative monitoring, records management, and decision-support purposes only. Released and denied clearance outputs are recorded administrative results. They do not automatically transfer land ownership, mutate parcel ownership or registry records, or replace separate legal and administrative procedures.',
            'areaNotice' => 'Recorded output area is the summed parcel area preserved in final clearance snapshots. It is not a measurement of land whose legal ownership has been transferred.',
        ];
    }

    private function applyApplicationFilters(Builder $query, array $filters): void
    {
        if ($filters['date_from']) {
            $createdFrom = CarbonImmutable::parse($filters['date_from'], config('app.timezone'))->startOfDay();

            $query->where(function (Builder $dateQuery) use ($filters, $createdFrom) {
                $dateQuery
                    // date_of_application is already a DATE column; wrapping it in
                    // whereDate() would prevent normal B-tree index usage.
                    ->where('date_of_application', '>=', $filters['date_from'])
                    ->orWhere(function (Builder $fallback) use ($createdFrom) {
                        $fallback
                            ->whereNull('date_of_application')
                            ->where('created_at', '>=', $createdFrom);
                    });
            });
        }

        if ($filters['date_to']) {
            $createdBefore = CarbonImmutable::parse($filters['date_to'], config('app.timezone'))
                ->startOfDay()
                ->addDay();

            $query->where(function (Builder $dateQuery) use ($filters, $createdBefore) {
                $dateQuery
                    ->where('date_of_application', '<=', $filters['date_to'])
                    ->orWhere(function (Builder $fallback) use ($createdBefore) {
                        $fallback
                            ->whereNull('date_of_application')
                            ->where('created_at', '<', $createdBefore);
                    });
            });
        }

        if ($filters['municipality']) {
            $query->where('municipality', $filters['municipality']);
        }

        if ($filters['status']) {
            $statuses = match ($filters['status']) {
                LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW => [
                    LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                    LandTransferApplication::STATUS_PENDING_REVIEW,
                    LandTransferApplication::STATUS_DRAFT,
                ],
                LandTransferApplication::STATUS_RELEASED => [
                    LandTransferApplication::STATUS_RELEASED,
                    LandTransferApplication::STATUS_APPROVED,
                ],
                LandTransferApplication::STATUS_DENIED => [
                    LandTransferApplication::STATUS_DENIED,
                    LandTransferApplication::STATUS_NOT_APPROVED,
                ],
                default => [$filters['status']],
            };

            $query->whereIn('status', $statuses);
        }
    }
}
