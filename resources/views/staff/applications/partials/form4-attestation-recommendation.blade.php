@php
    $subjectLandFindings = collect((array) old('ltc_form4_subject_land_findings', $application->ltc_form4_subject_land_findings ?? []));
    $recommendationFindings = collect((array) old('ltc_form4_recommendation_findings', $application->ltc_form4_recommendation_findings ?? []));

    $subjectLandOptions = [
        'pd27_not_covered_tenanted_retained_area' => 'Not covered by P.D. No. 27/E.O. No. 228 — Tenanted retained area',
        'pd27_not_covered_not_tenanted_retained_area' => 'Not covered by P.D. No. 27/E.O. No. 228 — Not tenanted retained area',
        'ra6657_not_covered_tenanted_retained_area' => 'Not covered by R.A. No. 6657, as amended by R.A. No. 9700 — Tenanted retained area',
        'ra6657_not_covered_not_tenanted_retained_area' => 'Not covered by R.A. No. 6657, as amended by R.A. No. 9700 — Not tenanted retained area',
        'ra6657_not_covered_personally_tilled' => 'Not covered by R.A. No. 6657 — Personally tilled by the landowner',
        'ra6657_not_covered_above_18_slope' => 'Not covered by R.A. No. 6657 — Un-acquired portion above 18% slope',
        'pd27_covered_cf_under_process' => 'Covered by P.D. No. 27/E.O. No. 228 — CF under process',
        'pd27_covered_dnyd' => 'Covered by P.D. No. 27/E.O. No. 228 — Distributed but not yet documented (DNYD)',
        'pd27_covered_dnyp' => 'Covered by P.D. No. 27/E.O. No. 228 — Distributed but not yet paid (DNYP)',
        'pd27_covered_under_protest' => 'Covered by P.D. No. 27/E.O. No. 228 — Under protest',
        'ra6657_covered_cf_under_process' => 'Covered by R.A. No. 6657 — CF under process',
        'ra6657_covered_dnyd' => 'Covered by R.A. No. 6657 — Distributed but not yet documented (DNYD)',
        'ra6657_covered_dnyp' => 'Covered by R.A. No. 6657 — Distributed but not yet paid (DNYP)',
        'ra6657_covered_under_protest' => 'Covered by R.A. No. 6657 — Under protest',
    ];

    $recommendationOptions = [
        'application_complete' => 'The duly accomplished application/request is in order and complete.',
        'requirements_complete_consistent' => 'The mandatory documentary requirements and pertinent documents are complete and consistent in form and substance.',
        'no_pending_case_or_conflict' => 'There is no pending case, protest, or conflict of claims involving the subject land.',
    ];

    $form4Decision = old('ltc_form4_recommendation_decision', $application->ltc_form4_recommendation_decision);
@endphp

<style>
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
    }
</style>

<section class="review-panel" id="ltc-form-no-4-review">
    <div class="review-panel-header">
        <div>
            <h2 class="review-panel-title">LTC Form No. 4 — Certification, Attestation and Recommendation</h2>
            <p class="review-panel-subtitle">
                Encode LTI/Legal review findings and recommendation details before opening the formal PDF output.
            </p>
        </div>

        <a href="{{ route('staff.applications.form4.pdf', $application) }}"
           class="staff-button staff-button-primary"
           target="_blank">
            <i class="fa-solid fa-file-pdf"></i>
            Open Form No. 4 PDF
        </a>
    </div>

    <div class="review-panel-body">
        <form method="POST" action="{{ route('staff.applications.form4.update', $application) }}" class="ltc-form4-workspace">
            @csrf
            @method('PATCH')

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
</section>
