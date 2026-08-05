<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $filteredQuery = $this->filteredQuery($filters);

        $summary = [
            'matching_records' => (clone $filteredQuery)->count(),
            'actions_today' => (clone $filteredQuery)
                ->whereDate('created_at', today())
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

        $actions = AuditLog::query()
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

        $auditLogs = $this->filteredQuery($filters)->get();

        return view('staff.audit-logs.print', [
            'auditLogs' => $auditLogs,
            'filters' => $filters,
            'generatedAt' => now(),
            'generatedBy' => $request->user(),
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'application_code' => ['nullable', 'string', 'max:100'],
            'actor' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function filteredQuery(array $filters): Builder
    {
        $query = AuditLog::query()
            ->with(['actor', 'application'])
            ->latest();

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['application_code'])) {
            $query->whereHas('application', function (Builder $applicationQuery) use ($filters) {
                $applicationQuery->where(
                    'application_code',
                    'like',
                    '%' . $filters['application_code'] . '%'
                );
            });
        }

        if (! empty($filters['actor'])) {
            $query->whereHas('actor', function (Builder $actorQuery) use ($filters) {
                $actorQuery
                    ->where('name', 'like', '%' . $filters['actor'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['actor'] . '%');
            });
        }

        return $query;
    }
}
