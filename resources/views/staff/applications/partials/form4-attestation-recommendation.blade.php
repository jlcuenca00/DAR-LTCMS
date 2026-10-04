@php
    $subjectLandFindings = collect((array) old('ltc_form4_subject_land_findings', $application->ltc_form4_subject_land_findings ?? []));
    $recommendationFindings = collect((array) old('ltc_form4_recommendation_findings', $application->ltc_form4_recommendation_findings ?? []));

    $subjectLandOptions = \App\Models\LandTransferApplication::form4SubjectLandOptions();

    $recommendationOptions = \App\Models\LandTransferApplication::form4RecommendationOptions();

    $form4Decision = old('ltc_form4_recommendation_decision', $application->ltc_form4_recommendation_decision);

    $form4BlockingRequirements = $blockingRequirements ?? $transferorRequirements->concat($transfereeRequirements)
        ->filter(fn ($requirement) => method_exists($requirement, 'blocksAcceptance') ? $requirement->blocksAcceptance() : (bool) $requirement->is_mandatory);

    $form4Evaluation = $requirementEvaluation ?? app(\App\Services\ApplicationRequirementService::class)->evaluate($application);
    $form4RequirementsReady = (bool) $form4Evaluation['complete'];
    $clearanceSourceCandidates = app(\App\Services\ClearanceDocumentSourceService::class)->candidates($application);

    $form4HasData = $subjectLandFindings->isNotEmpty()
        || $recommendationFindings->isNotEmpty()
        || filled(old('ltc_form4_other_findings', $application->ltc_form4_other_findings))
        || filled($form4Decision)
        || filled(old('ltc_form4_certified_at', $application->ltc_form4_certified_at))
        || filled(old('ltc_form4_certifying_officer_name', $application->ltc_form4_certifying_officer_name));
@endphp

<style>
    #ltc-form-no-4-review > summary {
        cursor: pointer;
        list-style: none;
    }

    #ltc-form-no-4-review > summary::-webkit-details-marker {
        display: none;
    }

    #ltc-form-no-4-review:not([open]) > .review-panel-header {
        border-bottom: 0;
    }

    #ltc-form-no-4-review .ltc-form-accordion-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    #ltc-form-no-4-review .ltc-form-accordion-status {
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

    #ltc-form-no-4-review .ltc-form-accordion-status.is-progress {
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    #ltc-form-no-4-review .ltc-form-accordion-status.is-pending {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
    }

    #ltc-form-no-4-review .ltc-form-accordion-status.is-locked {
        border: 1px solid #d1d5db;
        background: #f3f4f6;
        color: #374151;
    }

    #ltc-form-no-4-review .ltc-form-accordion-chevron {
        color: #64748b;
        font-size: 14px;
        transition: transform 160ms ease;
    }

    #ltc-form-no-4-review[open] .ltc-form-accordion-chevron {
        transform: rotate(180deg);
    }

    #ltc-form-no-4-review .ltc-form-accordion-actions {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 14px;
    }

    #ltc-form-no-4-review .ltc-form4-workspace {
        display: grid;
        gap: 12px;
    }

    #ltc-form-no-4-review .ltc-form4-card {
        border: 1px solid #d1d5db;
        border-radius: 12px;
        background: #ffffff;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    #ltc-form-no-4-review .ltc-form4-card-title {
        margin: 0 0 10px;
        color: #111827;
        font-size: 14px;
        font-weight: 900;
        line-height: 1.25;
    }

    #ltc-form-no-4-review .ltc-form4-option-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 7px 12px;
    }

    #ltc-form-no-4-review .ltc-form4-recommendation-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.3fr) minmax(280px, .7fr);
        gap: 12px;
    }

    #ltc-form-no-4-review .ltc-form4-option {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        color: #374151;
        font-size: 12.5px;
        font-weight: 700;
        line-height: 1.32;
    }

    #ltc-form-no-4-review .ltc-form4-option span {
        min-width: 0;
    }

    #ltc-form-no-4-review .ltc-form4-label {
        display: block;
        margin: 0 0 5px;
        color: #64748b;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    #ltc-form-no-4-review .ltc-form4-input,
    #ltc-form-no-4-review .ltc-form4-textarea {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #111827;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.35;
        padding: 8px 10px;
    }

    #ltc-form-no-4-review .ltc-form4-textarea {
        min-height: 84px;
        resize: vertical;
    }

    #ltc-form-no-4-review .ltc-form4-field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    #ltc-form-no-4-review .ltc-form4-decision-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 12px;
    }

    #ltc-form-no-4-review .ltc-form4-choice {
        flex: 0 0 15px;
    }

    #ltc-form-no-4-review .ltc-form4-choice[type="checkbox"] {
        appearance: none !important;
        -webkit-appearance: none !important;
        width: 15px !important;
        height: 15px !important;
        margin: 1px 0 0 !important;
        border: 1.5px solid #16a34a !important;
        border-radius: 2px !important;
        background: #ffffff !important;
        display: inline-grid !important;
        place-content: center !important;
        cursor: pointer;
    }

    #ltc-form-no-4-review .ltc-form4-choice[type="checkbox"]:checked {
        background: #16a34a !important;
        border-color: #16a34a !important;
    }

    #ltc-form-no-4-review .ltc-form4-choice[type="checkbox"]:checked::after {
        content: "";
        width: 4px;
        height: 8px;
        border: solid #ffffff;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
        margin-top: -1px;
    }

    #ltc-form-no-4-review .ltc-form4-choice[type="radio"] {
        accent-color: #16a34a !important;
        width: 15px !important;
        height: 15px !important;
    }

    #ltc-form-no-4-review .ltc-form4-choice:disabled,
    #ltc-form-no-4-review .ltc-form4-input:disabled,
    #ltc-form-no-4-review .ltc-form4-textarea:disabled {
        cursor: not-allowed;
        opacity: 0.72;
    }

    @media (max-width: 1100px) {
        #ltc-form-no-4-review .ltc-form4-option-grid,
        #ltc-form-no-4-review .ltc-form4-recommendation-grid,
        #ltc-form-no-4-review .ltc-form4-field-grid {
            grid-template-columns: 1fr;
        }

        #ltc-form-no-4-review > .review-panel-header {
            align-items: center;
        }
    }
</style>

<details class="review-panel ltc-form-accordion"
         id="ltc-form-no-4-review"
         name="ltc-review-form"
         @if (! $isFinal && $form4RequirementsReady) open @endif>
    <summary class="review-panel-header">
        <div>
            <h2 class="review-panel-title">LTC Form No. 4 — Certification, Attestation and Recommendation</h2>
            <p class="review-panel-subtitle">
                Encode LTI/Legal review findings and recommendation details before opening the formal PDF output.
            </p>
        </div>

        <div class="ltc-form-accordion-meta">
            @if ($isFinal)
                <span class="ltc-form-accordion-status is-locked">Read only</span>
            @elseif (! $form4RequirementsReady)
                <span class="ltc-form-accordion-status is-pending">Requirements pending</span>
            @elseif ($form4HasData)
                <span class="ltc-form-accordion-status is-progress">In progress</span>
            @else
                <span class="ltc-form-accordion-status is-pending">Not started</span>
            @endif
            <i class="fa-solid fa-chevron-down ltc-form-accordion-chevron" aria-hidden="true"></i>
        </div>
    </summary>

    <div class="review-panel-body">
        <div class="ltc-form-accordion-actions">
            <a href="{{ route('staff.applications.form4.pdf', $application) }}"
               class="staff-button staff-button-primary"
               target="_blank">
                <i class="fa-solid fa-file-pdf"></i>
                Open Form No. 4 PDF
            </a>
        </div>

        <form data-submit-feedback method="POST" action="{{ route('staff.applications.form4.update', $application) }}" class="ltc-form4-workspace">
            @csrf
            <input type="hidden" name="expected_workflow_revision" value="{{ old('expected_workflow_revision', $application->workflow_revision) }}">
            @method('PATCH')

            <div class="ltc-form4-card">
                <h3 class="ltc-form4-card-title">Clearance output evidence sources</h3>
                <p class="text-sm mb-3">Use the reviewed title/ownership and transfer document for LTC Form No. 5. A single eligible source is used automatically. Select a source when several are recorded.</p>
                <div class="ltc-form4-field-grid">
                    @foreach (['title' => 'Title / ownership evidence', 'transfer' => 'Transfer document / deed'] as $sourceRole => $sourceLabel)
                        @php $sourceField = 'ltc_'.$sourceRole.'_document_id'; @endphp
                        <div>
                            <label class="ltc-form4-label" for="{{ $sourceField }}">{{ $sourceLabel }}</label>
                            <select id="{{ $sourceField }}" name="{{ $sourceField }}" class="ltc-form4-input" {{ $isFinal ? 'disabled' : '' }}>
                                <option value="">Automatic when a single source is recorded</option>
                                @foreach ($clearanceSourceCandidates[$sourceRole] as $sourceDocument)
                                    <option value="{{ $sourceDocument->id }}" @selected((string) old($sourceField, $application->{$sourceField}) === (string) $sourceDocument->id)>{{ $sourceDocument->requiredDocument?->name }}{{ $sourceDocument->annex_reference ? ' — '.$sourceDocument->annex_reference : '' }} (record #{{ $sourceDocument->id }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="ltc-form4-card">
                <h3 class="ltc-form4-card-title">I. Facts / Information of the Subject Land</h3>

                <div class="ltc-form4-option-grid">
                    @foreach ($subjectLandOptions as $value => $label)
                        <label class="ltc-form4-option">
                            <input type="checkbox"
                                   class="ltc-form4-choice"
                                   name="ltc_form4_subject_land_findings[]"
                                   value="{{ $value }}"
                                   @checked($subjectLandFindings->contains($value))
                                   {{ $isFinal ? 'disabled' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="ltc-form4-recommendation-grid">
                <div class="ltc-form4-card">
                    <h3 class="ltc-form4-card-title">II. Recommendation</h3>

                    <div class="ltc-form4-option-grid" style="grid-template-columns:1fr;">
                        @foreach ($recommendationOptions as $value => $label)
                            <label class="ltc-form4-option">
                                <input type="checkbox"
                                       class="ltc-form4-choice"
                                       name="ltc_form4_recommendation_findings[]"
                                       value="{{ $value }}"
                                       @checked($recommendationFindings->contains($value))
                                       {{ $isFinal ? 'disabled' : '' }}>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div style="margin-top:12px;">
                        <label class="ltc-form4-label">Other findings</label>
                        <textarea name="ltc_form4_other_findings"
                                  rows="3"
                                  class="ltc-form4-textarea"
                                  {{ $isFinal ? 'disabled' : '' }}>{{ old('ltc_form4_other_findings', $application->ltc_form4_other_findings) }}</textarea>
                    </div>
                </div>

                <div class="ltc-form4-card">
                    <h3 class="ltc-form4-card-title">Review Decision Details</h3>

                    <label class="ltc-form4-label">Recommendation</label>
                    <div class="ltc-form4-decision-row">
                        <label class="ltc-form4-option">
                            <input type="radio"
                                   class="ltc-form4-choice"
                                   name="ltc_form4_recommendation_decision"
                                   value="approval"
                                   @checked($form4Decision === 'approval')
                                   {{ $isFinal ? 'disabled' : '' }}>
                            <span>Approval</span>
                        </label>

                        <label class="ltc-form4-option">
                            <input type="radio"
                                   class="ltc-form4-choice"
                                   name="ltc_form4_recommendation_decision"
                                   value="denial"
                                   @checked($form4Decision === 'denial')
                                   {{ $isFinal ? 'disabled' : '' }}>
                            <span>Denial</span>
                        </label>
                    </div>

                    <div class="ltc-form4-field-grid" style="margin-top:14px;">
                        <div>
                            <label class="ltc-form4-label">Date</label>
                            <input type="date"
                                   name="ltc_form4_certified_at"
                                   value="{{ old('ltc_form4_certified_at', optional($application->ltc_form4_certified_at)->format('Y-m-d')) }}"
                                   class="ltc-form4-input"
                                   {{ $isFinal ? 'disabled' : '' }}>
                        </div>

                        <div>
                            <label class="ltc-form4-label">Authorized Officer</label>
                            <input type="text"
                                   name="ltc_form4_certifying_officer_name"
                                   value="{{ old('ltc_form4_certifying_officer_name', $application->ltc_form4_certifying_officer_name) }}"
                                   placeholder="Signature over printed name"
                                   class="ltc-form4-input"
                                   {{ $isFinal ? 'disabled' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            @unless ($isFinal)
                <div style="display:flex; justify-content:flex-end;">
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Form No. 4 Review Details
                    </button>
                </div>
            @endunless
        </form>
    </div>
</details>
