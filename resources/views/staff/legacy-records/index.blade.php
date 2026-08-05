<x-staff-shell
    title="Source Records"
    subtitle="Encode, import, search, and review digitized source packages used during clearance processing."
    active="source-records"
>
    <style>
        .source-page { display: grid; gap: 1rem; }

        .source-toolbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .source-toolbar-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .6rem;
            flex-wrap: wrap;
        }

        .source-mode-tabs {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            padding: .25rem;
            border: 1px solid #dbe4dd;
            border-radius: .78rem;
            background: #f8fafc;
        }

        .source-mode-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .42rem;
            border-radius: .58rem;
            padding: .58rem .78rem;
            color: #475569;
            font-size: .78rem;
            font-weight: 850;
            text-decoration: none;
            white-space: nowrap;
        }

        .source-mode-tab:hover { color: #065f46; background: #ecfdf5; }
        .source-mode-tab.active { color: #fff; background: #166534; }

        .source-filter-grid {
            display: grid;
            grid-template-columns: minmax(280px, 1.8fr) repeat(3, minmax(165px, 1fr));
            gap: .8rem;
            align-items: end;
        }

        .source-filter-grid.packages {
            grid-template-columns: minmax(300px, 2fr) minmax(180px, 1fr);
        }

        .source-filter-actions {
            display: flex;
            align-items: center;
            gap: .55rem;
            grid-column: 1 / -1;
        }

        .source-view-card {
            border: 1px solid #dbe4dd;
            border-radius: 1rem;
            background: #fff;
            overflow: hidden;
        }

        .source-view-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.05rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .source-view-title { margin: 0; color: #0f172a; font-size: 1rem; font-weight: 950; }
        .source-view-subtitle { margin: .22rem 0 0; color: #64748b; font-size: .82rem; line-height: 1.45; }
        .source-view-count { color: #166534; font-size: .8rem; font-weight: 900; white-space: nowrap; }

        .source-package-list { display: grid; gap: .72rem; padding: 1rem; }

        .source-package-row {
            display: grid;
            grid-template-columns: minmax(210px, 1fr) minmax(0, 1.45fr) auto;
            gap: 1rem;
            align-items: center;
            border: 1px solid #e2e8f0;
            border-radius: .9rem;
            padding: .9rem 1rem;
            background: #fff;
            transition: 150ms ease;
        }

        .source-package-row:hover {
            border-color: #86efac;
            background: #fcfffd;
        }

        .source-package-code { margin: 0; color: #065f46; font-size: .9rem; font-weight: 950; overflow-wrap: anywhere; }
        .source-package-meta { margin: .25rem 0 0; color: #64748b; font-size: .76rem; line-height: 1.4; }

        .source-package-facts {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .55rem;
        }

        .source-package-fact { min-width: 0; }
        .source-package-fact-label { margin: 0 0 .18rem; color: #64748b; font-size: .62rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .source-package-fact-value { margin: 0; color: #0f172a; font-size: .8rem; font-weight: 800; line-height: 1.35; overflow-wrap: anywhere; }

        .source-badge-stack { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
        .source-record-main, .source-parcel-link { color: #065f46; font-weight: 900; text-decoration: none; }
        .source-record-main:hover, .source-parcel-link:hover { text-decoration: underline; }
        .source-subtext { margin-top: .18rem; color: #64748b; font-size: .76rem; line-height: 1.35; }

        @media (max-width: 1180px) {
            .source-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .source-package-row { grid-template-columns: 1fr; }
            .source-package-facts { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 760px) {
            .source-filter-grid,
            .source-filter-grid.packages,
            .source-package-facts { grid-template-columns: 1fr; }
            .source-toolbar-actions, .source-filter-actions { width: 100%; }
            .source-toolbar-actions .staff-button, .source-filter-actions .staff-button { flex: 1 1 auto; justify-content: center; }
            .source-mode-tabs { width: 100%; }
            .source-mode-tab { flex: 1 1 0; }
            .source-view-header { align-items: flex-start; }
        }
    </style>

    @php
        $activeArchiveView = $archiveView ?? (request('view') === 'packages' ? 'packages' : 'individual');
    @endphp

    <div class="source-page">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <section class="staff-panel staff-panel-pad">
            <div class="source-toolbar">
                <div>
                    <h2 class="staff-panel-title">Source Record Workspace</h2>
                    <p class="staff-panel-subtitle">Create a source package, attach its reference file, and review the generated searchable records.</p>
                </div>

                <div class="source-toolbar-actions" data-main-card-actions-moved>
                    <a href="{{ route('staff.source-record-packages.create') }}" class="staff-button staff-button-primary">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        Encode Package
                    </a>
                    <a href="{{ route('staff.source-record-package-imports.create') }}" class="staff-button staff-button-light">
                        <i class="fa-solid fa-file-arrow-up"></i>
                        Import CSV
                    </a>
                </div>
            </div>

            <div class="mt-5 source-mode-tabs" aria-label="Source record view">
                <a href="{{ route('staff.legacy-records.index', request()->except('view') + ['view' => 'individual']) }}" class="source-mode-tab {{ $activeArchiveView === 'individual' ? 'active' : '' }}">
                    <i class="fa-solid fa-list-ul"></i>
                    Generated Records
                </a>
                <a href="{{ route('staff.legacy-records.index', request()->except('view') + ['view' => 'packages']) }}" class="source-mode-tab {{ $activeArchiveView === 'packages' ? 'active' : '' }}">
                    <i class="fa-solid fa-box-open"></i>
                    Source Packages
                </a>
            </div>

            <form method="GET" action="{{ route('staff.legacy-records.index') }}" class="mt-4 source-filter-grid {{ $activeArchiveView === 'packages' ? 'packages' : '' }}">
                <input type="hidden" name="view" value="{{ $activeArchiveView }}">

                <div class="staff-filter-field">
                    <label class="staff-form-label">SEARCH</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, control number, parcel, party, or source reference" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                </div>

                @if ($activeArchiveView === 'individual')
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
                @endif

                <div class="staff-filter-field">
                    <label class="staff-form-label">MUNICIPALITY</label>
                    <input type="text" name="municipality" value="{{ request('municipality') }}" placeholder="Municipality" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                </div>

                <div class="source-filter-actions">
                    <button type="submit" class="staff-button staff-button-dark"><i class="fa-solid fa-filter"></i>Apply Filters</button>
                    <a href="{{ route('staff.legacy-records.index', ['view' => $activeArchiveView]) }}" class="staff-button staff-button-light">Reset</a>
                </div>
            </form>
        </section>

        @if ($activeArchiveView === 'packages')
            <section class="source-view-card">
                <div class="source-view-header">
                    <div>
                        <h2 class="source-view-title">Source Packages</h2>
                        <p class="source-view-subtitle">Open a package to review its attached file, generated records, and parcel or landowner links.</p>
                    </div>
                    <span class="source-view-count">{{ $sourcePackages->count() }} package(s)</span>
                </div>

                @if ($sourcePackages->isEmpty())
                    <div class="m-5 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm font-semibold text-gray-500">
                        No source packages found for the current search.
                    </div>
                @else
                    <div class="source-package-list">
                        @foreach ($sourcePackages as $package)
                            <article class="source-package-row">
                                <div>
                                    <p class="source-package-code">{{ $package->package_code }}</p>
                                    <p class="source-package-meta">{{ $package->source_record_scope_label }} · {{ $package->records_count }} generated record(s)</p>
                                    <div class="source-badge-stack">
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
                                        <p class="source-package-fact-label">Parcel Reference</p>
                                        <p class="source-package-fact-value">{{ $package->parcel_code ?? 'N/A' }}</p>
                                    </div>
                                    <div class="source-package-fact">
                                        <p class="source-package-fact-label">Location</p>
                                        <p class="source-package-fact-value">{{ $package->barangay ?? 'N/A' }}, {{ $package->municipality ?? 'N/A' }}</p>
                                    </div>
                                </div>

                                <a href="{{ route('staff.source-record-packages.show', $package) }}" class="staff-button staff-button-light justify-center">
                                    Open Package
                                    <i class="fa-solid fa-arrow-right"></i>
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
                        <h2 class="source-view-title">Generated Source Records</h2>
                        <p class="source-view-subtitle">Searchable records produced from encoded or imported source packages.</p>
                    </div>
                    <span class="source-view-count">{{ $records->total() }} record(s)</span>
                </div>

                <div class="staff-table-wrap">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Record</th>
                                <th>Type and Origin</th>
                                <th>Reference</th>
                                <th>Party and Location</th>
                                <th>Parcel Link</th>
                                <th class="staff-table-action">Action</th>
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
                                        <div class="source-badge-stack mt-0">
                                            <span class="staff-badge staff-badge-blue">{{ $typeLabel }}</span>
                                            <span class="staff-badge {{ $record->origin === 'encoded' ? 'staff-badge-green' : 'staff-badge-amber' }}">{{ $originLabel }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-semibold text-gray-900">{{ $record->control_number ?? $record->application_reference_number ?? $record->landholding_reference_number ?? 'N/A' }}</div>
                                        <div class="source-subtext">Title: {{ $record->title_number ?? 'N/A' }}</div>
                                        <div class="source-subtext">Parcel ref: {{ $record->parcel_code ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <div class="font-semibold text-gray-900">{{ $record->landowner_name ?? $record->transferor_name ?? 'N/A' }}</div>
                                        @if ($record->transferee_name)
                                            <div class="source-subtext">To: {{ $record->transferee_name }}</div>
                                        @endif
                                        <div class="source-subtext">{{ $record->barangay ?? 'N/A' }}, {{ $record->municipality ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        @if ($record->parcel)
                                            <a href="{{ route('staff.records.parcels.show', $record->parcel) }}" class="source-parcel-link">{{ $record->parcel->parcel_code }}</a>
                                        @else
                                            <span class="staff-badge staff-badge-slate">Unlinked</span>
                                        @endif
                                    </td>
                                    <td class="staff-table-action">
                                        <div class="staff-table-action-group">
                                            <a href="{{ route('staff.legacy-records.show', $record) }}" class="staff-button staff-button-light">
                                            Open
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-8 text-center text-gray-500">No source records found.</td></tr>
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
