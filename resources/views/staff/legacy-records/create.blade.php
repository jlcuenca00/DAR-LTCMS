<x-staff-shell
    title="Encode Single Source Record"
    active="source-records"
    maxWidth="max-w-7xl"
>
    <x-slot name="actions">
        <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Archive
        </a>
        <a href="{{ route('staff.source-record-packages.create') }}" class="staff-button staff-button-primary">
            <i class="fa-solid fa-layer-group"></i>
            Encode Package Instead
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .single-source-page {
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
                max-width: 840px;
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

            .source-type-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 9px;
            }

            .source-type-card {
                display: grid;
                gap: 8px;
                min-height: 86px;
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                border-radius: 12px;
                padding: 13px 14px;
                text-decoration: none;
                transition: 150ms ease;
            }

            .source-type-card:hover {
                border-color: #86efac;
                background: #f0fdf4;
                transform: translateY(-1px);
            }

            .source-type-card.is-active {
                border-color: #86efac;
                background: #f0fdf4;
                box-shadow: inset 0 0 0 1px rgba(22, 101, 52, 0.08);
            }

            .source-type-title {
                display: flex;
                align-items: center;
                gap: 9px;
                color: #14532d;
                font-size: 13px;
                font-weight: 900;
            }

            .source-type-icon {
                width: 26px;
                height: 26px;
                border-radius: 9px;
                border: 1px solid #bbf7d0;
                background: #ecfdf5;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
            }

            .source-type-copy {
                margin: 0;
                color: #64748b;
                font-size: 12px;
                line-height: 1.45;
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
            .source-option-card.has-error,
            .source-type-card.source-invalid-input,
            .source-type-card.has-error {
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

            @media (max-width: 1100px) {
                .source-type-grid,
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

                .source-type-grid,
                .source-field-grid,
                .source-field-grid.two {
                    grid-template-columns: 1fr;
                }

                .source-footer-note-wrap {
                    max-width: none;
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

    <div class="single-source-page">

        <section class="staff-panel source-form-panel">
            <div class="source-form-section">
                <div class="source-section-header">
                    <div class="source-section-heading">
                        <span class="source-step-number"><i class="fa-solid fa-list-check"></i></span>
                        <div>
                            <h2 class="source-section-title">Choose Source Record Type</h2>
                            <p class="source-section-copy">
                                Select the documentary record type being indexed. The selected type controls the required fields below.
                            </p>
                        </div>
                    </div>
                    <span class="source-section-chip">One record type</span>
                </div>

                <div class="source-type-grid">
                    @foreach ($recordTypes as $value => $label)
                        <a href="{{ route('staff.legacy-records.create', ['record_type' => $value]) }}"
                           class="source-type-card {{ $recordType === $value ? 'is-active' : '' }}">
                            <div class="source-type-title">
                                <span class="source-type-icon">
                                    <i class="fa-solid {{ $value === 'title' ? 'fa-file-signature' : ($value === 'landholding' ? 'fa-seedling' : ($value === 'parcel_source' ? 'fa-map-location-dot' : 'fa-stamp')) }}"></i>
                                </span>
                                {{ $label }}
                            </div>
                            <p class="source-type-copy">
                                @if ($value === 'title')
                                    Title number and registered owner reference.
                                @elseif ($value === 'landholding')
                                    Landholding reference, owner, area, and support details.
                                @elseif ($value === 'parcel_source')
                                    Parcel code, lot/survey details, and optional geometry.
                                @else
                                    Historical or previous clearance reference data.
                                @endif
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('staff.legacy-records.store') }}" class="staff-panel source-form-panel" data-source-validation-form>
            @csrf
            <input type="hidden" name="record_type" value="{{ $recordType }}">

            <section class="source-form-section">
                <div class="source-section-header">
                    <div class="source-section-heading">
                        <span class="source-step-number">1</span>
                        <div>
                            <h2 class="source-section-title">Primary Source Details</h2>
                            <p class="source-section-copy">
                                Encode only the key reference data needed for staff review, indexing, and traceability.
                            </p>
                        </div>
                    </div>
                    <span class="source-section-chip">{{ $recordTypes[$recordType] ?? 'Source Record' }}</span>
                </div>

                <div class="source-field-grid two">
                    @if ($recordType === 'title')
                        <div class="source-field">
                            <label for="title_number">Title Number *</label>
                            <input id="title_number" name="title_number" value="{{ old('title_number') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="source-field">
                            <label for="landowner_name">Registered Owner / Landowner Name *</label>
                            <input id="landowner_name" name="landowner_name" value="{{ old('landowner_name') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
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
                            <label for="record_date">Title / Registration Date</label>
                            <input id="record_date" type="date" name="record_date" value="{{ old('record_date') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    @endif

                    @if ($recordType === 'landholding')
                        <div class="source-field">
                            <label for="landholding_reference_number">Landholding Reference Number *</label>
                            <input id="landholding_reference_number" name="landholding_reference_number" value="{{ old('landholding_reference_number') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="source-field">
                            <label for="landowner_name">Landowner Name *</label>
                            <input id="landowner_name" name="landowner_name" value="{{ old('landowner_name') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="source-field">
                            <label for="title_number">Title Number</label>
                            <input id="title_number" name="title_number" value="{{ old('title_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="source-field">
                            <label for="lot_number">Lot Number</label>
                            <input id="lot_number" name="lot_number" value="{{ old('lot_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="source-field">
                            <label for="previous_dar_reference_number">Previous DAR Reference Number</label>
                            <input id="previous_dar_reference_number" name="previous_dar_reference_number" value="{{ old('previous_dar_reference_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="source-field">
                            <label for="record_date">Record Date</label>
                            <input id="record_date" type="date" name="record_date" value="{{ old('record_date') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    @endif

                    @if ($recordType === 'parcel_source')
                        <div class="source-field">
                            <label for="parcel_code">Parcel Reference Code *</label>
                            <input id="parcel_code" name="parcel_code" value="{{ old('parcel_code') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="source-field">
                            <label for="landowner_name">Owner / Landowner Name *</label>
                            <input id="landowner_name" name="landowner_name" value="{{ old('landowner_name') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="source-field">
                            <label for="lot_number">Lot Number *</label>
                            <input id="lot_number" name="lot_number" value="{{ old('lot_number') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="source-field">
                            <label for="survey_number">Survey Number</label>
                            <input id="survey_number" name="survey_number" value="{{ old('survey_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="source-field">
                            <label for="title_number">Title Number</label>
                            <input id="title_number" name="title_number" value="{{ old('title_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    @endif

                    @if ($recordType === 'historical_clearance')
                        <div class="source-field">
                            <label for="control_number">Clearance Control Number *</label>
                            <input id="control_number" name="control_number" value="{{ old('control_number') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="source-field">
                            <label for="transferor_name">Transferor Name *</label>
                            <input id="transferor_name" name="transferor_name" value="{{ old('transferor_name') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="source-field">
                            <label for="transferee_name">Transferee Name *</label>
                            <input id="transferee_name" name="transferee_name" value="{{ old('transferee_name') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="source-field">
                            <label for="record_date">Clearance / Record Date *</label>
                            <input id="record_date" type="date" name="record_date" value="{{ old('record_date') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="source-field">
                            <label for="application_reference_number">Application Reference Number</label>
                            <input id="application_reference_number" name="application_reference_number" value="{{ old('application_reference_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="source-field">
                            <label for="lot_number">Lot Number</label>
                            <input id="lot_number" name="lot_number" value="{{ old('lot_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    @endif
                </div>
            </section>

            <section class="source-form-section">
                <div class="source-section-header">
                    <div class="source-section-heading">
                        <span class="source-step-number">2</span>
                        <div>
                            <h2 class="source-section-title">Scope and Parcel Link</h2>
                            <p class="source-section-copy">
                                Link only when staff has confirmed that this source record refers to an existing main Parcel Record.
                            </p>
                        </div>
                    </div>
                    <span class="source-section-chip">Confirmed links only</span>
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
                        <select id="parcel_id" name="parcel_id" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Not linked yet</option>
                            @foreach ($parcels as $parcel)
                                <option value="{{ $parcel->id }}" @selected((string) old('parcel_id') === (string) $parcel->id)>
                                    {{ $parcel->parcel_code }}
                                    @if ($parcel->title_no) — {{ $parcel->title_no }} @endif
                                    @if ($parcel->barangay || $parcel->municipality) — {{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="source-field-wide">
                        <p class="source-help-box">
                            Linking attaches documentary support only. It does not change ownership, assign the parcel to another person, or mutate any registry record.
                        </p>
                    </div>
                </div>
            </section>

            <section class="source-form-section">
                <div class="source-section-header">
                    <div class="source-section-heading">
                        <span class="source-step-number">3</span>
                        <div>
                            <h2 class="source-section-title">Parcel / Location Information</h2>
                            <p class="source-section-copy">
                                Optional supporting location, area, and land-use details copied from the source document.
                            </p>
                        </div>
                    </div>
                    <span class="source-section-chip">Optional support data</span>
                </div>

                <div class="source-field-grid">
                    <div class="source-field">
                        <label for="area_hectares">Area in Hectares</label>
                        <input id="area_hectares" type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="source-field">
                        <label for="crop_or_land_use">Land Use / Title Reference Notation</label>
                        <input id="crop_or_land_use" name="crop_or_land_use" value="{{ old('crop_or_land_use') }}" placeholder="e.g. private agricultural land reference, CLOA reference, EP reference" class="w-full rounded-lg border-gray-300 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Reference notation only. This does not classify the parcel for approval, prove ownership, or mutate registry records.</p>
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
                        <span class="source-step-number">4</span>
                        <div>
                            <h2 class="source-section-title">Reference Geometry</h2>
                            <p class="source-section-copy">
                                Geometry is stored only as source reference data unless staff later creates or links a confirmed main Parcel Record.
                            </p>
                        </div>
                    </div>
                    <span class="source-section-chip">Reference only</span>
                </div>

                <div class="source-field-grid two">
                    <div class="source-field-wide">
                        <label for="boundary_description">Boundary / Technical Description Notes</label>
                        <textarea id="boundary_description" name="boundary_description" rows="2" class="w-full rounded-lg border-gray-300 text-sm">{{ old('boundary_description') }}</textarea>
                    </div>

                    <div class="source-field-wide">
                        <label for="source_geometry_geojson">Source GeoJSON Geometry</label>
                        <textarea id="source_geometry_geojson" name="source_geometry_geojson" rows="4" class="w-full rounded-lg border-gray-300 text-xs" placeholder='Example polygon: {"type":"Polygon","coordinates":[[[123.3048,9.3064],[123.3058,9.3064],[123.3058,9.3072],[123.3048,9.3072],[123.3048,9.3064]]]}' >{{ old('source_geometry_geojson') }}</textarea>
                        <p class="source-help-box mt-2">
                            Source geometry entered here remains documentary/reference geometry. It does not automatically appear as an official parcel or change map ownership visibility.
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
                                Record where the information came from so the source record remains traceable and auditable.
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
                        <p class="source-footer-title">Ready to save source record</p>
                        <p class="source-footer-note">
                            Saving creates one indexed documentary/provenance record for staff review and traceability only. It does not transfer ownership, assign parcels, or mutate registry records.
                        </p>
                    </div>
                </div>

                <div class="source-footer-buttons">
                    <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
                        Cancel
                    </a>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Source Record
                    </button>
                </div>
            </section>
        </form>
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
