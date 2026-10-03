<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LTC Form No. 4 - {{ $application->application_code }}</title>

    @php
        $subjectLandFindings = collect((array) ($application->ltc_form4_subject_land_findings ?? []));
        $recommendationFindings = collect((array) ($application->ltc_form4_recommendation_findings ?? []));

        $subjectLandOptions = \App\Models\LandTransferApplication::form4SubjectLandOptions();
        $recommendationOptions = \App\Models\LandTransferApplication::form4RecommendationOptions();

        $checkbox = fn (bool $checked) => $checked ? '✓' : '';

        $recommendationDecision = $application->ltc_form4_recommendation_decision;
        $certifiedAt = $application->ltc_form4_certified_at;
        $form4Draft = ! $certifiedAt || blank($application->ltc_form4_certifying_officer_name)
            || blank($application->ltc_form4_recommendation_decision)
            || ($subjectLandFindings->isEmpty() && $recommendationFindings->isEmpty() && blank($application->ltc_form4_other_findings));

        $applicantName = $application->applicant_name
            ?: collect([$application->transferor_name, $application->transferee_name])->filter()->implode(' / ');

        $parcelRows = $application->applicationParcels ?? collect();
    @endphp

    <style>
        @page {
            size: A4;
            margin: 8px 14px;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            color: #111827;
            font-size: 8.4px;
            line-height: 1.04;
        }

        .form-no {
            text-align: right;
            font-weight: bold;
            margin-bottom: 1px;
        }

        .header {
            text-align: center;
            line-height: 1.04;
            margin-bottom: 4px;
        }

        .agency {
            font-weight: bold;
            text-transform: uppercase;
        }

        h1 {
            text-align: center;
            font-size: 10.8px;
            margin: 5px 0 2px;
            text-transform: uppercase;
        }

        .subtitle {
            text-align: center;
            font-size: 8.4px;
            margin-bottom: 1px;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .meta td {
            padding: 1px 3px;
            vertical-align: top;
        }

        .meta .label {
            width: 120px;
            font-weight: bold;
        }

        .section-title {
            font-weight: bold;
            margin: 5px 0 3px;
            text-transform: uppercase;
        }

        .check-list {
            display: grid;
            gap: 1px 8px;
        }

        .check-list.two-column {
            grid-template-columns: 1fr 1fr;
        }

        .check-row {
            margin-bottom: 1px;
            padding-left: 2px;
            page-break-inside: avoid;
        }

        .check {
            font-family: DejaVu Sans, sans-serif;
            display: inline-block;
            width: 10px;
            height: 10px;
            line-height: 9px;
            margin-right: 4px;
            border: 1px solid #111827;
            color: #111827;
            text-align: center;
            font-size: 7.2px;
            font-weight: bold;
            vertical-align: -1px;
        }

        .paragraph {
            text-align: justify;
            margin-bottom: 1px;
        }

        .parcel-table {
            width: 100%;
            border-collapse: collapse;
            margin: 3px 0 4px;
            font-size: 8.4px;
        }

        .parcel-table th,
        .parcel-table td {
            border: 1px solid #cbd5e1;
            padding: 1.5px 2.5px;
            text-align: left;
        }

        .parcel-table th {
            background: #f8fafc;
            font-weight: bold;
        }

        .signature {
            width: 260px;
            margin-left: auto;
            margin-top: 6px;
            text-align: center;
            font-size: 8.4px;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 4px;
            font-weight: bold;
        }

        .copy-distribution {
            margin-top: 4px;
            font-size: 8.4px;
            line-height: 1.04;
        }

        .system-note {
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #d1d5db;
            font-size: 8.4px;
            color: #374151;
            text-align: justify;
        }

        .meta,
        .parcel-table,
        .signature,
        .copy-distribution,
        .system-note,
        .compact-block {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="form-no">LTC Form No. 4</div>

    <div class="header">
        <div>Republic of the Philippines</div>
        <div class="agency">Department of Agrarian Reform</div>
        <div>Region VII</div>
        <div>Province of Negros Oriental</div>
    </div>

    <h1>Certification / Attestation and Recommendation</h1>
    @if ($form4Draft)
        <p style="text-align:center; font-weight:bold;">DRAFT — Incomplete certification details</p>
    @endif
    <div class="subtitle">Land Transfer Clearance Application Review</div>

    <table class="meta">
        <tr>
            <td class="label">LTC Application No.</td>
            <td>{{ $application->application_code }}</td>
        </tr>
        <tr>
            <td class="label">Applicant</td>
            <td>{{ $applicantName ?: '__________' }}</td>
        </tr>
        <tr>
            <td class="label">Transferor</td>
            <td>{{ $application->transferor_name ?: '__________' }}</td>
        </tr>
        <tr>
            <td class="label">Transferee</td>
            <td>{{ $application->transferee_name ?: '__________' }}</td>
        </tr>
        <tr>
            <td class="label">Location</td>
            <td>{{ trim(($application->barangay ? $application->barangay . ', ' : '') . ($application->municipality ?? '')) ?: '__________' }}</td>
        </tr>
    </table>

    @if ($parcelRows->isNotEmpty())
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
                @foreach ($parcelRows as $applicationParcel)
                    <tr>
                        <td>{{ $applicationParcel->parcel_code ?? $applicationParcel->parcel?->parcel_code ?? '—' }}</td>
                        <td>{{ $applicationParcel->lot_number ?? $applicationParcel->parcel?->lot_number ?? '—' }}</td>
                        <td>{{ $applicationParcel->survey_plan_number ?? $applicationParcel->parcel?->survey_plan_number ?? '—' }}</td>
                        <td>{{ $applicationParcel->title_type ?? $applicationParcel->parcel?->title_type ?? '—' }}</td>
                        <td>{{ $applicationParcel->title_no ?? $applicationParcel->parcel?->title_no ?? '—' }}</td>
                        <td>{{ number_format((float) ($applicationParcel->area_hectares ?? 0), 4) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">I. Facts / Information of the Subject Land</div>

    <div class="check-list two-column compact-block">
        @foreach ($subjectLandOptions as $value => $label)
            <div class="check-row">
                <span class="check">{{ $checkbox($subjectLandFindings->contains($value)) }}</span>
                {{ $label }}
            </div>
        @endforeach
    </div>

    <div class="section-title">II. Recommendation</div>

    <div class="check-list compact-block">
        @foreach ($recommendationOptions as $value => $label)
            <div class="check-row">
                <span class="check">{{ $checkbox($recommendationFindings->contains($value)) }}</span>
                {{ $label }}
            </div>
        @endforeach
    </div>

    @if ($application->ltc_form4_other_findings)
        <div class="section-title">Other Findings</div>
        <p class="paragraph">{{ $application->ltc_form4_other_findings }}</p>
    @endif

    <div class="section-title">Recommendation Decision</div>
    <div class="check-row">
        <span class="check">{{ $checkbox($recommendationDecision === 'approval') }}</span>
        Recommended for approval
    </div>
    <div class="check-row">
        <span class="check">{{ $checkbox($recommendationDecision === 'denial') }}</span>
        Recommended for denial
    </div>

    <p style="margin-top:6px;">
        Done this {{ optional($certifiedAt)->format('d') ?? '____' }} day of
        {{ optional($certifiedAt)->format('F') ?? '__________' }},
        {{ optional($certifiedAt)->format('Y') ?? '20____' }}.
    </p>

    <div class="signature">
        <div class="signature-line">
            {{ $application->ltc_form4_certifying_officer_name ?: 'Signature Over Printed Name' }}
        </div>
        <div>Chief Legal Division / Authorized Legal Officer</div>
    </div>

    <div class="copy-distribution">
        <strong>Copy Distribution:</strong><br>
        Original-LTC Folder<br>
        Duplicate-Review Records
    </div>

    <div class="system-note">
        System note: This Form No. 4 output is generated from encoded administrative review details.
        It is a recommendation/attestation record only. It does not automatically approve/release the clearance,
        deny the application, transfer land ownership, change parcel ownership linkage, or mutate Registry of Deeds records.
    </div>
</body>
</html>
