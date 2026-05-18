<x-staff-shell
    title="Import Source Packages"
    active="source-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Archive
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .source-import-page {
                display: grid;
                gap: 18px;
            }

            .source-import-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.65fr) minmax(340px, .85fr);
                gap: 18px;
                align-items: start;
            }

            .source-import-hero {
                display: grid;
                grid-template-columns: 48px minmax(0, 1fr);
                gap: 14px;
                align-items: start;
            }

            .source-import-hero-icon {
                width: 48px;
                height: 48px;
                border-radius: 16px;
                border: 1px solid #bbf7d0;
                background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, .65);
            }

            .source-template-card {
                margin-top: 18px;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                background: #ffffff;
                padding: 14px 16px;
                display: grid;
                grid-template-columns: 40px minmax(0, 1fr) auto;
                gap: 12px;
                align-items: center;
                box-shadow: 0 1px 0 rgba(17, 24, 39, .03);
            }

            .source-template-icon {
                width: 40px;
                height: 40px;
                border-radius: 13px;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .source-template-title {
                margin: 0;
                color: #111827;
                font-size: 13px;
                font-weight: 900;
            }

            .source-template-copy {
                margin: 3px 0 0;
                color: #6b7280;
                font-size: 12px;
                line-height: 1.5;
            }

            .source-template-card .staff-button {
                height: 40px;
                min-height: 40px;
                padding: 0 14px;
                white-space: nowrap;
            }

            .source-import-upload-box {
                margin-top: 22px;
                border: 1px dashed #d1d5db;
                border-radius: 18px;
                background: #fafafa;
                padding: 18px;
                transition: border-color .18s ease, background .18s ease, box-shadow .18s ease;
            }

            .source-import-upload-box:focus-within {
                border-color: #16a34a;
                background: #fafffb;
                box-shadow: 0 0 0 4px rgba(22, 163, 74, .08);
            }

            .source-import-file-input {
                margin-top: 10px;
                display: block;
                width: 100%;
                border-radius: 12px;
                border: 1px solid #d1d5db;
                background: #ffffff;
                padding: 11px 12px;
                color: #374151;
                font-size: 13px;
            }

            .source-import-file-input::file-selector-button {
                margin-right: 12px;
                border: 0;
                border-radius: 9px;
                background: #0f172a;
                color: #ffffff;
                padding: 8px 12px;
                font-size: 12px;
                font-weight: 800;
                cursor: pointer;
            }

            .source-import-help-row {
                margin-top: 10px;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                color: #6b7280;
                font-size: 12px;
                line-height: 1.5;
            }

            .source-import-help-pill {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border-radius: 999px;
                border: 1px solid #e5e7eb;
                background: #ffffff;
                padding: 5px 9px;
                font-weight: 700;
                color: #374151;
            }

            .source-submit-footer {
                margin-top: 18px;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                background: #ffffff;
                padding: 16px;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 18px;
                align-items: center;
            }

            .source-submit-note {
                display: grid;
                grid-template-columns: 38px minmax(0, 1fr);
                gap: 12px;
                align-items: start;
                min-width: 0;
            }

            .source-submit-icon {
                width: 38px;
                height: 38px;
                border-radius: 12px;
                border: 1px solid #bbf7d0;
                background: #ecfdf5;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
            }

            .source-submit-title {
                margin: 0;
                font-size: 14px;
                font-weight: 900;
                color: #111827;
            }

            .source-submit-copy {
                margin: 3px 0 0;
                max-width: 740px;
                color: #6b7280;
                font-size: 12.5px;
                line-height: 1.5;
            }

            .source-submit-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 10px;
                flex-wrap: nowrap;
            }

            .source-submit-actions .staff-button {
                height: 42px;
                min-height: 42px;
                min-width: 142px;
                padding: 0 16px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                white-space: nowrap;
            }

            .source-submit-actions .staff-button-primary {
                min-width: 184px;
            }

            .source-flow-card {
                position: sticky;
                top: 18px;
            }

            .source-flow-list {
                margin-top: 16px;
                display: grid;
                gap: 12px;
            }

            .source-flow-item {
                display: grid;
                grid-template-columns: 34px minmax(0, 1fr);
                gap: 12px;
                align-items: start;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                background: #ffffff;
                padding: 14px;
            }

            .source-flow-number {
                width: 34px;
                height: 34px;
                border-radius: 12px;
                background: #f0fdf4;
                border: 1px solid #bbf7d0;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 13px;
                font-weight: 900;
            }

            .source-flow-title {
                margin: 0;
                color: #111827;
                font-size: 13px;
                font-weight: 900;
            }

            .source-flow-copy {
                margin: 4px 0 0;
                color: #6b7280;
                font-size: 12px;
                line-height: 1.55;
            }


            .source-import-error {
                margin-top: 9px;
                display: flex;
                align-items: flex-start;
                gap: 7px;
                color: #b91c1c;
                font-size: 12px;
                font-weight: 700;
            }

            @media (max-width: 1180px) {
                .source-import-grid {
                    grid-template-columns: 1fr;
                }

                .source-flow-card {
                    position: static;
                }
            }

            @media (max-width: 820px) {
                .source-submit-footer,
                .source-template-card {
                    grid-template-columns: 1fr;
                    padding: 14px;
                }

                .source-template-card .staff-button {
                    justify-self: start;
                }

                .source-submit-actions {
                    justify-content: stretch;
                }

                .source-submit-actions .staff-button {
                    flex: 1 1 0;
                    min-width: 0;
                }
            }

            @media (max-width: 560px) {
                .source-import-hero,
                .source-submit-note,
                .source-flow-item {
                    grid-template-columns: 1fr;
                }

                .source-submit-actions {
                    flex-direction: column-reverse;
                    align-items: stretch;
                }

                .source-submit-actions .staff-button {
                    width: 100%;
                }
            }
        </style>
    </x-slot>

    <div class="source-import-page">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
        @endif

        <section class="staff-scope-banner">
            <div>
                <h3>Bulk Source Package Import</h3>
                <p>
                    Importing source packages creates documentary/provenance records only after staff preview and commit. Imported rows do not automatically transfer ownership, mutate registry records, or appear as parcels on the map.
                </p>
            </div>
            <span class="staff-scope-pill">Preview Before Commit</span>
        </section>

        <section class="source-import-grid">
            <div class="staff-panel staff-panel-pad">
                <div class="source-import-hero">
                    <span class="source-import-hero-icon">
                        <i class="fa-solid fa-file-arrow-up"></i>
                    </span>
                    <div>
                        <h2 class="staff-panel-title">Upload Completed CSV Template</h2>
                        <p class="staff-panel-subtitle">
                            Upload the official completed template. The system will preview valid rows, blocked rows, and possible duplicates before saving anything to the source archive.
                        </p>
                    </div>
                </div>

                <div class="source-template-card">
                    <span class="source-template-icon" aria-hidden="true">
                        <i class="fa-solid fa-download"></i>
                    </span>
                    <div>
                        <p class="source-template-title">Start with the official CSV template</p>
                        <p class="source-template-copy">
                            Download the template first and keep the column headers unchanged before uploading the completed file.
                        </p>
                    </div>
                    <a href="{{ route('staff.source-record-package-imports.template') }}" class="staff-button staff-button-dark">
                        <i class="fa-solid fa-file-arrow-down"></i>
                        Download CSV Template
                    </a>
                </div>

                <form method="POST" action="{{ route('staff.source-record-package-imports.preview.store') }}" enctype="multipart/form-data" class="mt-4">
                    @csrf

                    <div class="source-import-upload-box">
                        <label for="import_file" class="staff-form-label">COMPLETED CSV FILE *</label>
                        <input
                            id="import_file"
                            type="file"
                            name="import_file"
                            accept=".csv,.txt"
                            class="source-import-file-input {{ $errors->has('import_file') ? 'border-red-300 bg-red-50' : '' }}"
                        >

                        @error('import_file')
                            <p class="source-import-error">
                                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror

                        <div class="source-import-help-row">
                            <span class="source-import-help-pill">
                                <i class="fa-solid fa-file-csv"></i>
                                CSV or TXT only
                            </span>
                            <span class="source-import-help-pill">
                                <i class="fa-solid fa-code"></i>
                                Keep GeoJSON valid inside cells
                            </span>
                            <span class="source-import-help-pill">
                                <i class="fa-solid fa-table-columns"></i>
                                Do not rename headers
                            </span>
                        </div>
                    </div>

                    <div class="source-submit-footer">
                        <div class="source-submit-note">
                            <span class="source-submit-icon" aria-hidden="true">
                                <i class="fa-solid fa-table-list"></i>
                            </span>

                            <div>
                                <p class="source-submit-title">Ready to review import rows</p>
                                <p class="source-submit-copy">
                                    The next screen checks row validity, missing fields, and possible duplicates before staff commits selected records.
                                </p>
                            </div>
                        </div>

                        <div class="source-submit-actions">
                            <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
                                Cancel
                            </a>
                            <button type="submit" class="staff-button staff-button-primary">
                                <i class="fa-solid fa-magnifying-glass-chart"></i>
                                Upload and Preview
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <aside class="staff-panel staff-panel-pad source-flow-card">
                <h2 class="staff-panel-title">Import Flow</h2>
                <p class="staff-panel-subtitle">
                    A controlled staff workflow for documentary source records. Nothing is committed until after preview.
                </p>

                <div class="source-flow-list">
                    <div class="source-flow-item">
                        <span class="source-flow-number">1</span>
                        <div>
                            <p class="source-flow-title">Download template</p>
                            <p class="source-flow-copy">Use the official columns so required source and provenance fields stay consistent.</p>
                        </div>
                    </div>

                    <div class="source-flow-item">
                        <span class="source-flow-number">2</span>
                        <div>
                            <p class="source-flow-title">Upload and preview</p>
                            <p class="source-flow-copy">Review valid rows, blocked rows, and possible duplicates before saving anything.</p>
                        </div>
                    </div>

                    <div class="source-flow-item">
                        <span class="source-flow-number">3</span>
                        <div>
                            <p class="source-flow-title">Commit selected rows</p>
                            <p class="source-flow-copy">Only staff-selected valid rows become imported documentary source packages.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-staff-shell>
