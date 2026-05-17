<x-staff-shell
    title="Import Source Packages"
    active="source-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Archive
        </a>
        <a href="{{ route('staff.source-record-package-imports.template') }}" class="staff-button staff-button-dark">
            <i class="fa-solid fa-download"></i>
            Download CSV Template
        </a>
    </x-slot>


    <x-slot name="styles">
        <style>
            .source-submit-footer {
                border-top: 1px solid #e5e7eb;
                background: #f8fafc;
                padding: 18px 24px;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 20px;
                align-items: center;
            }

            .source-submit-note {
                display: grid;
                grid-template-columns: 40px minmax(0, 1fr);
                gap: 12px;
                align-items: start;
                min-width: 0;
            }

            .source-submit-icon {
                width: 40px;
                height: 40px;
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
                margin: 4px 0 0;
                max-width: 820px;
                color: #64748b;
                font-size: 13px;
                line-height: 1.55;
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
                min-width: 158px;
                padding: 0 18px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                white-space: nowrap;
            }

            .source-submit-actions .staff-button-primary {
                min-width: 190px;
            }

            @media (max-width: 820px) {
                .source-submit-footer {
                    grid-template-columns: 1fr;
                    padding: 16px;
                }

                .source-submit-actions {
                    justify-content: stretch;
                }

                .source-submit-actions .staff-button {
                    flex: 1 1 0;
                    min-width: 0;
                }
            }

            @media (max-width: 520px) {
                .source-submit-note {
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

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <div class="font-bold mb-1">Please correct the following:</div>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
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

    <section class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="staff-panel staff-panel-pad xl:col-span-2">
            <div class="flex items-start gap-4">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-green-700 text-white">
                    <i class="fa-solid fa-file-import"></i>
                </span>
                <div>
                    <h2 class="staff-panel-title">Upload Completed CSV Template</h2>
                    <p class="staff-panel-subtitle">Use the official template and keep the header row unchanged. The system will show valid rows, blocked rows, and possible duplicates before saving anything.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('staff.source-record-package-imports.preview.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf

                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-5">
                    <label class="staff-form-label">COMPLETED CSV FILE *</label>
                    <input type="file" name="import_file" accept=".csv,.txt" class="mt-2 block w-full rounded-lg border border-gray-300 bg-white p-3 text-sm text-gray-700">
                    <p class="mt-2 text-xs text-gray-500">Accepted: CSV or TXT. GeoJSON values must remain valid JSON text inside the CSV cell.</p>
                </div>

                <div class="source-submit-footer">
                    <div class="source-submit-note">
                        <span class="source-submit-icon" aria-hidden="true">
                            <i class="fa-solid fa-table-list"></i>
                        </span>

                        <div>
                            <p class="source-submit-title">Ready to preview import rows</p>
                            <p class="source-submit-copy">
                                The next screen checks valid rows, errors, and possible duplicates before anything is committed to the archive.
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

        <aside class="staff-panel staff-panel-pad">
            <h2 class="staff-panel-title">Import Flow</h2>
            <div class="mt-4 space-y-3 text-sm text-gray-700">
                <div class="rounded-lg border border-gray-200 p-3">
                    <div class="font-black text-gray-900">1. Download template</div>
                    <p class="mt-1 text-xs leading-relaxed text-gray-600">Use the provided columns to avoid missing required source fields.</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3">
                    <div class="font-black text-gray-900">2. Upload and preview</div>
                    <p class="mt-1 text-xs leading-relaxed text-gray-600">Review valid rows, errors, and possible duplicates before committing.</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3">
                    <div class="font-black text-gray-900">3. Commit selected rows</div>
                    <p class="mt-1 text-xs leading-relaxed text-gray-600">Only selected valid rows become imported source packages.</p>
                </div>
            </div>
        </aside>
    </section>
</x-staff-shell>
