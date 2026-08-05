<x-staff-shell
    title="Landowner Records"
    subtitle="Search and review landowner records used during clearance processing."
    active="landowner-records"
>
    <style>
        .records-toolbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .landowner-filter-grid {
            display: grid;
            grid-template-columns: minmax(260px, 1.8fr) repeat(3, minmax(170px, 1fr));
            gap: .8rem;
            align-items: end;
        }

        .landowner-filter-actions {
            display: flex;
            align-items: center;
            gap: .55rem;
            grid-column: 1 / -1;
        }

        .landowner-name {
            color: #0f172a;
            font-size: .92rem;
            font-weight: 900;
            line-height: 1.3;
        }

        .landowner-meta {
            margin-top: .22rem;
            color: #64748b;
            font-size: .76rem;
            line-height: 1.4;
        }

        .landholding-summary {
            display: grid;
            gap: .35rem;
            min-width: 180px;
        }

        .landholding-area {
            color: #0f172a;
            font-size: .95rem;
            font-weight: 950;
        }

        @media (max-width: 1120px) {
            .landowner-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .landowner-filter-grid {
                grid-template-columns: 1fr;
            }

            .landowner-filter-actions {
                width: 100%;
            }

            .landowner-filter-actions .staff-button {
                flex: 1 1 auto;
                justify-content: center;
            }
        }
    </style>

    <span class="sr-only">Staff Landowner Record Search</span>

    <section class="staff-panel staff-panel-pad">
        <div class="records-toolbar">
            <div>
                <h2 class="staff-panel-title">Landowner Directory</h2>
                <p class="staff-panel-subtitle">{{ $landowners->total() }} total record(s). Hectare checks are assistive references only.</p>
            </div>

            <a href="{{ route('staff.records.landowners.create') }}" class="staff-button staff-button-primary" data-main-card-actions-moved>
                <i class="fa-solid fa-user-plus"></i>
                Add Landowner
            </a>
        </div>

        <form method="GET" action="{{ route('staff.records.landowners.index') }}" class="mt-5 landowner-filter-grid">
            <div class="staff-filter-field">
                <label class="staff-form-label">SEARCH</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, contact number, or address" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>

            <div class="staff-filter-field">
                <label class="staff-form-label">MUNICIPALITY</label>
                <select name="municipality" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All municipalities</option>
                    @foreach ($municipalities as $municipality)
                        <option value="{{ $municipality }}" @selected(($filters['municipality'] ?? '') === $municipality)>{{ $municipality }}</option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field">
                <label class="staff-form-label">BARANGAY</label>
                <select name="barangay" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All barangays</option>
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay }}" @selected(($filters['barangay'] ?? '') === $barangay)>{{ $barangay }}</option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field">
                <label class="staff-form-label">ACCOUNT LINK</label>
                <select name="linked_status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All records</option>
                    <option value="linked" @selected(($filters['linked_status'] ?? '') === 'linked')>Linked to user account</option>
                    <option value="unlinked" @selected(($filters['linked_status'] ?? '') === 'unlinked')>Not linked</option>
                </select>
            </div>

            <div class="landowner-filter-actions">
                <button type="submit" class="staff-button staff-button-dark"><i class="fa-solid fa-filter"></i>Apply Filters</button>
                <a href="{{ route('staff.records.landowners.index') }}" class="staff-button staff-button-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad">
            <h2 class="staff-panel-title">Landowner List</h2>
            <p class="staff-panel-subtitle">Showing {{ $landowners->count() }} of {{ $landowners->total() }} matching record(s).</p>
        </div>

        <div class="staff-table-wrap">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Landowner</th>
                        <th>Contact and Location</th>
                        <th>Landholding Summary</th>
                        <th>Account Access</th>
                        <th>Added</th>
                        <th class="staff-table-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($landowners as $landowner)
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

                        <tr>
                            <td>
                                <a href="{{ route('staff.records.landowners.show', $landowner) }}" class="landowner-name">{{ $landowner->full_name }}</a>
                                <div class="landowner-meta">{{ $landowner->registered_owner_status_label }}</div>
                                @if ($landowner->registered_owner_status === \App\Models\Landowner::STATUS_MARRIED)
                                    <div class="landowner-meta">Spouse: {{ $landowner->spouse_name ?? 'Not encoded' }}</div>
                                @endif
                                <div class="landowner-meta">Record ID {{ $landowner->id }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-gray-900">{{ $landowner->contact_number ?? 'No contact number' }}</div>
                                <div class="landowner-meta">{{ $landowner->address_line ?? 'No street address' }}</div>
                                <div class="landowner-meta">{{ $landowner->barangay ?? 'N/A' }}, {{ $landowner->municipality ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="landholding-summary">
                                    <div class="landholding-area">{{ number_format($activeArea, 4) }} ha</div>
                                    <div class="landowner-meta">{{ $activeCount }} active landholding record(s)</div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="staff-badge {{ $hectareBadge }}">{{ $hectareStatus }}</span>
                                        <span class="text-xs font-bold text-gray-500">{{ number_format($remainingArea, 4) }} ha remaining</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($landowner->user)
                                    <div class="font-semibold text-gray-900">{{ $landowner->user->name }}</div>
                                    <div class="landowner-meta">{{ $landowner->user->email ?? $landowner->user->username ?? 'Linked account' }}</div>
                                    <span class="staff-badge mt-2 {{ $landowner->user->is_active ? 'staff-badge-green' : 'staff-badge-red' }}">
                                        {{ $landowner->user->is_active ? 'Active account' : 'Inactive account' }}
                                    </span>
                                @else
                                    <span class="staff-badge staff-badge-slate">Not linked</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $landowner->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="staff-table-action">
                                <div class="staff-table-action-group">
                                    <a href="{{ route('staff.records.landowners.show', $landowner) }}" class="staff-button staff-button-light">
                                    Open
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-gray-500">No landowner records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4">{{ $landowners->withQueryString()->links() }}</div>
    </section>
</x-staff-shell>
