@php
    $approved = $statusDistribution->firstWhere('status', \App\Models\LandTransferApplication::STATUS_APPROVED)['count'] ?? 0;
    $pending = $statusDistribution->firstWhere('status', \App\Models\LandTransferApplication::STATUS_PENDING_REVIEW)['count'] ?? 0;
    $notApproved = $statusDistribution->firstWhere('status', \App\Models\LandTransferApplication::STATUS_NOT_APPROVED)['count'] ?? 0;
    $draft = $statusDistribution->firstWhere('status', \App\Models\LandTransferApplication::STATUS_DRAFT)['count'] ?? 0;

    $distributionTotal = max($approved + $pending + $notApproved + $draft, 0);

    $approvedPct = $distributionTotal > 0 ? round(($approved / $distributionTotal) * 100, 2) : 0;
    $pendingPct = $distributionTotal > 0 ? round(($pending / $distributionTotal) * 100, 2) : 0;
    $notApprovedPct = $distributionTotal > 0 ? round(($notApproved / $distributionTotal) * 100, 2) : 0;

    $approvedEnd = $approvedPct;
    $pendingEnd = $approvedPct + $pendingPct;
    $notApprovedEnd = $approvedPct + $pendingPct + $notApprovedPct;
@endphp

<x-staff-shell title="Staff Operations Dashboard" active="dashboard">
    <x-slot name="styles">
        <style>
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 18px;
            }

            .stat-card {
                background: var(--panel);
                border: 1px solid var(--border);
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                padding: 20px;
                min-height: 120px;
                display: flex;
                justify-content: space-between;
                gap: 18px;
            }

            .stat-label {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #6b7280;
            }

            .stat-value {
                margin: 12px 0 0;
                font-family: var(--heading-font);
                font-size: 32px;
                line-height: 1;
                font-weight: 900;
                color: #111827;
            }

            .stat-description {
                margin: 12px 0 0;
                font-size: 12px;
                color: #6b7280;
                line-height: 1.45;
            }

            .stat-icon {
                width: 48px;
                height: 48px;
                border-radius: 10px;
                display: grid;
                place-items: center;
                color: #ffffff;
                flex: 0 0 auto;
                font-size: 18px;
            }

            .icon-slate { background: #334155; }
            .icon-amber { background: #ea580c; }
            .icon-green { background: #16a34a; }
            .icon-red { background: #dc2626; }

            .dashboard-grid {
                display: grid;
                grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
                gap: 20px;
            }

            .bar-chart {
                height: 305px;
                padding: 26px 28px 24px;
                display: flex;
                align-items: end;
                gap: 18px;
            }

            .bar-item {
                flex: 1;
                min-width: 0;
                display: grid;
                gap: 10px;
                align-items: end;
                text-align: center;
            }

            .bar-track {
                height: 215px;
                display: flex;
                align-items: end;
                border-left: 1px dashed #e5e7eb;
                border-bottom: 1px solid #cbd5e1;
                padding: 0 8px;
            }

            .bar-fill {
                width: 100%;
                min-height: 8px;
                border-radius: 6px 6px 0 0;
                background: #2e7d32;
            }

            .bar-label {
                margin: 0;
                font-size: 12px;
                font-weight: 700;
                color: #4b5563;
            }

            .bar-count {
                margin: -6px 0 0;
                font-size: 11px;
                color: #6b7280;
            }

            .donut-wrap {
                padding: 28px 24px 24px;
                display: grid;
                place-items: center;
            }

            .donut {
                width: 152px;
                height: 152px;
                border-radius: 999px;
                background:
                    @if ($distributionTotal > 0)
                        conic-gradient(
                            #16a34a 0 {{ $approvedEnd }}%,
                            #ea580c {{ $approvedEnd }}% {{ $pendingEnd }}%,
                            #dc2626 {{ $pendingEnd }}% {{ $notApprovedEnd }}%,
                            #94a3b8 {{ $notApprovedEnd }}% 100%
                        );
                    @else
                        #e5e7eb;
                    @endif
                position: relative;
            }

            .donut::after {
                content: "";
                position: absolute;
                inset: 32px;
                background: #ffffff;
                border-radius: 999px;
            }

            .legend {
                width: 100%;
                margin-top: 26px;
                display: grid;
                gap: 13px;
            }

            .legend-row {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                align-items: center;
                font-size: 13px;
            }

            .legend-left {
                display: flex;
                align-items: center;
                gap: 9px;
                color: #64748b;
                font-weight: 700;
            }

            .dot {
                width: 11px;
                height: 11px;
                border-radius: 999px;
            }

            .dot.green { background: #16a34a; }
            .dot.orange { background: #ea580c; }
            .dot.red { background: #dc2626; }
            .dot.gray { background: #94a3b8; }

            .quick-list {
                padding: 20px 22px 22px;
                display: grid;
                gap: 10px;
            }

            .quick-link {
                min-height: 68px;
                display: flex;
                justify-content: space-between;
                gap: 16px;
                align-items: center;
                text-decoration: none;
                border: 1px solid #dbe4dd;
                border-radius: 10px;
                padding: 14px 15px;
                background: #f8faf9;
                transition: 160ms ease;
            }

            .quick-link:hover {
                border-color: #86efac;
                background: #f0fdf4;
            }

            .quick-title {
                margin: 0;
                font-size: 14px;
                font-weight: 800;
                color: #14532d;
            }

            .quick-desc {
                margin: 3px 0 0;
                font-size: 12px;
                color: #6b7280;
            }

            .quick-action-icon {
                width: 18px;
                text-align: center;
                color: #166534;
                font-size: 15px;
            }

            .table-wrap {
                padding: 16px 24px 24px;
                overflow-x: auto;
            }

            .data-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }

            .data-table th {
                text-align: left;
                padding: 12px 10px;
                border-bottom: 1px solid #d1d5db;
                font-size: 11px;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #64748b;
            }

            .data-table td {
                padding: 13px 10px;
                border-bottom: 1px solid #e5e7eb;
                color: #374151;
                white-space: nowrap;
            }

            .code-link {
                color: #166534;
                font-weight: 900;
                text-decoration: none;
            }

            .code-link:hover {
                text-decoration: underline;
            }

            .status-badge {
                display: inline-flex;
                border-radius: 999px;
                padding: 5px 10px;
                font-size: 11px;
                font-weight: 900;
                border: 1px solid;
            }

            .status-approved {
                background: #dcfce7;
                border-color: #bbf7d0;
                color: #166534;
            }

            .status-pending_review {
                background: #ffedd5;
                border-color: #fed7aa;
                color: #c2410c;
            }

            .status-not_approved {
                background: #fee2e2;
                border-color: #fecaca;
                color: #b91c1c;
            }

            .status-draft {
                background: #f1f5f9;
                border-color: #cbd5e1;
                color: #475569;
            }

            .mini-grid {
                padding: 22px 24px 24px;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .mini-card {
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 15px;
                background: #ffffff;
            }

            .mini-label {
                margin: 0;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #64748b;
            }

            .mini-value {
                margin: 8px 0 0;
                font-family: var(--heading-font);
                font-size: 24px;
                font-weight: 900;
                color: #111827;
            }

            .activity-list {
                padding: 18px 24px 24px;
                display: grid;
                gap: 10px;
            }

            .activity-card {
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 13px 14px;
                background: #ffffff;
            }

            .activity-action {
                margin: 0;
                font-size: 13px;
                font-weight: 900;
                color: #111827;
            }

            .activity-meta {
                margin: 4px 0 0;
                font-size: 11px;
                color: #6b7280;
            }

            @media (max-width: 1180px) {
                .stats-grid,
                .dashboard-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .dashboard-grid > .panel:first-child {
                    grid-column: 1 / -1;
                }
            }

            @media (max-width: 760px) {
                .stats-grid,
                .dashboard-grid,
                .mini-grid {
                    grid-template-columns: 1fr;
                }

                .bar-chart {
                    height: 260px;
                    padding: 20px 18px;
                    gap: 10px;
                }

                .bar-track {
                    height: 180px;
                    padding: 0 4px;
                }

                .stat-card {
                    min-height: auto;
                }
            }
        </style>
    </x-slot>

    <section class="scope-notice">
        <div>
            <h3>DAR-LTCMS Scope Reminder</h3>
            <p>
                Clearance approval records and generates the clearance result only. The system does not automatically transfer land ownership,
                mutate registry records, or finalize legal land transfer.
            </p>
        </div>

        <span class="scope-pill">Clearance System Only</span>
    </section>

    <section class="stats-grid">
        @foreach ($statusCards as $index => $card)
            @php
                $iconClass = match ($index) {
                    1 => 'icon-amber',
                    2 => 'icon-green',
                    3 => 'icon-red',
                    default => 'icon-slate',
                };

                $icon = match ($index) {
                    1 => 'fa-clock',
                    2 => 'fa-circle-check',
                    3 => 'fa-circle-xmark',
                    default => 'fa-file-lines',
                };
            @endphp

            <article class="stat-card">
                <div>
                    <p class="stat-label">{{ $card['label'] }}</p>
                    <p class="stat-value">{{ number_format($card['value']) }}</p>
                    <p class="stat-description">{{ $card['description'] }}</p>
                </div>

                <div class="stat-icon {{ $iconClass }}">
                    <i class="fa-solid {{ $icon }}"></i>
                </div>
            </article>
        @endforeach
    </section>

    <section class="dashboard-grid">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Monthly Application Submissions</h2>
                    <p class="panel-subtitle">Encoded clearance applications during the last six months.</p>
                </div>

                <a href="{{ route('staff.applications.index') }}" class="panel-link">
                    View Applications →
                </a>
            </div>

            <div class="bar-chart">
                @foreach ($monthlyApplications as $month)
                    @php
                        $height = max(6, round(($month['count'] / $maxMonthlyCount) * 100));
                    @endphp

                    <div class="bar-item">
                        <div class="bar-track">
                            <div
                                class="bar-fill"
                                style="height: {{ $height }}%;"
                                title="{{ $month['count'] }} application(s)"
                            ></div>
                        </div>

                        <p class="bar-label">{{ $month['label'] }}</p>
                        <p class="bar-count">{{ $month['count'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Application Status Distribution</h2>
                    <p class="panel-subtitle">Current workflow status totals.</p>
                </div>
            </div>

            <div class="donut-wrap">
                <div class="donut" aria-label="Application status distribution chart"></div>

                <div class="legend">
                    <div class="legend-row">
                        <div class="legend-left">
                            <span class="dot green"></span>
                            Approved Clearances
                        </div>
                        <strong>{{ number_format($approved) }}</strong>
                    </div>

                    <div class="legend-row">
                        <div class="legend-left">
                            <span class="dot orange"></span>
                            Pending Review
                        </div>
                        <strong>{{ number_format($pending) }}</strong>
                    </div>

                    <div class="legend-row">
                        <div class="legend-left">
                            <span class="dot red"></span>
                            Not Approved
                        </div>
                        <strong>{{ number_format($notApproved) }}</strong>
                    </div>

                    <div class="legend-row">
                        <div class="legend-left">
                            <span class="dot gray"></span>
                            Draft
                        </div>
                        <strong>{{ number_format($draft) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-grid">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Recent Clearance Applications</h2>
                    <p class="panel-subtitle">Latest encoded applications for staff monitoring.</p>
                </div>

                <a href="{{ route('staff.applications.index') }}" class="panel-link">
                    View All Applications →
                </a>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Transferor</th>
                            <th>Transferee</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($recentApplications as $application)
                            <tr>
                                <td>
                                    <a href="{{ route('staff.applications.show', $application) }}" class="code-link">
                                        {{ $application->application_code }}
                                    </a>
                                </td>
                                <td>{{ $application->transferor_name }}</td>
                                <td>{{ $application->transferee_name }}</td>
                                <td>
                                    <span class="status-badge status-{{ $application->status }}">
                                        {{ $application->status === 'approved'
                                            ? 'Approved Clearance'
                                            : ucwords(str_replace('_', ' ', $application->status)) }}
                                    </span>
                                </td>
                                <td>{{ $application->created_at?->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">No clearance applications found yet.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Quick Actions</h2>
                    <p class="panel-subtitle">Common staff-side modules for daily processing.</p>
                </div>
            </div>

            <div class="quick-list">
                <a href="{{ \Illuminate\Support\Facades\Route::has('staff.applications.create') ? route('staff.applications.create') : route('staff.applications.index') }}" class="quick-link">
                    <div>
                        <p class="quick-title">Encode New Application</p>
                        <p class="quick-desc">Create a new staff-encoded clearance application.</p>
                    </div>
                    <i class="fa-solid fa-plus quick-action-icon"></i>
                </a>

                <a href="{{ route('staff.applications.index', ['status' => \App\Models\LandTransferApplication::STATUS_PENDING_REVIEW]) }}" class="quick-link">
                    <div>
                        <p class="quick-title">Review Pending Applications</p>
                        <p class="quick-desc">Open applications awaiting staff review or decision.</p>
                    </div>
                    <i class="fa-solid fa-clock quick-action-icon"></i>
                </a>

                <a href="{{ route('staff.records.parcels.index') }}" class="quick-link">
                    <div>
                        <p class="quick-title">Search Parcel Records</p>
                        <p class="quick-desc">Find main parcel records and mapped references.</p>
                    </div>
                    <i class="fa-solid fa-magnifying-glass-location quick-action-icon"></i>
                </a>

                <a href="{{ route('staff.legacy-records.index') }}" class="quick-link">
                    <div>
                        <p class="quick-title">Open Source Records Archive</p>
                        <p class="quick-desc">Review encoded and imported documentary source records.</p>
                    </div>
                    <i class="fa-solid fa-box-archive quick-action-icon"></i>
                </a>

                <a href="{{ route('staff.reports.monitoring.index') }}" class="quick-link">
                    <div>
                        <p class="quick-title">Generate Monitoring Report</p>
                        <p class="quick-desc">Open monitoring summaries and printable report outputs.</p>
                    </div>
                    <i class="fa-solid fa-chart-line quick-action-icon"></i>
                </a>

                <a href="{{ route('staff.audit-logs.index') }}" class="quick-link">
                    <div>
                        <p class="quick-title">View Audit Logs</p>
                        <p class="quick-desc">Review timestamped activity and traceability records.</p>
                    </div>
                    <i class="fa-solid fa-clipboard-list quick-action-icon"></i>
                </a>
            </div>
        </div>
    </section>

    <section class="dashboard-grid">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Records Summary</h2>
                    <p class="panel-subtitle">Current administrative record totals.</p>
                </div>
            </div>

            <div class="mini-grid">
                <div class="mini-card">
                    <p class="mini-label">Landowners</p>
                    <p class="mini-value">{{ number_format($recordsSummary['landowners']) }}</p>
                </div>

                <div class="mini-card">
                    <p class="mini-label">Parcels</p>
                    <p class="mini-value">{{ number_format($recordsSummary['parcels']) }}</p>
                </div>

                <div class="mini-card">
                    <p class="mini-label">Source Packages</p>
                    <p class="mini-value">{{ number_format($recordsSummary['source_packages']) }}</p>
                </div>

                <div class="mini-card">
                    <p class="mini-label">Source Records</p>
                    <p class="mini-value">{{ number_format($recordsSummary['legacy_records']) }}</p>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Municipality Breakdown</h2>
                    <p class="panel-subtitle">Top locations based on encoded applications.</p>
                </div>
            </div>

            <div class="activity-list">
                @forelse ($municipalityBreakdown as $row)
                    <div class="activity-card">
                        <p class="activity-action">{{ $row['municipality'] }}</p>
                        <p class="activity-meta">{{ number_format($row['total']) }} application(s)</p>
                    </div>
                @empty
                    <div class="empty-state">No municipality data available yet.</div>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Recent Audit Activity</h2>
                    <p class="panel-subtitle">Latest trace records from important staff-side actions.</p>
                </div>

                <a href="{{ route('staff.audit-logs.index') }}" class="panel-link">
                    View →
                </a>
            </div>

            <div class="activity-list">
                @forelse ($recentAuditLogs as $log)
                    <div class="activity-card">
                        <p class="activity-action">
                            {{ ucwords(str_replace('_', ' ', $log->action)) }}
                        </p>
                        <p class="activity-meta">
                            {{ $log->actor?->name ?? 'System' }} · {{ $log->created_at?->format('M d, Y h:i A') }}
                        </p>
                    </div>
                @empty
                    <div class="empty-state">No audit activity recorded yet.</div>
                @endforelse
            </div>
        </div>
    </section>
</x-staff-shell>
