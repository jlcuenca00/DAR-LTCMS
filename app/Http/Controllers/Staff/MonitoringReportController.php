<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationClearance;
use App\Models\LandTransferApplication;
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

        $clearances = ApplicationClearance::query()
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

        $totalApplications = (clone $applications)->count();
        $totalClearances = (clone $clearances)->count();
        $totalClearanceArea = (float) (clone $clearances)->sum('total_area_hectares');

        $releasedOutputArea = (float) (clone $clearances)
            ->whereIn('decision_status', [
                LandTransferApplication::STATUS_RELEASED,
                LandTransferApplication::STATUS_APPROVED,
            ])
            ->sum('total_area_hectares');

        $deniedOutputArea = (float) (clone $clearances)
            ->whereIn('decision_status', [
                LandTransferApplication::STATUS_DENIED,
                LandTransferApplication::STATUS_NOT_APPROVED,
            ])
            ->sum('total_area_hectares');

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
            $filters['date_from'] ? 'From ' . $filters['date_from'] : null,
            $filters['date_to'] ? 'To ' . $filters['date_to'] : null,
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
            $query->where(function (Builder $dateQuery) use ($filters) {
                $dateQuery
                    ->whereDate('date_of_application', '>=', $filters['date_from'])
                    ->orWhere(function (Builder $fallback) use ($filters) {
                        $fallback
                            ->whereNull('date_of_application')
                            ->whereDate('created_at', '>=', $filters['date_from']);
                    });
            });
        }

        if ($filters['date_to']) {
            $query->where(function (Builder $dateQuery) use ($filters) {
                $dateQuery
                    ->whereDate('date_of_application', '<=', $filters['date_to'])
                    ->orWhere(function (Builder $fallback) use ($filters) {
                        $fallback
                            ->whereNull('date_of_application')
                            ->whereDate('created_at', '<=', $filters['date_to']);
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
