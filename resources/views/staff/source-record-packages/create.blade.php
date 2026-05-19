<x-staff-shell
    title="Encode Source Package"
    active="source-records"
    maxWidth="max-w-7xl"
>
    <x-slot name="actions">
        <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Source Records
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .source-package-page {
                display: grid;
                gap: 14px;
            }

            .source-form-panel {
                overflow: hidden;
                border-radius: 16px;
            }

            .source-form-section {
                padding: 18px 22px;
                border-bottom: 1px solid #e5e7eb;
                display: grid;
                gap: 14px;
                background: #ffffff;
                position: relative;
            }


            .source-form-section:last-child {
                border-bottom: 0;
            }

            .source-section-header {
                display: flex;
                justify-content: space-between;
                gap: 18px;
                align-items: flex-start;
            }

            .source-section-heading {
                display: flex;
                align-items: flex-start;
                gap: 11px;
                min-width: 0;
            }

            .source-step-number {
                width: 30px;
                height: 30px;
                border-radius: 10px;
                border: 1px solid #bbf7d0;
                background: #ecfdf5;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 13px;
                font-weight: 900;
                flex: 0 0 auto;
                margin-top: -1px;
            }

            .source-section-title {
                margin: 0;
                font-size: 16px;
                line-height: 1.25;
                font-weight: 900;
                color: #111827;
            }

            .source-section-copy {
                margin: 3px 0 0;
                color: #64748b;
                font-size: 12.5px;
                line-height: 1.45;
                max-width: 820px;
            }

            .source-section-chip {
                flex: 0 0 auto;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #166534;
                border-radius: 999px;
                padding: 5px 10px;
                font-size: 10.5px;
                font-weight: 900;
                white-space: nowrap;
            }

            .source-option-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 9px;
            }

            .source-option-card {
                display: flex;
                align-items: center;
                gap: 10px;
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                border-radius: 10px;
                padding: 11px 13px;
                cursor: pointer;
                transition: 150ms ease;
            }

            .source-option-card:hover {
                border-color: #86efac;
                background: #f0fdf4;
            }

            .source-option-card input[type="checkbox"] {
                width: 16px;
                height: 16px;
                flex: 0 0 auto;
                appearance: none;
                -webkit-appearance: none;
                border: 1.5px solid #9ca3af;
                border-radius: 3px;
                background: #ffffff;
                display: grid;
                place-content: center;
                cursor: pointer;
                transition: 150ms ease;
            }

            .source-option-card input[type="checkbox"]::before {
                content: "";
                width: 9px;
                height: 9px;
                transform: scale(0);
                transition: 120ms ease-in-out;
                background: #ffffff;
                clip-path: polygon(14% 44%, 0 58%, 38% 96%, 100% 22%, 86% 8%, 36% 66%);
            }

            .source-option-card input[type="checkbox"]:checked {
                border-color: #15803d;
                background: #15803d;
            }

            .source-option-card input[type="checkbox"]:checked::before {
                transform: scale(1);
            }

            .source-option-card input[type="checkbox"]:focus-visible {
                outline: 2px solid rgba(22, 101, 52, 0.25);
                outline-offset: 2px;
            }

            .source-option-card span {
                font-size: 13px;
                font-weight: 900;
                color: #14532d;
            }

            .source-field-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px 14px;
            }

            .source-field-grid.two {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .source-field,
            .source-field-wide {
                display: grid;
                gap: 5px;
            }

            .source-field-wide {
                grid-column: 1 / -1;
            }

            .source-field label,
            .source-field-wide label {
                font-size: 10.5px !important;
                font-weight: 900 !important;
                letter-spacing: 0.085em;
                text-transform: uppercase;
                color: #374151 !important;
            }

            .source-field input,
            .source-field select,
            .source-field textarea,
            .source-field-wide input,
            .source-field-wide select,
            .source-field-wide textarea {
                border-color: #cbd5e1;
                min-height: 40px;
            }

            .source-field textarea,
            .source-field-wide textarea {
                resize: vertical;
            }

            .source-help-box {
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                color: #14532d;
                border-radius: 10px;
                padding: 9px 11px;
                font-size: 12px;
                line-height: 1.45;
            }

            .source-footer-actions {
                padding: 14px 22px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                background: #f8fafc;
                border-top: 1px solid #e5e7eb;
            }

            .source-footer-note-wrap {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                min-width: 0;
                max-width: 820px;
            }

            .source-footer-icon {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                background: #ecfdf5;
                border: 1px solid #bbf7d0;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
            }

            .source-footer-title {
                margin: 0;
                font-size: 12.5px;
                font-weight: 900;
                color: #111827;
            }

            .source-footer-note {
                margin: 3px 0 0;
                color: #64748b;
                font-size: 12px;
                line-height: 1.45;
            }

            .source-footer-buttons {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                gap: 10px;
                flex: 0 0 auto;
            }

            .source-footer-buttons .staff-button {
                height: 40px;
                min-height: 40px;
                min-width: 150px;
                padding: 0 16px;
                justify-content: center;
                white-space: nowrap;
            }

            .source-footer-buttons .staff-button-primary {
                min-width: 184px;
            }



            .source-validation-summary {
                border: 1px solid #fecaca;
                background: #fff1f2;
                color: #991b1b;
                border-radius: 12px;
                padding: 14px 16px;
                display: grid;
                gap: 8px;
            }

            .source-validation-title {
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 0;
                font-size: 14px;
                font-weight: 900;
                color: #991b1b;
            }

            .source-validation-copy {
                margin: 0;
                font-size: 12.5px;
                line-height: 1.5;
                color: #7f1d1d;
            }

            .source-validation-list {
                margin: 0;
                padding-left: 1.1rem;
                display: grid;
                gap: 4px;
                font-size: 12.5px;
                color: #991b1b;
            }

            .source-invalid-input {
                border-color: #dc2626 !important;
                background: #fff7f7 !important;
                box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.10) !important;
            }

            .source-field.has-error label,
            .source-field-wide.has-error label,
            .source-validation-field.has-error label,
            .source-form-field.has-error label {
                color: #991b1b !important;
            }

            .source-option-card.source-invalid-input,
            .source-option-card.has-error {
                border-color: #dc2626 !important;
                background: #fff7f7 !important;
            }

            .source-inline-error {
                margin-top: 4px;
                display: flex;
                align-items: flex-start;
                gap: 6px;
                color: #991b1b;
                font-size: 12px;
                font-weight: 800;
                line-height: 1.45;
            }

            .source-inline-error i {
                margin-top: 2px;
                font-size: 11px;
                flex: 0 0 auto;
            }

            .source-field-grid.core-party-grid {
                grid-template-columns: repeat(12, minmax(0, 1fr));
                gap: 12px 16px;
                align-items: start;
            }

            .source-field-grid.core-party-grid .source-field { grid-column: span 4; }
            .source-field-grid.core-party-grid .source-field:nth-child(10),
            .source-field-grid.core-party-grid .source-field:nth-child(11) { grid-column: span 6; }

            @media (max-width: 1100px) {
                .source-option-grid,
                .source-field-grid,
                .source-field-grid.two {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 720px) {
                .source-form-section {
                    padding: 18px;
                }

                .source-section-header,
                .source-section-heading,
                .source-footer-actions {
                    flex-direction: column;
                    align-items: stretch;
                }

                .source-step-number {
                    margin-top: 0;
                }

                .source-footer-note-wrap {
                    max-width: none;
                }

                .source-option-grid,
                .source-field-grid,
                .source-field-grid.two {
                    grid-template-columns: 1fr;
                }

                .source-footer-buttons {
                    flex-direction: column-reverse;
                    width: 100%;
                }

                .source-footer-buttons .staff-button {
                    width: 100%;
                    min-width: 0;
                }
            }
        </style>
    </x-slot>

    <div class="source-package-page">

        <form method="POST" action="{{ route('staff.source-record-packages.store') }}" class="staff-panel source-form-panel" data-source-validation-form>
            @csrf

            <section class="source-form-section">
                <div class="source-section-header">
                    <div class="source-section-heading">
                        <span class="source-step-number">1</span>
                        <div>
                            <h2 class="source-section-title">Choose Included Source Sections</h2>
                            <p class="source-section-copy">
                            Select only the sections present in the physical/digital source file. The system will create connected source records under one package for traceability.
                            </p>
                        </div>
                    </div>
                    <span class="source-section-chip">At least one required</span>
                </div>

                <div class="source-option-grid">
                    <label class="source-option-card">
                        <input type="checkbox" name="include_title" value="1" @checked(old('include_title', true))>
                        <span>Title</span>
                    </label>

                    <label class="source-option-card">
                        <input type="checkbox" name="include_landholding" value="1" @checked(old('include_landholding', true))>
                        <span>Landholding</span>
                    </label>

                    <label class="source-option-card">
                        <input type="checkbox" name="include_parcel_source" value="1" @checked(old('include_parcel_source', true))>
                        <span>Parcel Source</span>
                    </label>

                    <label class="source-option-card">
                        <input type="checkbox" name="include_historical_clearance" value="1" @checked(old('include_historical_clearance'))>
                        <span>Historical Clearance</span>
                    </label>
                </div>
            </section>

            <section class="source-form-section">
                <div class="source-section-header">
                    <div class="source-section-heading">
                        <span class="source-step-number">2</span>
                        <div>
                            <h2 class="source-section-title">Core Party and Parcel Details</h2>
                            <p class="source-section-copy">
                            Encode shared reference values once. These are indexing fields for review and monitoring, not automatic legal verification fields.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="source-field-grid core-party-grid">
                    <div class="source-field">
                        <label for="landowner_name">Landowner / Owner Name *</label>
                        <input id="landowner_name" name="landowner_name" value="{{ old('landowner_name') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                    </div>

                    <div class="source-field">
                        <label for="parcel_code">Parcel Reference Code</label>
                        <input id="parcel_code" name="parcel_code" value="{{ old('parcel_code') }}" placeholder="e.g. SRC-PCL-003" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="title_number">Title Number</label>
                        <input id="title_number" name="title_number" value="{{ old('title_number') }}" placeholder="e.g. TCT-000123" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="landholding_reference_number">Landholding Reference Number</label>
                        <input id="landholding_reference_number" name="landholding_reference_number" value="{{ old('landholding_reference_number') }}" placeholder="e.g. LH-2026-001" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="lot_number">Lot Number</label>
                        <input id="lot_number" name="lot_number" value="{{ old('lot_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="survey_number">Survey Number</label>
                        <input id="survey_number" name="survey_number" value="{{ old('survey_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="area_hectares">Area in Hectares</label>
                        <input id="area_hectares" type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="crop_or_land_use">Agricultural Classification</label>
                        @php
    $agriculturalClassificationOptions = [
        'Private Agricultural Land',
        'Awarded CLOA Land',
        'Emancipation Patent Land',
        'CARP-Covered Land',
        'Not Yet Determined',
        'Non-Agricultural / Reference Only',
    ];
@endphp
<select id="crop_or_land_use" name="crop_or_land_use" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
    <option value="">Select classification</option>
    @foreach ($agriculturalClassificationOptions as $classification)
        <option value="{{ $classification }}" @selected(old('crop_or_land_use') === $classification)>{{ $classification }}</option>
    @endforeach
</select>
<p class="mt-1 text-xs text-gray-500">Use the classification indicated by the source document, if available.</p>
                    </div>

                    <div class="source-field">
                        <label for="province">Province</label>
                        <input id="province" name="province" value="{{ old('province', 'Negros Oriental') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="barangay">Barangay</label>
                        <input id="barangay" name="barangay" value="{{ old('barangay') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="municipality">Municipality</label>
                        <input id="municipality" name="municipality" value="{{ old('municipality') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </div>
            </section>

            <section class="source-form-section">
                <div class="source-section-header">
                    <div class="source-section-heading">
                        <span class="source-step-number">3</span>
                        <div>
                            <h2 class="source-section-title">Historical Clearance Details</h2>
                            <p class="source-section-copy">
                            Fill this only when the package includes an old or current clearance reference.
                            </p>
                        </div>
                    </div>
                    <span class="source-section-chip">Optional</span>
                </div>

                <div class="source-field-grid">
                    <div class="source-field">
                        <label for="control_number">Clearance Control Number</label>
                        <input id="control_number" name="control_number" value="{{ old('control_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="transferor_name">Transferor Name</label>
                        <input id="transferor_name" name="transferor_name" value="{{ old('transferor_name') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="transferee_name">Transferee Name</label>
                        <input id="transferee_name" name="transferee_name" value="{{ old('transferee_name') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </div>
            </section>

            <section class="source-form-section">
                <div class="source-section-header">
                    <div class="source-section-heading">
                        <span class="source-step-number">4</span>
                        <div>
                            <h2 class="source-section-title">Scope, Parcel Link, and Geometry</h2>
                            <p class="source-section-copy">
                            Link to an existing main Parcel Record when applicable. Source geometry is stored as reference data and does not appear on the map by itself.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="source-field-grid two">
                    <div class="source-field">
                        <label for="source_record_scope">Source Record Scope *</label>
                        <select id="source_record_scope" name="source_record_scope" class="w-full rounded-lg border-gray-300 text-sm" required>
                            @foreach ($sourceScopes as $value => $label)
                                <option value="{{ $value }}" @selected(old('source_record_scope', 'current_active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="source-field">
                        <label for="parcel_id">Link to Existing Parcel</label>
                        <select id="parcel_id" name="parcel_id" class="w-full rounded-lg border-gray-300 text-sm" data-source-parcel-autofill>
                            <option value="">Not linked yet</option>
                            @foreach ($parcels as $parcel)
                                <option value="{{ $parcel->id }}" data-parcel-code="{{ $parcel->parcel_code }}" data-title="{{ $parcel->title_no }}" data-area="{{ $parcel->area_hectares }}" data-province="{{ $parcel->province }}" data-municipality="{{ $parcel->municipality }}" data-barangay="{{ $parcel->barangay }}" @selected((string) old('parcel_id') === (string) $parcel->id)>
                                    {{ $parcel->parcel_code }}
                                    @if ($parcel->title_no) — {{ $parcel->title_no }} @endif
                                    @if ($parcel->barangay || $parcel->municipality) — {{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="source-field-wide">
                        <label for="boundary_description">Boundary / Technical Description Notes</label>
                        <textarea id="boundary_description" name="boundary_description" rows="2" class="w-full rounded-lg border-gray-300 text-sm">{{ old('boundary_description') }}</textarea>
                    </div>

                    <div class="source-field-wide">
                        <label for="source_geometry_geojson">Source GeoJSON Geometry</label>
                        <textarea id="source_geometry_geojson" name="source_geometry_geojson" rows="4" placeholder='Example polygon: {"type":"Polygon","coordinates":[[[123.3048,9.3064],[123.3058,9.3064],[123.3058,9.3072],[123.3048,9.3072],[123.3048,9.3064]]]}' class="w-full rounded-lg border-gray-300 text-xs">{{ old('source_geometry_geojson') }}</textarea>
                        <p class="source-help-box mt-2">
                            Geometry entered here is documentary/reference geometry only. Staff may later copy it into a main Parcel Record through a confirmed parcel creation/linking action.
                        </p>
                    </div>
                </div>
            </section>

            <section class="source-form-section">
                <div class="source-section-header">
                    <div class="source-section-heading">
                        <span class="source-step-number">5</span>
                        <div>
                            <h2 class="source-section-title">Provenance and Encoding Details</h2>
                            <p class="source-section-copy">
                            Record where the information came from so the source package remains traceable and auditable.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="source-field-grid two">
                    <div class="source-field">
                        <label for="source_book">Source Book / File *</label>
                        <input id="source_book" name="source_book" value="{{ old('source_book') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                    </div>

                    <div class="source-field">
                        <label for="page_number">Page Number</label>
                        <input id="page_number" name="page_number" value="{{ old('page_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="source-field">
                        <label for="transcribed_by">Transcribed By *</label>
                        <input id="transcribed_by" name="transcribed_by" value="{{ old('transcribed_by', auth()->user()->name) }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                    </div>

                    <div class="source-field">
                        <label for="transcription_date">Transcription Date *</label>
                        <input id="transcription_date" type="date" name="transcription_date" value="{{ old('transcription_date', now()->toDateString()) }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                    </div>

                    <div class="source-field">
                        <label for="remarks">Remarks</label>
                        <textarea id="remarks" name="remarks" rows="2" class="w-full rounded-lg border-gray-300 text-sm">{{ old('remarks') }}</textarea>
                    </div>

                    <div class="source-field">
                        <label for="source_notes">Source Notes</label>
                        <textarea id="source_notes" name="source_notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm">{{ old('source_notes') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="source-form-section source-footer-actions">
                <div class="source-footer-note-wrap">
                    <span class="source-footer-icon" aria-hidden="true">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>

                    <div>
                        <p class="source-footer-title">Ready to save source package</p>
                        <p class="source-footer-note">
                            Saving creates documentary/provenance source records only. It does not create legal ownership, assign parcels, or mutate registry records.
                        </p>
                    </div>
                </div>

                <div class="source-footer-buttons">
                    <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
                        Cancel
                    </a>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Source Package
                    </button>
                </div>
            </section>
        </form>


        <script data-source-parcel-autofill-script>
            document.addEventListener('DOMContentLoaded', function () {
                const select = document.querySelector('[data-source-parcel-autofill]');
                if (!select) return;
                const fillIfBlank = function (id, value) {
                    const field = document.getElementById(id);
                    if (field && value && !field.value) field.value = value;
                };
                select.addEventListener('change', function () {
                    const option = select.options[select.selectedIndex];
                    if (!option) return;
                    fillIfBlank('parcel_code', option.dataset.parcelCode || '');
                    fillIfBlank('title_number', option.dataset.title || '');
                    fillIfBlank('area_hectares', option.dataset.area ? parseFloat(option.dataset.area).toFixed(4) : '');
                    fillIfBlank('province', option.dataset.province || 'Negros Oriental');
                    fillIfBlank('municipality', option.dataset.municipality || '');
                    fillIfBlank('barangay', option.dataset.barangay || '');
                });
            });
        </script>

    </div>


    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const validationErrors = @json($errors->toArray());
                const form = document.querySelector('[data-source-validation-form]');

                if (!form || !validationErrors) {
                    return;
                }

                let firstInvalidElement = null;

                Object.entries(validationErrors).forEach(function ([fieldName, messages]) {
                    const fields = Array.from(form.querySelectorAll('[name]')).filter(function (field) {
                        return field.name === fieldName || field.name === fieldName + '[]';
                    });

                    if (!fields.length) {
                        return;
                    }

                    fields.forEach(function (field) {
                        const isCheckboxOrRadio = field.type === 'checkbox' || field.type === 'radio';
                        const wrapper = field.closest('.source-field, .source-field-wide, .source-validation-field, .source-form-field, div') || field.parentElement;
                        const message = Array.isArray(messages) ? messages[0] : messages;

                        if (!firstInvalidElement) {
                            firstInvalidElement = field;
                        }

                        field.setAttribute('aria-invalid', 'true');

                        if (isCheckboxOrRadio) {
                            const optionCard = field.closest('.source-option-card');
                            if (optionCard) {
                                optionCard.classList.add('has-error', 'source-invalid-input');
                            }
                        } else {
                            field.classList.add('source-invalid-input');
                        }

                        if (wrapper) {
                            wrapper.classList.add('has-error');

                            if (!wrapper.querySelector('[data-validation-for="' + fieldName + '"]')) {
                                const errorLine = document.createElement('p');
                                errorLine.className = 'source-inline-error';
                                errorLine.dataset.validationFor = fieldName;
                                errorLine.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i><span>' + message + '</span>';
                                wrapper.appendChild(errorLine);
                            }
                        }
                    });
                });

                if (firstInvalidElement) {
                    setTimeout(function () {
                        firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        if (typeof firstInvalidElement.focus === 'function' && firstInvalidElement.type !== 'hidden') {
                            firstInvalidElement.focus({ preventScroll: true });
                        }
                    }, 120);
                }
            });
        </script>
    @endif

</x-staff-shell>
