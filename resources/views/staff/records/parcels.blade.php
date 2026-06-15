<x-staff-shell
    title="Parcel Records"
    subtitle="Search and review agricultural parcel records used for clearance reference checking, monitoring, and map display."
    active="parcel-records"
>
<span class="sr-only">Staff Parcel Record Search</span>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="staff-panel-title">Search and Filter Parcels</h2>
                <p class="staff-panel-subtitle">Filter parcel records by code, title number, tax declaration number, location, record status, or remarks.</p>
            </div>
            <p class="text-sm font-bold text-gray-500">{{ $parcels->total() }} record(s)</p>
        </div>

        <form method="GET" action="{{ route('staff.records.parcels.index') }}" class="mt-5 staff-filter-grid filter-grid-4">
            <div class="staff-filter-field">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Parcel code, title no., tax declaration no., remarks" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Municipality</label>
                <select name="municipality" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All municipalities</option>
                    @foreach ($municipalities as $municipality)
                        <option value="{{ $municipality }}" @selected(($filters['municipality'] ?? '') === $municipality)>{{ $municipality }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Barangay</label>
                <select name="barangay" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All barangays</option>
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay }}" @selected(($filters['barangay'] ?? '') === $barangay)>{{ $barangay }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Record Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All record statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="staff-filter-actions">
                <button type="submit" class="staff-button staff-button-dark"><i class="fa-solid fa-filter"></i>Apply Filters</button>
                <a href="{{ route('staff.records.parcels.index') }}" class="staff-button staff-button-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="staff-panel staff-panel-pad border border-emerald-100 bg-emerald-50/60">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-sm font-black uppercase tracking-[0.18em] text-emerald-800">DAR Clearance Scope</h2>
                <p class="mt-2 text-sm leading-6 text-emerald-900">
                    Parcel records in this module are maintained as agricultural land records for DAR clearance review, monitoring, source/reference matching, and map visualization. OCT, TCT, CLOA, and EP references should be treated as title or document references, not parcel classification workflows.
                </p>
            </div>
            <span class="staff-badge staff-badge-green whitespace-nowrap">Agricultural Land Records</span>
        </div>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="staff-panel-title">Parcel List</h2>
                <p class="staff-panel-subtitle">Showing {{ $parcels->count() }} of {{ $parcels->total() }} parcel record(s).</p>
            </div>
            <div class="flex flex-wrap gap-2" data-main-card-actions-moved>
                <a href="{{ route('staff.records.parcels.create') }}" class="staff-button staff-button-primary">
                            <i class="fa-solid fa-plus"></i>
                            Add Parcel
                        </a>
                        <a href="{{ route('staff.parcel-map.index') }}" class="staff-button staff-button-light">
                            <i class="fa-solid fa-map"></i>
                            Open Parcel Map
                        </a>
                        <a href="{{ route('staff.records.landowners.index') }}" class="staff-button staff-button-light">
                            <i class="fa-solid fa-users"></i>
                            Landowner Records
                        </a>
            </div>
        </div>
        <div class="staff-table-wrap">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Parcel</th>
                        <th>Title / Tax Declaration</th>
                        <th>Location</th>
                        <th>Area</th>
                        <th>Record Status</th>
                        <th>Map Data</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parcels as $parcel)
                        <tr>
                            <td>
                                <a href="{{ route('staff.records.parcels.show', $parcel) }}" class="staff-link">{{ $parcel->parcel_code }}</a>
                                <div class="text-xs text-gray-500">Parcel ID: {{ $parcel->id }}</div>
                            </td>
                            <td>
                                <div>{{ $parcel->title_no ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $parcel->tax_decl_no ?? 'No tax declaration' }}</div>
                            </td>
                            <td>
                                <div>{{ $parcel->municipality ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $parcel->barangay ?? 'N/A' }}</div>
                            </td>
                            <td class="whitespace-nowrap">{{ $parcel->area_hectares ? number_format((float) $parcel->area_hectares, 4) . ' ha' : 'N/A' }}</td>
                            <td>
                                <span class="staff-badge {{ $parcel->status === 'active' ? 'staff-badge-green' : 'staff-badge-slate' }}">{{ ucwords(str_replace('_', ' ', $parcel->status ?? 'Unspecified')) }}</span>
                            </td>
                            <td><span class="staff-badge {{ $parcel->geometry_geojson ? 'staff-badge-blue' : 'staff-badge-slate' }}">{{ $parcel->geometry_geojson ? 'Mapped' : 'No Geometry' }}</span></td>
                            <td class="text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('staff.records.parcels.show', $parcel) }}" class="staff-button staff-button-light">View</a>
                                    <a href="{{ route('staff.records.parcels.edit', $parcel) }}" class="staff-button staff-button-light">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-gray-500">No parcel records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4">{{ $parcels->withQueryString()->links() }}</div>
    </section>
</x-staff-shell>
