<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Report | DAR-LTCMS</title>

    @php
        $generatedAtPh = $generatedAt?->timezone('Asia/Manila');
        $normalizedStatusCounts = collect($statusCounts ?? []);
        $normalizedClearanceCounts = collect($clearanceCounts ?? []);

        $statusRows = [
            'Pending Review by Legal Officer' => (int) (($normalizedStatusCounts['pending_legal_review'] ?? 0) + ($normalizedStatusCounts['pending_review'] ?? 0) + ($normalizedStatusCounts['draft'] ?? 0)),
            'Endorsed to LTI Division' => (int) ($normalizedStatusCounts['endorsed_lti'] ?? 0),
            'Endorsed to Chief Legal' => (int) ($normalizedStatusCounts['endorsed_chief_legal'] ?? 0),
            'Endorsed to PARPO II' => (int) ($normalizedStatusCounts['endorsed_parpo'] ?? 0),
            'For Releasing' => (int) ($normalizedStatusCounts['for_releasing'] ?? 0),
            'Released' => (int) (($normalizedStatusCounts['released'] ?? 0) + ($normalizedStatusCounts['approved'] ?? 0)),
            'Denied' => (int) (($normalizedStatusCounts['denied'] ?? 0) + ($normalizedStatusCounts['not_approved'] ?? 0)),
        ];

        $activeApplicationCount = collect($statusRows)->only([
            'Pending Review by Legal Officer',
            'Endorsed to LTI Division',
            'Endorsed to Chief Legal',
            'Endorsed to PARPO II',
            'For Releasing',
        ])->sum();

        $releasedResults = (int) (($normalizedClearanceCounts['released'] ?? 0) + ($normalizedClearanceCounts['approved'] ?? 0));
        $deniedResults = (int) (($normalizedClearanceCounts['denied'] ?? 0) + ($normalizedClearanceCounts['not_approved'] ?? 0));
        $backParams = array_filter($filters ?? [], fn ($value) => filled($value));

        $decisionLabel = function (?string $status): string {
            return match ($status) {
                'released', 'approved' => 'Released',
                'denied', 'not_approved' => 'Denied',
                default => ucwords(str_replace('_', ' ', (string) $status)),
            };
        };

        $darLogoDataUri = null;
        foreach (['images/dar-logo.png', 'images/dar-logo.svg', 'images/dar-logo.jpg', 'images/dar-logo.jpeg'] as $logoCandidate) {
            $logoPath = public_path($logoCandidate);
            if (! file_exists($logoPath)) {
                continue;
            }

            $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/png',
            };
            $darLogoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            break;
        }
    @endphp

    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.45; }
        .toolbar { max-width: 980px; margin: 16px auto; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .toolbar a, .toolbar button { min-height: 38px; display: inline-flex; align-items: center; justify-content: center; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #111827; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar button { border-color: #166534; background: #166534; color: #fff; }
        .page { width: 210mm; min-height: 297mm; max-width: 980px; margin: 0 auto 24px; padding: 16mm; background: #fff; box-shadow: 0 12px 32px rgba(15,23,42,.14); }
        .header { display: grid; grid-template-columns: 58px 1fr 58px; align-items: center; border-bottom: 3px double #14532d; padding-bottom: 10px; text-align: center; }
        .logo { width: 50px; height: 50px; object-fit: contain; }
        .logo-fallback { width: 46px; height: 46px; display: grid; place-items: center; border: 1px solid #bbf7d0; border-radius: 10px; color: #14532d; font-weight: 900; }
        .republic { margin: 0; color: #475569; font-size: 10px; }
        .agency { margin: 2px 0 0; font-size: 14px; font-weight: 900; text-transform: uppercase; }
        .office { margin: 2px 0 0; color: #14532d; font-size: 11px; font-weight: 800; }
        .system { margin: 2px 0 0; color: #64748b; font-size: 9.5px; }
        .title-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin: 16px 0 10px; }
        h1 { margin: 0; font-size: 18px; font-weight: 900; text-transform: uppercase; letter-spacing: .05em; }
        .subtitle { margin: 4px 0 0; color: #64748b; }
        .chip { display: inline-block; border: 1px solid #bbf7d0; border-radius: 999px; background: #f0fdf4; color: #14532d; padding: 5px 9px; font-size: 9px; font-weight: 900; text-transform: uppercase; }
        .meta, .summary, .two-col { width: 100%; border-collapse: collapse; }
        .meta td { width: 33.333%; border: 1px solid #d1d5db; padding: 7px 9px; vertical-align: top; }
        .label { display: block; margin-bottom: 2px; color: #64748b; font-size: 8.5px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .value { font-weight: 800; }
        .filters { margin: 10px 0; padding: 8px 10px; border: 1px solid #dbe7de; background: #f8fcf9; }
        .filters strong { color: #14532d; }
        .scope { margin: 10px 0 14px; padding: 9px 10px; border: 1px solid #bbf7d0; border-radius: 7px; background: #f0fdf4; color: #064e3b; }
        .scope p { margin: 0; }
        .scope p + p { margin-top: 5px; font-size: 9.5px; }
        .section { margin-top: 14px; }
        .section-title { margin: 0 0 7px; padding-bottom: 4px; border-bottom: 1px solid #cbd5e1; font-size: 11px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; }
        .summary { table-layout: fixed; }
        .summary td { width: 25%; border: 1px solid #d1d5db; padding: 9px; vertical-align: top; }
        .summary-number { margin-top: 4px; font-size: 19px; font-weight: 900; }
        .summary-note { margin-top: 3px; color: #64748b; font-size: 8.8px; }
        .two-col { table-layout: fixed; }
        .two-col > tbody > tr > td { width: 50%; padding-right: 8px; vertical-align: top; }
        .two-col > tbody > tr > td:last-child { padding-right: 0; padding-left: 8px; }
        .data { width: 100%; border-collapse: collapse; font-size: 9.5px; }
        .data th, .data td { border: 1px solid #d1d5db; padding: 5px 6px; vertical-align: top; }
        .data th { background: #f8fafc; color: #475569; font-size: 8.3px; font-weight: 900; text-align: left; text-transform: uppercase; letter-spacing: .05em; }
        .number { text-align: right; white-space: nowrap; font-weight: 800; }
        .result-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .result { border: 1px solid #d1d5db; border-radius: 6px; padding: 8px; }
        .result.released { border-left: 4px solid #16a34a; }
        .result.denied { border-left: 4px solid #dc2626; }
        .result strong { display: block; font-size: 18px; }
        .result span { color: #64748b; font-size: 9px; }
        .footer { margin-top: 16px; padding-top: 7px; border-top: 1px solid #e5e7eb; color: #64748b; font-size: 8.8px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page { width: auto; min-height: auto; max-width: none; margin: 0; padding: 0; box-shadow: none; }
            .section { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('staff.reports.monitoring.index', $backParams) }}">Back to Monitoring Reports</a>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <main class="page">
        <header class="header">
            <div>
                @if ($darLogoDataUri)
                    <img src="{{ $darLogoDataUri }}" alt="Department of Agrarian Reform logo" class="logo">
                @else
                    <div class="logo-fallback">DAR</div>
                @endif
            </div>
            <div>
                <p class="republic">Republic of the Philippines</p>
                <p class="agency">Department of Agrarian Reform</p>
                <p class="office">Negros Oriental Provincial Office</p>
                <p class="system">Land Transfer Clearance and Monitoring System</p>
            </div>
            <div></div>
        </header>

        <div class="title-row">
            <div>
                <h1>Monitoring Report</h1>
                <p class="subtitle">Administrative clearance processing, final-result recording, and monitoring summary.</p>
            </div>
            <span class="chip">Administrative Report</span>
        </div>

        <table class="meta">
            <tr>
                <td><span class="label">Date Generated</span><span class="value">{{ $generatedAtPh?->format('M d, Y h:i A') }} PHT</span></td>
                <td><span class="label">Generated By</span><span class="value">{{ $generatedBy?->name ?? 'Authorized Staff' }}</span></td>
                <td><span class="label">Dataset</span><span class="value">{{ $hasActiveFilters ? 'Filtered application dataset' : 'All application records' }}</span></td>
            </tr>
        </table>

        <div class="filters">
            <strong>Report Filters:</strong>
            @if ($hasActiveFilters)
                {{ $filterLabels->implode(' · ') }}
            @else
                None — all application records are included.
            @endif
            <br>
            <span>Date filtering uses Date of Application, with encoded date used only when Date of Application is missing.</span>
        </div>

        <div class="scope">
            <p><strong>Scope Notice:</strong> {{ $scopeNotice }}</p>
            <p>{{ $areaNotice }}</p>
        </div>

        <section class="section">
            <h2 class="section-title">Summary</h2>
            <table class="summary">
                <tr>
                    <td><span class="label">Total Applications</span><div class="summary-number">{{ number_format($totalApplications) }}</div><div class="summary-note">Matching report filters</div></td>
                    <td><span class="label">Active Applications</span><div class="summary-number">{{ number_format($activeApplicationCount) }}</div><div class="summary-note">Still in clearance processing</div></td>
                    <td><span class="label">Recorded Results</span><div class="summary-number">{{ number_format($totalClearances) }}</div><div class="summary-note">Released or denied snapshots</div></td>
                    <td><span class="label">Recorded Output Area</span><div class="summary-number">{{ number_format((float) $totalClearanceArea, 4) }}</div><div class="summary-note">ha in final snapshots; not ownership transferred</div></td>
                </tr>
            </table>
        </section>

        <section class="section">
            <table class="two-col">
                <tr>
                    <td>
                        <h2 class="section-title">Workflow Status Breakdown</h2>
                        <table class="data">
                            <thead><tr><th>Status</th><th class="number">Count</th></tr></thead>
                            <tbody>
                                @foreach ($statusRows as $label => $count)
                                    <tr><td>{{ $label }}</td><td class="number">{{ number_format($count) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <h2 class="section-title">Final Output Results</h2>
                        <div class="result-grid">
                            <div class="result released">
                                <span class="label">Released</span>
                                <strong>{{ number_format($releasedResults) }}</strong>
                                <span>{{ number_format((float) $releasedOutputArea, 4) }} ha in snapshots</span>
                            </div>
                            <div class="result denied">
                                <span class="label">Denied</span>
                                <strong>{{ number_format($deniedResults) }}</strong>
                                <span>{{ number_format((float) $deniedOutputArea, 4) }} ha in snapshots</span>
                            </div>
                        </div>

                        <h2 class="section-title" style="margin-top: 13px;">Municipality Breakdown</h2>
                        <table class="data">
                            <thead><tr><th>Municipality / City</th><th class="number">Applications</th></tr></thead>
                            <tbody>
                                @forelse ($municipalityBreakdown as $row)
                                    <tr><td>{{ $row->municipality }}</td><td class="number">{{ number_format((int) $row->total) }}</td></tr>
                                @empty
                                    <tr><td colspan="2">No municipality data matches the current filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
        </section>

        <section class="section">
            <h2 class="section-title">Recent Applications</h2>
            <table class="data">
                <thead>
                    <tr><th>Application</th><th>Transferor</th><th>Transferee</th><th>Location</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($recentApplications as $application)
                        <tr>
                            <td>{{ $application->application_code }}</td>
                            <td>{{ $application->transferorDisplayName() ?: 'Not specified' }}</td>
                            <td>{{ $application->transfereeDisplayName() ?: 'Not specified' }}</td>
                            <td>{{ collect([$application->barangay, $application->municipality])->filter()->implode(', ') ?: 'Not specified' }}</td>
                            <td>{{ ($application->date_of_application ?? $application->created_at)?->format('M d, Y') }}</td>
                            <td>{{ $application->statusLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No applications match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2 class="section-title">Recent Release / Denial Outputs</h2>
            <table class="data">
                <thead>
                    <tr><th>Clearance No.</th><th>Application</th><th>Decision</th><th class="number">Snapshot Area</th><th>Generated</th></tr>
                </thead>
                <tbody>
                    @forelse ($recentClearances as $clearance)
                        <tr>
                            <td>{{ $clearance->clearance_number }}</td>
                            <td>{{ $clearance->application_code }}</td>
                            <td>{{ $decisionLabel($clearance->decision_status) }}</td>
                            <td class="number">{{ number_format((float) $clearance->total_area_hectares, 4) }} ha</td>
                            <td>{{ $clearance->generated_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No final clearance outputs match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div class="footer">
            DAR-LTCMS is an administrative clearance generation, processing, records-management, and monitoring platform for the DAR Negros Oriental Provincial Office. This report does not constitute a registry mutation or evidence that legal land ownership transfer has been completed.
        </div>
    </main>
</body>
</html>
