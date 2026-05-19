<x-staff-shell
    title="Encode New Clearance Application"
    active="applications"
    maxWidth="max-w-6xl"
>
    <x-slot name="actions">
        <a href="{{ route('staff.applications.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Applications
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .application-create-page {
                display: grid;
                gap: 20px;
            }

            .application-create-page .form-shell {
                overflow: hidden;
                background: #ffffff;
                border: 1px solid var(--border);
                border-radius: 14px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            }

            .application-create-page .form-header {
                display: flex;
                justify-content: space-between;
                gap: 18px;
                align-items: flex-start;
                padding: 22px 24px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(180deg, #ffffff 0%, #fbfcfb 100%);
            }

            .application-create-page .form-header h2,
            .application-create-page .section-title {
                margin: 0;
                font-family: var(--heading-font);
                color: #111827;
            }

            .application-create-page .form-header h2 {
                font-size: 19px;
                font-weight: 900;
            }

            .application-create-page .form-header p,
            .application-create-page .section-copy {
                margin: 6px 0 0;
                color: #6b7280;
                font-size: 13px;
                line-height: 1.55;
            }

            .application-create-page .draft-pill {
                flex: 0 0 auto;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #166534;
                border-radius: 999px;
                padding: 7px 12px;
                font-size: 12px;
                font-weight: 900;
                white-space: nowrap;
            }

            .application-create-page .form-section {
                padding: 22px 24px;
                border-bottom: 1px solid #e5e7eb;
            }

            .application-create-page .form-section:last-of-type {
                border-bottom: 0;
            }

            .application-create-page .section-head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 18px;
                margin-bottom: 18px;
            }

            .application-create-page .section-title {
                font-size: 16px;
                font-weight: 900;
            }

            .application-create-page .field-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px 18px;
            }

            .application-create-page .field-span-2 {
                grid-column: 1 / -1;
            }

            .application-create-page .field-group {
                min-width: 0;
            }

            .application-create-page .field-label {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin-bottom: 6px;
                font-size: 12px;
                font-weight: 900;
                letter-spacing: 0.03em;
                color: #1f2937;
            }

            .application-create-page .required-mark {
                color: #dc2626;
                font-weight: 900;
            }

            .application-create-page .field-help {
                margin: 6px 0 0;
                color: #6b7280;
                font-size: 12px;
                line-height: 1.45;
            }

            .application-create-page .staff-input,
            .application-create-page .staff-select,
            .application-create-page .staff-textarea {
                width: 100%;
                border: 1px solid #cbd5d1;
                border-radius: 9px;
                background: #ffffff;
                color: #111827;
                font-size: 14px;
                outline: none;
                transition: 150ms ease;
            }

            .application-create-page .staff-input,
            .application-create-page .staff-select {
                min-height: 42px;
                height: 42px;
                padding: 0 12px;
            }

            .application-create-page .staff-textarea {
                min-height: 118px;
                padding: 12px;
                resize: vertical;
            }

            .application-create-page .staff-input:focus,
            .application-create-page .staff-select:focus,
            .application-create-page .staff-textarea:focus {
                border-color: #15803d;
                box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.14);
            }

            .application-create-page .subsection-card {
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: #f9fafb;
                padding: 16px;
            }

            .application-create-page .subsection-card .field-grid {
                gap: 14px 16px;
            }

            .application-create-page .form-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                padding: 18px 24px;
                background: #f8fafc;
                border-top: 1px solid #e5e7eb;
            }

            .application-create-page .footer-note {
                margin: 0;
                max-width: 620px;
                color: #6b7280;
                font-size: 12px;
                line-height: 1.45;
            }

            .application-create-page .footer-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 10px;
                flex: 0 0 auto;
            }

            .application-create-page .scope-alert {
                display: flex;
                gap: 12px;
                align-items: flex-start;
                border: 1px solid #fed7aa;
                background: #fff7ed;
                color: #9a3412;
                border-radius: 12px;
                padding: 14px 16px;
                font-size: 13px;
                line-height: 1.55;
                font-weight: 600;
            }

            .application-create-page .scope-alert i {
                margin-top: 2px;
                color: #ea580c;
            }

            @media (max-width: 900px) {
                .application-create-page .field-grid {
                    grid-template-columns: 1fr;
                }

                .application-create-page .form-header,
                .application-create-page .section-head,
                .application-create-page .form-footer {
                    flex-direction: column;
                    align-items: stretch;
                }

                .application-create-page .footer-actions {
                    width: 100%;
                    flex-direction: column-reverse;
                }

                .application-create-page .footer-actions .staff-button {
                    width: 100%;
                }
            }

            @media (max-width: 560px) {
                .application-create-page .form-header,
                .application-create-page .form-section,
                .application-create-page .form-footer {
                    padding-left: 18px;
                    padding-right: 18px;
                }
            }
        </style>
    </x-slot>

    <div class="application-create-page">

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="mb-2 font-bold">Please correct the following:</p>
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('staff.applications.store') }}" class="form-shell" data-autosave-key="clearance-application-create" data-autosave-label="clearance application draft">
            @csrf

            <div class="form-header">
                <div>
                    <h2>New Clearance Application Record</h2>
                    <p>
                        Encode the parties, location, filing dates, and optional parcel reference. The record starts as a draft
                        until submitted for review.
                    </p>
                </div>
                <span class="draft-pill">
                    <i class="fa-solid fa-file-pen"></i>
                    Draft Record
                </span>
            </div>

            <section class="form-section">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">Party Records</h3>
                        <p class="section-copy">Link existing landowner records when available, then encode the names used in the application.</p>
                    </div>
                </div>

                <div class="field-grid">
                    <div class="subsection-card">
                        <div class="field-grid">
                            <div class="field-group field-span-2">
                                <label for="transferor_landowner_id" class="field-label">Transferor Landowner Record</label>
                                <select id="transferor_landowner_id" name="transferor_landowner_id" class="staff-select">
                                    <option value="">No linked landowner record</option>
                                    @foreach ($landowners as $landowner)
                                        <option value="{{ $landowner->id }}" data-name="{{ $landowner->full_name }}" @selected(old('transferor_landowner_id') == $landowner->id)>
                                            {{ $landowner->full_name }} — {{ $landowner->municipality ?? 'No municipality' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="field-help">Optional, but recommended for validation and traceability.</p>
                            </div>

                            <div class="field-group field-span-2">
                                <label for="transferor_name" class="field-label">
                                    Transferor Name <span class="required-mark">*</span>
                                </label>
                                <input id="transferor_name" type="text" name="transferor_name" value="{{ old('transferor_name') }}" required class="staff-input" placeholder="Enter transferor name">
                            </div>
                        </div>
                    </div>

                    <div class="subsection-card">
                        <div class="field-grid">
                            <div class="field-group field-span-2">
                                <label for="transferee_landowner_id" class="field-label">Transferee Landowner Record</label>
                                <select id="transferee_landowner_id" name="transferee_landowner_id" class="staff-select">
                                    <option value="">No linked landowner record</option>
                                    @foreach ($landowners as $landowner)
                                        <option value="{{ $landowner->id }}" data-name="{{ $landowner->full_name }}" @selected(old('transferee_landowner_id') == $landowner->id)>
                                            {{ $landowner->full_name }} — {{ $landowner->municipality ?? 'No municipality' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="field-help">Used for assistive validation such as landholding area checks.</p>
                            </div>

                            <div class="field-group field-span-2">
                                <label for="transferee_name" class="field-label">
                                    Transferee Name <span class="required-mark">*</span>
                                </label>
                                <input id="transferee_name" type="text" name="transferee_name" value="{{ old('transferee_name') }}" required class="staff-input" placeholder="Enter transferee name">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">Location and Filing Details</h3>
                        <p class="section-copy">Record the application location and filing dates for monitoring and report generation.</p>
                    </div>
                </div>

                <div class="field-grid">
                    <div class="field-group">
                        <label for="municipality" class="field-label">Municipality</label>
                        <input id="municipality" type="text" name="municipality" value="{{ old('municipality') }}" class="staff-input" placeholder="Example: Dumaguete City">
                    </div>

                    <div class="field-group">
                        <label for="barangay" class="field-label">Barangay</label>
                        <input id="barangay" type="text" name="barangay" value="{{ old('barangay') }}" class="staff-input" placeholder="Example: Barangay Alpha">
                    </div>

                    <div class="field-group">
                        <label for="date_filed" class="field-label">Date Filed</label>
                        <input id="date_filed" type="date" name="date_filed" value="{{ old('date_filed', now()->toDateString()) }}" class="staff-input">
                    </div>

                    <div class="field-group">
                        <label for="date_of_transfer" class="field-label">Date of Intended Transfer</label>
                        <input id="date_of_transfer" type="date" name="date_of_transfer" value="{{ old('date_of_transfer') }}" class="staff-input">
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">Parcel Reference</h3>
                        <p class="section-copy">Link a main parcel record for review and reference only.</p>
                    </div>
                </div>

                <div class="scope-alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        Linked parcels support application review and monitoring only. Creating this application does not transfer ownership or change registry records.
                    </div>
                </div>

                <div class="mt-4 field-grid">
                    <div class="field-group">
                        <label for="parcel_id" class="field-label">Main Parcel Record</label>
                        <select id="parcel_id" name="parcel_id" class="staff-select">
                            <option value="">No parcel linked yet</option>
                            @foreach ($parcels as $parcel)
                                <option value="{{ $parcel->id }}" data-area="{{ $parcel->area_hectares }}" @selected(old('parcel_id') == $parcel->id)>
                                    {{ $parcel->parcel_code }}
                                    @if ($parcel->title_no)
                                        — {{ $parcel->title_no }}
                                    @endif
                                    @if ($parcel->municipality)
                                        — {{ $parcel->municipality }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="area_hectares" class="field-label">Application Area in Hectares</label>
                        <input id="area_hectares" type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares') }}" class="staff-input" placeholder="Leave blank to use parcel area">
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">Staff Remarks</h3>
                        <p class="section-copy">Optional notes for encoding context, document follow-up, or review preparation.</p>
                    </div>
                </div>

                <div class="field-group">
                    <label for="remarks" class="field-label">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="4" class="staff-textarea" placeholder="Optional staff notes for application encoding">{{ old('remarks') }}</textarea>
                </div>
            </section>

            <div class="form-footer">
                <p class="footer-note">
                    Saving creates a draft clearance application record. Submission, review, approval, not-approval, and clearance generation remain separate staff actions.
                </p>

                <div class="footer-actions">
                    <a href="{{ route('staff.applications.index') }}" class="staff-button staff-button-light">
                        Cancel
                    </a>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Draft Application
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function wireNameAutofill(selectId, inputId) {
                const select = document.getElementById(selectId);
                const input = document.getElementById(inputId);
                if (!select || !input) return;

                select.addEventListener('change', function () {
                    const selected = select.options[select.selectedIndex];
                    const name = selected ? selected.dataset.name : '';
                    if (name) input.value = name;
                });

                const selected = select.options[select.selectedIndex];
                if (selected && selected.dataset.name && !input.value) {
                    input.value = selected.dataset.name;
                }
            }

            wireNameAutofill('transferor_landowner_id', 'transferor_name');
            wireNameAutofill('transferee_landowner_id', 'transferee_name');

            const parcelSelect = document.getElementById('parcel_id');
            const areaInput = document.getElementById('area_hectares');
            if (parcelSelect && areaInput) {
                parcelSelect.addEventListener('change', function () {
                    const selected = parcelSelect.options[parcelSelect.selectedIndex];
                    if (selected && selected.dataset.area && !areaInput.value) {
                        areaInput.value = parseFloat(selected.dataset.area).toFixed(4);
                    }
                });
            }
        });
    </script>

    @include('staff.partials.form-autosave')

</x-staff-shell>
