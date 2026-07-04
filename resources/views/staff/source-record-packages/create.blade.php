<x-staff-shell
    title="Source Package Workspace"
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
                gap: 12px;
            }

            .source-form-panel {
                overflow: hidden;
                border-radius: 16px;
            }

            .source-intro-card {
                padding: 16px 20px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                display: flex;
                justify-content: space-between;
                gap: 16px;
                align-items: center;
            }

            .source-intro-title {
                margin: 0;
                font-size: 17px;
                font-weight: 900;
                color: #111827;
            }

            .source-intro-copy {
                margin: 4px 0 0;
                max-width: 880px;
                color: #64748b;
                font-size: 12.5px;
                line-height: 1.45;
            }

            .source-intro-chip {
                flex: 0 0 auto;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #166534;
                border-radius: 999px;
                padding: 6px 11px;
                font-size: 11px;
                font-weight: 900;
                white-space: nowrap;
            }

            .source-choice-panel {
                padding: 18px 20px 20px;
                display: grid;
                gap: 14px;
                background: #ffffff;
            }

            .source-choice-header {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                align-items: center;
                padding: 13px 14px;
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                border-radius: 14px;
            }

            .source-choice-title {
                margin: 0;
                font-size: 16px;
                font-weight: 900;
                color: #111827;
            }

            .source-choice-copy {
                margin: 4px 0 0;
                color: #64748b;
                font-size: 12.5px;
                line-height: 1.45;
            }

            .source-choice-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
                gap: 10px;
            }

            .source-choice-card {
                position: relative;
                min-height: 96px;
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                border-radius: 14px;
                padding: 13px;
                cursor: pointer;
                display: grid;
                grid-template-columns: auto minmax(0, 1fr);
                grid-template-areas:
                    "icon name"
                    "icon note";
                gap: 4px 11px;
                align-items: start;
                transition: 150ms ease;
            }

            .source-choice-card:hover {
                border-color: #86efac;
                background: #f0fdf4;
                transform: translateY(-1px);
            }

            .source-choice-card input[type="radio"] {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            .source-choice-card.is-selected {
                border-color: #15803d;
                background: #ecfdf5;
                box-shadow: 0 10px 24px rgba(21, 128, 61, 0.12);
            }

            .source-choice-icon {
                grid-area: icon;
                width: 36px;
                height: 36px;
                border-radius: 11px;
                border: 1px solid #bbf7d0;
                background: #ffffff;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .source-choice-name {
                grid-area: name;
                margin: 0;
                color: #111827;
                font-size: 13px;
                font-weight: 900;
                line-height: 1.25;
            }

            .source-choice-note {
                grid-area: note;
                margin: 0;
                color: #64748b;
                font-size: 11.5px;
                line-height: 1.4;
            }

            .source-choice-actions {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                border-top: 1px solid #e5e7eb;
                padding-top: 14px;
            }

            .source-choice-error {
                display: none;
                color: #991b1b;
                font-size: 12px;
                font-weight: 800;
            }

            .source-choice-error.is-visible {
                display: inline-flex;
                gap: 6px;
                align-items: center;
            }

            .source-workspace-body {
                display: none;
            }

            .source-workspace-body.is-visible {
                display: block;
                background: #ffffff;
            }

            .source-form-section {
                padding: 16px 20px;
                border-bottom: 1px solid #e5e7eb;
                display: grid;
                gap: 12px;
                background: #ffffff;
                position: relative;
            }

            .source-form-section:last-child {
                border-bottom: 0;
            }

            .source-section-header {
                display: flex;
                justify-content: space-between;
                gap: 14px;
                align-items: center;
            }

            .source-section-heading {
                display: flex;
                align-items: center;
                gap: 10px;
                min-width: 0;
            }

            .source-step-number {
                width: 28px;
                height: 28px;
                border-radius: 9px;
                border: 1px solid #bbf7d0;
                background: #ecfdf5;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 12.5px;
                font-weight: 900;
                flex: 0 0 auto;
            }

            .source-section-title {
                margin: 0;
                font-size: 15px;
                line-height: 1.25;
                font-weight: 900;
                color: #111827;
            }

            .source-section-copy {
                margin: 2px 0 0;
                color: #64748b;
                font-size: 12px;
                line-height: 1.4;
                max-width: 820px;
            }

            .source-section-chip,
            .source-selected-chip {
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

            .source-package-type-bar {
                padding: 12px 20px;
                border-bottom: 1px solid #e5e7eb;
                background: #f8fafc;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 14px;
            }

            .source-package-type-title {
                margin: 0;
                color: #111827;
                font-size: 13px;
                font-weight: 900;
            }

            .source-package-type-copy {
                margin: 2px 0 0;
                color: #64748b;
                font-size: 12px;
                line-height: 1.4;
            }

            .source-package-type-actions {
                display: flex;
                align-items: center;
                gap: 10px;
                flex: 0 0 auto;
            }

            .source-option-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

            .source-option-card span {
                font-size: 13px;
                font-weight: 900;
                color: #14532d;
            }

            .source-field-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
                gap: 11px 12px;
            }

            .source-field-grid.two {
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
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
                min-height: 38px;
            }

            .source-field textarea,
            .source-field-wide textarea {
                resize: vertical;
            }

            .source-field.is-hidden,
            .source-field-wide.is-hidden,
            .source-conditional-section.is-hidden,
            .source-option-grid.is-hidden {
                display: none !important;
            }

            .source-file-card {
                border: 1px solid #bbf7d0;
                background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
                border-radius: 14px;
                padding: 14px;
                display: grid;
                grid-template-columns: minmax(0, 1.1fr) minmax(260px, 0.9fr);
                gap: 14px;
                align-items: stretch;
            }

            .source-file-upload-box {
                border: 1px dashed #86efac;
                background: #ffffff;
                border-radius: 14px;
                padding: 16px;
                display: grid;
                gap: 10px;
                align-content: start;
            }

            .source-file-upload-box label {
                font-size: 10.5px !important;
                font-weight: 900 !important;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: #14532d !important;
            }

            .source-file-upload-box input[type="file"] {
                width: 100%;
                color: #475569;
                font-size: 13px;
                cursor: pointer;
            }

            .source-file-upload-box input[type="file"]::file-selector-button {
                margin-right: 12px;
                border: 1px solid #166534;
                border-radius: 9px;
                background: #166534;
                color: #ffffff;
                padding: 9px 13px;
                font-size: 12px;
                font-weight: 900;
                cursor: pointer;
                transition: 150ms ease;
            }

            .source-file-upload-box input[type="file"]::file-selector-button:hover {
                background: #14532d;
                border-color: #14532d;
            }

            .source-file-note {
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                color: #14532d;
                border-radius: 14px;
                padding: 16px;
                font-size: 12.5px;
                line-height: 1.55;
                display: grid;
                grid-template-columns: auto minmax(0, 1fr);
                gap: 11px;
                align-items: flex-start;
            }

            .source-file-note i {
                margin-top: 2px;
                color: #166534;
            }

            .source-file-note strong {
                display: block;
                margin-bottom: 4px;
                font-family: var(--heading-font);
                font-size: 13px;
                color: #064e3b;
            }

            .source-file-help {
                margin: 0;
                color: #64748b;
                font-size: 11.5px;
                line-height: 1.45;
            }

            .source-footer-actions {
                padding: 13px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 14px;
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



            .source-form-section:not(.source-footer-actions):hover {
                background: #ffffff;
            }

            .source-conditional-section.is-hidden .source-step-number {
                display: none;
            }

            .source-choice-card.is-selected::after {
                content: "Selected";
                position: absolute;
                right: 10px;
                top: 10px;
                border-radius: 999px;
                background: #15803d;
                color: #ffffff;
                font-size: 9.5px;
                font-weight: 900;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                padding: 4px 7px;
            }

            @media (max-width: 900px) {
                .source-file-card {
                    grid-template-columns: 1fr;
                }

                .source-choice-grid,
                .source-option-grid,
                .source-field-grid,
                .source-field-grid.two {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 720px) {
                .source-intro-card,
                .source-choice-header,
                .source-section-header,
                .source-section-heading,
                .source-package-type-bar,
                .source-footer-actions {
                    flex-direction: column;
                    align-items: stretch;
                }

                .source-form-section,
                .source-choice-panel,
                .source-intro-card,
                .source-package-type-bar,
                .source-footer-actions {
                    padding-left: 18px;
                    padding-right: 18px;
                }

                .source-choice-grid,
                .source-option-grid,
                .source-field-grid,
                .source-field-grid.two {
                    grid-template-columns: 1fr;
                }

                .source-footer-buttons,
                .source-package-type-actions,
                .source-choice-actions {
                    flex-direction: column-reverse;
                    align-items: stretch;
                    width: 100%;
                }

                .source-footer-buttons .staff-button,
                .source-package-type-actions .staff-button,
                .source-choice-actions .staff-button {
                    width: 100%;
                    min-width: 0;
                    justify-content: center;
                }
            }
        </style>
    </x-slot>

    @php
        $includeTitle = old('include_title') === '1' || old('include_title') === 1 || old('include_title') === true;
        $includeLandholding = old('include_landholding') === '1' || old('include_landholding') === 1 || old('include_landholding') === true;
        $includeParcelSource = old('include_parcel_source') === '1' || old('include_parcel_source') === 1 || old('include_parcel_source') === true;
        $includeHistoricalClearance = old('include_historical_clearance') === '1' || old('include_historical_clearance') === 1 || old('include_historical_clearance') === true;

        $oldMode = old('source_package_mode');
        if (! $oldMode && $includeTitle && ! $includeLandholding && ! $includeParcelSource && ! $includeHistoricalClearance) {
            $oldMode = 'title';
        } elseif (! $oldMode && ! $includeTitle && $includeLandholding && ! $includeParcelSource && ! $includeHistoricalClearance) {
            $oldMode = 'landholding';
        } elseif (! $oldMode && ! $includeTitle && ! $includeLandholding && $includeParcelSource && ! $includeHistoricalClearance) {
            $oldMode = 'parcel_source';
        } elseif (! $oldMode && ! $includeTitle && ! $includeLandholding && ! $includeParcelSource && $includeHistoricalClearance) {
            $oldMode = 'historical_clearance';
        } elseif (! $oldMode && ($includeTitle || $includeLandholding || $includeParcelSource || $includeHistoricalClearance)) {
            $oldMode = 'combined';
        }

        $landUseReferenceOptions = [
            'Private agricultural land reference',
            'CLOA title reference',
            'Emancipation patent title reference',
            'CARP program reference',
            'Classification not yet verified from source',
        ];
    @endphp

    <div class="source-package-page">
        <form method="POST" action="{{ route('staff.source-record-packages.store') }}" enctype="multipart/form-data" class="staff-panel source-form-panel" data-source-validation-form data-autosave-key="source-package-create" data-autosave-label="source package encoding">
            @csrf

            <input type="hidden" name="source_package_mode" value="{{ $oldMode }}" data-source-package-mode>
            <input type="hidden" name="source_record_scope" value="{{ old('source_record_scope', 'current_active') }}" data-scope-value>
            <input type="hidden" name="include_title" value="{{ $includeTitle ? '1' : '0' }}" data-include-field="title">
            <input type="hidden" name="include_landholding" value="{{ $includeLandholding ? '1' : '0' }}" data-include-field="landholding">
            <input type="hidden" name="include_parcel_source" value="{{ $includeParcelSource ? '1' : '0' }}" data-include-field="parcel_source">
            <input type="hidden" name="include_historical_clearance" value="{{ $includeHistoricalClearance ? '1' : '0' }}" data-include-field="historical_clearance">

            <div class="source-intro-card">
                <div>
                    <h2 class="source-intro-title">Encode Source Package</h2>
                    <p class="source-intro-copy">
                        Start by choosing the kind of source record to encode. The form will show only the fields needed for that source type, so staff do not have to fill unrelated sections.
                    </p>
                </div>
                <span class="source-intro-chip">Step-by-step encoding</span>
            </div>

            @if ($errors->any())
                <div class="source-form-section">
                    <div class="source-validation-summary">
                        <p class="source-validation-title"><i class="fa-solid fa-circle-exclamation"></i> Please review the highlighted fields</p>
                        <ul class="source-validation-list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <section class="source-choice-panel" data-source-choice-panel>
                <div class="source-choice-header">
                    <div>
                        <h2 class="source-choice-title">What do you want to create a source record from?</h2>
                        <p class="source-choice-copy">Choose one path first. The next form starts at Step 1 and only shows fields needed for that source type.</p>
                    </div>
                    <span class="source-selected-chip" data-selected-label>{{ $oldMode ? 'Selection loaded' : 'No selection yet' }}</span>
                </div>

                <div class="source-choice-grid" role="radiogroup" aria-label="Source package type">
                    <label class="source-choice-card" data-source-choice-card data-mode="title">
                        <input type="radio" name="source_type_choice" value="title" @checked($oldMode === 'title')>
                        <span class="source-choice-icon"><i class="fa-solid fa-file-contract"></i></span>
                        <span class="source-choice-name">Title Source</span>
                        <span class="source-choice-note">For title number or title reference encoding.</span>
                    </label>

                    <label class="source-choice-card" data-source-choice-card data-mode="landholding">
                        <input type="radio" name="source_type_choice" value="landholding" @checked($oldMode === 'landholding')>
                        <span class="source-choice-icon"><i class="fa-solid fa-layer-group"></i></span>
                        <span class="source-choice-name">Landholding Source</span>
                        <span class="source-choice-note">For landholding reference and area details.</span>
                    </label>

                    <label class="source-choice-card" data-source-choice-card data-mode="parcel_source">
                        <input type="radio" name="source_type_choice" value="parcel_source" @checked($oldMode === 'parcel_source')>
                        <span class="source-choice-icon"><i class="fa-solid fa-map-location-dot"></i></span>
                        <span class="source-choice-name">Parcel Source</span>
                        <span class="source-choice-note">For parcel reference, location, and geometry details.</span>
                    </label>

                    <label class="source-choice-card" data-source-choice-card data-mode="historical_clearance">
                        <input type="radio" name="source_type_choice" value="historical_clearance" @checked($oldMode === 'historical_clearance')>
                        <span class="source-choice-icon"><i class="fa-solid fa-stamp"></i></span>
                        <span class="source-choice-name">Historical Clearance</span>
                        <span class="source-choice-note">For old clearance control and party references.</span>
                    </label>

                    <label class="source-choice-card" data-source-choice-card data-mode="combined">
                        <input type="radio" name="source_type_choice" value="combined" @checked($oldMode === 'combined')>
                        <span class="source-choice-icon"><i class="fa-solid fa-folder-tree"></i></span>
                        <span class="source-choice-name">Combined Package</span>
                        <span class="source-choice-note">For one file with multiple related source sections.</span>
                    </label>
                </div>

                <div class="source-choice-actions">
                    <span class="source-choice-error" data-source-choice-error><i class="fa-solid fa-circle-exclamation"></i> Select a source type first.</span>
                    <button type="button" class="staff-button staff-button-primary" data-source-continue>
                        Continue
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </section>

            <div class="source-workspace-body" data-source-workspace-body>
                <div class="source-package-type-bar">
                    <div>
                        <p class="source-package-type-title" data-workspace-title>Source record form</p>
                        <p class="source-package-type-copy" data-workspace-copy>Only relevant fields are shown for the selected source type.</p>
                    </div>
                    <div class="source-package-type-actions">
                        <span class="source-selected-chip" data-workspace-chip>Selected source</span>
                        <button type="button" class="staff-button staff-button-light" data-source-change>
                            <i class="fa-solid fa-rotate-left"></i>
                            Change Type
                        </button>
                    </div>
                </div>

                <section class="source-form-section source-conditional-section" data-combined-section>
                    <div class="source-section-header">
                        <div class="source-section-heading">
                            <span class="source-step-number">1</span>
                            <div>
                                <h2 class="source-section-title">Select Package Sections</h2>
                                <p class="source-section-copy">
                                    Use this only for a combined package. Check only the sections present in the source file.
                                </p>
                            </div>
                        </div>
                        <span class="source-section-chip">Combined only</span>
                    </div>

                    <div class="source-option-grid" data-combined-options>
                        <label class="source-option-card">
                            <input type="checkbox" value="title" data-combined-toggle="title" @checked($includeTitle)>
                            <span>Title</span>
                        </label>

                        <label class="source-option-card">
                            <input type="checkbox" value="landholding" data-combined-toggle="landholding" @checked($includeLandholding)>
                            <span>Landholding</span>
                        </label>

                        <label class="source-option-card">
                            <input type="checkbox" value="parcel_source" data-combined-toggle="parcel_source" @checked($includeParcelSource)>
                            <span>Parcel Source</span>
                        </label>

                        <label class="source-option-card">
                            <input type="checkbox" value="historical_clearance" data-combined-toggle="historical_clearance" @checked($includeHistoricalClearance)>
                            <span>Historical Clearance</span>
                        </label>
                    </div>
                </section>

                <section class="source-form-section">
                    <div class="source-section-header">
                        <div class="source-section-heading">
                            <span class="source-step-number">2</span>
                            <div>
                                <h2 class="source-section-title">Core Reference Details</h2>
                                <p class="source-section-copy">
                                    Basic source values for review, search, and linkage. Fields adjust based on the selected source type.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="source-field-grid">
                        <div class="source-field" data-field-for="all">
                            <label for="landowner_name">Landowner / Owner Name *</label>
                            <input id="landowner_name" name="landowner_name" value="{{ old('landowner_name') }}" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>

                        <div class="source-field" data-field-for="title parcel_source historical_clearance combined">
                            <label for="title_number">Title Number <span data-required-for="title">*</span></label>
                            <input id="title_number" name="title_number" value="{{ old('title_number') }}" placeholder="e.g. TCT-000123" class="w-full rounded-lg border-gray-300 text-sm" data-required-when="title">
                        </div>

                        <div class="source-field" data-field-for="landholding combined">
                            <label for="landholding_reference_number">Landholding Reference Number <span data-required-for="landholding">*</span></label>
                            <input id="landholding_reference_number" name="landholding_reference_number" value="{{ old('landholding_reference_number') }}" placeholder="e.g. LH-2026-001" class="w-full rounded-lg border-gray-300 text-sm" data-required-when="landholding">
                        </div>

                        <div class="source-field" data-field-for="parcel_source historical_clearance combined">
                            <label for="parcel_code">Parcel Reference Code <span data-required-for="parcel_source">*</span></label>
                            <input id="parcel_code" name="parcel_code" value="{{ old('parcel_code') }}" placeholder="e.g. SRC-PCL-003" class="w-full rounded-lg border-gray-300 text-sm" data-required-when="parcel_source">
                        </div>

                        <div class="source-field" data-field-for="historical_clearance combined">
                            <label for="control_number">Clearance Control Number <span data-required-for="historical_clearance">*</span></label>
                            <input id="control_number" name="control_number" value="{{ old('control_number') }}" class="w-full rounded-lg border-gray-300 text-sm" data-required-when="historical_clearance">
                        </div>

                        <div class="source-field" data-field-for="historical_clearance combined">
                            <label for="transferor_name">Transferor Name</label>
                            <input id="transferor_name" name="transferor_name" value="{{ old('transferor_name') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div class="source-field" data-field-for="historical_clearance combined">
                            <label for="transferee_name">Transferee Name</label>
                            <input id="transferee_name" name="transferee_name" value="{{ old('transferee_name') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div class="source-field" data-field-for="title landholding parcel_source combined">
                            <label for="lot_number">Lot Number</label>
                            <input id="lot_number" name="lot_number" value="{{ old('lot_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div class="source-field" data-field-for="title landholding parcel_source combined">
                            <label for="survey_number">Survey Number</label>
                            <input id="survey_number" name="survey_number" value="{{ old('survey_number') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div class="source-field" data-field-for="landholding parcel_source combined">
                            <label for="area_hectares">Area in Hectares</label>
                            <input id="area_hectares" type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div class="source-field" data-field-for="landholding parcel_source combined">
                            <label for="crop_or_land_use">Land Use / Title Reference Notation</label>
                            <select id="crop_or_land_use" name="crop_or_land_use" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                <option value="">Select reference notation</option>
                                @foreach ($landUseReferenceOptions as $classification)
                                    <option value="{{ $classification }}" @selected(old('crop_or_land_use') === $classification)>{{ $classification }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="source-field" data-field-for="title landholding parcel_source historical_clearance combined">
                            <label for="province">Province</label>
                            <input id="province" name="province" value="{{ old('province', 'Negros Oriental') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div class="source-field" data-field-for="landholding parcel_source historical_clearance combined">
                            <label for="municipality">Municipality</label>
                            <input id="municipality" name="municipality" value="{{ old('municipality') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div class="source-field" data-field-for="landholding parcel_source historical_clearance combined">
                            <label for="barangay">Barangay</label>
                            <input id="barangay" name="barangay" value="{{ old('barangay') }}" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    </div>
                </section>

                <section class="source-form-section source-conditional-section" data-panel-for="parcel_source combined">
                    <div class="source-section-header">
                        <div class="source-section-heading">
                            <span class="source-step-number">3</span>
                            <div>
                                <h2 class="source-section-title">Parcel Link and Reference Geometry</h2>
                                <p class="source-section-copy">
                                    Use this when the source is tied to a parcel reference or map-based information.
                                </p>
                            </div>
                        </div>
                        <span class="source-section-chip">Parcel source</span>
                    </div>

                    <div class="source-field-grid two">
                        <div class="source-field">
                            <label for="parcel_id">Link Existing Parcel Record</label>
                            <select id="parcel_id" name="parcel_id" class="w-full rounded-lg border-gray-300 text-sm" data-source-parcel-autofill>
                                <option value="">No linked parcel yet</option>
                                @foreach ($parcels as $parcel)
                                    <option
                                        value="{{ $parcel->id }}"
                                        data-parcel-code="{{ $parcel->parcel_code }}"
                                        data-title="{{ $parcel->title_no }}"
                                        data-municipality="{{ $parcel->municipality }}"
                                        data-barangay="{{ $parcel->barangay }}"
                                        @selected((string) old('parcel_id') === (string) $parcel->id)
                                    >
                                        {{ $parcel->parcel_code }}
                                        @if($parcel->title_no)
                                            — {{ $parcel->title_no }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="source-field">
                            <label for="source_record_scope">Source Record Scope *</label>
                            <select id="source_record_scope" class="w-full rounded-lg border-gray-300 text-sm" data-scope-select>
                                @foreach ($sourceScopes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('source_record_scope', 'current_active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="source-field-wide">
                            <label for="source_geometry_geojson">Reference Geometry GeoJSON</label>
                            <textarea id="source_geometry_geojson" name="source_geometry_geojson" rows="4" class="w-full rounded-lg border-gray-300 text-sm" placeholder='{"type":"Polygon","coordinates":[...] }'>{{ old('source_geometry_geojson') }}</textarea>
                        </div>

                        <div class="source-field-wide">
                            <label for="boundary_description">Boundary / Location Notes</label>
                            <textarea id="boundary_description" name="boundary_description" rows="3" class="w-full rounded-lg border-gray-300 text-sm">{{ old('boundary_description') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="source-form-section source-conditional-section" data-panel-for="title landholding historical_clearance">
                    <div class="source-section-header">
                        <div class="source-section-heading">
                            <span class="source-step-number">3</span>
                            <div>
                                <h2 class="source-section-title">Scope Reference</h2>
                                <p class="source-section-copy">
                                    Classify how this source record should be treated in the source archive.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="source-field-grid two">
                        <div class="source-field">
                            <label for="source_record_scope_alt">Source Record Scope *</label>
                            <select id="source_record_scope_alt" class="w-full rounded-lg border-gray-300 text-sm" data-scope-mirror>
                                @foreach ($sourceScopes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('source_record_scope', 'current_active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section class="source-form-section">
                    <div class="source-section-header">
                        <div class="source-section-heading">
                            <span class="source-step-number">4</span>
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

                <section class="source-form-section">
                    <div class="source-section-header">
                        <div class="source-section-heading">
                            <span class="source-step-number">5</span>
                            <div>
                                <h2 class="source-section-title">Source Scan / Reference File</h2>
                                <p class="source-section-copy">
                                    Attach the scanned source document, PDF, or image that supports the encoded source record.
                                </p>
                            </div>
                        </div>
                        <span class="source-section-chip">Recommended</span>
                    </div>

                    <div class="source-file-card">
                        <div class="source-file-upload-box">
                            <label for="source_file">Upload source file</label>
                            <input id="source_file" type="file" name="source_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                            <p class="source-file-help">Accepted files: PDF, JPG, JPEG, PNG. Maximum file size: 10 MB.</p>
                            <p class="source-file-help">This can be left blank during encoding. The source file can still be attached later from the Source Package Details page.</p>
                        </div>

                        <div class="source-file-note">
                            <i class="fa-solid fa-circle-info"></i>
                            <div>
                                <strong>Digitization reference</strong>
                                Attach the scanned source document when available. This supports traceability and later office review.
                            </div>
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
                                Saving creates the selected source record under one traceable source package for review, search, and parcel/reference linking.
                            </p>
                        </div>
                    </div>

                    <div class="source-footer-buttons">
                        <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
                            Cancel
                        </a>
                        <button type="submit" class="staff-button staff-button-primary" data-submit-source-package>
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Source Package
                        </button>
                    </div>
                </section>
            </div>
        </form>

        <script data-source-package-wizard-script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.querySelector('[data-source-validation-form]');
                if (!form) return;

                const choicePanel = form.querySelector('[data-source-choice-panel]');
                const workspaceBody = form.querySelector('[data-source-workspace-body]');
                const modeInput = form.querySelector('[data-source-package-mode]');
                const includeFields = {
                    title: form.querySelector('[data-include-field="title"]'),
                    landholding: form.querySelector('[data-include-field="landholding"]'),
                    parcel_source: form.querySelector('[data-include-field="parcel_source"]'),
                    historical_clearance: form.querySelector('[data-include-field="historical_clearance"]'),
                };

                const labels = {
                    title: 'Title Source',
                    landholding: 'Landholding Source',
                    parcel_source: 'Parcel Source',
                    historical_clearance: 'Historical Clearance Source',
                    combined: 'Combined Source Package',
                };

                const descriptions = {
                    title: 'Only title-related fields are shown. Use this for title number or title reference encoding.',
                    landholding: 'Only landholding-related fields are shown. Use this for landholding reference and area details.',
                    parcel_source: 'Parcel, location, link, and reference geometry fields are shown for parcel-based source review.',
                    historical_clearance: 'Clearance control and transfer party fields are shown for historical clearance references.',
                    combined: 'Choose the package sections included in the source file. The form updates based on your checked sections.',
                };

                const selectedLabel = form.querySelector('[data-selected-label]');
                const workspaceTitle = form.querySelector('[data-workspace-title]');
                const workspaceCopy = form.querySelector('[data-workspace-copy]');
                const workspaceChip = form.querySelector('[data-workspace-chip]');
                const choiceError = form.querySelector('[data-source-choice-error]');
                const combinedSection = form.querySelector('[data-combined-section]');
                const combinedToggles = Array.from(form.querySelectorAll('[data-combined-toggle]'));
                const choiceCards = Array.from(form.querySelectorAll('[data-source-choice-card]'));
                const fields = Array.from(form.querySelectorAll('[data-field-for]'));
                const panels = Array.from(form.querySelectorAll('[data-panel-for]'));
                const requiredFields = Array.from(form.querySelectorAll('[data-required-when]'));
                const sourceScopeValue = form.querySelector('[data-scope-value]');
                const sourceScope = form.querySelector('[data-scope-select]');
                const sourceScopeMirror = form.querySelector('[data-scope-mirror]');

                const sectionKeys = ['title', 'landholding', 'parcel_source', 'historical_clearance'];

                const getMode = function () {
                    const checked = form.querySelector('input[name="source_type_choice"]:checked');
                    return checked ? checked.value : (modeInput.value || '');
                };

                const setIncludeValues = function (activeSections) {
                    sectionKeys.forEach(function (key) {
                        if (includeFields[key]) {
                            includeFields[key].value = activeSections.includes(key) ? '1' : '0';
                        }
                    });
                };

                const activeSectionsForMode = function (mode) {
                    if (mode === 'combined') {
                        const checked = combinedToggles.filter(function (toggle) { return toggle.checked; }).map(function (toggle) { return toggle.value; });
                        return checked.length ? checked : [];
                    }
                    return sectionKeys.includes(mode) ? [mode] : [];
                };

                const elementApplies = function (element, mode, activeSections) {
                    const value = element.dataset.fieldFor || element.dataset.panelFor || '';
                    if (!value) return true;
                    const tokens = value.split(/\s+/).filter(Boolean);
                    if (tokens.includes('all')) return true;
                    if (mode === 'combined') {
                        return tokens.includes('combined') || activeSections.some(function (section) { return tokens.includes(section); });
                    }
                    return tokens.includes(mode);
                };

                const setNestedDisabled = function (container, disabled) {
                    Array.from(container.querySelectorAll('input, select, textarea')).forEach(function (input) {
                        if (input.matches('[data-include-field], [data-source-package-mode]')) return;
                        if (input.name === 'source_type_choice' || input.matches('[data-combined-toggle]')) return;
                        if (input.matches('[data-scope-mirror]')) return;
                        input.disabled = disabled;
                    });
                };

                const updateRequiredFields = function (activeSections) {
                    requiredFields.forEach(function (field) {
                        const key = field.dataset.requiredWhen;
                        field.required = activeSections.includes(key);
                    });
                };

                const syncScope = function (fromMirror) {
                    if (!sourceScopeValue) return;

                    if (fromMirror && sourceScopeMirror) {
                        sourceScopeValue.value = sourceScopeMirror.value;
                        if (sourceScope) sourceScope.value = sourceScopeMirror.value;
                        return;
                    }

                    if (sourceScope && sourceScope.offsetParent !== null) {
                        sourceScopeValue.value = sourceScope.value;
                        if (sourceScopeMirror) sourceScopeMirror.value = sourceScope.value;
                        return;
                    }

                    if (sourceScopeMirror) {
                        sourceScopeValue.value = sourceScopeMirror.value;
                        if (sourceScope) sourceScope.value = sourceScopeMirror.value;
                    }
                };

                const renumberVisibleSteps = function () {
                    if (!workspaceBody) return;
                    let step = 1;
                    Array.from(workspaceBody.querySelectorAll('.source-form-section')).forEach(function (section) {
                        const marker = section.querySelector('.source-step-number');
                        if (!marker || section.classList.contains('is-hidden')) return;
                        marker.textContent = String(step);
                        step += 1;
                    });
                };

                const renderMode = function (mode, revealWorkspace) {
                    const activeSections = activeSectionsForMode(mode);
                    modeInput.value = mode || '';
                    setIncludeValues(activeSections);

                    choiceCards.forEach(function (card) {
                        const selected = card.dataset.mode === mode;
                        card.classList.toggle('is-selected', selected);
                        const radio = card.querySelector('input[type="radio"]');
                        if (radio) radio.checked = selected;
                    });

                    if (selectedLabel) selectedLabel.textContent = mode ? labels[mode] : 'No selection yet';
                    if (workspaceTitle) workspaceTitle.textContent = mode ? labels[mode] : 'Source record form';
                    if (workspaceCopy) workspaceCopy.textContent = mode ? descriptions[mode] : 'Only relevant fields are shown for the selected source type.';
                    if (workspaceChip) workspaceChip.textContent = mode ? labels[mode] : 'Selected source';

                    if (combinedSection) {
                        combinedSection.classList.toggle('is-hidden', mode !== 'combined');
                        setNestedDisabled(combinedSection, mode !== 'combined');
                    }

                    fields.forEach(function (field) {
                        const visible = elementApplies(field, mode, activeSections);
                        field.classList.toggle('is-hidden', !visible);
                        setNestedDisabled(field, !visible);
                    });

                    panels.forEach(function (panel) {
                        const visible = elementApplies(panel, mode, activeSections);
                        panel.classList.toggle('is-hidden', !visible);
                        setNestedDisabled(panel, !visible);
                    });

                    updateRequiredFields(activeSections);
                    syncScope(false);

                    if (workspaceBody) workspaceBody.classList.toggle('is-visible', Boolean(revealWorkspace && mode));
                    if (choicePanel) choicePanel.style.display = revealWorkspace && mode ? 'none' : 'grid';
                    if (choiceError) choiceError.classList.remove('is-visible');
                    renumberVisibleSteps();
                };

                choiceCards.forEach(function (card) {
                    card.addEventListener('click', function () {
                        renderMode(card.dataset.mode, false);
                    });
                });

                const continueButton = form.querySelector('[data-source-continue]');
                if (continueButton) {
                    continueButton.addEventListener('click', function () {
                        const mode = getMode();
                        if (!mode) {
                            if (choiceError) choiceError.classList.add('is-visible');
                            return;
                        }

                        if (mode === 'combined' && !activeSectionsForMode(mode).length) {
                            combinedToggles.forEach(function (toggle) { toggle.checked = ['title', 'landholding', 'parcel_source'].includes(toggle.value); });
                        }

                        renderMode(mode, true);
                    });
                }

                const changeButton = form.querySelector('[data-source-change]');
                if (changeButton) {
                    changeButton.addEventListener('click', function () {
                        const mode = getMode();
                        renderMode(mode, false);
                    });
                }

                combinedToggles.forEach(function (toggle) {
                    toggle.addEventListener('change', function () {
                        renderMode('combined', true);
                    });
                });

                if (sourceScope && sourceScopeMirror) {
                    sourceScope.addEventListener('change', function () { syncScope(false); });
                    sourceScopeMirror.addEventListener('change', function () { syncScope(true); });
                    if (sourceScopeValue && sourceScopeValue.value) {
                        sourceScope.value = sourceScopeValue.value;
                        sourceScopeMirror.value = sourceScopeValue.value;
                    }
                    syncScope(false);
                }

                const parcelSelect = form.querySelector('[data-source-parcel-autofill]');
                if (parcelSelect) {
                    const fillIfBlank = function (id, value) {
                        const field = document.getElementById(id);
                        if (field && value && !field.value) field.value = value;
                    };

                    parcelSelect.addEventListener('change', function () {
                        const option = parcelSelect.options[parcelSelect.selectedIndex];
                        if (!option) return;
                        fillIfBlank('parcel_code', option.dataset.parcelCode || '');
                        fillIfBlank('title_number', option.dataset.title || '');
                        fillIfBlank('municipality', option.dataset.municipality || '');
                        fillIfBlank('barangay', option.dataset.barangay || '');
                    });
                }

                form.addEventListener('submit', function (event) {
                    const mode = getMode();
                    const activeSections = activeSectionsForMode(mode);

                    if (!mode || !activeSections.length) {
                        event.preventDefault();
                        renderMode(mode, false);
                        if (choiceError) {
                            choiceError.textContent = mode === 'combined'
                                ? 'Select at least one package section.'
                                : 'Select a source type first.';
                            choiceError.classList.add('is-visible');
                        }
                        return;
                    }

                    setIncludeValues(activeSections);
                    syncScope(sourceScopeMirror && sourceScopeMirror.offsetParent !== null);
                });

                const hasOldMode = Boolean(modeInput.value);
                renderMode(modeInput.value || '', hasOldMode);
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
                        const wrapper = field.closest('.source-field, .source-field-wide, .source-validation-field, .source-form-field, div') || field.parentElement;
                        const message = Array.isArray(messages) ? messages[0] : messages;

                        if (!firstInvalidElement && field.type !== 'hidden' && !field.disabled) {
                            firstInvalidElement = field;
                        }

                        field.setAttribute('aria-invalid', 'true');
                        field.classList.add('source-invalid-input');

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
                        if (typeof firstInvalidElement.focus === 'function') {
                            firstInvalidElement.focus({ preventScroll: true });
                        }
                    }, 120);
                }
            });
        </script>
    @endif

    @include('staff.partials.form-autosave')
</x-staff-shell>
