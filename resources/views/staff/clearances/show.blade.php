<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $clearance->clearance_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            background: #e5e7eb;
            margin: 0;
            padding: 24px;
            color: #111827;
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
            font-size: 14px;
        }

        .btn-dark { background: #111827; }
        .btn-blue { background: #1d4ed8; }

        .page {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #9ca3af;
            padding: 44px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        }

        .official-header {
            text-align: center;
            border-bottom: 3px double #111827;
            padding-bottom: 18px;
            margin-bottom: 24px;
        }

        .official-header .small {
            font-size: 13px;
            margin: 3px 0;
        }

        .official-header .agency {
            font-size: 16px;
            font-weight: 700;
            margin: 4px 0;
            text-transform: uppercase;
        }

        .official-header .office {
            font-size: 14px;
            font-weight: 600;
            margin: 4px 0;
        }

        .document-title {
            text-align: center;
            margin: 26px 0 18px 0;
        }

        .document-title h1 {
            font-size: 22px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .document-title p {
            margin: 8px 0 0 0;
            font-size: 14px;
            color: #374151;
        }

        .notice {
            border: 1px solid #92400e;
            background: #fffbeb;
            color: #78350f;
            padding: 14px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.6;
            margin: 18px 0 24px 0;
        }

        .section {
            margin-top: 24px;
        }

        .section-title {
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 10px;
            border-bottom: 1px solid #9ca3af;
            padding-bottom: 6px;
        }

        .grid {
            display: grid;
            grid-template-columns: 230px 1fr;
            gap: 8px 16px;
            font-size: 14px;
        }

        .grid div {
            line-height: 1.5;
        }

        .decision-box {
            margin-top: 18px;
            border: 2px solid #111827;
            padding: 14px;
            text-align: center;
        }

        .decision-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #374151;
        }

        .decision-value {
            font-size: 22px;
            font-weight: 800;
            margin-top: 6px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 13px;
        }

        th, td {
            border: 1px solid #9ca3af;
            padding: 9px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
        }

        .certification {
            font-size: 14px;
            line-height: 1.7;
            text-align: justify;
        }

        .footer {
            margin-top: 38px;
            font-size: 13px;
            color: #374151;
            line-height: 1.6;
        }

        .signature-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 60px;
            font-size: 14px;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 8px;
            text-align: center;
        }

        .muted {
            color: #6b7280;
            font-size: 12px;
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
                box-shadow: none;
                padding: 24px;
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

                <div><strong>Total Area Covered</strong></div>
                <div>{{ number_format((float) $clearance->total_area_hectares, 4) }} hectares</div>

                <div><strong>Reviewing Officer</strong></div>
                <div>{{ $clearance->review_officer_name }}</div>

                <div><strong>Decision Timestamp</strong></div>
                <div>{{ optional($clearance->reviewed_at)->format('M d, Y h:i A') ?? '—' }}</div>

                <div><strong>Generated Timestamp</strong></div>
                <div>{{ optional($clearance->generated_at)->format('M d, Y h:i A') ?? '—' }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Final Decision</div>

            <div class="decision-box">
                <div class="decision-label">Recorded Application Decision</div>
                <div class="decision-value">
                    {{ strtoupper(str_replace('_', ' ', $clearance->decision_status)) }}
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Parcel Snapshot</div>

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
        </div>

        <div class="section">
            <div class="section-title">Certification Statement</div>

            <p class="certification">
                This document certifies that the above land transfer clearance application has been processed
                through the Department of Agrarian Reform Land Transfer Clearance and Monitoring System and that
                the decision shown in this record reflects the finalized decision stored in the system for
                administrative monitoring, documentation, and audit reference.
            </p>
        </div>

        <div class="signature-row">
            <div></div>

            <div class="signature-line">
                <strong>{{ $clearance->review_officer_name }}</strong><br>
                Reviewing Officer
            </div>
        </div>

        <div class="footer">
            <p>
                Generated by the Department of Agrarian Reform Land Transfer Clearance and Monitoring System.
                This document is valid as a system-generated administrative record only and remains subject to
                applicable DAR procedures, official verification, and legal requirements.
            </p>
        </div>
    </div>
</body>
</html>