<x-staff-shell
    title="Source Records Archive"
    active="source-records"
>
    <style>
        .source-action-card {
            border-color: #dbe4dd;
        }

        .source-action-card:hover {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .source-action-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            font-size: 1.15rem;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10);
        }

        .source-action-icon.package {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .source-action-icon.import {
            background: #eef2ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
        }

        .source-action-icon.single {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .source-action-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .source-action-grid .source-action-card {
            min-height: 8.25rem;
            height: 100%;
        }

        .source-record-main {
            font-weight: 800;
            color: #065f46;
            text-decoration: none;
        }

        .source-record-main:hover {
            text-decoration: underline;
        }

        .source-subtext {
            margin-top: 0.18rem;
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.35;
        }

        .source-badge-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .source-parcel-link {
            color: #065f46;
            font-weight: 800;
            text-decoration: none;
        }

        .source-parcel-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 1100px) {
            .source-action-grid {
                grid-template-columns: 1fr;
            }

            .source-action-grid .source-action-card {
                min-height: auto;
            }
        }
    </style>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="source-action-grid">
        <a href="{{ route('staff.source-record-packages.create') }}" class="source-action-card staff-panel staff-panel-pad block transition">
            <div class="flex items-start gap-4">
                <span class="source-action-icon package" aria-hidden="true">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </span>
                <div>
                    <h2 class="staff-panel-title">Encode Source Package</h2>
                    <p class="staff-panel-subtitle">Best for one source file containing connected title, landholding, parcel source, and historical clearance details.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('staff.source-record-package-imports.create') }}" class="source-action-card staff-panel staff-panel-pad block transition">
            <div class="flex items-start gap-4">
                <span class="source-action-icon import" aria-hidden="true">
                    <i class="fa-solid fa-file-arrow-up"></i>
                </span>
                <div>
                    <h2 class="staff-panel-title">Bulk Import Packages</h2>
                    <p class="staff-panel-subtitle">Upload a CSV template, preview valid/error/duplicate rows, then commit selected source packages.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('staff.legacy-records.create') }}" class="source-action-card staff-panel staff-panel-pad block transition">
            <div class="flex items-start gap-4">
                <span class="source-action-icon single" aria-hidden="true">
                    <i class="fa-solid fa-file-pen"></i>
                </span>
                <div>
                    <h2 class="staff-panel-title">Encode Single Source Record</h2>
                    <p class="staff-panel-subtitle">Use when the file contains only one title, landholding, parcel source, or historical clearance reference.</p>
                </div>
            </div>
        </a>
    </section>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="staff-panel-title">Search and Filter Source Records</h2>
                <p class="staff-panel-subtitle">Find source records by reference number, party name, parcel code, location, record type, or origin.</p>
            </div>
            <p class="text-sm font-bold text-gray-500">{{ $records->total() }} record(s)</p>
        </div>

        <form method="GET" action="{{ route('staff.legacy-records.index') }}" class="mt-5 staff-filter-grid filter-grid-4">
            <div class="staff-filter-field">
                <label class="staff-form-label">SEARCH</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, control no., parcel code, landowner, lot no." class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>

            <div class="staff-filter-field">
                <label class="staff-form-label">TYPE</label>
                <select name="record_type" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All types</option>
                    @foreach ($recordTypes as $value => $label)
                        <option value="{{ $value }}" @selected(request('record_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field">
                <label class="staff-form-label">ORIGIN</label>
                <select name="origin" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All origins</option>
                    @foreach ($origins as $value => $label)
                        <option value="{{ $value }}" @selected(request('origin') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field">
                <label class="staff-form-label">MUNICIPALITY</label>
                <input type="text" name="municipality" value="{{ request('municipality') }}" placeholder="Municipality" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>

            <div class="staff-filter-actions">
                <button type="submit" class="staff-button staff-button-dark">
                    <i class="fa-solid fa-filter"></i>
                    Apply Filters
                </button>
                <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="staff-panel-title">Source Record List</h2>
                <p class="staff-panel-subtitle">Showing {{ $records->count() }} of {{ $records->total() }} source record(s).</p>
            </div>
        </div>

        <div class="staff-table-wrap">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Record</th>
                        <th>Type / Origin</th>
                        <th>Reference</th>
                        <th>Party / Landowner</th>
                        <th>Location</th>
                        <th>Parcel Link</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        @php
                            $displayTitle = $record->title_number
                                ?? $record->control_number
                                ?? $record->landholding_reference_number
                                ?? $record->parcel_code
                                ?? 'Source Record #' . $record->id;

                            $typeLabel = $recordTypes[$record->record_type] ?? ucwords(str_replace('_', ' ', $record->record_type));
                            $originLabel = $origins[$record->origin] ?? ucwords($record->origin ?? 'Unknown');
                        @endphp

                        <tr>
                            <td>
                                <a href="{{ route('staff.legacy-records.show', $record) }}" class="source-record-main">
                                    {{ $displayTitle }}
                                </a>
                                <div class="source-subtext">ID: {{ $record->id }}</div>
                            </td>

                            <td>
                                <div class="source-badge-stack">
                                    <span class="staff-badge staff-badge-blue">{{ $typeLabel }}</span>
                                    <span class="staff-badge {{ $record->origin === 'encoded' ? 'staff-badge-green' : 'staff-badge-amber' }}">{{ $originLabel }}</span>
                                </div>
                            </td>

                            <td>
                                <div>{{ $record->control_number ?? $record->application_reference_number ?? $record->landholding_reference_number ?? 'N/A' }}</div>
                                <div class="source-subtext">Title: {{ $record->title_number ?? 'N/A' }}</div>
                                <div class="source-subtext">Parcel ref: {{ $record->parcel_code ?? 'N/A' }}</div>
                            </td>

                            <td>
                                <div class="font-semibold text-gray-900">
                                    {{ $record->landowner_name ?? $record->transferor_name ?? 'N/A' }}
                                </div>
                                @if ($record->transferee_name)
                                    <div class="source-subtext">To: {{ $record->transferee_name }}</div>
                                @endif
                            </td>

                            <td>
                                <div>{{ $record->municipality ?? 'N/A' }}</div>
                                <div class="source-subtext">{{ $record->barangay ?? 'N/A' }}</div>
                            </td>

                            <td>
                                @if ($record->parcel)
                                    <a href="{{ route('staff.records.parcels.show', $record->parcel) }}" class="source-parcel-link">
                                        {{ $record->parcel->parcel_code }}
                                    </a>
                                @else
                                    <span class="staff-badge staff-badge-slate">Unlinked</span>
                                @endif
                            </td>

                            <td class="text-right">
                                <a href="{{ route('staff.legacy-records.show', $record) }}" class="staff-button staff-button-light">
                                    Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500">
                                No source records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4">
            {{ $records->withQueryString()->links() }}
        </div>
    </section>
</x-staff-shell>
