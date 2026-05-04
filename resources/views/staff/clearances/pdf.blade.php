<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $clearance->clearance_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.5;
        }

        .official-header {
            text-align: center;
            border-bottom: 3px double #111827;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }

        .small {
            font-size: 11px;
            margin: 2px 0;
        }

        .agency {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 3px 0;
        }

        .office {
            font-size: 12px;
            font-weight: bold;
            margin: 3px 0;
        }

        .document-title {
            text-align: center;
            margin: 22px 0 16px 0;
        }

        .document-title h1 {
            font-size: 18px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .document-title p {
            margin: 6px 0 0 0;
            font-size: 12px;
        }

        .notice {
            border: 1px solid #92400e;
            background: #fffbeb;
            color: #78350f;
            padding: 10px;
            font-size: 11px;
            margin: 16px 0 20px 0;
            text-align: justify;
        }

        .section-title {
            margin-top: 18px;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid #9ca3af;
            padding-bottom: 4px;
        }

        .meta-table,
        .parcel-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 5px 7px;
            vertical-align: top;
        }

        .meta-table td:first-child {
            width: 30%;
        }

        .decision-box {
            margin-top: 10px;
            border: 2px solid #111827;
            padding: 12px;
            text-align: center;
        }

        .decision-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #374151;
        }

        .decision-value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 4px;
            text-transform: uppercase;
        }

        .parcel-table {
            margin-top: 8px;
        }

        .parcel-table th,
        .parcel-table td {
            border: 1px solid #374151;
            padding: 7px;
            text-align: left;
            vertical-align: top;
        }

        .parcel-table th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .certification {
            text-align: justify;
            margin-top: 8px;
        }

        .muted {
            color: #6b7280;
            font-size: 10px;
            margin-top: 6px;
        }

        .signature-row {
            width: 100%;
            margin-top: 54px;
        }

        .signature-cell-left {
            width: 50%;
        }

        .signature-cell-right {
            width: 50%;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 6px;
        }

        .footer {
            margin-top: 28px;
            font-size: 10px;
            color: #374151;
            text-align: justify;
        }
    </style>
</head>
<body>
    <div class="official-header">
        <div class="small">Republic of the Philippines</div>
        <div class="agency">Department of Agrarian Reform</div>
        <div class="office">Provincial Office — Negros Oriental</div>
        <div class="small">Land Transfer Clearance and Monitoring System</div>
    </div>

    <div class="document-title">
        <h1>Clearance Decision Record</h1>
        <p><strong>Clearance No.:</strong> {{ $clearance->clearance_number }}</p>
    </div>

    <div class="notice">
        <strong>Scope Notice:</strong>
        This system-generated clearance record documents the finalized processing decision recorded by DAR staff.
        It does not automatically transfer land ownership, alter registry records, or replace separate legal and
        administrative procedures required for actual land transfer or ownership mutation.
    </div>

    <div class="section-title">Application Information</div>

    <table class="meta-table">
        <tr>
            <td><strong>Application Code</strong></td>
            <td>{{ $clearance->application_code }}</td>
        </tr>
        <tr>
            <td><strong>Transferor</strong></td>
            <td>{{ $clearance->transferor_name }}</td>
        </tr>
        <tr>
            <td><strong>Transferee</strong></td>
            <td>{{ $clearance->transferee_name }}</td>
        </tr>
        <tr>
            <td><strong>Location</strong></td>
            <td>{{ $clearance->barangay ?? '—' }}, {{ $clearance->municipality ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Total Area Covered</strong></td>
            <td>{{ number_format((float) $clearance->total_area_hectares, 4) }} hectares</td>
        </tr>
        <tr>
            <td><strong>Reviewing Officer</strong></td>
            <td>{{ $clearance->review_officer_name }}</td>
        </tr>
        <tr>
            <td><strong>Decision Timestamp</strong></td>
            <td>{{ optional($clearance->reviewed_at)->format('M d, Y h:i A') ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Generated Timestamp</strong></td>
            <td>{{ optional($clearance->generated_at)->format('M d, Y h:i A') ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Final Decision</div>

    <div class="decision-box">
        <div class="decision-label">Recorded Application Decision</div>
        <div class="decision-value">
            {{ strtoupper(str_replace('_', ' ', $clearance->decision_status)) }}
        </div>
    </div>

    <div class="section-title">Parcel Snapshot</div>

    <table class="parcel-table">
        <thead>
            <tr>
                <th>Parcel ID</th>
                <th>Parcel Number</th>
                <th>Lot Number</th>
                <th>Title Number</th>
                <th>Area (ha)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clearance->parcel_snapshot as $parcel)
                <tr>
                    <td>{{ $parcel['parcel_id'] ?? '—' }}</td>
                    <td>{{ $parcel['parcel_number'] ?? '—' }}</td>
                    <td>{{ $parcel['lot_number'] ?? '—' }}</td>
                    <td>{{ $parcel['title_number'] ?? '—' }}</td>
                    <td>
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

    <p class="muted">
        Parcel information is recorded as a snapshot at the time the clearance decision was generated.
    </p>

    <div class="section-title">Certification Statement</div>

    <p class="certification">
        This document certifies that the above land transfer clearance application has been processed
        through the Department of Agrarian Reform Land Transfer Clearance and Monitoring System and that
        the decision shown in this record reflects the finalized decision stored in the system for
        administrative monitoring, documentation, and audit reference.
    </p>

    <table class="signature-row">
        <tr>
            <td class="signature-cell-left"></td>
            <td class="signature-cell-right">
                <div class="signature-line">
                    <strong>{{ $clearance->review_officer_name }}</strong><br>
                    Reviewing Officer
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated by the Department of Agrarian Reform Land Transfer Clearance and Monitoring System.
        This document is valid as a system-generated administrative record only and remains subject to
        applicable DAR procedures, official verification, and legal requirements.
    </div>
</body>
</html>