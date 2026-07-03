<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clearance->clearance_number }} | LTC Form No. 5 Print View</title>

    @php
        $rawDecisionStatus = strtolower((string) $clearance->decision_status);
        $isApprovedForForm = in_array($rawDecisionStatus, ['released', 'approved'], true);
        $isDeniedForForm = in_array($rawDecisionStatus, ['denied', 'not_approved'], true);

        $formDecisionLabel = $isApprovedForForm ? 'APPROVED' : ($isDeniedForForm ? 'DENIED' : strtoupper(str_replace('_', ' ', $rawDecisionStatus)));

        $generatedAt = $clearance->generated_at;
        $reviewedAt = $clearance->reviewed_at;
        $issueDate = $generatedAt ?? $reviewedAt ?? now();

        $parcels = collect($clearance->parcel_snapshot ?? []);
        $firstParcel = $parcels->first() ?? [];

        $titleType = $firstParcel['title_type'] ?? 'OCT/TCT';
        $titleNo = $firstParcel['title_number'] ?? $firstParcel['title_no'] ?? '__________';
        $lotNo = $firstParcel['lot_number'] ?? '__________';
        $surveyNo = $firstParcel['survey_plan_number'] ?? '__________';
        $taxDeclNo = $firstParcel['tax_decl_no'] ?? '__________';
        $location = trim(($clearance->barangay ? $clearance->barangay . ', ' : '') . ($clearance->municipality ?? ''));
        $location = $location !== '' ? $location : '__________';
        $areaHectares = number_format((float) $clearance->total_area_hectares, 4);

        $applicationCode = $application->application_code ?? $clearance->application_code ?? '__________';
        $applicantName = $application->applicant_name ?: ($clearance->transferor_name ?: '__________');
        $applicantRoleLabel = match ($application->applicant_type ?? null) {
            'transferor' => 'Transferor',
            'transferee' => 'Transferee',
            'authorized_representative' => 'Authorized Representative',
            default => 'Applicant / Requesting Party',
        };
        $authorizedRepresentativeName = $application->authorized_representative_name ?: null;
        $province = 'Negros Oriental';
        $region = 'VII';
    @endphp

    <style>
        @page {
            size: A4;
            margin: 9px 16px;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            color: #111827;
            font-size: 10.2px;
            line-height: 1.16;
        }

        .form-no {
            text-align: right;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .header {
            text-align: center;
            line-height: 1.08;
            margin-bottom: 4px;
        }

        .header .republic {
            font-size: 10.8px;
        }

        .header .agency {
            font-weight: bold;
            font-size: 10.2px;
            text-transform: uppercase;
        }

        .header .region,
        .header .province {
            font-size: 10.8px;
        }

        .title {
            text-align: center;
            margin: 7px 0 8px;
        }

        .title h1 {
            margin: 0;
            font-size: 14px;
            letter-spacing: 0.30em;
            font-weight: bold;
        }

        .title p {
            margin: 2px 0 0;
            font-size: 10.8px;
        }

        .meta {
            width: 100%;
            margin-bottom: 7px;
            border-collapse: collapse;
        }

        .meta td {
            vertical-align: top;
            padding: 1px 0;
            font-size: 10.2px;
        }

        .meta .label {
            width: 105px;
            font-weight: bold;
        }

        .body-text {
            text-align: justify;
            margin: 2px 0;
            text-indent: 24px;
        }

        .checkbox-group {
            margin: 5px 0 5px 28px;
        }

        .checkbox-row {
            margin: 2px 0;
            font-weight: bold;
            font-size: 10.8px;
        }

        .box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #111827;
            margin-right: 8px;
            vertical-align: -2px;
            text-align: center;
            line-height: 11px;
            font-size: 10px;
            color: #111827;
            font-weight: bold;
            font-family: DejaVu Sans, sans-serif;
        }

        .parcel-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 5px;
            font-size: 8.3px;
        }

        .parcel-table th,
        .parcel-table td {
            border: 1px solid #cbd5e1;
            padding: 1.5px 3px;
            text-align: left;
        }

        .parcel-table th {
            background: #f8fafc;
            font-weight: bold;
        }

        .signature {
            width: 260px;
            margin-left: auto;
            margin-top: 12px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 4px;
            font-weight: bold;
        }

        .signature-role {
            font-size: 9.8px;
        }

        .copy-distribution {
            margin-top: 5px;
            font-size: 8.8px;
            line-height: 1.08;
        }

        .copy-distribution strong {
            display: block;
        }

        .system-note {
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px solid #d1d5db;
            font-size: 8.8px;
            color: #374151;
            text-align: justify;
        }

        .muted {
            color: #4b5563;
        }

        .meta,
        .parcel-table,
        .signature,
        .copy-distribution,
        .system-note {
            page-break-inside: avoid;
        }

        p {
            orphans: 2;
            widows: 2;
        }


        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(241, 245, 249, 0.96);
            border-bottom: 1px solid #cbd5e1;
            backdrop-filter: blur(8px);
            font-family: Arial, Helvetica, sans-serif;
        }

        .print-toolbar-inner {
            max-width: 920px;
            margin: 0 auto;
            min-height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 10px;
        }

        .print-toolbar-title {
            color: #334155;
            font-size: 10.2px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .print-toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background: #ffffff;
            color: #0f172a;
            padding: 0 13px;
            font-size: 10.2px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
        }

        .print-btn.primary {
            border-color: #111827;
            background: #111827;
            color: #ffffff;
        }

        .print-shell {
            background: #ffffff;
        }

        @media screen {
            html,
            body {
                margin: 0;
                background: #e2e8f0;
            }

            .print-shell {
                width: 794px;
                min-height: auto;
                margin: 14px auto 24px;
                padding: 16px 24px;
                box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
            }
        }

        @media print {
            .print-toolbar {
                display: none !important;
            }

            .print-shell {
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }

    </style>
</head>
<body>
    <div class="print-toolbar">
        <div class="print-toolbar-inner">
            <div class="print-toolbar-title">LTC Form No. 5 Print View</div>
            <div class="print-toolbar-actions">
                <a href="{{ route('staff.applications.show', $application) }}" class="print-btn">Back to Application</a>
                <a href="{{ route('staff.applications.clearance.pdf', $application) }}" target="_blank" class="print-btn">Open PDF</a>
                <button type="button" onclick="window.print()" class="print-btn primary">Print</button>
            </div>
        </div>
    </div>

    <main class="print-shell">
    <div class="form-no">LTC Form No. 5</div>

    <div class="header">
        <div class="republic">Republic of the Philippines</div>
        <div class="agency">Department of Agrarian Reform</div>
        <div class="region">Region {{ $region }}</div>
        <div class="province">Province of {{ $province }}</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">LTC Application No.</td>
            <td>{{ $applicationCode }}</td>
        </tr>
        <tr>
            <td class="label">Applicant Role</td>
            <td>{{ $applicantRoleLabel }}</td>
        </tr>
        @if ($authorizedRepresentativeName)
            <tr>
                <td class="label">Authorized Representative</td>
                <td>{{ $authorizedRepresentativeName }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">System Result No.</td>
            <td>{{ $clearance->clearance_number }}</td>
        </tr>
        @if ($application->or_number || $application->or_date || $application->amount_paid)
            <tr>
                <td class="label">Payment Reference</td>
                <td>
                    OR Number: {{ $application->or_number ?: '—' }};
                    OR Date: {{ optional($application->or_date)->format('M d, Y') ?? '—' }};
                    Amount Paid (PHP): {{ $application->amount_paid ? '₱' . number_format((float) $application->amount_paid, 2) : '—' }}
                </td>
            </tr>
        @endif
    </table>

    <div class="title">
        <h1>CERTIFICATION</h1>
        <p>(Land Transfer Clearance)</p>
    </div>

    <p class="body-text">
        This is to certify that the application/request for Issuance of Land Transfer Clearance (LTC)
        filed to this Office in the name of <strong>{{ $applicantName }}</strong>, as {{ strtolower($applicantRoleLabel) }}, covered by
        {{ $titleType ?: 'OCT/TCT' }} No. <strong>{{ $titleNo }}</strong>, with Lot No.
        <strong>{{ $lotNo }}</strong>, Approved Survey No. <strong>{{ $surveyNo }}</strong>,
        with an area of <strong>{{ $areaHectares }}</strong> hectares, more or less, and located at
        <strong>{{ $location }}</strong> or declared under Tax Declaration No.
        <strong>{{ $taxDeclNo }}</strong> is hereby:
    </p>

    <div class="checkbox-group">
        <div class="checkbox-row">
            <span class="box">{{ $isApprovedForForm ? '✓' : '' }}</span> APPROVED
        </div>
        <div class="checkbox-row">
            <span class="box">{{ $isDeniedForForm ? '✓' : '' }}</span> DENIED
        </div>
    </div>

    <p class="body-text">
        based on the Attestation of the CARPO-LTS/FOD and from the report and recommendation of the
        Chief Legal Division/Authorized Legal Officer pursuant to Administrative Order (A.O.) No.
        _____, Series of 2020.
    </p>

    <p class="body-text">
        Any actual change in the use of the land and/or development over the subject land require a prior
        Order of Conversion or Exemption/Exclusion from the Office of the DAR Regional Director.
    </p>

    <p class="body-text">
        This Office reserves the right to revoke this Certification of LTC in case of findings of
        misrepresentation or submission of falsified documents by either or both parties to the Deed of
        Transfer and any third person who may be affected by the transfer.
    </p>

    <p class="body-text">
        This Certification is hereby issued only for the purpose stated in the application/request for
        issuance of LTC.
    </p>

    <p>
        Done and issued this day <strong>{{ $issueDate?->format('d') ?? '____' }}</strong> of
        <strong>{{ $issueDate?->format('F') ?? '__________' }}</strong>
        <strong>{{ $issueDate?->format('Y') ?? '20____' }}</strong> at the DAR Provincial Office.
    </p>

    @if ($parcels->count() > 1)
        <p class="muted">
            The application contains multiple parcel snapshot entries recorded by the system for audit reference:
        </p>

        <table class="parcel-table">
            <thead>
                <tr>
                    <th>Parcel / Code</th>
                    <th>Lot No.</th>
                    <th>Survey Plan No.</th>
                    <th>Title Type</th>
                    <th>Title No.</th>
                    <th>Area (ha)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($parcels as $parcel)
                    <tr>
                        <td>{{ $parcel['parcel_number'] ?? $parcel['parcel_code'] ?? '—' }}</td>
                        <td>{{ $parcel['lot_number'] ?? '—' }}</td>
                        <td>{{ $parcel['survey_plan_number'] ?? '—' }}</td>
                        <td>{{ $parcel['title_type'] ?? '—' }}</td>
                        <td>{{ $parcel['title_number'] ?? $parcel['title_no'] ?? '—' }}</td>
                        <td>{{ isset($parcel['area_hectares']) ? number_format((float) $parcel['area_hectares'], 4) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="signature">
        <div class="signature-line">Signature Over Printed Name</div>
        <div class="signature-role">Provincial Agrarian Reform Program Officer II</div>
    </div>

    <div class="copy-distribution">
        <strong>Copy Distribution:</strong>
        Original-Applicant<br>
        Duplicate-LTC Folder
    </div>

    <div class="system-note">
        System note: This PDF is a system-generated administrative clearance result patterned after LTC Form No. 5
        for monitoring, documentation, and audit purposes. It records the clearance result only and does not
        automatically transfer land ownership, change parcel ownership linkage, or mutate Registry of Deeds records.
        Internal system status: {{ $rawDecisionStatus }} / Form decision: {{ $formDecisionLabel }}.
    </div>
    </main>
</body>
</html>
