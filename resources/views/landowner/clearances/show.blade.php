<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clearance->clearance_number }} | Decision Output</title>

    @php
        $decisionLabel = $clearance->decision_status === 'approved'
            ? 'Approved Clearance'
            : ucwords(str_replace('_', ' ', $clearance->decision_status));
        $decisionClass = strtolower((string) $clearance->decision_status) === 'approved' ? 'approved' : 'not-approved';
        $reviewedAt = optional($clearance->reviewed_at)->timezone('Asia/Manila');
        $generatedAt = optional($clearance->generated_at)->timezone('Asia/Manila');

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
            margin: 14mm;
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
            background: #e5e7eb;
            color: #111827;
            font-family: var(--font-ui);
            font-size: 13px;
            line-height: 1.5;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(243, 244, 246, 0.96);
            border-bottom: 1px solid #d1d5db;
            backdrop-filter: blur(8px);
        }

        .toolbar-inner {
            max-width: 980px;
            margin: 0 auto;
            min-height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
        }

        .toolbar-left,
        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .toolbar-title {
            color: #374151;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .btn {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 14px;
            border-radius: 9px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            text-decoration: none;
            font-family: var(--font-ui);
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
        }

        .btn.primary {
            border-color: #166534;
            background: #166534;
            color: #ffffff;
        }

        .btn.dark {
            border-color: #111827;
            background: #111827;
            color: #ffffff;
        }

        .page-shell {
            max-width: 980px;
            margin: 20px auto 30px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
        }

        .page {
            padding: 34px 42px 38px;
        }

        .official-header {
            display: grid;
            grid-template-columns: 64px 1fr 64px;
            align-items: center;
            text-align: center;
            border-bottom: 3px double #14532d;
            padding-bottom: 15px;
            margin-bottom: 18px;
        }

        .seal {
            width: 54px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .seal-logo {
            width: 54px;
            height: 54px;
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
            font-size: 13px;
            font-weight: 900;
        }

        .republic {
            margin: 0;
            color: #374151;
            font-size: 12px;
        }

        .agency {
            margin: 2px 0 0;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .office {
            margin: 2px 0 0;
            color: #14532d;
            font-size: 13px;
            font-weight: 900;
        }

        .system-name {
            margin: 2px 0 0;
            color: #4b5563;
            font-size: 11px;
        }

        .document-title-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 18px;
            align-items: start;
            margin-bottom: 16px;
        }

        .document-title h1 {
            margin: 0;
            color: #111827;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .document-title p {
            margin: 7px 0 0;
            color: #4b5563;
            font-size: 13px;
        }

        .clearance-number-box {
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 12px 14px;
            min-width: 230px;
            background: #f8fafc;
        }

        .box-label {
            display: block;
            margin-bottom: 4px;
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .box-value {
            color: #111827;
            font-size: 16px;
            font-weight: 900;
        }

        .scope-notice {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #064e3b;
            padding: 12px 14px;
            margin: 14px 0 18px;
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.6;
        }

        .scope-notice strong {
            color: #14532d;
        }

        .decision-panel {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
            gap: 14px;
            margin: 16px 0 18px;
        }

        .decision-box {
            border: 2px solid #14532d;
            border-radius: 12px;
            padding: 16px;
            background: #f0fdf4;
        }

        .decision-box.not-approved {
            border-color: #dc2626;
            background: #fef2f2;
        }

        .decision-box.not-approved .decision-label {
            color: #991b1b;
        }

        .decision-label {
            color: #475569;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .decision-value {
            margin-top: 6px;
            color: #14532d;
            font-size: 26px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .decision-box.not-approved .decision-value {
            color: #b91c1c;
        }

        .decision-box.not-approved .decision-note {
            color: #7f1d1d;
        }

        .decision-note {
            margin-top: 8px;
            color: #4b5563;
            font-size: 12px;
        }

        .info-card {
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: #ffffff;
            padding: 14px;
        }

        .info-card-title {
            margin: 0 0 10px;
            color: #111827;
            font-size: 13px;
            font-weight: 900;
        }

        .mini-meta {
            display: grid;
            gap: 7px;
        }

        .mini-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
        }

        .mini-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .mini-row span:first-child {
            color: #64748b;
            font-weight: 800;
        }

        .mini-row span:last-child {
            color: #111827;
            font-weight: 900;
            text-align: right;
        }

        .section {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .section-title {
            margin: 0 0 9px;
            padding-bottom: 6px;
            border-bottom: 1px solid #cbd5e1;
            color: #111827;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 220px 1fr;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            overflow: hidden;
        }

        .meta-grid .label,
        .meta-grid .value {
            padding: 9px 11px;
            border-bottom: 1px solid #e5e7eb;
        }

        .meta-grid .label {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .meta-grid .value {
            color: #111827;
            font-weight: 800;
        }

        .meta-grid .label:nth-last-child(2),
        .meta-grid .value:last-child {
            border-bottom: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 8px 9px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .area-cell {
            text-align: right;
            white-space: nowrap;
            font-weight: 900;
        }

        .certification {
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 14px;
            background: #ffffff;
            text-align: justify;
            font-size: 13px;
            line-height: 1.7;
        }

        .muted {
            margin-top: 8px;
            color: #64748b;
            font-size: 11px;
        }

        .signature-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 44px;
            margin-top: 48px;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 7px;
            text-align: center;
            font-weight: 900;
        }

        .signature-role {
            margin-top: 2px;
            text-align: center;
            color: #64748b;
            font-size: 11px;
        }

        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            color: #475569;
            font-size: 11px;
            text-align: justify;
        }

        @media print {
            html,
            body {
                background: #ffffff;
            }

            .toolbar {
                display: none !important;
            }

            .page-shell {
                max-width: none;
                margin: 0;
                border: 0;
                box-shadow: none;
            }

            .page {
                padding: 0;
            }
        }

        @media (max-width: 760px) {
            .toolbar-inner,
            .page-shell {
                max-width: calc(100% - 24px);
            }

            .toolbar-inner,
            .toolbar-left,
            .toolbar-right,
            .document-title-row,
            .decision-panel,
            .signature-row {
                display: flex;
                flex-direction: column;
                align-items: stretch;
            }

            .page {
                padding: 22px;
            }

            .meta-grid {
                grid-template-columns: 1fr;
            }

            .meta-grid .label {
                border-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="toolbar-inner">
            <div class="toolbar-left">
                <a href="{{ route('landowner.applications.index') }}" class="btn">Back to My Applications</a>
                <span class="toolbar-title">Decision Output Preview</span>
            </div>
            <div class="toolbar-right">
                <a href="{{ route('landowner.applications.clearance.pdf', $application) }}" class="btn primary" target="_blank">Open PDF Output</a>
                <button type="button" onclick="window.print()" class="btn dark">Print / Save as PDF</button>
            </div>
        </div>
    </div>

    <main class="page-shell">
        <div class="page">
            <header class="official-header">
                <div class="seal">
                    @if ($darLogoDataUri)
                        <img src="{{ $darLogoDataUri }}" alt="Department of Agrarian Reform Logo" class="seal-logo">
                    @else
                        <div class="seal-fallback">DAR</div>
                    @endif
                </div>
                <div>
                    <p class="republic">Republic of the Philippines</p>
                    <p class="agency">Department of Agrarian Reform</p>
                    <p class="office">Negros Oriental Provincial Office</p>
                    <p class="system-name">Land Transfer Clearance and Monitoring System</p>
                </div>
                <div></div>
            </header>

            <section class="document-title-row">
                <div class="document-title">
                    <h1>Clearance Decision Record</h1>
                    <p>System-generated administrative record of the finalized clearance application decision.</p>
                </div>
                <div class="clearance-number-box">
                    <span class="box-label">Clearance Number</span>
                    <span class="box-value">{{ $clearance->clearance_number }}</span>
                </div>
            </section>

            <div class="scope-notice">
                <strong>Scope Notice:</strong>
                This clearance output records the finalized processing decision only. It does not automatically transfer land ownership, alter registry records, assign landholdings, or replace separate legal and administrative procedures required for actual land transfer or ownership mutation.
            </div>

            <section class="decision-panel">
                <div class="decision-box {{ $decisionClass }}">
                    <div class="decision-label">Recorded Application Decision</div>
                    <div class="decision-value">{{ $decisionLabel }}</div>
                    <div class="decision-note">Decision status is locked in the system for auditability and monitoring.</div>
                </div>
                <div class="info-card">
                    <h2 class="info-card-title">Output Metadata</h2>
                    <div class="mini-meta">
                        <div class="mini-row">
                            <span>Reviewed by</span>
                            <span>{{ $clearance->review_officer_name }}</span>
                        </div>
                        <div class="mini-row">
                            <span>Reviewed at</span>
                            <span>{{ $reviewedAt?->format('M d, Y h:i A') ?? '—' }}</span>
                        </div>
                        <div class="mini-row">
                            <span>Generated at</span>
                            <span>{{ $generatedAt?->format('M d, Y h:i A') ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Application Information</h2>
                <div class="meta-grid">
                    <div class="label">Application Code</div>
                    <div class="value">{{ $clearance->application_code }}</div>

                    <div class="label">Transferor</div>
                    <div class="value">{{ $clearance->transferor_name }}</div>

                    <div class="label">Transferee</div>
                    <div class="value">{{ $clearance->transferee_name }}</div>

                    <div class="label">Location</div>
                    <div class="value">{{ $clearance->barangay ?? '—' }}, {{ $clearance->municipality ?? '—' }}</div>

                    <div class="label">Total Area Covered</div>
                    <div class="value">{{ number_format((float) $clearance->total_area_hectares, 4) }} hectares</div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Parcel Snapshot</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Parcel ID</th>
                            <th>Parcel / Code</th>
                            <th>Lot Number</th>
                            <th>Title Number</th>
                            <th>Area (ha)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clearance->parcel_snapshot as $parcel)
                            <tr>
                                <td>{{ $parcel['parcel_id'] ?? '—' }}</td>
                                <td>{{ $parcel['parcel_number'] ?? $parcel['parcel_code'] ?? '—' }}</td>
                                <td>{{ $parcel['lot_number'] ?? '—' }}</td>
                                <td>{{ $parcel['title_number'] ?? $parcel['title_no'] ?? '—' }}</td>
                                <td class="area-cell">
                                    {{ isset($parcel['area_hectares']) ? number_format((float) $parcel['area_hectares'], 4) : '0.0000' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">No parcel snapshot recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <p class="muted">Parcel information is a snapshot captured at clearance generation time for documentation and audit reference only.</p>
            </section>

            <section class="section">
                <h2 class="section-title">Certification Statement</h2>
                <p class="certification">
                    This document certifies that the above land transfer clearance application has been processed through the Department of Agrarian Reform Land Transfer Clearance and Monitoring System. The decision shown in this record reflects the finalized administrative decision stored in the system for monitoring, documentation, and audit reference only.
                </p>
            </section>

            <section class="signature-row">
                <div></div>
                <div>
                    <div class="signature-line">{{ $clearance->review_officer_name }}</div>
                    <div class="signature-role">Reviewing Officer</div>
                </div>
            </section>

            <footer class="footer">
                Generated by DAR-LTCMS as an administrative clearance output. This document remains subject to applicable DAR procedures, official verification, and separate legal requirements. It is not an automatic land ownership transfer, parcel ownership mutation, or Registry of Deeds alteration.
            </footer>
        </div>
    </main>
</body>
</html>
