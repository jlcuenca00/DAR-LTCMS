<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $clearance->clearance_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 24px;
            color: #111827;
        }
        .page {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border: 1px solid #d1d5db;
            padding: 40px;
        }
        .toolbar {
            max-width: 900px;
            margin: 0 auto 16px auto;
            display: flex;
            gap: 12px;
        }
        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
        }
        .btn-dark { background: #111827; }
        .btn-blue { background: #1d4ed8; }
        .header {
            text-align: center;
            margin-bottom: 28px;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 22px;
        }
        .header p {
            margin: 4px 0;
            font-size: 14px;
        }
        .section {
            margin-top: 24px;
        }
        .section-title {
            font-weight: 700;
            margin-bottom: 10px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 6px;
        }
        .grid {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 8px 16px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 10px;
            text-align: left;
        }
        .decision {
            margin-top: 18px;
            font-size: 15px;
            font-weight: 700;
        }
        .footer {
            margin-top: 42px;
            font-size: 14px;
        }
        .signature {
            margin-top: 60px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .toolbar {
                display: none;
            }
            .page {
                border: 0;
                margin: 0;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('staff.applications.show', $application) }}" class="btn btn-dark">Back to Application</a>
        <a href="{{ route('staff.applications.clearance.pdf', $application) }}" class="btn btn-blue" target="_blank">Open PDF</a>
        <a href="#" onclick="window.print(); return false;" class="btn btn-dark">Print</a>
    </div>

    <div class="page">
        <div class="header">
            <p>Republic of the Philippines</p>
            <p>Department of Agrarian Reform</p>
            <p>Provincial Office — Negros Oriental</p>
            <h1>APPLICATION CLEARANCE</h1>
            <p><strong>Clearance No.:</strong> {{ $clearance->clearance_number }}</p>
        </div>

        <div class="section">
            <div class="section-title">Application Information</div>
            <div class="grid">
                <div><strong>Application Code</strong></div>
                <div>{{ $clearance->application_code }}</div>

                <div><strong>Transferor</strong></div>
                <div>{{ $clearance->transferor_name }}</div>

                <div><strong>Transferee</strong></div>
                <div>{{ $clearance->transferee_name }}</div>

                <div><strong>Location</strong></div>
                <div>{{ $clearance->barangay ?? '—' }}, {{ $clearance->municipality ?? '—' }}</div>

                <div><strong>Total Area</strong></div>
                <div>{{ number_format((float) $clearance->total_area_hectares, 4) }} hectares</div>

                <div><strong>Review Officer</strong></div>
                <div>{{ $clearance->review_officer_name }}</div>

                <div><strong>Decision Timestamp</strong></div>
                <div>{{ optional($clearance->reviewed_at)->format('M d, Y h:i A') ?? '—' }}</div>

                <div><strong>Generated Timestamp</strong></div>
                <div>{{ optional($clearance->generated_at)->format('M d, Y h:i A') ?? '—' }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Parcel Details</div>
            <table>
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
        </div>

        <div class="footer">
            <p>
                This clearance is system-generated from the finalized decision recorded in DAR-iLAND
                and is retained for audit, registry, and administrative reference purposes.
            </p>

            <div class="signature">
                <p><strong>{{ $clearance->review_officer_name }}</strong></p>
                <p>Reviewing Officer</p>
            </div>
        </div>
    </div>
</body>
</html>