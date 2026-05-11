<x-staff-shell
    title="Audit Log Viewer"
    subtitle="Read-only traceability records for important staff-side actions and clearance processing events."
    active="audit-logs"
>
    <section class="staff-scope-banner">
        <div>
            <h3>System Activity History</h3>
            <p>
                This page provides a read-only audit trail of important staff-side actions. It supports traceability, accountability, and monitoring of clearance application processing. It does not perform ownership transfer or registry mutation.
            </p>
        </div>
        <span class="staff-scope-pill">Read-Only Audit Trail</span>
    </section>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="staff-panel-title">Filter Audit Logs</h2>
                <p class="staff-panel-subtitle">Filter activity by action type, application code, or actor name/email.</p>
            </div>
            <p class="text-sm font-bold text-gray-500">{{ $auditLogs->total() }} record(s)</p>
        </div>

        <form method="GET" action="{{ route('staff.audit-logs.index') }}" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Action</label>
                <select name="action" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ ucwords(str_replace('_', ' ', $action)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Application Code</label>
                <input type="text" name="application_code" value="{{ $filters['application_code'] ?? '' }}" placeholder="e.g., APP-2026-0001" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Actor</label>
                <input type="text" name="actor" value="{{ $filters['actor'] ?? '' }}" placeholder="Name or email" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="staff-button staff-button-dark"><i class="fa-solid fa-filter"></i>Filter</button>
                <a href="{{ route('staff.audit-logs.index') }}" class="staff-button staff-button-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad">
            <h2 class="staff-panel-title">Audit Records</h2>
            <p class="staff-panel-subtitle">Showing {{ $auditLogs->count() }} of {{ $auditLogs->total() }} timestamped audit log record(s).</p>
        </div>
        <div class="staff-table-wrap">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>Action</th>
                        <th>Actor</th>
                        <th>Application</th>
                        <th>Auditable</th>
                        <th>Metadata</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $log)
                        <tr>
                            <td class="whitespace-nowrap">{{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                            <td><span class="staff-badge staff-badge-blue">{{ ucwords(str_replace('_', ' ', $log->action)) }}</span></td>
                            <td>
                                <div class="font-bold text-gray-900">{{ $log->actor?->name ?? 'System' }}</div>
                                <div class="text-xs text-gray-500">{{ $log->actor?->email ?? 'No user account' }}</div>
                            </td>
                            <td>
                                @if ($log->application)
                                    <a href="{{ route('staff.applications.show', $log->application) }}" class="staff-link">{{ $log->application->application_code }}</a>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ class_basename($log->auditable_type) ?: 'N/A' }}</div>
                                <div class="text-xs text-gray-500">ID: {{ $log->auditable_id ?? 'N/A' }}</div>
                            </td>
                            <td>
                                @if (! empty($log->metadata))
                                    <details class="max-w-md">
                                        <summary class="cursor-pointer text-sm font-bold text-green-700">View details</summary>
                                        <pre class="mt-2 max-h-56 overflow-auto rounded bg-gray-950 p-3 text-xs text-gray-100">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    <span class="text-gray-500">No metadata</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-gray-500">No audit logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-4">{{ $auditLogs->withQueryString()->links() }}</div>
    </section>
</x-staff-shell>
