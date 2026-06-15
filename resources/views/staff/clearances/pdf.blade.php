<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $clearance->clearance_number }}</title>

    @php
        $rawDecisionStatus = strtolower((string) $clearance->decision_status);
        $isReleasedDecision = in_array($rawDecisionStatus, ['released', 'approved'], true);
        $isDeniedDecision = in_array($rawDecisionStatus, ['denied', 'not_approved'], true);

        $decisionLabel = match (true) {
            $isReleasedDecision => 'Released Clearance',
            $isDeniedDecision => 'Denied',
            default => ucwords(str_replace('_', ' ', (string) $clearance->decision_status)),
        };

        $decisionClass = $isDeniedDecision ? 'denied' : 'released';
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
            margin: 13mm 14mm 15mm;
        }

        :root {
            --font-ui: 'Google Sans', 'Product Sans', Arial, Helvetica, sans-serif;
        }

        * {
            font-family: var(--font-ui) !important;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: var(--font-ui);
            font-size: 11px;
            line-height: 1.42;
        }

        .official-header {
            width: 100%;
            border-bottom: 3px double #14532d;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .seal-cell {
            width: 58px;
            vertical-align: middle;
        }

        .seal {
            width: 48px;
            height: 48px;
            text-align: center;
        }

        .seal-logo {
            width: 48px;
            height: 48px;
        }

        .seal-fallback {
            width: 40px;
            height: 40px;
            line-height: 40px;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            background: #f0fdf4;
            color: #14532d;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
        }

        .header-text {
            text-align: center;
            padding-right: 58px;
        }

        .republic {
            font-size: 10px;
            color: #374151;
        }

        .agency {
            margin-top: 2px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .office {
            margin-top: 2px;
            color: #14532d;
            font-size: 11px;
            font-weight: bold;
        }

        .system-name {
            margin-top: 2px;
            color: #4b5563;
            font-size: 9.5px;
        }

        .title-table {
            width: 100%;
            margin-bottom: 12px;
        }

        .title-cell h1 {
            margin: 0;
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .title-cell p {
            margin: 4px 0 0;
            color: #4b5563;
            font-size: 10px;
        }

        .clearance-box {
            width: 190px;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            padding: 8px 9px;
        }

        .box-label {
            display: block;
            margin-bottom: 3px;
            color: #64748b;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        .box-value {
            color: #111827;
            font-size: 12px;
            font-weight: bold;
        }

        .notice {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #064e3b;
            padding: 9px 10px;
            margin: 10px 0 12px;
            font-size: 10px;
            text-align: justify;
        }

        .notice strong {
            color: #14532d;
        }

        .decision-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0;
            margin: 10px 0 14px;
        }

        .decision-cell {
            width: 56%;
            border: 2px solid #14532d;
            background: #f0fdf4;
            padding: 11px;
            vertical-align: top;
        }

        .decision-cell.denied {
            border-color: #dc2626;
            background: #fef2f2;
        }

        .decision-label {
            color: #475569;
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .decision-cell.denied .decision-label {
            color: #991b1b;
        }

        .decision-value {
            margin-top: 5px;
            color: #14532d;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .decision-cell.denied .decision-value {
            color: #b91c1c;
        }

        .decision-note {
            margin-top: 5px;
            color: #4b5563;
            font-size: 9.5px;
        }

        .decision-cell.denied .decision-note {
            color: #7f1d1d;
        }

        .metadata-cell {
            width: 44%;
            border: 1px solid #d1d5db;
            padding: 10px;
            vertical-align: top;
        }

        .metadata-title {
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: bold;
        }

        .mini-table {
            width: 100%;
            border-collapse: collapse;
        }

        .mini-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 4px 0;
            vertical-align: top;
            font-size: 9.5px;
        }

        .mini-table td:first-child {
            color: #64748b;
            font-weight: bold;
        }

        .mini-table td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .section-title {
            margin-top: 13px;
            margin-bottom: 7px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .meta-table,
        .parcel-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            border: 1px solid #d1d5db;
            padding: 6px 7px;
            vertical-align: top;
        }

        .meta-table td:first-child {
            width: 31%;
            background: #f8fafc;
            color: #475569;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .meta-table td:last-child {
            font-weight: bold;
        }

        .parcel-table {
            margin-top: 6px;
        }

        .parcel-table th,
        .parcel-table td {
            border: 1px solid #d1d5db;
            padding: 6px 7px;
            text-align: left;
            vertical-align: top;
            font-size: 9.8px;
        }

        .parcel-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 8.8px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .area-cell {
            text-align: right;
            white-space: nowrap;
            font-weight: bold;
        }

        .muted {
            color: #64748b;
            font-size: 9px;
            margin-top: 5px;
        }

        .certification {
            border: 1px solid #d1d5db;
            padding: 10px;
            text-align: justify;
            font-size: 10.5px;
            line-height: 1.6;
        }

        .signature-table {
            width: 100%;
            margin-top: 40px;
        }

        .signature-table td {
            width: 50%;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 6px;
            text-align: center;
            font-weight: bold;
        }

        .signature-role {
            margin-top: 2px;
            color: #64748b;
            font-size: 9.5px;
            text-align: center;
        }

        .footer {
            margin-top: 22px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            color: #475569;
            font-size: 9px;
            text-align: justify;
        }
    </style>
</head>
<body>
    <table class="official-header">
        <tr>
            <td class="seal-cell">
                <div class="seal">
                    @if ($darLogoDataUri)
                        <img src="{{ $darLogoDataUri }}" alt="Department of Agrarian Reform Logo" class="seal-logo">
                    @else
                        <div class="seal-fallback">DAR</div>
                    @endif
                </div>
            </td>
            <td class="header-text">
                <div class="republic">Republic of the Philippines</div>
                <div class="agency">Department of Agrarian Reform</div>
                <div class="office">Negros Oriental Provincial Office</div>
                <div class="system-name">Land Transfer Clearance and Monitoring System</div>
            </td>
        </tr>
    </table>

    <table class="title-table">
        <tr>
            <td class="title-cell">
                <h1>Clearance Release / Denial Record</h1>
                <p>System-generated administrative record of the finalized clearance application result.</p>
            </td>
            <td class="clearance-box">
                <span class="box-label">Clearance Number</span>
                <span class="box-value">{{ $clearance->clearance_number }}</span>
            </td>
        </tr>
    </table>

    <div class="notice">
        <strong>Scope Notice:</strong>
        This clearance output records the finalized processing decision only. It does not automatically transfer land ownership, alter registry records, assign landholdings, or replace separate legal and administrative procedures required for actual land transfer or ownership mutation.
    </div>

    <table class="decision-table">
        <tr>
            <td class="decision-cell {{ $decisionClass }}">
                <div class="decision-label">Recorded Application Result</div>
                <div class="decision-value">{{ $decisionLabel }}</div>
                <div class="decision-note">Release or denial status is locked in the system for auditability and monitoring.</div>
            </td>
            <td style="width: 10px;"></td>
            <td class="metadata-cell">
                <div class="metadata-title">Output Metadata</div>
                <table class="mini-table">
                    <tr>
                        <td>Reviewed by</td>
                        <td>{{ $clearance->review_officer_name }}</td>
                    </tr>
                    <tr>
                        <td>Reviewed at</td>
                        <td>{{ $reviewedAt?->format('M d, Y h:i A') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td>Generated at</td>
                        <td>{{ $generatedAt?->format('M d, Y h:i A') ?? '—' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Application Information</div>
    <table class="meta-table">
        <tr>
            <td>Application Code</td>
            <td>{{ $clearance->application_code }}</td>
        </tr>
        <tr>
            <td>Transferor</td>
            <td>{{ $clearance->transferor_name }}</td>
        </tr>
        <tr>
            <td>Transferee</td>
            <td>{{ $clearance->transferee_name }}</td>
        </tr>
        <tr>
            <td>Location</td>
            <td>{{ $clearance->barangay ?? '—' }}, {{ $clearance->municipality ?? '—' }}</td>
        </tr>
        <tr>
            <td>Total Area Covered</td>
            <td>{{ number_format((float) $clearance->total_area_hectares, 4) }} hectares</td>
        </tr>
    </table>

    <div class="section-title">Parcel Snapshot</div>
    <table class="parcel-table">
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
    <div class="muted">Parcel information is a snapshot captured at clearance generation time for documentation and audit reference only.</div>

    <div class="section-title">Certification Statement</div>
    <div class="certification">
        This document certifies that the above land transfer clearance application has been processed through the Department of Agrarian Reform Land Transfer Clearance and Monitoring System. The result shown in this record reflects the finalized administrative release or denial stored in the system for monitoring, documentation, and audit reference only.
    </div>

    <table class="signature-table">
        <tr>
            <td></td>
            <td>
                <div class="signature-line">{{ $clearance->review_officer_name }}</div>
                <div class="signature-role">Reviewing Officer</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated by DAR-LTCMS as an administrative release or denial output. This document remains subject to applicable DAR procedures, official verification, and separate legal requirements. It is not an automatic land ownership transfer, parcel ownership mutation, or Registry of Deeds alteration.
    </div>
</body>
</html>
