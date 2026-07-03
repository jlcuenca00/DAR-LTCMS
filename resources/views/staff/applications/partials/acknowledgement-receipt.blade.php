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

    $renderRequirementChecklist = function ($requirements) use ($uploaded) {
        return $requirements->map(function ($requirement) use ($uploaded) {
            $doc = $uploaded->get($requirement->id);

            return [
                'name' => $requirement->name,
                'checked' => ! is_null($doc),
                'annex' => $doc?->annex_reference ?: '____',
                'classification' => method_exists($requirement, 'classificationLabel')
                    ? $requirement->classificationLabel()
                    : ($requirement->is_mandatory ? 'Mandatory' : 'Case-dependent'),
            ];
        });
    };

    $transferorAcknowledgementItems = $renderRequirementChecklist($transferorRequirements);
    $transfereeAcknowledgementItems = $renderRequirementChecklist($transfereeRequirements);
@endphp

<section class="review-panel" id="ltc-form-no-3-acknowledgement">
    <div class="review-panel-header">
        <div>
            <h2 class="review-panel-title">LTC Form No. 3 — Acknowledgement Receipt</h2>
            <p class="review-panel-subtitle">
                System preview of the acknowledgement receipt checklist based on uploaded required documents.
                This supports intake tracking and checklist review.
            </p>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <a href="{{ route('staff.applications.acknowledgement.pdf', $application) }}"
               class="staff-button staff-button-primary"
               target="_blank">
                <i class="fa-solid fa-file-pdf"></i>
                Open Form No. 3 PDF
            </a>

            <button type="button" class="staff-button staff-button-light" onclick="window.print()">
                <i class="fa-solid fa-print"></i>
                Print Page
            </button>
        </div>
    </div>

    <div class="review-panel-body">
        <div style="border:1px solid #d1d5db; border-radius:12px; background:#ffffff; padding:22px; color:#111827;">
            <div style="text-align:right; font-family:'Times New Roman', serif; font-weight:700; margin-bottom:12px;">
                LTC Form No. 3
            </div>

            <div style="text-align:center; font-family:'Times New Roman', serif; line-height:1.25; margin-bottom:18px;">
                <div>Republic of the Philippines</div>
                <div style="font-weight:700; text-transform:uppercase;">Department of Agrarian Reform</div>
                <div>Region VII</div>
                <div>Province of Negros Oriental</div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; font-family:'Times New Roman', serif; margin-bottom:14px;">
                <strong>LTC Application No.</strong>
                <span>{{ $application->application_code }}</span>
            </div>

            <h3 style="text-align:center; font-family:'Times New Roman', serif; font-size:18px; font-weight:700; margin:18px 0;">
                ACKNOWLEDGEMENT RECEIPT
            </h3>

            <p style="font-family:'Times New Roman', serif; font-size:14px; text-align:justify; line-height:1.55; margin:0 0 16px;">
                Pursuant to Administrative Order (A.O.) No. _____, Series of 2020, the undersigned acknowledges
                the receipt of the duly notarized Application for Issuance of Certification on Land Transfer Clearance
                (LTC Form No. 1) and the attached mandatory documentary requirements filed by
                <strong>{{ $acknowledgementApplicantNames }}</strong>, to wit:
            </p>

            <div style="display:grid; gap:18px; font-family:'Times New Roman', serif; font-size:14px;">
                <div>
                    <strong>1. FOR THE TRANSFEROR:</strong>
                    <div style="margin-top:8px; display:grid; gap:5px;">
                        @foreach ($transferorAcknowledgementItems as $item)
                            <div style="display:grid; grid-template-columns: 18px minmax(0, 1fr) 110px; gap:8px; align-items:start;">
                                <span style="font-family:DejaVu Sans, sans-serif;">{{ $item['checked'] ? '☑' : '☐' }}</span>
                                <span>{{ $item['name'] }}</span>
                                <span>: Annex {{ $item['annex'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <strong>2. FOR THE TRANSFEREE:</strong>
                    <div style="margin-top:8px; display:grid; gap:5px;">
                        @foreach ($transfereeAcknowledgementItems as $item)
                            <div style="display:grid; grid-template-columns: 18px minmax(0, 1fr) 110px; gap:8px; align-items:start;">
                                <span style="font-family:DejaVu Sans, sans-serif;">{{ $item['checked'] ? '☑' : '☐' }}</span>
                                <span>{{ $item['name'] }}</span>
                                <span>: Annex {{ $item['annex'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div style="margin-top:22px; font-family:'Times New Roman', serif; font-size:14px;">
                <p style="margin-bottom:12px;">
                    Further, the undersigned initially examined the said application and the aforementioned documents
                    and found the following:
                </p>

                <div style="display:grid; gap:12px;">
                    <div>
                        <span style="font-family:DejaVu Sans, sans-serif;">{{ $acknowledgementComplete ? '☑' : '☐' }}</span>
                        Complete and in order; or
                    </div>

                    <div>
                        <span style="font-family:DejaVu Sans, sans-serif;">{{ $acknowledgementComplete ? '☐' : '☑' }}</span>
                        Incomplete and with lacking documents:
                        @if ($acknowledgementMissingRequirements->isNotEmpty())
                            <ul style="margin:8px 0 0 26px;">
                                @foreach ($acknowledgementMissingRequirements as $missingRequirement)
                                    <li>{{ $missingRequirement->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span>None</span>
                        @endif
                    </div>
                </div>
            </div>

            <p style="font-family:'Times New Roman', serif; font-size:14px; margin-top:22px;">
                Done this {{ optional($acknowledgementIssuedAt)->format('d') ?? '____' }} day of
                {{ optional($acknowledgementIssuedAt)->format('F') ?? '__________' }},
                {{ optional($acknowledgementIssuedAt)->format('Y') ?? '20____' }}.
            </p>

            <div style="width:320px; margin-left:auto; margin-top:46px; text-align:center; font-family:'Times New Roman', serif; font-size:13px;">
                <div style="border-top:1px solid #111827; padding-top:4px;">Signature Over Printed Name</div>
                <div>Agrarian Reform Legal Assistance Division / Designated Personnel</div>
            </div>

            <div style="margin-top:38px; font-family:'Times New Roman', serif; font-size:11px; line-height:1.25;">
                <strong>Copy Distribution:</strong><br>
                Original-Applicant<br>
                Duplicate-DARPOS
            </div>

            <div style="margin-top:14px; padding-top:8px; border-top:1px solid #e5e7eb; font-size:12px; color:#4b5563;">
                System note: This acknowledgement preview is generated from encoded application and document records.
                It supports administrative tracking and checklist review only. It does not approve the clearance, transfer
                ownership, change parcel ownership linkage, or mutate Registry of Deeds records.
            </div>
        </div>
    </div>
</section>
