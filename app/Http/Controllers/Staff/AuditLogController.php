<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuditLogController extends Controller
{
    private const PRINT_RECORD_LIMIT = 500;
    private const LOGIN_ACTIONS = ['user_login', 'user_logout'];

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $filteredQuery = $this->filteredQuery($filters);

        $todayStart = today();
        $tomorrowStart = today()->addDay();

        $summary = [
            'matching_records' => (clone $filteredQuery)->count(),
            'actions_today' => (clone $filteredQuery)
                ->where('created_at', '>=', $todayStart)
                ->where('created_at', '<', $tomorrowStart)
                ->count(),
            'active_actors' => (clone $filteredQuery)
                ->whereNotNull('actor_user_id')
                ->distinct('actor_user_id')
                ->count('actor_user_id'),
            'linked_applications' => (clone $filteredQuery)
                ->whereNotNull('land_transfer_application_id')
                ->distinct('land_transfer_application_id')
                ->count('land_transfer_application_id'),
        ];

        $auditLogs = (clone $filteredQuery)
            ->paginate(15)
            ->withQueryString();

        $actions = $this->scopedQuery($filters['view'])
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('staff.audit-logs.index', compact(
            'auditLogs',
            'actions',
            'filters',
            'summary'
        ));
    }

    public function print(Request $request)
    {
        $filters = $this->validatedFilters($request);

        $auditLogs = $this->filteredQuery($filters)
            ->limit(self::PRINT_RECORD_LIMIT + 1)
            ->get();

        $printTruncated = $auditLogs->count() > self::PRINT_RECORD_LIMIT;

        if ($printTruncated) {
            $auditLogs = $auditLogs->take(self::PRINT_RECORD_LIMIT)->values();
        }

        return view('staff.audit-logs.print', [
            'auditLogs' => $auditLogs,
            'filters' => $filters,
            'generatedAt' => now(),
            'generatedBy' => $request->user(),
            'printTruncated' => $printTruncated,
            'printLimit' => self::PRINT_RECORD_LIMIT,
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        $filters = $request->validate([
            'view' => ['nullable', 'in:activity,logins'],
            'action' => ['nullable', 'string', 'max:100'],
            'application_code' => ['nullable', 'string', 'max:100'],
            'actor' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $filters['view'] = $filters['view'] ?? 'activity';

        return $filters;
    }

    private function scopedQuery(string $view): Builder
    {
        $query = AuditLog::query();

        return $view === 'logins'
            ? $query->whereIn('action', self::LOGIN_ACTIONS)
            : $query->whereNotIn('action', self::LOGIN_ACTIONS);
    }

    private function filteredQuery(array $filters): Builder
    {
        $query = $this->scopedQuery($filters['view'])
            ->with(['actor', 'application'])
            ->latest();

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['application_code'])) {
            $term = '%' . mb_strtolower($filters['application_code']) . '%';

            $query->where(function (Builder $applicationFilter) use ($term) {
                $applicationFilter
                    ->whereRaw("LOWER(COALESCE(application_code_snapshot, '')) LIKE ?", [$term])
                    ->orWhereHas('application', function (Builder $applicationQuery) use ($term) {
                        $applicationQuery->whereRaw('LOWER(application_code) LIKE ?', [$term]);
                    });
            });
        }

        if (! empty($filters['actor'])) {
            $term = '%' . mb_strtolower($filters['actor']) . '%';

            $query->where(function (Builder $actorFilter) use ($term) {
                $actorFilter
                    ->whereRaw(
                        "LOWER(COALESCE(actor_name_snapshot, '') || ' ' || COALESCE(actor_username_snapshot, '') || ' ' || COALESCE(actor_role_snapshot, '')) LIKE ?",
                        [$term]
                    )
                    ->orWhereHas('actor', function (Builder $actorQuery) use ($term) {
                        $actorQuery->whereRaw(
                            "LOWER(COALESCE(name, '') || ' ' || COALESCE(username, '') || ' ' || COALESCE(email, '')) LIKE ?",
                            [$term]
                        );
                    });
            });
        }

        if (! empty($filters['date_from'])) {
            $query->where(
                'created_at',
                '>=',
                Carbon::parse($filters['date_from'])->startOfDay()
            );
        }

        if (! empty($filters['date_to'])) {
            $query->where(
                'created_at',
                '<',
                Carbon::parse($filters['date_to'])->addDay()->startOfDay()
            );
        }

        return $query;
    }
}

