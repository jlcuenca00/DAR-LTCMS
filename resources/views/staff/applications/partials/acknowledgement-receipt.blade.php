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

<style>
    #ltc-form-no-3-acknowledgement > summary {
        cursor: pointer;
        list-style: none;
    }

    #ltc-form-no-3-acknowledgement > summary::-webkit-details-marker {
        display: none;
    }

    #ltc-form-no-3-acknowledgement:not([open]) > .review-panel-header {
        border-bottom: 0;
    }

    #ltc-form-no-3-acknowledgement .ltc-form-accordion-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    #ltc-form-no-3-acknowledgement .ltc-form-accordion-status {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 900;
        line-height: 1;
        white-space: nowrap;
    }

    #ltc-form-no-3-acknowledgement .ltc-form-accordion-status.is-complete {
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #166534;
    }

    #ltc-form-no-3-acknowledgement .ltc-form-accordion-status.needs-attention {
        border: 1px solid #fed7aa;
        background: #fff7ed;
        color: #9a3412;
    }

    #ltc-form-no-3-acknowledgement .ltc-form-accordion-chevron {
        color: #64748b;
        font-size: 14px;
        transition: transform 160ms ease;
    }

    #ltc-form-no-3-acknowledgement[open] .ltc-form-accordion-chevron {
        transform: rotate(180deg);
    }

    #ltc-form-no-3-acknowledgement .ltc-form-accordion-actions {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 14px;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-workspace {
        display: grid;
        gap: 14px;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-summary-card,
    #ltc-form-no-3-acknowledgement .ltc-form3-card {
        border: 1px solid #d1d5db;
        border-radius: 12px;
        background: #ffffff;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-label {
        margin: 0 0 4px;
        color: #64748b;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-value {
        margin: 0;
        color: #111827;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.4;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-checklist-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-card-title {
        margin: 0 0 10px;
        color: #111827;
        font-size: 14px;
        font-weight: 900;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-list {
        display: grid;
        gap: 8px;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-row {
        display: grid;
        grid-template-columns: 16px minmax(0, 1fr) auto;
        gap: 8px;
        align-items: start;
        color: #374151;
        font-size: 12.5px;
        line-height: 1.35;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-check {
        width: 15px;
        height: 15px;
        border: 1.5px solid #16a34a;
        border-radius: 2px;
        background: #ffffff;
        display: inline-grid;
        place-content: center;
        color: #ffffff;
        font-size: 10px;
        font-weight: 900;
        line-height: 1;
        margin-top: 1px;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-check.is-checked {
        background: #16a34a;
        border-color: #16a34a;
    }

    #ltc-form-no-3-acknowledgement .ltc-form3-annex {
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    @media (max-width: 980px) {
        #ltc-form-no-3-acknowledgement .ltc-form3-summary-grid,
        #ltc-form-no-3-acknowledgement .ltc-form3-checklist-grid {
            grid-template-columns: 1fr;
        }

        #ltc-form-no-3-acknowledgement > .review-panel-header {
            align-items: center;
        }
    }
</style>

<details class="review-panel ltc-form-accordion"
         id="ltc-form-no-3-acknowledgement"
         name="ltc-review-form"
         @if (! $isFinal && ! $acknowledgementComplete) open @endif>
    <summary class="review-panel-header">
        <div>
            <h2 class="review-panel-title">LTC Form No. 3 — Acknowledgement Receipt</h2>
            <p class="review-panel-subtitle">
                Review the encoded acknowledgement receipt checklist before opening the formal PDF output.
            </p>
        </div>

        <div class="ltc-form-accordion-meta">
            @if ($acknowledgementComplete)
                <span class="ltc-form-accordion-status is-complete">Complete</span>
            @else
                <span class="ltc-form-accordion-status needs-attention">
                    {{ $acknowledgementMissingRequirements->count() }} missing
                </span>
            @endif
            <i class="fa-solid fa-chevron-down ltc-form-accordion-chevron" aria-hidden="true"></i>
        </div>
    </summary>

    <div class="review-panel-body">
        <div class="ltc-form-accordion-actions">
            <a href="{{ route('staff.applications.acknowledgement.pdf', $application) }}"
               class="staff-button staff-button-primary"
               target="_blank">
                <i class="fa-solid fa-file-pdf"></i>
                Open Form No. 3 PDF
            </a>
        </div>

        <div class="ltc-form3-workspace">
            <div class="ltc-form3-summary-grid">
                <div class="ltc-form3-summary-card">
                    <p class="ltc-form3-label">LTC Application No.</p>
                    <p class="ltc-form3-value">{{ $application->application_code }}</p>
                </div>
                <div class="ltc-form3-summary-card">
                    <p class="ltc-form3-label">Applicant / Parties</p>
                    <p class="ltc-form3-value">{{ $acknowledgementApplicantNames }}</p>
                </div>
                <div class="ltc-form3-summary-card">
                    <p class="ltc-form3-label">Receipt Date</p>
                    <p class="ltc-form3-value">
                        {{ optional($acknowledgementIssuedAt)->format('F d, Y') ?? 'Not set' }}
                    </p>
                </div>
            </div>

            <div class="ltc-form3-checklist-grid">
                <div class="ltc-form3-card">
                    <h3 class="ltc-form3-card-title">1. For the Transferor</h3>
                    <div class="ltc-form3-list">
                        @foreach ($transferorAcknowledgementItems as $item)
                            <div class="ltc-form3-row">
                                <span class="ltc-form3-check {{ $item['checked'] ? 'is-checked' : '' }}">{{ $item['checked'] ? '✓' : '' }}</span>
                                <span>{{ $item['name'] }}</span>
                                <span class="ltc-form3-annex">Annex {{ $item['annex'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="ltc-form3-card">
                    <h3 class="ltc-form3-card-title">2. For the Transferee</h3>
                    <div class="ltc-form3-list">
                        @foreach ($transfereeAcknowledgementItems as $item)
                            <div class="ltc-form3-row">
                                <span class="ltc-form3-check {{ $item['checked'] ? 'is-checked' : '' }}">{{ $item['checked'] ? '✓' : '' }}</span>
                                <span>{{ $item['name'] }}</span>
                                <span class="ltc-form3-annex">Annex {{ $item['annex'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</details>
