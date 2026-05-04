<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'application_code' => ['nullable', 'string', 'max:100'],
            'actor' => ['nullable', 'string', 'max:255'],
        ]);

        $auditLogQuery = AuditLog::with([
                'actor',
                'application',
            ])
            ->latest();

        if (! empty($filters['action'])) {
            $auditLogQuery->where('action', $filters['action']);
        }

        if (! empty($filters['application_code'])) {
            $auditLogQuery->whereHas('application', function ($query) use ($filters) {
                $query->where('application_code', 'like', '%' . $filters['application_code'] . '%');
            });
        }

        if (! empty($filters['actor'])) {
            $auditLogQuery->whereHas('actor', function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['actor'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['actor'] . '%');
            });
        }

        $auditLogs = $auditLogQuery
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
            'filters'
        ));
    }
}