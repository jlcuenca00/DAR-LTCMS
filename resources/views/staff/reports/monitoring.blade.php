<x-staff-shell
    title="Monitoring and Reports"
    active="reports"
    maxWidth="max-w-7xl"
>
    <x-slot name="styles">
        <style>
            .monitor-page {
                display: grid;
                gap: 18px;
            }

            .monitor-hero {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                padding: 20px 22px;
                border: 1px solid #d7e3da;
                border-radius: 16px;
                background: linear-gradient(110deg, #f8fcf9 0%, #ffffff 66%);
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.07);
            }

            .monitor-eyebrow {
                margin: 0 0 5px;
                color: #15803d;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.15em;
                text-transform: uppercase;
            }

            .monitor-title {
                margin: 0;
                color: #111827;
                font-family: var(--heading-font);
                font-size: 22px;
                font-weight: 900;
            }

            .monitor-subtitle {
                max-width: 720px;
                margin: 7px 0 0;
                color: #64748b;
                font-size: 13px;
                line-height: 1.55;
            }

            .monitor-hero-meta {
                display: flex;
                align-items: center;
                gap: 12px;
                flex: 0 0 auto;
            }

            .monitor-generated {
                text-align: right;
                color: #64748b;
                font-size: 12px;
                line-height: 1.45;
            }

            .monitor-generated strong {
                display: block;
                color: #334155;
                font-size: 12.5px;
            }

            .monitor-metrics {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                overflow: hidden;
                border: 1px solid var(--border);
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.07);
            }

            .monitor-metric {
                min-width: 0;
                padding: 17px 19px;
                border-right: 1px solid #e5e7eb;
            }

            .monitor-metric:last-child { border-right: 0; }

            .monitor-metric-label {
                margin: 0;
                color: #64748b;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            .monitor-metric-line {
                display: flex;
                align-items: baseline;
                gap: 7px;
                margin-top: 8px;
            }

            .monitor-metric-value {
                margin: 0;
                color: #111827;
                font-family: var(--heading-font);
                font-size: 27px;
                font-weight: 900;
                line-height: 1;
            }

            .monitor-metric-unit {
                color: #64748b;
                font-size: 12px;
                font-weight: 700;
            }

            .monitor-metric-note {
                margin: 8px 0 0;
                color: #64748b;
                font-size: 12px;
                line-height: 1.4;
            }

            .monitor-main-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.6fr) minmax(300px, 0.8fr);
                gap: 18px;
                align-items: stretch;
            }

            .monitor-bottom-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.45fr) minmax(330px, 0.75fr);
                gap: 18px;
                align-items: stretch;
            }

            .monitor-panel {
                min-width: 0;
                overflow: hidden;
                border: 1px solid var(--border);
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.07);
            }

            .monitor-panel-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                padding: 17px 19px 14px;
                border-bottom: 1px solid #e5e7eb;
            }

            .monitor-panel-title {
                margin: 0;
                color: #111827;
                font-family: var(--heading-font);
                font-size: 16px;
                font-weight: 900;
            }

            .monitor-panel-copy {
                margin: 5px 0 0;
                color: #64748b;
                font-size: 12.5px;
                line-height: 1.45;
            }

            .monitor-count-pill {
                flex: 0 0 auto;
                padding: 5px 9px;
                border: 1px solid #bbf7d0;
                border-radius: 999px;
                background: #f0fdf4;
                color: #166534;
                font-size: 11.5px;
                font-weight: 900;
                white-space: nowrap;
            }

            .workflow-list {
                display: grid;
                gap: 13px;
                padding: 18px 19px 20px;
            }

            .workflow-row {
                display: grid;
                grid-template-columns: minmax(180px, 1fr) minmax(120px, 0.9fr) 42px;
                align-items: center;
                gap: 13px;
            }

            .workflow-label {
                color: #334155;
                font-size: 13px;
                font-weight: 750;
                line-height: 1.35;
            }

            .workflow-track {
                height: 8px;
                overflow: hidden;
                border-radius: 999px;
                background: #e8edf0;
            }

            .workflow-fill {
                height: 100%;
                min-width: 0;
                border-radius: inherit;
                background: #15803d;
            }

            .workflow-fill.amber { background: #d97706; }
            .workflow-fill.blue { background: #2563eb; }
            .workflow-fill.red { background: #dc2626; }

            .workflow-count {
                color: #111827;
                font-size: 13px;
                font-weight: 900;
                text-align: right;
            }

            .monitor-side-stack {
                display: grid;
                align-content: start;
            }

            .decision-summary {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                padding: 17px 19px;
            }

            .decision-card {
                padding: 13px 14px;
                border: 1px solid #e5e7eb;
                border-radius: 11px;
                background: #f8fafc;
            }

            .decision-card-label {
                margin: 0;
                color: #64748b;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.1em;
                text-transform: uppercase;
            }

            .decision-card-value {
                margin: 7px 0 0;
                color: #111827;
                font-family: var(--heading-font);
                font-size: 22px;
                font-weight: 900;
            }

            .decision-card.released { border-left: 4px solid #16a34a; }
            .decision-card.denied { border-left: 4px solid #dc2626; }

            .municipality-list {
                display: grid;
                gap: 0;
                padding: 0 19px 14px;
            }

            .municipality-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 11px 0;
                border-top: 1px solid #eef0f2;
            }

            .municipality-row:first-child { border-top: 0; }

            .municipality-name {
                overflow: hidden;
                color: #334155;
                font-size: 13px;
                font-weight: 700;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .municipality-count {
                color: #166534;
                font-size: 13px;
                font-weight: 900;
            }

            .monitor-table-wrap {
                width: 100%;
                overflow-x: auto;
            }

            .monitor-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }

            .monitor-table th {
                padding: 11px 13px;
                border-bottom: 1px solid #dbe0e5;
                background: #f8fafc;
                color: #64748b;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.1em;
                text-align: left;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .monitor-table td {
                padding: 13px;
                border-bottom: 1px solid #edf0f2;
                color: #374151;
                vertical-align: middle;
            }

            .monitor-table tbody tr:last-child td { border-bottom: 0; }
            .monitor-table tbody tr:hover td { background: #fbfcfb; }

            .monitor-code {
                color: #166534;
                font-weight: 900;
                text-decoration: none;
                white-space: nowrap;
            }

            .monitor-code:hover { text-decoration: underline; }

            .monitor-parties {
                min-width: 220px;
                line-height: 1.4;
            }

            .monitor-party-primary {
                color: #1f2937;
                font-weight: 750;
            }

            .monitor-party-secondary {
                margin-top: 2px;
                color: #64748b;
                font-size: 11.5px;
            }

            .monitor-empty {
                padding: 28px 18px !important;
                color: #64748b !important;
                text-align: center;
            }

            .monitor-scope-note {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                padding: 13px 15px;
                border: 1px solid #dbe7de;
                border-radius: 12px;
                background: #f8fcf9;
                color: #52615a;
                font-size: 12px;
                line-height: 1.5;
            }

            .monitor-scope-note i {
                margin-top: 2px;
                color: #15803d;
            }

            @media (max-width: 1120px) {
                .monitor-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .monitor-metric:nth-child(2) { border-right: 0; }
                .monitor-metric:nth-child(-n+2) { border-bottom: 1px solid #e5e7eb; }
                .monitor-main-grid,
                .monitor-bottom-grid { grid-template-columns: 1fr; }
            }

            @media (max-width: 760px) {
                .monitor-hero {
                    align-items: stretch;
                    flex-direction: column;
                }

                .monitor-hero-meta {
                    align-items: stretch;
                    flex-direction: column;
                }

                .monitor-generated { text-align: left; }
                .monitor-hero-meta .staff-button { justify-content: center; }
                .monitor-metrics { grid-template-columns: 1fr; }
                .monitor-metric,
                .monitor-metric:nth-child(2) { border-right: 0; border-bottom: 1px solid #e5e7eb; }
                .monitor-metric:last-child { border-bottom: 0; }
                .workflow-row { grid-template-columns: minmax(150px, 1fr) 42px; }
                .workflow-track { grid-column: 1 / -1; grid-row: 2; }
            }
        </style>
    </x-slot>

    @php
        $normalizedStatusCounts = collect($statusCounts ?? []);
        $normalizedClearanceCounts = collect($clearanceCounts ?? []);

        $statusRows = [
            'pending_legal_review' => [
                'label' => 'Pending Review by Legal Officer',
                'count' => (int) (($normalizedStatusCounts['pending_legal_review'] ?? 0) + ($normalizedStatusCounts['pending_review'] ?? 0) + ($normalizedStatusCounts['draft'] ?? 0)),
                'tone' => 'amber',
                'class' => 'staff-badge-amber',
            ],
            'endorsed_lti' => [
                'label' => 'Endorsed to LTI Division',
                'count' => (int) ($normalizedStatusCounts['endorsed_lti'] ?? 0),
                'tone' => 'blue',
                'class' => 'staff-badge-blue',
            ],
            'endorsed_chief_legal' => [
                'label' => 'Endorsed to Chief Legal',
                'count' => (int) ($normalizedStatusCounts['endorsed_chief_legal'] ?? 0),
                'tone' => 'blue',
                'class' => 'staff-badge-blue',
            ],
            'endorsed_parpo' => [
                'label' => 'Endorsed to PARPO II',
                'count' => (int) ($normalizedStatusCounts['endorsed_parpo'] ?? 0),
                'tone' => 'blue',
                'class' => 'staff-badge-blue',
            ],
            'for_releasing' => [
                'label' => 'For Releasing',
                'count' => (int) ($normalizedStatusCounts['for_releasing'] ?? 0),
                'tone' => 'amber',
                'class' => 'staff-badge-amber',
            ],
            'released' => [
                'label' => 'Released',
                'count' => (int) (($normalizedStatusCounts['released'] ?? 0) + ($normalizedStatusCounts['approved'] ?? 0)),
                'tone' => '',
                'class' => 'staff-badge-green',
            ],
            'denied' => [
                'label' => 'Denied',
                'count' => (int) (($normalizedStatusCounts['denied'] ?? 0) + ($normalizedStatusCounts['not_approved'] ?? 0)),
                'tone' => 'red',
                'class' => 'staff-badge-red',
            ],
        ];

        $activeApplicationCount = collect($statusRows)
            ->only(['pending_legal_review', 'endorsed_lti', 'endorsed_chief_legal', 'endorsed_parpo', 'for_releasing'])
            ->sum('count');

        $releasedResults = (int) (($normalizedClearanceCounts['released'] ?? 0) + ($normalizedClearanceCounts['approved'] ?? 0));
        $deniedResults = (int) (($normalizedClearanceCounts['denied'] ?? 0) + ($normalizedClearanceCounts['not_approved'] ?? 0));
        $workflowTotal = max(1, (int) $totalApplications);

        $statusClassFor = function (?string $status): string {
            return match ($status) {
                'released', 'approved' => 'staff-badge-green',
                'denied', 'not_approved' => 'staff-badge-red',
                'pending_legal_review', 'pending_review', 'draft', 'for_releasing' => 'staff-badge-amber',
                'endorsed_lti', 'endorsed_chief_legal', 'endorsed_parpo' => 'staff-badge-blue',
                default => 'staff-badge-slate',
            };
        };

        $decisionLabelFor = function (?string $status): string {
            return match ($status) {
                'released', 'approved' => 'Released',
                'denied', 'not_approved' => 'Denied',
                default => $status ? ucwords(str_replace('_', ' ', $status)) : 'Recorded',
            };
        };
    @endphp

    <div class="monitor-page">
        <section class="monitor-hero">
            <div>
                <p class="monitor-eyebrow">Administrative Monitoring</p>
                <h2 class="monitor-title">Monitoring Overview</h2>
                <p class="monitor-subtitle">
                    Review the current clearance workflow, recorded final results, and application distribution for DAR Negros Oriental Provincial Office.
                </p>
            </div>

            <div class="monitor-hero-meta">
                <div class="monitor-generated">
                    <span>Report generated</span>
                    <strong>{{ $generatedAt?->timezone('Asia/Manila')->format('M d, Y · h:i A') }}</strong>
                </div>
                <a href="{{ route('staff.reports.monitoring.print') }}" target="_blank" class="staff-button staff-button-primary">
                    <i class="fa-solid fa-print"></i>
                    Print / Save as PDF
                </a>
            </div>
        </section>

        <section class="monitor-metrics" aria-label="Monitoring report summary">
            <article class="monitor-metric">
                <p class="monitor-metric-label">Total Applications</p>
                <div class="monitor-metric-line">
                    <p class="monitor-metric-value">{{ number_format($totalApplications) }}</p>
                </div>
                <p class="monitor-metric-note">All encoded clearance applications.</p>
            </article>

            <article class="monitor-metric">
                <p class="monitor-metric-label">Active Workflow</p>
                <div class="monitor-metric-line">
                    <p class="monitor-metric-value">{{ number_format($activeApplicationCount) }}</p>
                </div>
                <p class="monitor-metric-note">Applications still undergoing processing.</p>
            </article>

            <article class="monitor-metric">
                <p class="monitor-metric-label">Recorded Results</p>
                <div class="monitor-metric-line">
                    <p class="monitor-metric-value">{{ number_format($totalClearances) }}</p>
                </div>
                <p class="monitor-metric-note">Released or denied results recorded.</p>
            </article>

            <article class="monitor-metric">
                <p class="monitor-metric-label">Recorded Clearance Area</p>
                <div class="monitor-metric-line">
                    <p class="monitor-metric-value">{{ number_format((float) $totalClearanceArea, 4) }}</p>
                    <span class="monitor-metric-unit">ha</span>
                </div>
                <p class="monitor-metric-note">Area shown in generated clearance records.</p>
            </article>
        </section>

        <section class="monitor-main-grid">
            <article class="monitor-panel">
                <div class="monitor-panel-header">
                    <div>
                        <h2 class="monitor-panel-title">Application Workflow Stage</h2>
                        <p class="monitor-panel-copy">Current application count across the DAR clearance processing stages.</p>
                    </div>
                    <span class="monitor-count-pill">{{ number_format($totalApplications) }} total</span>
                </div>

                <div class="workflow-list">
                    @foreach ($statusRows as $row)
                        @php
                            $percentage = $totalApplications > 0
                                ? max(0, min(100, round(($row['count'] / $workflowTotal) * 100, 1)))
                                : 0;
                        @endphp
                        <div class="workflow-row">
                            <span class="workflow-label">{{ $row['label'] }}</span>
                            <div class="workflow-track" aria-hidden="true">
                                <div class="workflow-fill {{ $row['tone'] }}" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="workflow-count">{{ number_format($row['count']) }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <aside class="monitor-panel monitor-side-stack">
                <div class="monitor-panel-header">
                    <div>
                        <h2 class="monitor-panel-title">Decision Outcomes</h2>
                        <p class="monitor-panel-copy">Final clearance results currently recorded.</p>
                    </div>
                </div>

                <div class="decision-summary">
                    <div class="decision-card released">
                        <p class="decision-card-label">Released</p>
                        <p class="decision-card-value">{{ number_format($releasedResults) }}</p>
                    </div>
                    <div class="decision-card denied">
                        <p class="decision-card-label">Denied</p>
                        <p class="decision-card-value">{{ number_format($deniedResults) }}</p>
                    </div>
                </div>

                <div class="monitor-panel-header">
                    <div>
                        <h2 class="monitor-panel-title">Municipality Breakdown</h2>
                        <p class="monitor-panel-copy">Applications grouped by recorded municipality.</p>
                    </div>
                </div>

                <div class="municipality-list">
                    @forelse ($municipalityBreakdown as $row)
                        <div class="municipality-row">
                            <span class="municipality-name">{{ $row->municipality ?: 'Unspecified' }}</span>
                            <span class="municipality-count">{{ number_format($row->total) }}</span>
                        </div>
                    @empty
                        <div class="monitor-empty">No municipality data available.</div>
                    @endforelse
                </div>
            </aside>
        </section>

        <section class="monitor-bottom-grid">
            <article class="monitor-panel">
                <div class="monitor-panel-header">
                    <div>
                        <h2 class="monitor-panel-title">Recent Applications</h2>
                        <p class="monitor-panel-copy">Latest application records included in office monitoring.</p>
                    </div>
                </div>

                <div class="monitor-table-wrap">
                    <table class="monitor-table">
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Transfer</th>
                                <th>Status</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentApplications as $application)
                                <tr>
                                    <td>
                                        <a href="{{ route('staff.applications.show', $application) }}" class="monitor-code">
                                            {{ $application->application_code }}
                                        </a>
                                    </td>
                                    <td class="monitor-parties">
                                        <div class="monitor-party-primary">
                                            {{ method_exists($application, 'transferorDisplayName') ? $application->transferorDisplayName() : $application->transferor_name }}
                                        </div>
                                        <div class="monitor-party-secondary">
                                            To: {{ method_exists($application, 'transfereeDisplayName') ? $application->transfereeDisplayName() : $application->transferee_name }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="staff-badge {{ $statusClassFor($application->status) }}">
                                            {{ method_exists($application, 'statusLabel') ? $application->statusLabel() : $decisionLabelFor($application->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $application->municipality ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="monitor-empty">No recent applications.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="monitor-panel">
                <div class="monitor-panel-header">
                    <div>
                        <h2 class="monitor-panel-title">Recent Release / Denial Outputs</h2>
                        <p class="monitor-panel-copy">Latest finalized clearance result records.</p>
                    </div>
                </div>

                <div class="monitor-table-wrap">
                    <table class="monitor-table">
                        <thead>
                            <tr>
                                <th>Clearance</th>
                                <th>Result</th>
                                <th>Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentClearances as $clearance)
                                <tr>
                                    <td>
                                        <div class="font-bold text-gray-900 whitespace-nowrap">{{ $clearance->clearance_number }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format((float) $clearance->total_area_hectares, 4) }} ha</div>
                                    </td>
                                    <td>
                                        <span class="staff-badge {{ $statusClassFor($clearance->decision_status) }}">
                                            {{ $decisionLabelFor($clearance->decision_status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        {{ $clearance->generated_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="monitor-empty">No generated clearances yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <div class="monitor-scope-note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>{{ $scopeNotice }}</span>
        </div>
    </div>
</x-staff-shell>
