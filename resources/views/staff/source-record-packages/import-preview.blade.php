<x-staff-shell
    title="Source Package Import Preview"
    active="source-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.source-record-package-imports.create') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-upload"></i>
            Upload Another File
        </a>
        <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-dark">
            <i class="fa-solid fa-box-archive"></i>
            Back to Archive
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
            <h3>Review Import Before Commit</h3>
            <p>
                Rows shown here are not final records until staff commits selected valid rows. This preview protects data quality and prevents accidental creation of source records from error rows.
            </p>
        </div>
        <span class="staff-scope-pill">{{ ucfirst($batch->status) }}</span>
    </section>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="staff-panel-title">Import Preview</h2>
                <p class="staff-panel-subtitle">File: <span class="font-bold text-gray-800">{{ $batch->original_filename }}</span></p>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="staff-panel staff-panel-pad">
            <p class="text-xs font-black uppercase tracking-wider text-gray-500">Total Rows</p>
            <p class="mt-2 text-3xl font-black text-gray-900">{{ $batch->total_rows }}</p>
        </div>
        <div class="staff-panel staff-panel-pad">
            <p class="text-xs font-black uppercase tracking-wider text-gray-500">Valid Rows</p>
            <p class="mt-2 text-3xl font-black text-green-700">{{ $batch->valid_rows }}</p>
        </div>
        <div class="staff-panel staff-panel-pad">
            <p class="text-xs font-black uppercase tracking-wider text-gray-500">Rows With Errors</p>
            <p class="mt-2 text-3xl font-black text-red-700">{{ $batch->error_rows }}</p>
        </div>
        <div class="staff-panel staff-panel-pad">
            <p class="text-xs font-black uppercase tracking-wider text-gray-500">Possible Duplicates</p>
            <p class="mt-2 text-3xl font-black text-amber-700">{{ $batch->duplicate_rows }}</p>
        </div>
    </section>

    <form method="POST" action="{{ route('staff.source-record-package-imports.commit', $batch) }}">
        @csrf

        <section class="staff-panel overflow-hidden">
            <div class="staff-panel-pad flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="staff-panel-title">Preview Rows</h2>
                    <p class="staff-panel-subtitle">Select valid rows to commit. Error rows are blocked until corrected and re-uploaded.</p>
                </div>
                @if ($batch->status !== 'committed')
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa-solid fa-check"></i>
                        Commit Selected Valid Rows
                    </button>
                @else
                    <span class="staff-badge staff-badge-slate">Already Committed</span>
                @endif
            </div>

            <div class="staff-table-wrap">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Commit</th>
                            <th>Row</th>
                            <th>Status</th>
                            <th>Included Sections</th>
                            <th>References</th>
                            <th>Owner / Location</th>
                            <th>Issues</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $data = $row['data'];
                                $isValid = $row['status'] === 'valid';
                            @endphp
                            <tr class="{{ $isValid ? '' : 'bg-red-50' }}">
                                <td>
                                    @if ($isValid && $batch->status !== 'committed')
                                        <input type="checkbox" name="selected_rows[]" value="{{ $row['row_index'] }}" checked class="rounded border-gray-300 text-green-700 focus:ring-green-600">
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="font-black text-gray-900">{{ $row['row_index'] }}</td>
                                <td>
                                    @if ($isValid)
                                        <span class="staff-badge staff-badge-green">Valid</span>
                                    @else
                                        <span class="staff-badge staff-badge-red">Error</span>
                                    @endif
                                    @if ($row['possible_duplicate'])
                                        <div class="mt-1"><span class="staff-badge staff-badge-amber">Possible Duplicate</span></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @if ($data['include_title']) <span class="staff-badge staff-badge-blue">Title</span> @endif
                                        @if ($data['include_landholding']) <span class="staff-badge staff-badge-green">Landholding</span> @endif
                                        @if ($data['include_parcel_source']) <span class="staff-badge staff-badge-slate">Parcel Source</span> @endif
                                        @if ($data['include_historical_clearance']) <span class="staff-badge staff-badge-amber">Historical Clearance</span> @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($data['parcel_code']) <div><strong>Parcel Ref:</strong> {{ $data['parcel_code'] }}</div> @endif
                                    @if ($data['title_number']) <div><strong>Title:</strong> {{ $data['title_number'] }}</div> @endif
                                    @if ($data['landholding_reference_number']) <div><strong>Landholding:</strong> {{ $data['landholding_reference_number'] }}</div> @endif
                                    @if ($data['control_number']) <div><strong>Control:</strong> {{ $data['control_number'] }}</div> @endif
                                    @if (! $data['parcel_code'] && ! $data['title_number'] && ! $data['landholding_reference_number'] && ! $data['control_number']) — @endif
                                </td>
                                <td>
                                    <div><strong>Owner:</strong> {{ $data['landowner_name'] ?: '—' }}</div>
                                    <div><strong>Barangay:</strong> {{ $data['barangay'] ?: '—' }}</div>
                                    <div><strong>Municipality:</strong> {{ $data['municipality'] ?: '—' }}</div>
                                    <div><strong>Area:</strong> {{ $data['area_hectares'] ?: '—' }}</div>
                                </td>
                                <td>
                                    @if (count($row['errors']) > 0)
                                        <div class="font-bold text-red-700">Errors:</div>
                                        <ul class="mt-1 list-disc space-y-1 pl-5 text-red-700">
                                            @foreach ($row['errors'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if (count($row['warnings']) > 0)
                                        <div class="mt-2 font-bold text-amber-700">Warnings:</div>
                                        <ul class="mt-1 list-disc space-y-1 pl-5 text-amber-700">
                                            @foreach ($row['warnings'] as $warning)
                                                <li>{{ $warning }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if (count($row['errors']) === 0 && count($row['warnings']) === 0)
                                        <span class="text-gray-400">No issues</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-gray-500">No rows found in this import batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="source-submit-footer">
                <div class="source-submit-note">
                    <span class="source-submit-icon" aria-hidden="true">
                        <i class="fa-solid fa-check-double"></i>
                    </span>
                    <div>
                        <p class="source-submit-title">Ready to commit selected rows</p>
                        <p class="source-submit-copy">Only selected valid rows will be committed as imported source packages. Error rows remain blocked.</p>
                    </div>
                </div>

                <div class="source-submit-actions">
                    <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">Back to Source Records</a>
                    @if ($batch->status !== 'committed')
                        <button type="submit" class="staff-button staff-button-primary">
                            <i class="fa-solid fa-check"></i>
                            Commit Selected Valid Rows
                        </button>
                    @endif
                </div>
            </div>
        </section>
    </form>
</x-staff-shell>
