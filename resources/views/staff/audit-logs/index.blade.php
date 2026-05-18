<x-staff-shell
    title="Audit Log Viewer"
    subtitle="Read-only traceability records for important staff-side actions and clearance processing events."
    active="audit-logs"
>
    <span class="sr-only">Audit Log Viewer</span>
    <span class="sr-only">System Activity History</span>

    <style>
        .audit-table {
            table-layout: fixed;
            width: 100%;
            min-width: 1180px;
        }

        .audit-table th,
        .audit-table td {
            vertical-align: top;
        }

        .audit-table th:nth-child(1),
        .audit-table td:nth-child(1) {
            width: 160px;
        }

        .audit-table th:nth-child(2),
        .audit-table td:nth-child(2) {
            width: 250px;
        }

        .audit-table th:nth-child(3),
        .audit-table td:nth-child(3) {
            width: 190px;
        }

        .audit-table th:nth-child(4),
        .audit-table td:nth-child(4) {
            width: 160px;
        }

        .audit-table th:nth-child(5),
        .audit-table td:nth-child(5) {
            width: 170px;
        }

        .audit-metadata-cell {
            width: 250px;
            max-width: 250px;
            vertical-align: top;
        }

        .audit-action-badge {
            max-width: 100%;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
            line-height: 1.25;
            text-align: left;
        }

        .audit-metadata-details {
            width: 100%;
            max-width: 100%;
        }

        .audit-metadata-summary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            color: #065f46;
            font-weight: 800;
            font-size: 13px;
            list-style: none;
        }

        .audit-metadata-summary::-webkit-details-marker {
            display: none;
        }

        .audit-metadata-arrow {
            color: #047857;
            font-size: 11px;
            transition: transform 160ms ease;
        }

        .audit-metadata-details[open] .audit-metadata-arrow {
            transform: rotate(90deg);
        }

        .audit-metadata-box {
            margin-top: 10px;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            border: 1px solid #dbe4dd;
            border-radius: 10px;
            background: #f8fafc;
            padding: 12px;
        }

        .audit-metadata-pre {
            margin: 0;
            max-width: 100%;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
            font-size: 12px;
            line-height: 1.55;
            color: #1f2937;
        }

        @media (max-width: 1200px) {
            .audit-table {
                width: max-content;
                min-width: 1180px;
            }

            .audit-metadata-cell {
                width: 250px;
                max-width: 250px;
            }
        }
    </style>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="staff-panel-title">Filter Audit Logs</h2>
                <p class="staff-panel-subtitle">Filter activity by action type, application code, or actor name/email.</p>
            </div>
            <p class="text-sm font-bold text-gray-500">{{ $auditLogs->total() }} record(s)</p>
        </div>

        <form method="GET" action="{{ route('staff.audit-logs.index') }}" class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-4 lg:items-end">
            <div>
                <label class="staff-form-label" for="action">ACTION</label>
                <select
                    id="action"
                    name="action"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600"
                >
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>
                            {{ ucwords(str_replace('_', ' ', $action)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="staff-form-label" for="application_code">APPLICATION CODE</label>
                <input
                    id="application_code"
                    type="text"
                    name="application_code"
                    value="{{ $filters['application_code'] ?? '' }}"
                    placeholder="e.g., APP-2026-0001"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600"
                >
            </div>

            <div>
                <label class="staff-form-label" for="actor">ACTOR</label>
                <input
                    id="actor"
                    type="text"
                    name="actor"
                    value="{{ $filters['actor'] ?? '' }}"
                    placeholder="Name or email"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600"
                >
            </div>

            <div class="flex items-end justify-start gap-2 sm:justify-end lg:self-end">
                <button type="submit" class="staff-button staff-button-dark h-10 min-h-10 shrink-0 px-4">
                    <i class="fa-solid fa-filter"></i>
                    Apply Filters
                </button>

                <a href="{{ route('staff.audit-logs.index') }}" class="staff-button staff-button-light h-10 min-h-10 shrink-0 px-4">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad">
            <h2 class="staff-panel-title">Audit Records</h2>
            <p class="staff-panel-subtitle">
                Showing {{ $auditLogs->count() }} of {{ $auditLogs->total() }} record(s).
                These entries are timestamped audit log records for traceability and accountability.
            </p>
        </div>
        <div class="staff-table-wrap">
            <table class="staff-table audit-table">
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
                            <td>
                                <span class="staff-badge staff-badge-blue audit-action-badge">
                                    {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                </span>
                            </td>
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
                            <td class="audit-metadata-cell">
                                @if (! empty($log->metadata))
                                    <details class="audit-metadata-details">
                                        <summary class="audit-metadata-summary">
                                            <span class="audit-metadata-arrow">▶</span>
                                            View details
                                        </summary>

                                        <div class="audit-metadata-box">
                                            <pre class="audit-metadata-pre">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
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
        <div class="border-t border-gray-200 px-6 py-4">{{ $auditLogs->withQueryString()->links() }}</div>
    </section>
</x-staff-shell>
