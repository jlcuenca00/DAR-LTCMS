<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LTC Form No. 3 - {{ $application->application_code }}</title>

    @php
        $acknowledgementBlockingRequirements = $blockingRequirements ?? $transferorRequirements->concat($transfereeRequirements)
            ->filter(fn ($requirement) => method_exists($requirement, 'blocksAcceptance') ? $requirement->blocksAcceptance() : (bool) $requirement->is_mandatory);

        $acknowledgementMissingRequirements = $acknowledgementBlockingRequirements
            ->filter(fn ($requirement) => ! $uploaded->has($requirement->id))
            ->values();

        $acknowledgementComplete = $acknowledgementMissingRequirements->isEmpty();
        $acknowledgementIssuedAt = $application->date_of_application ?? $application->created_at ?? now();

        $acknowledgementApplicantNames = collect([
            $application->transferor_name,
            $application->transferee_name,
            $application->applicant_name,
        ])->filter()->unique()->implode(' and ');

        $acknowledgementApplicantNames = $acknowledgementApplicantNames !== ''
            ? $acknowledgementApplicantNames
            : 'the applicant/authorized representative';

        $checkbox = fn (bool $checked) => $checked ? '[X]' : '[ ]';
    @endphp

    <style>
        @page {
            margin: 42px 48px;
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
            margin-bottom: 18px;
        }

        .header {
            text-align: center;
            line-height: 1.25;
            margin-bottom: 22px;
        }

        .agency {
            font-weight: bold;
            text-transform: uppercase;
        }

        .application-no {
            text-align: right;
            margin-bottom: 18px;
        }

        h1 {
            text-align: center;
            font-size: 17px;
            margin: 16px 0 22px;
            letter-spacing: 0.08em;
        }

        p {
            text-align: justify;
            margin: 0 0 12px;
        }

        .section-title {
            font-weight: bold;
            margin: 14px 0 8px;
        }

        .checklist {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .checklist td {
            vertical-align: top;
            padding: 3px 2px;
        }

        .check {
            width: 34px;
            font-family: DejaVu Sans, sans-serif;
            white-space: nowrap;
        }

        .annex {
            width: 110px;
            white-space: nowrap;
        }

        .finding {
            margin: 10px 0;
        }

        .missing-list {
            margin: 6px 0 0 32px;
        }

        .signature {
            width: 340px;
            margin-left: auto;
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 4px;
        }

        .copy-distribution {
            margin-top: 44px;
            font-size: 10.5px;
            line-height: 1.25;
        }

        .system-note {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 10.5px;
            color: #374151;
            text-align: justify;
        }
    </style>
</head>
<body>
    <div class="form-no">LTC Form No. 3</div>

    <div class="header">
        <div>Republic of the Philippines</div>
        <div class="agency">Department of Agrarian Reform</div>
        <div>Region VII</div>
        <div>Province of Negros Oriental</div>
    </div>

    <div class="application-no">
        <strong>LTC Application No.</strong> {{ $application->application_code }}
    </div>

    <h1>ACKNOWLEDGEMENT RECEIPT</h1>

    <p>
        Pursuant to Administrative Order (A.O.) No. _____, Series of 2020, the undersigned acknowledges
        the receipt of the duly notarized Application for Issuance of Certification on Land Transfer Clearance
        (LTC Form No. 1) and the attached mandatory documentary requirements filed by
        <strong>{{ $acknowledgementApplicantNames }}</strong>, to wit:
    </p>

    <div class="section-title">1. FOR THE TRANSFEROR:</div>
    <table class="checklist">
        @foreach ($transferorRequirements as $requirement)
            @php
                $doc = $uploaded->get($requirement->id);
            @endphp
            <tr>
                <td class="check">{{ $checkbox(! is_null($doc)) }}</td>
                <td>{{ $requirement->name }}</td>
                <td class="annex">: Annex {{ $doc?->annex_reference ?: '____' }}</td>
            </tr>
        @endforeach
    </table>

    <div class="section-title">2. FOR THE TRANSFEREE:</div>
    <table class="checklist">
        @foreach ($transfereeRequirements as $requirement)
            @php
                $doc = $uploaded->get($requirement->id);
            @endphp
            <tr>
                <td class="check">{{ $checkbox(! is_null($doc)) }}</td>
                <td>{{ $requirement->name }}</td>
                <td class="annex">: Annex {{ $doc?->annex_reference ?: '____' }}</td>
            </tr>
        @endforeach
    </table>

    <p>
        Further, the undersigned initially examined the said application and the aforementioned documents
        and found the following:
    </p>

    <div class="finding">
        {{ $checkbox($acknowledgementComplete) }} Complete and in order; or
    </div>

    <div class="finding">
        {{ $checkbox(! $acknowledgementComplete) }} Incomplete and with lacking documents:
        @if ($acknowledgementMissingRequirements->isNotEmpty())
            <ul class="missing-list">
                @foreach ($acknowledgementMissingRequirements as $missingRequirement)
                    <li>{{ $missingRequirement->name }}</li>
                @endforeach
            </ul>
        @else
            None
        @endif
    </div>

    <p style="margin-top:22px;">
        Done this {{ optional($acknowledgementIssuedAt)->format('d') ?? '____' }} day of
        {{ optional($acknowledgementIssuedAt)->format('F') ?? '__________' }},
        {{ optional($acknowledgementIssuedAt)->format('Y') ?? '20____' }}.
    </p>

    <div class="signature">
        <div class="signature-line">Signature Over Printed Name</div>
        <div>Agrarian Reform Legal Assistance Division / Designated Personnel</div>
    </div>

    <div class="copy-distribution">
        <strong>Copy Distribution:</strong><br>
        Original-Applicant<br>
        Duplicate-DARPOS
    </div>

    <div class="system-note">
        System note: This acknowledgement receipt is generated from encoded application and document records.
        It supports administrative intake tracking and checklist review only. It does not approve/release the clearance,
        deny the application, transfer land ownership, change parcel ownership linkage, or mutate Registry of Deeds records.
    </div>
</body>
</html>
