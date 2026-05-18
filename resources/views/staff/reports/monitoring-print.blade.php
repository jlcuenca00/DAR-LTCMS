<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Report | DAR-LTCMS</title>

    @php
        $generatedAtPh = $generatedAt?->timezone('Asia/Manila');
        $statusLabel = fn ($value) => ucwords(str_replace('_', ' ', (string) $value));
        $decisionLabel = fn ($value) => (string) $value === 'approved'
            ? 'Approved Clearance'
            : ucwords(str_replace('_', ' ', (string) $value));

        $darLogoDataUri = null;
        foreach (['images/dar-logo.png', 'images/dar-logo.svg', 'images/dar-logo.jpg', 'images/dar-logo.jpeg'] as $logoCandidate) {
            $logoPath = public_path($logoCandidate);

            if (file_exists($logoPath)) {
                $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'svg' => 'image/svg+xml',
                    'jpg', 'jpeg' => 'image/jpeg',
                    default => 'image/png',
                };
                $darLogoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
                break;
            }
        }
    @endphp

    <style>
        @page {
            size: A4;
            margin: 15mm 14mm 16mm;
        }

        :root {
            --font-ui: 'Google Sans', 'Product Sans', Arial, Helvetica, sans-serif;
        }

        * {
            box-sizing: border-box;
            font-family: var(--font-ui) !important;
        }

        html {
            background: #e5e7eb;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: var(--font-ui);
            font-size: 12px;
            line-height: 1.45;
            background: #e5e7eb;
        }

        .print-toolbar {
            max-width: 980px;
            margin: 18px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .toolbar-left,
        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .toolbar-title {
            font-size: 13px;
            font-weight: 800;
            color: #374151;
        }

        .print-button,
        .back-link {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border-radius: 9px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            text-decoration: none;
            font-family: var(--font-ui);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .print-button.primary {
            border-color: #166534;
            background: #166534;
            color: #ffffff;
        }

        .document-page {
            width: 210mm;
            min-height: 297mm;
            max-width: 980px;
            margin: 0 auto 24px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            padding: 18mm 16mm;
        }

        .document-header {
            display: table;
            width: 100%;
            border-bottom: 3px double #14532d;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .seal-cell {
            display: table-cell;
            width: 62px;
            vertical-align: middle;
        }

        .seal {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .seal-logo {
            width: 52px;
            height: 52px;
            object-fit: contain;
            display: block;
        }

        .seal-fallback {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #14532d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 13px;
            letter-spacing: 0.04em;
        }

        .agency-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            padding-right: 62px;
        }

        .republic {
            margin: 0;
            font-size: 11px;
            color: #374151;
        }

        .agency {
            margin: 2px 0 0;
            font-size: 15px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #111827;
        }

        .office {
            margin: 2px 0 0;
            font-size: 12px;
            font-weight: 800;
            color: #14532d;
        }

        .system-name {
            margin: 2px 0 0;
            font-size: 10.5px;
            color: #4b5563;
        }

        .report-title-block {
            margin: 16px 0 14px;
            display: table;
            width: 100%;
        }

        .report-title-main {
            display: table-cell;
            vertical-align: top;
        }

        .report-title-main h1 {
            margin: 0;
            color: #111827;
            font-size: 20px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .report-title-main p {
            margin: 5px 0 0;
            color: #4b5563;
            font-size: 11.5px;
        }

        .report-chip {
            display: table-cell;
            width: 190px;
            vertical-align: top;
            text-align: right;
        }

        .chip {
            display: inline-block;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #14532d;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 14px;
        }

        .meta-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            vertical-align: top;
        }

        .meta-label {
            display: block;
            margin-bottom: 2px;
            color: #64748b;
            font-size: 9.5px;
            font-weight: 900;
            letter-spacing: 0.10em;
            text-transform: uppercase;
        }

        .meta-value {
            color: #111827;
            font-weight: 800;
        }

        .scope-notice {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #064e3b;
            padding: 10px 12px;
            margin: 12px 0 16px;
            border-radius: 8px;
            font-size: 11px;
            line-height: 1.55;
        }

        .scope-notice strong {
            color: #14532d;
        }

        .section {
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .section-title {
            margin: 0 0 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #cbd5e1;
            color: #111827;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .summary-grid {
            display: table;
            width: 100%;
            border-spacing: 8px 0;
            margin-left: -8px;
            margin-right: -8px;
        }

        .summary-card {
            display: table-cell;
            width: 25%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
            background: #ffffff;
        }

        .summary-label {
            color: #64748b;
            font-size: 9.5px;
            font-weight: 900;
            letter-spacing: 0.10em;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 5px;
            color: #111827;
            font-size: 22px;
            line-height: 1;
            font-weight: 900;
        }

        .summary-unit {
            margin-top: 4px;
            color: #64748b;
            font-size: 10px;
        }

        .two-column {
            display: table;
            width: 100%;
            border-spacing: 10px 0;
            margin-left: -10px;
            margin-right: -10px;
        }

        .two-column .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 10.8px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #d1d5db;
            padding: 7px 8px;
            vertical-align: top;
        }

        .data-table th {
            background: #f8fafc;
            color: #475569;
            text-align: left;
            font-size: 9.5px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .count-cell,
        .area-cell {
            text-align: right;
            white-space: nowrap;
            font-weight: 800;
        }

        .status-pill {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 7px;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-approved,
        .status-approved-clearance {
            color: #14532d;
            background: #dcfce7;
            border-color: #bbf7d0;
        }

        .status-pending-review {
            color: #c2410c;
            background: #ffedd5;
            border-color: #fed7aa;
        }

        .status-not-approved {
            color: #b91c1c;
            background: #fee2e2;
            border-color: #fecaca;
        }

        .signature-grid {
            display: table;
            width: 100%;
            border-spacing: 26px 0;
            margin: 34px -26px 0;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            vertical-align: bottom;
        }

        .signature-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
        }

        .signature-line {
            margin-top: 36px;
            border-top: 1px solid #111827;
            padding-top: 5px;
            text-align: center;
            font-weight: 800;
        }

        .signature-role {
            margin-top: 2px;
            text-align: center;
            color: #64748b;
            font-size: 10px;
        }

        .document-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 9.8px;
            text-align: justify;
        }

        @media print {
            html,
            body {
                background: #ffffff;
            }

            .print-toolbar {
                display: none !important;
            }

            .document-page {
                width: auto;
                min-height: auto;
                max-width: none;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <div class="toolbar-left">
            <a href="{{ route('staff.reports.monitoring.index') }}" class="back-link">Back to Monitoring Reports</a>
            <span class="toolbar-title">Monitoring Report Print Output</span>
        </div>

        <div class="toolbar-right">
            <button type="button" class="print-button primary" onclick="window.print()">Print / Save as PDF</button>
        </div>
    </div>

    <main class="document-page">
        <header class="document-header">
            <div class="seal-cell">
                <div class="seal">
                    @if ($darLogoDataUri)
                        <img src="{{ $darLogoDataUri }}" alt="Department of Agrarian Reform Logo" class="seal-logo">
                    @else
                        <div class="seal-fallback">DAR</div>
                    @endif
                </div>
            </div>
            <div class="agency-cell">
                <p class="republic">Republic of the Philippines</p>
                <p class="agency">Department of Agrarian Reform</p>
                <p class="office">Negros Oriental Provincial Office</p>
                <p class="system-name">Land Transfer Clearance and Monitoring System</p>
            </div>
        </header>

        <section class="report-title-block">
            <div class="report-title-main">
                <h1>Monitoring Report</h1>
                <p>Clearance application processing, decision recording, and administrative monitoring summary.</p>
            </div>
            <div class="report-chip">
                <span class="chip">Office Report</span>
            </div>
        </section>

        <table class="meta-table">
            <tr>
                <td>
                    <span class="meta-label">Date Generated</span>
                    <span class="meta-value">{{ $generatedAtPh?->format('F d, Y h:i A') ?? 'N/A' }}</span>
                </td>
                <td>
                    <span class="meta-label">Generated By</span>
                    <span class="meta-value">{{ $generatedBy?->name ?? 'System User' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="meta-label">Report Type</span>
                    <span class="meta-value">Clearance Application Monitoring Summary</span>
                </td>
                <td>
                    <span class="meta-label">System Scope</span>
                    <span class="meta-value">Administrative processing and monitoring only</span>
                </td>
            </tr>
        </table>

        <div class="scope-notice">
            <strong>Scope Notice:</strong>
            {{ $scopeNotice }}
        </div>

        <section class="section">
            <h2 class="section-title">Executive Summary</h2>
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">Total Applications</div>
                    <div class="summary-value">{{ number_format($totalApplications) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Clearance Outputs</div>
                    <div class="summary-value">{{ number_format($totalClearances) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Recorded Area</div>
                    <div class="summary-value">{{ number_format((float) $totalClearanceArea, 2) }}</div>
                    <div class="summary-unit">hectares in generated clearances</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Municipalities</div>
                    <div class="summary-value">{{ number_format($municipalityBreakdown->count()) }}</div>
                </div>
            </div>
        </section>

        <section class="section two-column">
            <div class="column">
                <h2 class="section-title">Application Status Breakdown</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($statusCounts as $status => $total)
                            @php $statusClass = strtolower(str_replace('_', '-', (string) $status)); @endphp
                            <tr>
                                <td><span class="status-pill status-{{ $statusClass }}">{{ $statusLabel($status) }}</span></td>
                                <td class="count-cell">{{ number_format($total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2">No application status records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="column">
                <h2 class="section-title">Clearance Decision Breakdown</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Decision</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clearanceCounts as $decisionStatus => $total)
                            @php $decisionClass = strtolower(str_replace('_', '-', (string) $decisionStatus)); @endphp
                            <tr>
                                <td><span class="status-pill status-{{ $decisionClass }}">{{ $decisionLabel($decisionStatus) }}</span></td>
                                <td class="count-cell">{{ number_format($total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2">No clearance decision records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Municipality Breakdown</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Municipality</th>
                        <th>Total Applications</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($municipalityBreakdown as $row)
                        <tr>
                            <td>{{ $row->municipality }}</td>
                            <td class="count-cell">{{ number_format($row->total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">No municipality records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2 class="section-title">Recent Applications</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Application Code</th>
                        <th>Transferor</th>
                        <th>Transferee</th>
                        <th>Status</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentApplications as $application)
                        @php $statusClass = strtolower(str_replace('_', '-', (string) $application->status)); @endphp
                        <tr>
                            <td><strong>{{ $application->application_code }}</strong></td>
                            <td>{{ $application->transferor_name ?? 'N/A' }}</td>
                            <td>{{ $application->transferee_name ?? 'N/A' }}</td>
                            <td><span class="status-pill status-{{ $statusClass }}">{{ $statusLabel($application->status) }}</span></td>
                            <td>{{ $application->barangay ?? 'N/A' }}, {{ $application->municipality ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No recent applications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2 class="section-title">Recent Clearance Outputs</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Clearance No.</th>
                        <th>Decision</th>
                        <th>Total Area</th>
                        <th>Generated At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentClearances as $clearance)
                        @php $decisionClass = strtolower(str_replace('_', '-', (string) $clearance->decision_status)); @endphp
                        <tr>
                            <td><strong>{{ $clearance->clearance_number ?? 'N/A' }}</strong></td>
                            <td><span class="status-pill status-{{ $decisionClass }}">{{ $decisionLabel($clearance->decision_status) }}</span></td>
                            <td class="area-cell">{{ number_format((float) $clearance->total_area_hectares, 2) }} ha</td>
                            <td>{{ $clearance->generated_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No recent clearance outputs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="signature-grid">
            <div class="signature-box">
                <div class="signature-label">Prepared by:</div>
                <div class="signature-line">{{ $generatedBy?->name ?? 'System User' }}</div>
                <div class="signature-role">Authorized System User</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">Reviewed by:</div>
                <div class="signature-line">&nbsp;</div>
                <div class="signature-role">Authorized DAR Personnel</div>
            </div>
        </section>

        <footer class="document-footer">
            Generated by DAR-LTCMS for administrative monitoring and reporting. This report summarizes encoded system records only and does not constitute automatic land ownership transfer, registry mutation, or final legal confirmation outside authorized DAR procedures.
        </footer>
    </main>
</body>
</html>
