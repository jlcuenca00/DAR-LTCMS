<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $clearance->clearance_number }}</title>

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
            margin: 36px 46px;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            color: #111827;
            font-size: 12.5px;
            line-height: 1.45;
        }

        .form-no {
            text-align: right;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .header {
            text-align: center;
            line-height: 1.25;
            margin-bottom: 24px;
        }

        .header .republic {
            font-size: 13px;
        }

        .header .agency {
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }

        .header .region,
        .header .province {
            font-size: 13px;
        }

        .title {
            text-align: center;
            margin: 22px 0 24px;
        }

        .title h1 {
            margin: 0;
            font-size: 17px;
            letter-spacing: 0.42em;
            font-weight: bold;
        }

        .title p {
            margin: 2px 0 0;
            font-size: 13px;
        }

        .meta {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }

        .meta td {
            vertical-align: top;
            padding: 1px 0;
            font-size: 12px;
        }

        .meta .label {
            width: 120px;
            font-weight: bold;
        }

        .body-text {
            text-align: justify;
            margin: 12px 0;
            text-indent: 38px;
        }

        .checkbox-group {
            margin: 22px 0 20px 55px;
        }

        .checkbox-row {
            margin: 10px 0;
            font-weight: bold;
            font-size: 13px;
        }

        .box {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #111827;
            margin-right: 8px;
            vertical-align: -1px;
            text-align: center;
            line-height: 10px;
            font-size: 10px;
            font-family: DejaVu Sans, sans-serif;
        }

        .parcel-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 18px;
            font-size: 11px;
        }

        .parcel-table th,
        .parcel-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
        }

        .parcel-table th {
            background: #f8fafc;
            font-weight: bold;
        }

        .signature {
            width: 260px;
            margin-left: auto;
            margin-top: 42px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 4px;
            font-weight: bold;
        }

        .signature-role {
            font-size: 11.5px;
        }

        .copy-distribution {
            margin-top: 52px;
            font-size: 10.5px;
            line-height: 1.25;
        }

        .copy-distribution strong {
            display: block;
        }

        .system-note {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 10.5px;
            color: #374151;
            text-align: justify;
        }

        .muted {
            color: #4b5563;
        }
    </style>
</head>
<body>
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
</body>
</html>
