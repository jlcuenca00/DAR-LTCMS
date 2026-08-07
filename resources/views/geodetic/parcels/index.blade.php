<x-geodetic-shell title="Parcel References" active="parcels">
    <style>
        .geo-record-page { display: grid; gap: 18px; }

        .geo-record-overview {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 14px;
            padding: 21px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
        }

        .geo-record-kicker { margin: 0; color: var(--geo-green-800); font-size: 10px; font-weight: 900; letter-spacing: .16em; text-transform: uppercase; }
        .geo-record-title { margin: 5px 0 0; color: var(--geo-ink); font-size: 22px; line-height: 1.2; font-weight: 900; }
        .geo-record-copy { margin: 6px 0 0; max-width: 800px; color: var(--geo-muted); font-size: 13px; line-height: 1.5; }
        .geo-record-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .geo-record-count,
        .geo-readonly-badge {
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
        .geo-readonly-badge { border-color: #bbf7d0; background: var(--geo-green-50); color: var(--geo-green-900); }

        .geo-record-panel {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .geo-record-panel-header { padding: 18px 20px 15px; border-bottom: 1px solid #e8eeea; }
        .geo-record-panel-title { margin: 0; color: var(--geo-ink); font-size: 17px; font-weight: 900; }
        .geo-record-panel-copy { margin: 4px 0 0; color: var(--geo-muted); font-size: 12px; line-height: 1.45; }

        .geo-record-table-wrap { overflow-x: auto; }
        .geo-record-table { width: 100%; min-width: 1080px; border-collapse: collapse; font-size: 13px; }
        .geo-record-table th {
            padding: 12px 15px;
            background: #f8faf9;
            border-bottom: 1px solid var(--geo-line);
            color: #667085;
            text-align: left;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .13em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .geo-record-table td { padding: 15px; border-bottom: 1px solid #edf1ee; color: #344054; vertical-align: top; }
        .geo-record-table th.geo-record-action-column,
        .geo-record-table td.geo-record-action-column {
            width: 1%;
            min-width: 150px;
            text-align: center;
            white-space: nowrap;
        }
        .geo-record-table td.geo-record-action-column { vertical-align: middle; }
        .geo-record-table tbody tr:last-child td { border-bottom: 0; }

        .geo-record-code { color: var(--geo-green-900); text-decoration: none; font-weight: 900; white-space: nowrap; }
        .geo-record-code:hover { text-decoration: underline; }
        .geo-record-id { margin-top: 4px; color: #667085; font-size: 11px; }

        .geo-reference-list { display: grid; gap: 4px; min-width: 190px; }
        .geo-reference-row { display: grid; grid-template-columns: 58px minmax(0, 1fr); gap: 7px; color: #344054; font-size: 12px; line-height: 1.35; }
        .geo-reference-label { color: #667085; font-size: 10px; font-weight: 900; text-transform: uppercase; }

        .geo-owner-name { color: #111827; font-weight: 900; overflow-wrap: anywhere; }
        .geo-location-main { color: #1f2937; font-weight: 800; }
        .geo-location-sub { margin-top: 3px; color: #667085; font-size: 11px; }
        .geo-area-main { color: #0f172a; font-weight: 900; white-space: nowrap; }
        .geo-area-sub { margin-top: 3px; color: #667085; font-size: 11px; }

        .geo-state-stack { display: grid; gap: 6px; justify-items: start; }
        .geo-state-badge {
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
        .geo-state-badge.is-active { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
        .geo-state-badge.is-mapped { background: #dbeafe; border-color: #bfdbfe; color: #1d4ed8; }

        .geo-open-link {
            min-height: 32px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 0 11px;
            border: 1px solid #d7ded9;
            border-radius: 9px;
            background: #ffffff;
            color: var(--geo-green-900);
            text-decoration: none;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }
        .geo-open-link:hover { background: var(--geo-green-50); border-color: #bbf7d0; }
        .geo-record-empty { padding: 28px 20px; color: var(--geo-muted); font-size: 13px; line-height: 1.5; }

        @media (max-width: 760px) {
            .geo-record-overview { flex-direction: column; align-items: flex-start; }
            .geo-record-actions { width: 100%; }
        }
    </style>

    <section class="geo-record-page">
        <article class="geo-record-overview">
            <div>
                <p class="geo-record-kicker">Technical Reference View</p>
                <h2 class="geo-record-title">Parcel and Landholding Records</h2>
                <p class="geo-record-copy">Review encoded parcel references, linked landowners, hectare values, and geometry availability. Geodetic access remains read-only and does not include ownership changes or application approval.</p>
            </div>

            <div class="geo-record-actions">
                <span class="geo-record-count"><i class="fa-solid fa-layer-group"></i>{{ $landholdings->count() }} records</span>
                <span class="geo-readonly-badge"><i class="fa-solid fa-lock"></i>Read Only</span>
                <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-button geo-button-primary">
                    <i class="fa-solid fa-map-location-dot"></i>
                    Open Map
                </a>
            </div>
        </article>

        <article class="geo-record-panel">
            <header class="geo-record-panel-header">
                <h2 class="geo-record-panel-title">Reference Records</h2>
                <p class="geo-record-panel-copy">Essential parcel, landowner, area, and geometry information for technical review.</p>
            </header>

            @if ($landholdings->isEmpty())
                <div class="geo-record-empty">No parcel or landholding records are currently available.</div>
            @else
                <div class="geo-record-table-wrap">
                    <table class="geo-record-table">
                        <thead>
                            <tr>
                                <th>Parcel</th>
                                <th>References</th>
                                <th>Linked Landowner</th>
                                <th>Location</th>
                                <th>Recorded Area</th>
                                <th>Clearance Scope</th>
                                <th>Record State</th>
                                <th class="geo-record-action-column">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($landholdings as $holding)
                                @php($parcel = $holding->parcel)
                                <tr>
                                    <td>
                                        @if ($parcel)
                                            <a href="{{ route('geodetic.parcels.show', $parcel) }}" class="geo-record-code">{{ $parcel->parcel_code }}</a>
                                            <div class="geo-record-id">Parcel record #{{ $parcel->id }}</div>
                                        @else
                                            <span class="geo-record-code">Unlinked parcel</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="geo-reference-list">
                                            <div class="geo-reference-row"><span class="geo-reference-label">Title</span><span>{{ $parcel?->title_no ?? 'N/A' }}</span></div>
                                            <div class="geo-reference-row"><span class="geo-reference-label">Tax Dec.</span><span>{{ $parcel?->tax_decl_no ?? 'N/A' }}</span></div>
                                            <div class="geo-reference-row"><span class="geo-reference-label">Survey</span><span>{{ $parcel?->survey_plan_number ?? 'N/A' }}</span></div>
                                        </div>
                                    </td>
                                    <td><div class="geo-owner-name">{{ $holding->landowner?->full_name ?? 'N/A' }}</div></td>
                                    <td>
                                        <div class="geo-location-main">{{ $parcel?->municipality ?? 'N/A' }}</div>
                                        <div class="geo-location-sub">{{ $parcel?->barangay ?? 'N/A' }}, {{ $parcel?->province ?? 'Negros Oriental' }}</div>
                                    </td>
                                    <td>
                                        <div class="geo-area-main">{{ number_format((float) $holding->area_hectares, 4) }} ha</div>
                                        <div class="geo-area-sub">Landholding reference</div>
                                    </td>
                                    <td>
                                        @if ($parcel?->agricultural_status === 'non_agricultural')
                                            <span class="geo-state-badge">Outside DAR clearance scope</span>
                                        @else
                                            <span class="geo-state-badge is-active">Agricultural land record</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="geo-state-stack">
                                            <span class="geo-state-badge {{ $holding->status === 'active' ? 'is-active' : '' }}">{{ $holding->status ? ucwords(str_replace('_', ' ', $holding->status)) : 'Unspecified' }}</span>
                                            <span class="geo-state-badge {{ $parcel?->geometry_geojson ? 'is-mapped' : '' }}">{{ $parcel?->geometry_geojson ? 'Mapped' : 'No Geometry' }}</span>
                                        </div>
                                    </td>
                                    <td class="geo-record-action-column">
                                        @if ($parcel)
                                            <a href="{{ route('geodetic.parcels.show', $parcel) }}" class="geo-open-link">Review Details <i class="fa-solid fa-arrow-right"></i></a>
                                        @else
                                            <span class="geo-state-badge">Unavailable</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>
</x-geodetic-shell>
