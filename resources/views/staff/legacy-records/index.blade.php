<x-staff-shell
    title="Source Records Archive"
    active="source-records"
>
    <style>
        .source-archive-page { display: grid; gap: 1.15rem; }
        .source-action-card { border-color: #dbe4dd; }
        .source-action-card:hover { border-color: #86efac; background: #f0fdf4; }
        .source-action-icon { width: 3rem; height: 3rem; border-radius: 0.9rem; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; font-size: 1.15rem; box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10); }
        .source-action-icon.package { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .source-action-icon.import { background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; }
        .source-action-icon.single { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; }
        .source-action-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
        .source-action-grid .source-action-card { min-height: 8.25rem; height: 100%; }
        .source-view-card { border: 1px solid #dbe4dd; border-radius: 1rem; background: #fff; overflow: hidden; }
        .source-view-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.35rem; border-bottom: 1px solid #e5e7eb; background: linear-gradient(90deg, #f8fafc 0%, #ffffff 82%); }
        .source-view-title { margin: 0; font-size: 1.05rem; font-weight: 950; color: #0f172a; }
        .source-view-subtitle { margin: .25rem 0 0; color: #64748b; font-size: .86rem; line-height: 1.5; }
        .source-view-actions { display: flex; align-items: center; justify-content: flex-end; gap: .65rem; flex-wrap: wrap; }
        .source-mode-pill { display: inline-flex; align-items: center; gap: .45rem; border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; border-radius: 999px; padding: .45rem .72rem; font-size: .75rem; font-weight: 900; white-space: nowrap; }
        .source-record-main { font-weight: 900; color: #065f46; text-decoration: none; }
        .source-record-main:hover { text-decoration: underline; }
        .source-subtext { margin-top: 0.18rem; font-size: 0.78rem; color: #64748b; line-height: 1.35; }
        .source-badge-stack { display: flex; flex-wrap: wrap; gap: 0.35rem; }
        .source-parcel-link { color: #065f46; font-weight: 850; text-decoration: none; }
        .source-parcel-link:hover { text-decoration: underline; }
        .source-package-list { display: grid; gap: .9rem; padding: 1.1rem 1.25rem 1.25rem; }
        .source-package-row { border: 1px solid #dbe4dd; border-radius: 1rem; background: #ffffff; padding: 1rem; display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1.35fr) auto; gap: 1rem; align-items: center; transition: 160ms ease; }
        .source-package-row:hover { border-color: #86efac; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08); }
        .source-package-code { margin: 0; color: #064e3b; font-size: .98rem; font-weight: 950; line-height: 1.25; overflow-wrap: anywhere; }
        .source-package-meta { margin: .3rem 0 0; color: #64748b; font-size: .8rem; line-height: 1.4; }
        .source-package-facts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .5rem; }
        .source-package-fact { border: 1px solid #e5e7eb; background: #f8fafc; border-radius: .78rem; padding: .68rem .75rem; min-width: 0; }
        .source-package-fact-label { margin: 0 0 .2rem; color: #64748b; font-size: .62rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .source-package-fact-value { margin: 0; color: #0f172a; font-size: .82rem; font-weight: 850; line-height: 1.3; overflow-wrap: anywhere; }
        @media (max-width: 1180px) { .source-package-row { grid-template-columns: 1fr; } .source-package-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 1100px) { .source-action-grid { grid-template-columns: 1fr; } .source-action-grid .source-action-card { min-height: auto; } }
        @media (max-width: 760px) { .source-view-header { flex-direction: column; align-items: stretch; } .source-view-actions { justify-content: stretch; } .source-view-actions .staff-button { width: 100%; justify-content: center; } .source-package-facts { grid-template-columns: 1fr; } }
    </style>

    @php
        $activeArchiveView = $archiveView ?? (request('view') === 'packages' ? 'packages' : 'individual');
    @endphp

    <div class="source-archive-page">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <section class="staff-scope-banner">
            <div>
                <h3>Source Records Archive</h3>
                <p>
                    This archive stores documentary and provenance references used during clearance review. Source packages group scanned/source-file context; individual source records remain the searchable working list for review and linking.
                </p>
            </div>
            <span class="staff-scope-pill">Documentary Records Only</span>
        </section>

        <section class="source-action-grid">
            <a href="{{ route('staff.source-record-packages.create') }}" class="source-action-card staff-panel staff-panel-pad block transition">
                <div class="flex items-start gap-4">
                    <span class="source-action-icon package" aria-hidden="true"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <div>
                        <h2 class="staff-panel-title">Encode Source Package</h2>
                        <p class="staff-panel-subtitle">Best for one source file containing connected title, landholding, parcel source, or historical clearance details.</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('staff.source-record-package-imports.create') }}" class="source-action-card staff-panel staff-panel-pad block transition">
                <div class="flex items-start gap-4">
                    <span class="source-action-icon import" aria-hidden="true"><i class="fa-solid fa-file-arrow-up"></i></span>
                    <div>
                        <h2 class="staff-panel-title">Bulk Import Packages</h2>
                        <p class="staff-panel-subtitle">Upload CSV metadata, then attach source scans/PDFs afterward for digitization proof.</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('staff.legacy-records.create') }}" class="source-action-card staff-panel staff-panel-pad block transition">
                <div class="flex items-start gap-4">
                    <span class="source-action-icon single" aria-hidden="true"><i class="fa-solid fa-file-pen"></i></span>
                    <div>
                        <h2 class="staff-panel-title">Encode Single Source Record</h2>
                        <p class="staff-panel-subtitle">Use when the reference contains only one title, landholding, parcel source, or clearance entry.</p>
                    </div>
                </div>
            </a>
        </section>

        <section class="staff-panel staff-panel-pad">
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="staff-panel-title">Search and Filter Source Records</h2>
                    <p class="staff-panel-subtitle">Search applies to both individual source records and source packages.</p>
                </div>
                <span class="source-mode-pill">
                    <i class="fa-solid {{ $activeArchiveView === 'packages' ? 'fa-boxes-stacked' : 'fa-list-ul' }}"></i>
                    {{ $activeArchiveView === 'packages' ? 'Viewing Source Packages' : 'Viewing Individual Sources' }}
                </span>
            </div>

            <form method="GET" action="{{ route('staff.legacy-records.index') }}" class="mt-5 staff-filter-grid filter-grid-4">
                <input type="hidden" name="view" value="{{ $activeArchiveView }}">

                <div class="staff-filter-field">
                    <label class="staff-form-label">SEARCH</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, control no., parcel code, landowner, lot no." class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                </div>

                <div class="staff-filter-field">
                    <label class="staff-form-label">TYPE</label>
                    <select name="record_type" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600" {{ $activeArchiveView === 'packages' ? 'disabled' : '' }}>
                        <option value="">All types</option>
                        @foreach ($recordTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('record_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="staff-filter-field">
                    <label class="staff-form-label">ORIGIN</label>
                    <select name="origin" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600" {{ $activeArchiveView === 'packages' ? 'disabled' : '' }}>
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
                    <a href="{{ route('staff.legacy-records.index', ['view' => $activeArchiveView]) }}" class="staff-button staff-button-light">Reset</a>
                </div>
            </form>
        </section>

        @if ($activeArchiveView === 'packages')
            <section class="source-view-card">
                <div class="source-view-header">
                    <div>
                        <h2 class="source-view-title">Source Package View</h2>
                        <p class="source-view-subtitle">Use this view when you need the full digitized package, attached scan/reference file, and package-level linkage actions.</p>
                    </div>
                    <div class="source-view-actions">
                        <span class="staff-badge staff-badge-green">{{ $sourcePackages->count() }} package(s)</span>
                        <a href="{{ route('staff.legacy-records.index', request()->except('view') + ['view' => 'individual']) }}" class="staff-button staff-button-light">
                            <i class="fa-solid fa-list-ul"></i>
                            View Individual Sources
                        </a>
                    </div>
                </div>

                @if ($sourcePackages->isEmpty())
                    <div class="m-5 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm font-semibold text-gray-500">
                        No source packages found for the current search/filter.
                    </div>
                @else
                    <div class="source-package-list">
                        @foreach ($sourcePackages as $package)
                            <article class="source-package-row">
                                <div>
                                    <p class="source-package-code">{{ $package->package_code }}</p>
                                    <p class="source-package-meta">{{ $package->source_record_scope_label }} · {{ $package->records_count }} generated record(s)</p>
                                    <div class="mt-2 source-badge-stack">
                                        <span class="staff-badge {{ $package->source_file_status_class }}">{{ $package->source_file_status_label }}</span>
                                        <span class="staff-badge {{ $package->parcel ? 'staff-badge-green' : 'staff-badge-slate' }}">{{ $package->parcel ? 'Parcel Linked' : 'No Parcel Link' }}</span>
                                    </div>
                                </div>

                                <div class="source-package-facts">
                                    <div class="source-package-fact">
                                        <p class="source-package-fact-label">Party</p>
                                        <p class="source-package-fact-value">{{ $package->landowner_name ?? $package->transferor_name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="source-package-fact">
                                        <p class="source-package-fact-label">Parcel Ref</p>
                                        <p class="source-package-fact-value">{{ $package->parcel_code ?? 'N/A' }}</p>
                                    </div>
                                    <div class="source-package-fact">
                                        <p class="source-package-fact-label">Location</p>
                                        <p class="source-package-fact-value">{{ $package->barangay ?? 'N/A' }}, {{ $package->municipality ?? 'N/A' }}</p>
                                    </div>
                                    <div class="source-package-fact">
                                        <p class="source-package-fact-label">Status</p>
                                        <p class="source-package-fact-value">{{ $package->status_label }}</p>
                                    </div>
                                </div>

                                <a href="{{ route('staff.source-record-packages.show', $package) }}" class="staff-button staff-button-primary justify-center">
                                    <i class="fa-solid fa-box-open"></i>
                                    Open Full Source Package
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @else
            <section class="source-view-card">
                <div class="source-view-header">
                    <div>
                        <h2 class="source-view-title">Individual Source Record View</h2>
                        <p class="source-view-subtitle">Default working list for review. Open each generated source record by type, reference number, party, parcel link, or location.</p>
                    </div>
                    <div class="source-view-actions">
                        <span class="staff-badge staff-badge-green">{{ $records->total() }} record(s)</span>
                        <a href="{{ route('staff.legacy-records.index', request()->except('view') + ['view' => 'packages']) }}" class="staff-button staff-button-primary">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            View by Source Package
                        </a>
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
                                        <a href="{{ route('staff.legacy-records.show', $record) }}" class="source-record-main">{{ $displayTitle }}</a>
                                        <div class="source-subtext">Source Record #{{ $record->id }}</div>
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
                                        <div class="font-semibold text-gray-900">{{ $record->landowner_name ?? $record->transferor_name ?? 'N/A' }}</div>
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
                                            <a href="{{ route('staff.records.parcels.show', $record->parcel) }}" class="source-parcel-link">{{ $record->parcel->parcel_code }}</a>
                                        @else
                                            <span class="staff-badge staff-badge-slate">Unlinked</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('staff.legacy-records.show', $record) }}" class="staff-button staff-button-light">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-500">No source records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $records->withQueryString()->links() }}
                </div>
            </section>
        @endif
    </div>
</x-staff-shell>
