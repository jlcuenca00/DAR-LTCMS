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

<section class="review-panel" id="ltc-form-no-4-review">
    <div class="review-panel-header">
        <div>
            <h2 class="review-panel-title">LTC Form No. 4 — Certification, Attestation and Recommendation</h2>
            <p class="review-panel-subtitle">
                Encode LTI/Legal review findings and recommendation details. This is decision-support context only;
                final release or denial remains subject to authorized review and does not transfer ownership.
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
        <form method="POST" action="{{ route('staff.applications.form4.update', $application) }}" style="display:grid; gap:18px;">
            @csrf
            @method('PATCH')

            <div style="border:1px solid #d1d5db; border-radius:12px; padding:16px; background:#ffffff;">
                <h3 style="margin:0 0 10px; font-size:15px; font-weight:900; color:#111827;">I. Facts / Information of the Subject Land</h3>

                <div style="display:grid; gap:8px;">
                    @foreach ($subjectLandOptions as $value => $label)
                        <label style="display:flex; gap:8px; align-items:flex-start; font-size:13px; color:#374151;">
                            <input type="checkbox"
                                   name="ltc_form4_subject_land_findings[]"
                                   value="{{ $value }}"
                                   @checked($subjectLandFindings->contains($value))
                                   {{ $isFinal ? 'disabled' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="border:1px solid #d1d5db; border-radius:12px; padding:16px; background:#ffffff;">
                <h3 style="margin:0 0 10px; font-size:15px; font-weight:900; color:#111827;">II. Recommendation</h3>

                <div style="display:grid; gap:8px;">
                    @foreach ($recommendationOptions as $value => $label)
                        <label style="display:flex; gap:8px; align-items:flex-start; font-size:13px; color:#374151;">
                            <input type="checkbox"
                                   name="ltc_form4_recommendation_findings[]"
                                   value="{{ $value }}"
                                   @checked($recommendationFindings->contains($value))
                                   {{ $isFinal ? 'disabled' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div style="margin-top:12px;">
                    <label style="display:block; font-size:12px; font-weight:800; color:#374151; margin-bottom:6px;">Other findings</label>
                    <textarea name="ltc_form4_other_findings"
                              rows="3"
                              style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 10px;"
                              {{ $isFinal ? 'disabled' : '' }}>{{ old('ltc_form4_other_findings', $application->ltc_form4_other_findings) }}</textarea>
                </div>

                <div style="margin-top:12px;">
                    <label style="display:block; font-size:12px; font-weight:800; color:#374151; margin-bottom:6px;">Recommendation</label>
                    <div style="display:flex; flex-wrap:wrap; gap:14px;">
                        <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:#374151;">
                            <input type="radio"
                                   name="ltc_form4_recommendation_decision"
                                   value="approval"
                                   @checked($form4Decision === 'approval')
                                   {{ $isFinal ? 'disabled' : '' }}>
                            Approval
                        </label>

                        <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:#374151;">
                            <input type="radio"
                                   name="ltc_form4_recommendation_decision"
                                   value="denial"
                                   @checked($form4Decision === 'denial')
                                   {{ $isFinal ? 'disabled' : '' }}>
                            Denial
                        </label>
                    </div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label style="display:block; font-size:12px; font-weight:800; color:#374151; margin-bottom:6px;">Date</label>
                    <input type="date"
                           name="ltc_form4_certified_at"
                           value="{{ old('ltc_form4_certified_at', optional($application->ltc_form4_certified_at)->format('Y-m-d')) }}"
                           style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 10px;"
                           {{ $isFinal ? 'disabled' : '' }}>
                </div>

                <div>
                    <label style="display:block; font-size:12px; font-weight:800; color:#374151; margin-bottom:6px;">Chief Legal / Authorized Legal Officer</label>
                    <input type="text"
                           name="ltc_form4_certifying_officer_name"
                           value="{{ old('ltc_form4_certifying_officer_name', $application->ltc_form4_certifying_officer_name) }}"
                           placeholder="Signature over printed name"
                           style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 10px;"
                           {{ $isFinal ? 'disabled' : '' }}>
                </div>
            </div>

            <div style="padding:12px; border:1px solid #bbf7d0; border-radius:10px; background:#f0fdf4; color:#166534; font-size:12px; line-height:1.5;">
                These Form No. 4 entries are administrative review details. Saving them does not approve/release the clearance,
                deny the application, transfer land ownership, change parcel linkage, or mutate Registry of Deeds records.
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
