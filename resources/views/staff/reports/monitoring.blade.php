<x-staff-shell
    title="Monitoring and Reports"
    subtitle="Generate administrative monitoring summaries for clearance applications, clearance outputs, location totals, and recent decisions."
    active="reports"
    maxWidth="max-w-7xl"
>
    <x-slot name="actions">
        <a href="{{ route('staff.reports.monitoring.print') }}" target="_blank" class="staff-button staff-button-primary">
            <i class="fa-solid fa-print"></i>
            Print / Save as PDF
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .reports-page {
                display: grid;
                gap: 20px;
            }

            .reports-scope {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 18px;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #14532d;
                border-radius: 12px;
                padding: 16px 18px;
            }

            .reports-scope h2 {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 15px;
                font-weight: 900;
            }

            .reports-scope p {
                margin: 6px 0 0;
                max-width: 900px;
                font-size: 13px;
                line-height: 1.55;
                font-weight: 600;
            }

            .reports-scope .scope-pill {
                flex: 0 0 auto;
                border: 1px solid #bbf7d0;
                background: #dcfce7;
                border-radius: 999px;
                padding: 6px 12px;
                font-size: 11px;
                font-weight: 900;
                white-space: nowrap;
            }

            .report-metric-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 16px;
            }

            .report-metric-card {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                min-height: 128px;
                padding: 20px;
                border: 1px solid var(--border);
                border-radius: 12px;
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
                margin: 12px 0 0;
                font-family: var(--heading-font);
                font-size: 30px;
                line-height: 1;
                font-weight: 900;
                color: #111827;
            }

            .report-metric-description {
                margin: 12px 0 0;
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
                border-radius: 10px;
                color: #ffffff;
                background: #166534;
            }

            .report-metric-icon.slate { background: #334155; }
            .report-metric-icon.amber { background: #ea580c; }
            .report-metric-icon.green { background: #16a34a; }
            .report-metric-icon.blue { background: #2563eb; }

            .report-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 20px;
            }

            .report-panel {
                overflow: hidden;
                border: 1px solid var(--border);
                border-radius: 12px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            }

            .report-panel-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                padding: 20px 22px 16px;
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
                padding: 18px 22px 22px;
            }

            .report-list-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                min-height: 54px;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: #f8fafc;
                padding: 13px 16px;
            }

            .report-list-label {
                font-size: 14px;
                font-weight: 800;
                color: #1f2937;
            }

            .report-empty {
                border: 1px dashed #cbd5e1;
                border-radius: 10px;
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
            .report-table td:first-child {
                padding-left: 22px;
            }

            .report-table th:last-child,
            .report-table td:last-child {
                padding-right: 22px;
            }

            .report-table td {
                padding: 14px;
                border-bottom: 1px solid #e5e7eb;
                color: #374151;
                vertical-align: top;
            }

            .report-table tbody tr:hover td {
                background: #f9fafb;
            }

            .report-table-code {
                color: #166534;
                font-weight: 900;
                text-decoration: none;
                white-space: nowrap;
            }

            .report-table-code:hover {
                text-decoration: underline;
            }

            .status-breakdown-approved { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
            .status-breakdown-pending { background: #ffedd5; border-color: #fed7aa; color: #c2410c; }
            .status-breakdown-not-approved { background: #fee2e2; border-color: #fecaca; color: #b91c1c; }
            .status-breakdown-draft { background: #f1f5f9; border-color: #cbd5e1; color: #475569; }

            @media (max-width: 1180px) {
                .report-metric-grid,
                .report-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 760px) {
                .reports-scope,
                .report-panel-header {
                    flex-direction: column;
                }

                .report-metric-grid,
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
            'pending_review' => [
                'label' => 'Pending Review',
                'count' => (int) ($normalizedStatusCounts['pending_review'] ?? 0),
                'class' => 'staff-badge-amber',
            ],
            'approved' => [
                'label' => 'Approved Clearances',
                'count' => (int) ($normalizedStatusCounts['approved'] ?? 0),
                'class' => 'staff-badge-green',
            ],
            'not_approved' => [
                'label' => 'Not Approved',
                'count' => (int) ($normalizedStatusCounts['not_approved'] ?? 0),
                'class' => 'staff-badge-red',
            ],
            'draft' => [
                'label' => 'Draft',
                'count' => (int) ($normalizedStatusCounts['draft'] ?? 0),
                'class' => 'staff-badge-slate',
            ],
        ];

        $statusDisplay = [
            'approved' => 'Approved Clearance',
            'not_approved' => 'Not Approved',
            'pending_review' => 'Pending Review',
            'draft' => 'Draft',
        ];

        $normalizedAgriculturalStatusBreakdown = collect($agriculturalStatusBreakdown ?? []);
        $agriculturalStatusOptions = $agriculturalStatusOptions ?? \App\Models\Parcel::agriculturalStatusOptions();
        $agriculturalStatusRows = collect($agriculturalStatusOptions)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'count' => (int) ($normalizedAgriculturalStatusBreakdown[$key] ?? 0),
        ]);
    @endphp

    <div class="reports-page">
        <section class="reports-scope">
            <div>
                <h2>Monitoring Report Scope</h2>
                <p>{{ $scopeNotice }}</p>
            </div>
            <span class="scope-pill">Administrative Report</span>
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
                    <p class="report-metric-label">Pending Review</p>
                    <p class="report-metric-value text-amber-700">{{ number_format($statusRows['pending_review']['count']) }}</p>
                    <p class="report-metric-description">Applications awaiting staff-side review or decision.</p>
                </div>
                <div class="report-metric-icon amber"><i class="fa-solid fa-clock"></i></div>
            </article>

            <article class="report-metric-card">
                <div>
                    <p class="report-metric-label">Generated Clearances</p>
                    <p class="report-metric-value text-green-700">{{ number_format($totalClearances) }}</p>
                    <p class="report-metric-description">Clearance outputs generated and recorded by the system.</p>
                </div>
                <div class="report-metric-icon green"><i class="fa-solid fa-file-circle-check"></i></div>
            </article>

            <article class="report-metric-card">
                <div>
                    <p class="report-metric-label">Total Clearance Area</p>
                    <p class="report-metric-value">{{ number_format((float) $totalClearanceArea, 4) }}</p>
                    <p class="report-metric-description">Hectares recorded in generated clearance outputs.</p>
                </div>
                <div class="report-metric-icon blue"><i class="fa-solid fa-chart-area"></i></div>
            </article>
        </section>

        <section class="report-grid">
            <article class="report-panel">
                <div class="report-panel-header">
                    <div>
                        <h2 class="report-panel-title">Application Status Breakdown</h2>
                        <p class="report-panel-subtitle">Current workflow count by application status.</p>
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

            <article class="report-panel">
                <div class="report-panel-header">
                    <div>
                        <h2 class="report-panel-title">Agricultural Status Summary</h2>
                        <p class="report-panel-subtitle">Parcel classification context for DAR record monitoring.</p>
                    </div>
                    <span class="report-panel-count">Parcel records</span>
                </div>

                <div class="report-list">
                    @foreach ($agriculturalStatusRows as $row)
                        <div class="report-list-row">
                            <span class="report-list-label">{{ $row['label'] }}</span>
                            <span class="staff-badge staff-badge-slate">{{ number_format($row['count']) }}</span>
                        </div>
                    @endforeach
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
                                    $statusClass = match ($application->status) {
                                        'approved' => 'staff-badge-green',
                                        'not_approved' => 'staff-badge-red',
                                        'pending_review' => 'staff-badge-amber',
                                        default => 'staff-badge-slate',
                                    };
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
                                            {{ $statusDisplay[$application->status] ?? ucwords(str_replace('_', ' ', $application->status)) }}
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
                        <h2 class="report-panel-title">Recent Generated Clearances</h2>
                        <p class="report-panel-subtitle">Latest generated clearance outputs. These are not automatic ownership transfers.</p>
                    </div>
                </div>

                <div class="report-table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Clearance No.</th>
                                <th>Decision</th>
                                <th>Area</th>
                                <th>Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentClearances as $clearance)
                                <tr>
                                    <td class="font-bold text-gray-900 whitespace-nowrap">{{ $clearance->clearance_number }}</td>
                                    <td>
                                        <span class="staff-badge {{ $clearance->decision_status === 'approved' ? 'staff-badge-green' : 'staff-badge-red' }}">
                                            {{ $clearance->decision_status === 'approved' ? 'Approved Clearance' : ucwords(str_replace('_', ' ', $clearance->decision_status)) }}
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
