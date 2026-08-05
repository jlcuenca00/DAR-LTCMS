<x-staff-shell
    title="Audit Logs"
    active="audit-logs"
    maxWidth="max-w-7xl"
>
    <span class="sr-only">Audit Log Viewer</span>
    <span class="sr-only">System Activity History</span>

    <x-slot name="styles">
        <style>
            .audit-page {
                display: grid;
                gap: 18px;
            }

            .audit-hero {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 18px;
                border: 1px solid var(--border);
                border-radius: 16px;
                background: linear-gradient(90deg, #ffffff 0%, #f8faf9 100%);
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                padding: 18px 20px;
            }

            .audit-title {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 18px;
                font-weight: 900;
                color: #111827;
            }

            .audit-copy {
                margin: 6px 0 0;
                font-size: 13px;
                line-height: 1.55;
                color: #64748b;
            }

            .audit-hero-actions {
                display: flex;
                align-items: center;
                gap: 10px;
                flex: 0 0 auto;
            }

            .audit-metric-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
            }

            .audit-metric-card {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                min-height: 104px;
                padding: 16px 18px;
                border: 1px solid var(--border);
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            }

            .audit-metric-label {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.13em;
                text-transform: uppercase;
                color: #64748b;
            }

            .audit-metric-value {
                margin: 11px 0 0;
                font-family: var(--heading-font);
                font-size: 29px;
                line-height: 1;
                font-weight: 900;
                color: #111827;
            }

            .audit-metric-description {
                margin: 11px 0 0;
                font-size: 12.5px;
                line-height: 1.45;
                color: #64748b;
            }

            .audit-metric-icon {
                display: grid;
                place-items: center;
                flex: 0 0 auto;
                width: 42px;
                height: 42px;
                border-radius: 12px;
                color: #ffffff;
                background: #166534;
            }

            .audit-metric-icon.slate { background: #334155; }
            .audit-metric-icon.blue { background: #2563eb; }
            .audit-metric-icon.green { background: #16a34a; }
            .audit-metric-icon.amber { background: #ea580c; }

            .audit-filter-panel,
            .audit-records-panel {
                overflow: hidden;
                border: 1px solid var(--border);
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.07);
            }

            .audit-filter-header,
            .audit-records-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                padding: 16px 19px 13px;
                border-bottom: 1px solid #e5e7eb;
            }

            .audit-section-title {
                margin: 0;
                color: #111827;
                font-family: var(--heading-font);
                font-size: 16px;
                font-weight: 900;
            }

            .audit-section-copy {
                margin: 5px 0 0;
                color: #64748b;
                font-size: 12.5px;
                line-height: 1.45;
            }

            .audit-filter-form {
                display: grid;
                grid-template-columns: minmax(190px, 1fr) minmax(180px, 1fr) minmax(180px, 1fr) auto;
                align-items: end;
                gap: 13px;
                padding: 16px 19px 18px;
            }

            .audit-filter-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
            }

            .audit-active-filter {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 5px 9px;
                border: 1px solid #bbf7d0;
                border-radius: 999px;
                background: #f0fdf4;
                color: #166534;
                font-size: 11.5px;
                font-weight: 800;
            }

            .audit-table-wrap {
                width: 100%;
                overflow-x: auto;
            }

            .audit-table {
                width: 100%;
                min-width: 1180px;
                border-collapse: collapse;
                table-layout: fixed;
                font-size: 13px;
            }

            .audit-table th {
                padding: 11px 13px;
                border-bottom: 1px solid #dbe0e5;
                background: #f8fafc;
                color: #64748b;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.1em;
                text-align: left;
                text-transform: uppercase;
            }

            .audit-table td {
                padding: 14px 13px;
                border-bottom: 1px solid #edf0f2;
                color: #374151;
                vertical-align: top;
            }

            .audit-table tbody tr:last-child td { border-bottom: 0; }
            .audit-table tbody tr:hover td { background: #fbfcfb; }

            .audit-table th:nth-child(1),
            .audit-table td:nth-child(1) { width: 150px; }

            .audit-table th:nth-child(2),
            .audit-table td:nth-child(2) { width: 390px; }

            .audit-table th:nth-child(3),
            .audit-table td:nth-child(3) { width: 220px; }

            .audit-table th:nth-child(4),
            .audit-table td:nth-child(4) { width: 165px; }

            .audit-table th:nth-child(5),
            .audit-table td:nth-child(5) { width: auto; }

            .audit-table td:nth-child(2),
            .audit-table td:nth-child(3) {
                min-width: 0;
            }

            .audit-time-date {
                color: #1f2937;
                font-weight: 750;
                white-space: nowrap;
            }

            .audit-time-clock {
                margin-top: 3px;
                color: #64748b;
                font-size: 11.5px;
            }

            .audit-table .audit-action-badge {
                display: inline-flex !important;
                width: max-content;
                max-width: none;
                box-sizing: border-box;
                white-space: nowrap !important;
                line-height: 1.3;
                text-align: left;
                vertical-align: top;
            }

            .audit-record-type {
                max-width: 100%;
                margin-top: 7px;
                overflow-wrap: anywhere;
                color: #64748b;
                font-size: 11.5px;
                line-height: 1.35;
            }

            .audit-actor-name {
                color: #1f2937;
                font-weight: 800;
            }

            .audit-actor-detail {
                margin-top: 3px;
                overflow-wrap: anywhere;
                color: #64748b;
                font-size: 11.5px;
                line-height: 1.35;
            }

            .audit-application-link {
                color: #166534;
                font-weight: 900;
                text-decoration: none;
                white-space: nowrap;
            }

            .audit-application-link:hover { text-decoration: underline; }

            .audit-details {
                width: 100%;
                max-width: 100%;
            }

            .audit-details-summary {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                cursor: pointer;
                color: #166534;
                font-size: 12.5px;
                font-weight: 850;
                list-style: none;
            }

            .audit-details-summary::-webkit-details-marker { display: none; }

            .audit-details-chevron {
                font-size: 10px;
                transition: transform 160ms ease;
            }

            .audit-details[open] .audit-details-chevron { transform: rotate(90deg); }

            .audit-details-box {
                display: grid;
                gap: 10px;
                margin-top: 10px;
                padding: 11px 12px;
                border: 1px solid #dce4de;
                border-radius: 10px;
                background: #f8fafc;
            }

            .audit-context-row {
                display: grid;
                grid-template-columns: 90px minmax(0, 1fr);
                gap: 8px;
                font-size: 11.5px;
                line-height: 1.45;
            }

            .audit-context-label {
                color: #64748b;
                font-weight: 800;
            }

            .audit-context-value {
                overflow-wrap: anywhere;
                color: #334155;
            }

            .audit-metadata-pre {
                margin: 0;
                max-width: 100%;
                overflow-wrap: anywhere;
                white-space: pre-wrap;
                word-break: break-word;
                color: #334155;
                font-size: 11.5px;
                line-height: 1.5;
            }

            .audit-empty {
                padding: 34px 18px !important;
                color: #64748b !important;
                text-align: center;
            }

            .audit-pagination {
                padding: 13px 18px;
                border-top: 1px solid #e5e7eb;
            }

            @media (max-width: 1050px) {
                .audit-metric-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .audit-filter-form {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .audit-filter-actions { justify-content: flex-start; }
            }

            @media (max-width: 680px) {
                .audit-hero {
                    align-items: stretch;
                    flex-direction: column;
                }

                .audit-hero-actions {
                    align-items: stretch;
                }

                .audit-hero-actions .staff-button {
                    justify-content: center;
                }

                .audit-metric-grid {
                    grid-template-columns: 1fr;
                }

                .audit-filter-form { grid-template-columns: 1fr; }
                .audit-filter-actions .staff-button { flex: 1; justify-content: center; }
                .audit-records-header { flex-direction: column; }
            }
        </style>
    </x-slot>

    @php
        $hasFilters = collect($filters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();

        $actionBadgeClass = function (?string $action): string {
            $normalized = strtolower((string) $action);

            if (str_contains($normalized, 'denied') || str_contains($normalized, 'not_approved') || str_contains($normalized, 'deleted') || str_contains($normalized, 'deactivated')) {
                return 'staff-badge-red';
            }

            if (str_contains($normalized, 'released') || str_contains($normalized, 'approved') || str_contains($normalized, 'created')) {
                return 'staff-badge-green';
            }

            if (str_contains($normalized, 'review') || str_contains($normalized, 'endorsed') || str_contains($normalized, 'updated') || str_contains($normalized, 'uploaded')) {
                return 'staff-badge-blue';
            }

            return 'staff-badge-slate';
        };
    @endphp

    <div class="audit-page">
        <section class="audit-hero">
            <div>
                <h2 class="audit-title">Audit Trail Overview</h2>
                <p class="audit-copy">
                    Review timestamped actions, responsible users, linked applications, and record context for system accountability.
                </p>
            </div>
            <div class="audit-hero-actions">
                <a
                    href="{{ route('staff.audit-logs.print', array_filter($filters ?? [])) }}"
                    target="_blank"
                    rel="noopener"
                    class="staff-button staff-button-primary"
                >
                    <i class="fa-solid fa-print"></i>
                    Print / Save as PDF
                </a>
            </div>
        </section>

        <section class="audit-metric-grid" aria-label="Audit log summary cards">
            <article class="audit-metric-card">
                <div>
                    <p class="audit-metric-label">Matching Records</p>
                    <p class="audit-metric-value">{{ number_format($summary['matching_records']) }}</p>
                    <p class="audit-metric-description">Audit entries matching the current filters.</p>
                </div>
                <div class="audit-metric-icon slate"><i class="fa-solid fa-list-check"></i></div>
            </article>

            <article class="audit-metric-card">
                <div>
                    <p class="audit-metric-label">Actions Today</p>
                    <p class="audit-metric-value">{{ number_format($summary['actions_today']) }}</p>
                    <p class="audit-metric-description">Recorded system activity for the current date.</p>
                </div>
                <div class="audit-metric-icon amber"><i class="fa-solid fa-calendar-day"></i></div>
            </article>

            <article class="audit-metric-card">
                <div>
                    <p class="audit-metric-label">Active Actors</p>
                    <p class="audit-metric-value">{{ number_format($summary['active_actors']) }}</p>
                    <p class="audit-metric-description">Distinct user accounts represented in the results.</p>
                </div>
                <div class="audit-metric-icon green"><i class="fa-solid fa-user-shield"></i></div>
            </article>

            <article class="audit-metric-card">
                <div>
                    <p class="audit-metric-label">Linked Applications</p>
                    <p class="audit-metric-value">{{ number_format($summary['linked_applications']) }}</p>
                    <p class="audit-metric-description">Clearance applications referenced by the audit trail.</p>
                </div>
                <div class="audit-metric-icon blue"><i class="fa-solid fa-file-lines"></i></div>
            </article>
        </section>

        <section class="audit-filter-panel">
            <div class="audit-filter-header">
                <div>
                    <h2 class="audit-section-title">Filter Audit Logs</h2>
                    <p class="audit-section-copy">Narrow the audit trail by action, application code, or actor.</p>
                </div>
                @if ($hasFilters)
                    <span class="audit-active-filter">
                        <i class="fa-solid fa-filter"></i>
                        Filters active
                    </span>
                @endif
            </div>

            <form method="GET" action="{{ route('staff.audit-logs.index') }}" class="audit-filter-form">
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
                        placeholder="e.g., 2026-0026"
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

                <div class="audit-filter-actions">
                    <button type="submit" class="staff-button staff-button-dark h-10 min-h-10 px-4">
                        <i class="fa-solid fa-filter"></i>
                        Apply
                    </button>
                    <a href="{{ route('staff.audit-logs.index') }}" class="staff-button staff-button-light h-10 min-h-10 px-4">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="audit-records-panel">
            <div class="audit-records-header">
                <div>
                    <h2 class="audit-section-title">Audit Records</h2>
                    <p class="audit-section-copy">
                        Showing {{ $auditLogs->count() }} of {{ $auditLogs->total() }} record(s). Entries cannot be edited from this viewer.
                    </p>
                </div>
            </div>

            <div class="audit-table-wrap">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Activity</th>
                            <th>Actor</th>
                            <th>Application</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($auditLogs as $log)
                            <tr>
                                <td>
                                    <div class="audit-time-date">{{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</div>
                                    <div class="audit-time-clock">{{ $log->created_at?->timezone('Asia/Manila')->format('h:i A') ?? '' }}</div>
                                </td>
                                <td>
                                    <span class="staff-badge {{ $actionBadgeClass($log->action) }} audit-action-badge">
                                        {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                    </span>
                                    <div class="audit-record-type">
                                        {{ class_basename($log->auditable_type) ?: 'System record' }}
                                        @if ($log->auditable_id)
                                            · ID {{ $log->auditable_id }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="audit-actor-name">{{ $log->actor?->name ?? 'System' }}</div>
                                    <div class="audit-actor-detail">{{ $log->actor?->email ?? 'No user account' }}</div>
                                </td>
                                <td>
                                    @if ($log->application)
                                        <a href="{{ route('staff.applications.show', $log->application) }}" class="audit-application-link">
                                            {{ $log->application->application_code }}
                                        </a>
                                    @else
                                        <span class="text-gray-500">Not linked</span>
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($log->metadata) || $log->ip_address || $log->user_agent)
                                        <details class="audit-details">
                                            <summary class="audit-details-summary">
                                                <span class="audit-details-chevron">▶</span>
                                                View context
                                            </summary>

                                            <div class="audit-details-box">
                                                @if ($log->ip_address)
                                                    <div class="audit-context-row">
                                                        <span class="audit-context-label">IP address</span>
                                                        <span class="audit-context-value">{{ $log->ip_address }}</span>
                                                    </div>
                                                @endif

                                                @if ($log->user_agent)
                                                    <div class="audit-context-row">
                                                        <span class="audit-context-label">User agent</span>
                                                        <span class="audit-context-value">{{ $log->user_agent }}</span>
                                                    </div>
                                                @endif

                                                @if (! empty($log->metadata))
                                                    <div class="audit-context-row">
                                                        <span class="audit-context-label">Metadata</span>
                                                        <pre class="audit-metadata-pre">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-gray-500">No additional context</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="audit-empty">No audit logs matched the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="audit-pagination">{{ $auditLogs->withQueryString()->links() }}</div>
        </section>
    </div>
</x-staff-shell>
