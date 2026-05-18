<x-staff-shell
    title="Landowner Records"
    subtitle="Search and review landowner records used for clearance application processing and privacy-filtered landowner access."
    active="landowner-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.records.landowners.create') }}" class="staff-button staff-button-primary">
            <i class="fa-solid fa-user-plus"></i>
            Add Landowner
        </a>        <a href="{{ route('staff.records.parcels.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-map-location-dot"></i>
            View Parcel Records
        </a>
    </x-slot>

    <span class="sr-only">Staff Landowner Record Search</span>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="staff-panel-title">Search and Filter Landowners</h2>
                <p class="staff-panel-subtitle">Filter by name, address, municipality, barangay, or user-account link status.</p>
            </div>
            <p class="text-sm font-bold text-gray-500">{{ $landowners->total() }} record(s)</p>
        </div>

        <form method="GET" action="{{ route('staff.records.landowners.index') }}" class="mt-5 staff-filter-grid filter-grid-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, contact, or address" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
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
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Account Link Status</label>
                <select name="linked_status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All records</option>
                    <option value="linked" @selected(($filters['linked_status'] ?? '') === 'linked')>Linked to user account</option>
                    <option value="unlinked" @selected(($filters['linked_status'] ?? '') === 'unlinked')>Not linked to user account</option>
                </select>
            </div>
            <div class="staff-filter-actions">
                <button type="submit" class="staff-button staff-button-dark"><i class="fa-solid fa-filter"></i>Apply Filters</button>
                <a href="{{ route('staff.records.landowners.index') }}" class="staff-button staff-button-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="staff-panel-title">Landowner List</h2>
                <p class="staff-panel-subtitle">Showing {{ $landowners->count() }} of {{ $landowners->total() }} landowner record(s). Current hectares are computed from active landholding records only; source records and pending applications are not counted until staff encodes them as active landholding records.</p>
            </div>
        </div>

        <div class="staff-table-wrap">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Landowner</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Municipality / Barangay</th>
                        <th>Current Hectares / 5-ha Check</th>
                        <th>Linked User Account</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($landowners as $landowner)
                        <tr>
                            <td>
                                <div class="font-bold text-gray-900">{{ $landowner->full_name }}</div>
                                <div class="text-xs text-gray-500">Landowner ID: {{ $landowner->id }}</div>
                            </td>
                            <td>{{ $landowner->contact_number ?? 'N/A' }}</td>
                            <td>{{ $landowner->address_line ?? 'N/A' }}</td>
                            <td>
                                <div>{{ $landowner->municipality ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $landowner->barangay ?? 'N/A' }}</div>
                            </td>
                            <td>
                                @php
                                    $activeArea = (float) ($landowner->active_landholding_area_hectares ?? 0);
                                    $activeCount = (int) ($landowner->active_landholding_count ?? 0);
                                    $remainingArea = max(0, ($fiveHectareLimit ?? 5) - $activeArea);
                                    $hectareBadge = $activeArea > ($fiveHectareLimit ?? 5)
                                        ? 'staff-badge-red'
                                        : ($activeArea >= 4.5 ? 'staff-badge-amber' : 'staff-badge-green');
                                    $hectareStatus = $activeArea > ($fiveHectareLimit ?? 5)
                                        ? 'Over limit'
                                        : ($activeArea >= 4.5 ? 'Near limit' : 'Within limit');
                                @endphp

                                <div class="font-black text-gray-950">{{ number_format($activeArea, 4) }} ha</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $activeCount }} active landholding record(s)</div>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="staff-badge {{ $hectareBadge }}">{{ $hectareStatus }}</span>
                                    <span class="text-xs font-bold text-gray-500">{{ number_format($remainingArea, 4) }} ha remaining</span>
                                </div>
                            </td>
                            <td>
                                @if ($landowner->user)
                                    <div class="font-bold text-gray-900">{{ $landowner->user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $landowner->user->email }}</div>
                                    <span class="staff-badge mt-2 {{ $landowner->user->is_active ? 'staff-badge-green' : 'staff-badge-red' }}">
                                        {{ $landowner->user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                @else
                                    <span class="staff-badge staff-badge-slate">Not linked</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $landowner->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('staff.records.landowners.show', $landowner) }}" class="staff-button staff-button-light">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-8 text-center text-gray-500">No landowner records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-4">{{ $landowners->withQueryString()->links() }}</div>
    </section>
</x-staff-shell>
