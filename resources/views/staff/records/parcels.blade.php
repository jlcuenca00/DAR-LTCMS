<x-staff-shell
    title="Parcel Records"
    subtitle="Search and review agricultural parcel records used for clearance reference checking and map display."
    active="parcel-records"
>
    <style>
        .records-toolbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .records-toolbar-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .parcel-filter-grid {
            display: grid;
            grid-template-columns: minmax(280px, 1.8fr) repeat(3, minmax(170px, 1fr));
            gap: .8rem;
            align-items: end;
        }

        .parcel-filter-actions {
            display: flex;
            align-items: center;
            gap: .55rem;
            grid-column: 1 / -1;
        }

        .parcel-code {
            color: #065f46;
            font-size: .92rem;
            font-weight: 950;
            text-decoration: none;
        }

        .parcel-code:hover { text-decoration: underline; }

        .parcel-reference-list {
            display: grid;
            gap: .22rem;
            margin-top: .4rem;
            color: #64748b;
            font-size: .76rem;
            line-height: 1.35;
        }

        .parcel-reference-row {
            display: grid;
            grid-template-columns: 4.4rem minmax(0, 1fr);
            gap: .4rem;
        }

        .parcel-reference-label {
            font-weight: 850;
            color: #64748b;
        }

        .parcel-state-stack {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            min-width: 150px;
        }

        @media (max-width: 1120px) {
            .parcel-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .parcel-filter-grid {
                grid-template-columns: 1fr;
            }

            .parcel-filter-actions,
            .records-toolbar-actions {
                width: 100%;
            }

            .parcel-filter-actions .staff-button,
            .records-toolbar-actions .staff-button {
                flex: 1 1 auto;
                justify-content: center;
            }
        }
    </style>

    <span class="sr-only">Staff Parcel Record Search</span>

    <section class="staff-panel staff-panel-pad">
        <div class="records-toolbar">
            <div>
                <h2 class="staff-panel-title">Parcel Directory</h2>
                <p class="staff-panel-subtitle">{{ $parcels->total() }} total record(s). Only agricultural parcel references are maintained in this workspace.</p>
            </div>

            <div class="records-toolbar-actions" data-main-card-actions-moved>
                <a href="{{ route('staff.records.parcels.create') }}" class="staff-button staff-button-primary">
                    <i class="fa-solid fa-plus"></i>
                    Add Parcel
                </a>
                <a href="{{ route('staff.parcel-map.index') }}" class="staff-button staff-button-light">
                    <i class="fa-solid fa-map"></i>
                    Open Map
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('staff.records.parcels.index') }}" class="mt-5 parcel-filter-grid">
            <div class="staff-filter-field">
                <label class="staff-form-label">SEARCH</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Parcel code, title, lot, survey plan, or tax declaration" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
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
                <label class="staff-form-label">RECORD STATUS</label>
                <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All record statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="parcel-filter-actions">
                <button type="submit" class="staff-button staff-button-dark"><i class="fa-solid fa-filter"></i>Apply Filters</button>
                <a href="{{ route('staff.records.parcels.index') }}" class="staff-button staff-button-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad">
            <h2 class="staff-panel-title">Parcel List</h2>
            <p class="staff-panel-subtitle">Showing {{ $parcels->count() }} of {{ $parcels->total() }} matching record(s).</p>
        </div>

        <div class="staff-table-wrap">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Parcel and References</th>
                        <th>Location</th>
                        <th>Area</th>
                        <th>Record State</th>
                        <th class="staff-table-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parcels as $parcel)
                        <tr>
                            <td>
                                <a href="{{ route('staff.records.parcels.show', $parcel) }}" class="parcel-code">{{ $parcel->parcel_code }}</a>
                                <div class="parcel-reference-list">
                                    <div class="parcel-reference-row"><span class="parcel-reference-label">Title</span><span>{{ $parcel->title_no ?? 'N/A' }}</span></div>
                                    <div class="parcel-reference-row"><span class="parcel-reference-label">Lot</span><span>{{ $parcel->lot_number ?? 'N/A' }}</span></div>
                                    <div class="parcel-reference-row"><span class="parcel-reference-label">Survey</span><span>{{ $parcel->survey_plan_number ?? 'N/A' }}</span></div>
                                    <div class="parcel-reference-row"><span class="parcel-reference-label">Tax Dec.</span><span>{{ $parcel->tax_decl_no ?? 'N/A' }}</span></div>
                                </div>
                            </td>
                            <td>
                                <div class="font-semibold text-gray-900">{{ $parcel->municipality ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $parcel->barangay ?? 'N/A' }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="font-semibold text-gray-900">{{ $parcel->area_hectares ? number_format((float) $parcel->area_hectares, 4).' ha' : 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $parcel->area_square_meters ? number_format((float) $parcel->area_square_meters, 2).' sq. m.' : 'No square-meter value' }}</div>
                            </td>
                            <td>
                                <div class="parcel-state-stack">
                                    <span class="staff-badge {{ $parcel->status === 'active' ? 'staff-badge-green' : 'staff-badge-slate' }}">{{ ucwords(str_replace('_', ' ', $parcel->status ?? 'Unspecified')) }}</span>
                                    @if ($parcel->is_flagged)
                                        <span class="staff-badge staff-badge-red">Flagged for Review</span>
                                    @endif
                                    <span class="staff-badge {{ $parcel->geometry_geojson ? 'staff-badge-blue' : 'staff-badge-slate' }}">{{ $parcel->geometry_geojson ? 'Mapped' : 'No Geometry' }}</span>
                                </div>
                            </td>
                            <td class="staff-table-action">
                                <div class="staff-table-action-group">
                                    <a href="{{ route('staff.records.parcels.show', $parcel) }}" class="staff-button staff-button-light">Open</a>
                                    <a href="{{ route('staff.records.parcels.edit', $parcel) }}" class="staff-button staff-button-light">Edit</a>
                                    <a href="{{ route('staff.records.parcels.review-flag.edit', $parcel) }}" class="staff-button {{ $parcel->is_flagged ? 'staff-button-danger' : 'staff-button-light' }}">
                                        <i class="fa-solid fa-flag"></i>
                                        {{ $parcel->is_flagged ? 'Review Flag' : 'Flag' }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-gray-500">No parcel records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-6 py-4">{{ $parcels->withQueryString()->links() }}</div>
    </section>
</x-staff-shell>
