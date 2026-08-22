<x-landowner-shell title="My Parcel Records" active="parcels">
    @push('styles')
        <style>
            .lo-parcel-page { display: grid; gap: 18px; }

            .lo-parcel-overview {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                padding: 21px 22px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 18px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
            }

            .lo-parcel-kicker { margin: 0; color: var(--lo-green-800); font-size: 10px; font-weight: 900; letter-spacing: .16em; text-transform: uppercase; }
            .lo-parcel-title { margin: 5px 0 0; color: var(--lo-ink); font-size: 22px; line-height: 1.2; font-weight: 900; }
            .lo-parcel-copy { margin: 6px 0 0; max-width: 760px; color: var(--lo-muted); font-size: 13px; line-height: 1.5; }

            .lo-parcel-overview-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
            .lo-parcel-count {
                min-height: 39px;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 0 12px;
                border: 1px solid #d7ded9;
                border-radius: 9px;
                background: #f8faf9;
                color: #475569;
                font-size: 11px;
                font-weight: 900;
                white-space: nowrap;
            }

            .lo-parcel-panel {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
                overflow: hidden;
            }

            .lo-parcel-panel-header { padding: 18px 20px 15px; border-bottom: 1px solid #e8eeea; }
            .lo-parcel-panel-title { margin: 0; color: var(--lo-ink); font-size: 17px; font-weight: 900; }
            .lo-parcel-panel-copy { margin: 4px 0 0; color: var(--lo-muted); font-size: 12px; line-height: 1.45; }

            .lo-parcel-table-wrap { overflow-x: auto; }
            .lo-parcel-table { width: 100%; min-width: 800px; border-collapse: collapse; font-size: 13px; }
            .lo-parcel-table th {
                padding: 12px 15px;
                background: #f8faf9;
                border-bottom: 1px solid var(--lo-line);
                color: #667085;
                text-align: left;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: .13em;
                text-transform: uppercase;
                white-space: nowrap;
            }
            .lo-parcel-table td { padding: 15px; border-bottom: 1px solid #edf1ee; color: #344054; vertical-align: top; }
            .lo-parcel-table tbody tr:last-child td { border-bottom: 0; }

            .lo-parcel-code-link { color: var(--lo-green-900); text-decoration: none; font-weight: 900; white-space: nowrap; }
            .lo-parcel-code-link:hover { text-decoration: underline; }
            .lo-parcel-record-id { margin-top: 4px; color: #667085; font-size: 11px; }

            .lo-reference-list { display: grid; gap: 4px; min-width: 190px; }
            .lo-reference-row { display: grid; grid-template-columns: 58px minmax(0, 1fr); gap: 7px; color: #344054; font-size: 12px; line-height: 1.35; }
            .lo-reference-label { color: #667085; font-size: 10px; font-weight: 900; text-transform: uppercase; }

            .lo-location-main { color: #1f2937; font-weight: 800; }
            .lo-location-sub { margin-top: 3px; color: #667085; font-size: 11px; }
            .lo-area-main { color: #0f172a; font-weight: 900; white-space: nowrap; }
            .lo-area-sub { margin-top: 3px; color: #667085; font-size: 11px; }

            .lo-state-stack { display: grid; gap: 6px; justify-items: start; }
            .lo-state-badge {
                display: inline-flex;
                align-items: center;
                min-height: 25px;
                padding: 0 9px;
                border: 1px solid #e2e8f0;
                border-radius: 999px;
                background: #f8fafc;
                color: #475569;
                font-size: 10px;
                font-weight: 900;
                white-space: nowrap;
            }
            .lo-state-badge.is-active { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
            .lo-state-badge.is-mapped { background: #dbeafe; border-color: #bfdbfe; color: #1d4ed8; }
            .lo-parcel-empty { padding: 28px 20px; color: var(--lo-muted); font-size: 13px; line-height: 1.5; }

            @media (max-width: 760px) {
                .lo-parcel-overview { flex-direction: column; align-items: flex-start; }
                .lo-parcel-overview-actions { width: 100%; }
            }
        </style>
    @endpush

    <section class="lo-parcel-page">
        <article class="lo-parcel-overview">
            <div>
                <p class="lo-parcel-kicker">Linked Land Records</p>
                <h2 class="lo-parcel-title">My Parcel Records</h2>
                <p class="lo-parcel-copy">View only parcel and landholding references connected to your landowner account. These records support monitoring and do not independently establish or transfer legal ownership.</p>
            </div>

            <div class="lo-parcel-overview-actions">
                <span class="lo-parcel-count"><i class="fa-solid fa-link"></i>{{ $landholdings->count() }} linked</span>
                <a href="{{ route('landowner.parcel-map.index') }}" class="lo-button lo-button-primary">
                    <i class="fa-solid fa-map-location-dot"></i>
                    Open Map
                </a>
            </div>
        </article>

        <article class="lo-parcel-panel">
            <header class="lo-parcel-panel-header">
                <h2 class="lo-parcel-panel-title">Parcel and Landholding References</h2>
                <p class="lo-parcel-panel-copy">Essential reference numbers, location, linked area, and map availability. Select any row to open its details.</p>
            </header>

            @if ($landholdings->isEmpty())
                <div class="lo-parcel-empty">No parcel records are currently linked to your landowner account.</div>
            @else
                <div class="lo-parcel-table-wrap">
                    <table class="lo-parcel-table">
                        <thead>
                            <tr>
                                <th>Parcel</th>
                                <th>References</th>
                                <th>Location</th>
                                <th>Linked Area</th>
                                <th>Clearance Scope</th>
                                <th>Record State</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($landholdings as $holding)
                                @php($parcel = $holding->parcel)
                                <tr
                                    @if ($parcel)
                                        data-record-row-href="{{ route('landowner.parcels.show', $parcel) }}"
                                        aria-label="Open parcel record {{ $parcel->parcel_code }}"
                                    @endif
                                >
                                    <td>
                                        @if ($parcel)
                                            <a href="{{ route('landowner.parcels.show', $parcel) }}" class="lo-parcel-code-link">{{ $parcel->parcel_code }}</a>
                                            <div class="lo-parcel-record-id">Parcel record #{{ $parcel->id }}</div>
                                        @else
                                            <span class="lo-parcel-code-link">Unlinked parcel</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="lo-reference-list">
                                            <div class="lo-reference-row"><span class="lo-reference-label">Title</span><span>{{ $parcel?->title_no ?? 'N/A' }}</span></div>
                                            <div class="lo-reference-row"><span class="lo-reference-label">Tax Dec.</span><span>{{ $parcel?->tax_decl_no ?? 'N/A' }}</span></div>
                                            <div class="lo-reference-row"><span class="lo-reference-label">Lot</span><span>{{ $parcel?->lot_number ?? 'N/A' }}</span></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="lo-location-main">{{ $parcel?->municipality ?? 'N/A' }}</div>
                                        <div class="lo-location-sub">{{ $parcel?->barangay ?? 'N/A' }}, {{ $parcel?->province ?? 'Negros Oriental' }}</div>
                                    </td>
                                    <td>
                                        <div class="lo-area-main">{{ number_format((float) $holding->area_hectares, 4) }} ha</div>
                                        <div class="lo-area-sub">Landholding reference</div>
                                    </td>
                                    <td>
                                        @if ($parcel?->agricultural_status === 'non_agricultural')
                                            <span class="lo-state-badge">Outside DAR clearance scope</span>
                                        @else
                                            <span class="lo-state-badge is-active">Agricultural land record</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="lo-state-stack">
                                            <span class="lo-state-badge {{ $holding->status === 'active' ? 'is-active' : '' }}">{{ $holding->status ? ucwords(str_replace('_', ' ', $holding->status)) : 'Unspecified' }}</span>
                                            <span class="lo-state-badge {{ $parcel?->geometry_geojson ? 'is-mapped' : '' }}">{{ $parcel?->geometry_geojson ? 'Mapped' : 'No Geometry' }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>

    <x-record-row-navigation />
</x-landowner-shell>
