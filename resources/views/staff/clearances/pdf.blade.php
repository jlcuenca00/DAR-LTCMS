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
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .header h1 {
            margin: 8px 0;
            font-size: 18px;
        }
        .meta-table,
        .parcel-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .meta-table td {
            padding: 6px 8px;
            vertical-align: top;
        }
        .parcel-table th,
        .parcel-table td {
            border: 1px solid #374151;
            padding: 8px;
            text-align: left;
        }
        .section-title {
            margin-top: 18px;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .decision {
            margin-top: 16px;
            font-weight: bold;
        }
        .footer {
            margin-top: 36px;
        }
        .signature {
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>Republic of the Philippines</div>
        <div>Department of Agrarian Reform</div>
        <div>Provincial Office — Negros Oriental</div>
        <h1>APPLICATION CLEARANCE</h1>
        <div><strong>Clearance No.:</strong> {{ $clearance->clearance_number }}</div>
    </div>

    <div class="section-title">Application Information</div>
    <table class="meta-table">
        <tr>
            <td width="28%"><strong>Application Code</strong></td>
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
            <td><strong>Total Area</strong></td>
            <td>{{ number_format((float) $clearance->total_area_hectares, 4) }} hectares</td>
        </tr>
        <tr>
            <td><strong>Review Officer</strong></td>
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

    <div class="section-title">Parcel Details</div>
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
                    <td>{{ isset($parcel['area_hectares']) ? number_format((float) $parcel['area_hectares'], 4) : '0.0000' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No parcel snapshot recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="decision">
        Decision: {{ strtoupper(str_replace('_', ' ', $clearance->decision_status)) }}
    </div>

    <div class="footer">
        <p>
            This clearance is system-generated from the finalized decision recorded in DAR-iLAND
            and is retained for audit, registry, and administrative reference purposes.
        </p>

        <div class="signature">
            <div><strong>{{ $clearance->review_officer_name }}</strong></div>
            <div>Reviewing Officer</div>
        </div>
    </div>
</body>
</html>