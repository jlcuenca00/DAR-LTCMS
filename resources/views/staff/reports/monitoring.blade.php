<x-staff-shell
    title="Monitoring and Reports"
    active="reports"
    maxWidth="max-w-7xl"
>
    <x-slot name="styles">
        <style>
            .reports-page {
                display: grid;
                gap: 18px;
            }

            .report-hero {
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

            .report-hero-title {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 18px;
                font-weight: 900;
                color: #111827;
            }

            .report-hero-copy {
                margin: 6px 0 0;
                font-size: 13px;
                line-height: 1.55;
                color: #64748b;
            }

            .report-hero-actions {
                display: flex;
                align-items: center;
                gap: 10px;
                flex: 0 0 auto;
            }

            .report-metric-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
            }

            .report-metric-card {
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

            .report-metric-label {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.13em;
                text-transform: uppercase;
                color: #64748b;
            }

            .report-metric-value {
                margin: 11px 0 0;
                font-family: var(--heading-font);
                font-size: 29px;
                line-height: 1;
                font-weight: 900;
                color: #111827;
            }

            .report-metric-description {
                margin: 11px 0 0;
                font-size: 12.5px;
                line-height: 1.45;
                color: #64748b;
            }

            .report-metric-icon {
                display: grid;
                place-items: center;
                flex: 0 0 auto;
                width: 42px;
                height: 42px;
                border-radius: 12px;
                color: #ffffff;
                background: #166534;
            }

            .report-metric-icon.slate { background: #334155; }
            .report-metric-icon.amber { background: #ea580c; }
            .report-metric-icon.green { background: #16a34a; }
            .report-metric-icon.blue { background: #2563eb; }

            .report-overview-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 18px;
                align-items: stretch;
            }

            .report-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
                align-items: stretch;
            }

            .report-panel {
                overflow: hidden;
                border: 1px solid var(--border);
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                min-width: 0;
            }

            .report-panel-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                padding: 18px 20px 14px;
                border-bottom: 1px solid #e5e7eb;
            }

            .report-panel-title {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 17px;
                font-weight: 900;
                color: #111827;
            }

            .report-panel-subtitle {
                margin: 5px 0 0;
                font-size: 13px;
                line-height: 1.5;
                color: #64748b;
            }

            .report-panel-count {
                flex: 0 0 auto;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #166534;
                border-radius: 999px;
                padding: 6px 10px;
                font-size: 12px;
                font-weight: 900;
                white-space: nowrap;
            }

            .report-list {
                display: grid;
                gap: 10px;
                padding: 16px 20px 20px;
            }

            .report-list-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                min-height: 50px;
                border: 1px solid #e5e7eb;
                border-radius: 11px;
                background: #f8fafc;
                padding: 12px 14px;
            }

            .report-list-label {
                font-size: 13.5px;
                font-weight: 850;
                color: #1f2937;
                line-height: 1.35;
            }

            .report-empty {
                border: 1px dashed #cbd5e1;
                border-radius: 11px;
                padding: 18px;
                text-align: center;
                font-size: 13px;
                color: #64748b;
                background: #f8fafc;
            }

            .report-table-wrap {
                width: 100%;
                overflow-x: auto;
            }

            .report-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }

            .report-table th {
                padding: 13px 14px;
                border-bottom: 1px solid #d1d5db;
                background: #f8fafc;
                color: #64748b;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.12em;
                text-align: left;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .report-table th:first-child,
            .report-table td:first-child { padding-left: 20px; }
            .report-table th:last-child,
            .report-table td:last-child { padding-right: 20px; }

            .report-table td {
                padding: 14px;
                border-bottom: 1px solid #e5e7eb;
                color: #374151;
                vertical-align: top;
            }

            .report-table tbody tr:hover td { background: #f9fafb; }

            .report-table-code {
                color: #166534;
                font-weight: 900;
                text-decoration: none;
                white-space: nowrap;
            }

            .report-table-code:hover { text-decoration: underline; }

            @media (max-width: 1080px) {
                .report-metric-grid,
                .report-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .report-overview-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 760px) {
                .report-panel-header,
                .report-hero {
                    flex-direction: column;
                    align-items: stretch;
                }

                .report-hero-actions .staff-button {
                    width: 100%;
                    justify-content: center;
                }

                .report-metric-grid,
                .report-overview-grid,
                .report-grid {
                    grid-template-columns: 1fr;
                }

                .report-metric-card {
                    min-height: auto;
                }
            }
        </style>
    </x-slot>

    @php
        $normalizedStatusCounts = collect($statusCounts ?? []);
        $statusRows = [
            'pending_legal_review' => [
                'label' => 'Pending Review by Legal Officer',
                'count' => (int) (($normalizedStatusCounts['pending_legal_review'] ?? 0) + ($normalizedStatusCounts['pending_review'] ?? 0) + ($normalizedStatusCounts['draft'] ?? 0)),
                'class' => 'staff-badge-amber',
            ],
            'endorsed_lti' => [
                'label' => 'Endorsed to LTI Division',
                'count' => (int) ($normalizedStatusCounts['endorsed_lti'] ?? 0),
                'class' => 'staff-badge-blue',
            ],
            'endorsed_chief_legal' => [
                'label' => 'Endorsed to Chief Legal',
                'count' => (int) ($normalizedStatusCounts['endorsed_chief_legal'] ?? 0),
                'class' => 'staff-badge-blue',
            ],
            'endorsed_parpo' => [
                'label' => 'Endorsed to PARPO II',
                'count' => (int) ($normalizedStatusCounts['endorsed_parpo'] ?? 0),
                'class' => 'staff-badge-blue',
            ],
            'for_releasing' => [
                'label' => 'For Releasing',
                'count' => (int) ($normalizedStatusCounts['for_releasing'] ?? 0),
                'class' => 'staff-badge-amber',
            ],
            'released' => [
                'label' => 'Released',
                'count' => (int) (($normalizedStatusCounts['released'] ?? 0) + ($normalizedStatusCounts['approved'] ?? 0)),
                'class' => 'staff-badge-green',
            ],
            'denied' => [
                'label' => 'Denied',
                'count' => (int) (($normalizedStatusCounts['denied'] ?? 0) + ($normalizedStatusCounts['not_approved'] ?? 0)),
                'class' => 'staff-badge-red',
            ],
        ];

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
                'released' => 'Released',
                'denied' => 'Denied',
                default => $status ? ucwords(str_replace('_', ' ', $status)) : 'Recorded',
            };
        };
    @endphp

    <div class="reports-page">
        <section class="report-hero">
            <div>
                <h2 class="report-hero-title">Monitoring Overview</h2>
                <p class="report-hero-copy">
                    Review clearance application stages, released or denied outputs, municipal distribution, and recent records for office monitoring.
                </p>
            </div>
            <div class="report-hero-actions">
                <a href="{{ route('staff.reports.monitoring.print') }}" target="_blank" class="staff-button staff-button-primary">
                    <i class="fa-solid fa-print"></i>
                    Print / Save as PDF
                </a>
            </div>
        </section>

        <section class="report-metric-grid" aria-label="Monitoring report summary cards">
            <article class="report-metric-card">
                <div>
                    <p class="report-metric-label">Total Applications</p>
                    <p class="report-metric-value">{{ number_format($totalApplications) }}</p>
                    <p class="report-metric-description">All staff-encoded clearance application records.</p>
                </div>
                <div class="report-metric-icon slate"><i class="fa-solid fa-file-lines"></i></div>
            </article>

            <article class="report-metric-card">
                <div>
                    <p class="report-metric-label">Legal Review</p>
                    <p class="report-metric-value text-amber-700">{{ number_format($statusRows['pending_legal_review']['count']) }}</p>
                    <p class="report-metric-description">Applications waiting for Legal Officer review before endorsement.</p>
                </div>
                <div class="report-metric-icon amber"><i class="fa-solid fa-clock"></i></div>
            </article>

            <article class="report-metric-card">
                <div>
                    <p class="report-metric-label">Recorded Results</p>
                    <p class="report-metric-value text-green-700">{{ number_format($totalClearances) }}</p>
                    <p class="report-metric-description">Released or denied clearance results generated and recorded by the system.</p>
                </div>
                <div class="report-metric-icon green"><i class="fa-solid fa-file-circle-check"></i></div>
            </article>

            <article class="report-metric-card">
                <div>
                    <p class="report-metric-label">Total Clearance Area</p>
                    <p class="report-metric-value">{{ number_format((float) $totalClearanceArea, 4) }}</p>
                    <p class="report-metric-description">Hectares recorded in generated release/denial outputs.</p>
                </div>
                <div class="report-metric-icon blue"><i class="fa-solid fa-chart-area"></i></div>
            </article>
        </section>

        <section class="report-overview-grid">
            <article class="report-panel">
                <div class="report-panel-header">
                    <div>
                        <h2 class="report-panel-title">Application Workflow Stage</h2>
                        <p class="report-panel-subtitle">Current count by actual DAR clearance processing stage.</p>
                    </div>
                    <span class="report-panel-count">{{ number_format($totalApplications) }} total</span>
                </div>

                <div class="report-list">
                    @foreach ($statusRows as $row)
                        <div class="report-list-row">
                            <span class="report-list-label">{{ $row['label'] }}</span>
                            <span class="staff-badge {{ $row['class'] }}">{{ number_format($row['count']) }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="report-panel">
                <div class="report-panel-header">
                    <div>
                        <h2 class="report-panel-title">Municipality Breakdown</h2>
                        <p class="report-panel-subtitle">Applications grouped by recorded municipality.</p>
                    </div>
                    <span class="report-panel-count">Top locations</span>
                </div>

                <div class="report-list">
                    @forelse ($municipalityBreakdown as $row)
                        <div class="report-list-row">
                            <span class="report-list-label">{{ $row->municipality ?: 'Unspecified' }}</span>
                            <span class="staff-badge staff-badge-blue">{{ number_format($row->total) }}</span>
                        </div>
                    @empty
                        <div class="report-empty">No municipality data available.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="report-grid">
            <article class="report-panel">
                <div class="report-panel-header">
                    <div>
                        <h2 class="report-panel-title">Recent Applications</h2>
                        <p class="report-panel-subtitle">Latest clearance applications included in monitoring.</p>
                    </div>
                </div>

                <div class="report-table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Parties</th>
                                <th>Status</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentApplications as $application)
                                @php
                                    $statusClass = $statusClassFor($application->status);
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('staff.applications.show', $application) }}" class="report-table-code">
                                            {{ $application->application_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="font-semibold text-gray-800">{{ $application->transferor_name }}</div>
                                        <div class="text-xs text-gray-500">To: {{ $application->transferee_name }}</div>
                                    </td>
                                    <td>
                                        <span class="staff-badge {{ $statusClass }}">
                                            {{ method_exists($application, 'statusLabel') ? $application->statusLabel() : $decisionLabelFor($application->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $application->municipality ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-500">No recent applications.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="report-panel">
                <div class="report-panel-header">
                    <div>
                        <h2 class="report-panel-title">Recent Release / Denial Outputs</h2>
                        <p class="report-panel-subtitle">Latest generated clearance results. These are not automatic ownership transfers.</p>
                    </div>
                </div>

                <div class="report-table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Clearance No.</th>
                                <th>Result</th>
                                <th>Area</th>
                                <th>Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentClearances as $clearance)
                                <tr>
                                    <td class="font-bold text-gray-900 whitespace-nowrap">{{ $clearance->clearance_number }}</td>
                                    <td>
                                        <span class="staff-badge {{ $statusClassFor($clearance->decision_status) }}">
                                            {{ $decisionLabelFor($clearance->decision_status) }}
                                        </span>
                                    </td>
                                    <td>{{ number_format((float) $clearance->total_area_hectares, 4) }} ha</td>
                                    <td>{{ $clearance->generated_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-500">No generated clearances yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
</x-staff-shell>
