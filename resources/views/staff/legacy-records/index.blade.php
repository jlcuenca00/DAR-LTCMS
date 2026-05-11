<x-staff-shell
    title="Source Records Archive"
    subtitle="Search documentary and provenance source records used to support clearance processing and parcel reference review."
    active="source-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.source-record-packages.create') }}" class="staff-button staff-button-primary">
            <i class="fa-solid fa-layer-group"></i>
            Encode Source Package
        </a>
        <a href="{{ route('staff.source-record-package-imports.create') }}" class="staff-button staff-button-dark">
            <i class="fa-solid fa-file-import"></i>
            Import Source Packages
        </a>
    </x-slot>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
    @endif

    <section class="staff-scope-banner">
        <div>
            <h3>Source Records Archive</h3>
            <p>
                Source records store documentary/provenance details only. They may support or link to main Parcel Records through staff-confirmed actions, but they do not automatically become mappable parcels or mutate ownership records.
            </p>
        </div>
        <span class="staff-scope-pill">Documentary Records Only</span>
    </section>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="staff-panel-title">Search and Filter Source Records</h2>
                <p class="staff-panel-subtitle">Search by title, control number, parcel code, lot number, landowner, transferor, or transferee.</p>
            </div>
            <a href="{{ route('staff.legacy-records.create', ['record_type' => 'historical_clearance']) }}" class="staff-button staff-button-light">
                <i class="fa-solid fa-file-circle-plus"></i>
                Encode Single Clearance
            </a>
        </div>

        <form method="GET" action="{{ route('staff.legacy-records.index') }}" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-5">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, control no., parcel code, landowner, lot no." class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Type</label>
                <select name="record_type" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All Types</option>
                    @foreach ($recordTypes as $value => $label)
                        <option value="{{ $value }}" @selected(request('record_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Origin</label>
                <select name="origin" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All Origins</option>
                    @foreach ($origins as $value => $label)
                        <option value="{{ $value }}" @selected(request('origin') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Municipality</label>
                <input type="text" name="municipality" value="{{ request('municipality') }}" placeholder="Municipality" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div class="md:col-span-5 flex flex-wrap gap-2">
                <button type="submit" class="staff-button staff-button-dark"><i class="fa-solid fa-filter"></i>Apply Filters</button>
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
                        <tr>
                            <td>
                                <a href="{{ route('staff.legacy-records.show', $record) }}" class="staff-link">
                                    {{ $record->title_number ?? $record->control_number ?? $record->application_reference_number ?? 'Source Record #' . $record->id }}
                                </a>
                                <div class="text-xs text-gray-500">ID: {{ $record->id }}</div>
                            </td>
                            <td>
                                <span class="staff-badge staff-badge-blue">{{ $recordTypes[$record->record_type] ?? ucwords(str_replace('_', ' ', $record->record_type)) }}</span>
                                <div class="mt-1"><span class="staff-badge {{ $record->origin === 'encoded' ? 'staff-badge-green' : 'staff-badge-amber' }}">{{ $origins[$record->origin] ?? ucwords($record->origin ?? 'Unknown') }}</span></div>
                            </td>
                            <td>
                                <div>{{ $record->control_number ?? $record->application_reference_number ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">Parcel: {{ $record->parcel_code ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div>{{ $record->landowner_name ?? $record->transferor_name ?? 'N/A' }}</div>
                                @if ($record->transferee_name)
                                    <div class="text-xs text-gray-500">To: {{ $record->transferee_name }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $record->municipality ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $record->barangay ?? 'N/A' }}</div>
                            </td>
                            <td>
                                @if ($record->parcel)
                                    <a href="{{ route('staff.records.parcels.show', $record->parcel) }}" class="staff-link">{{ $record->parcel->parcel_code }}</a>
                                @else
                                    <span class="staff-badge staff-badge-slate">Unlinked</span>
                                @endif
                            </td>
                            <td class="text-right"><a href="{{ route('staff.legacy-records.show', $record) }}" class="staff-button staff-button-light">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-gray-500">No source records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-4">{{ $records->withQueryString()->links() }}</div>
    </section>
</x-staff-shell>
