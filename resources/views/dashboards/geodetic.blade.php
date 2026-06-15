<x-geodetic-shell
    title="Geodetic Dashboard"
    active="dashboard"
>
    <style>
        .geo-dashboard-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .geo-kpi-card {
            min-height: 118px;
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 12px;
            padding: 18px;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }

        .geo-kpi-label {
            margin: 0;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #667085;
        }

        .geo-kpi-value {
            margin: 12px 0 0;
            font-size: 31px;
            line-height: 1;
            font-weight: 900;
            color: var(--geo-ink);
        }

        .geo-kpi-description {
            margin: 12px 0 0;
            font-size: 12px;
            color: var(--geo-muted);
            line-height: 1.45;
        }

        .geo-kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            color: #ffffff;
            background: var(--geo-green-800);
            flex: 0 0 auto;
            font-size: 17px;
        }

        .geo-kpi-icon.slate { background: #334155; }
        .geo-kpi-icon.green { background: var(--geo-green-800); }
        .geo-kpi-icon.blue { background: #166534; }
        .geo-kpi-icon.amber { background: #ea580c; }

        .geo-dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(330px, 0.95fr);
            gap: 20px;
            align-items: start;
        }

        .geo-stack {
            display: grid;
            gap: 20px;
        }

        .geo-panel-header {
            padding: 20px 22px 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .geo-panel-title {
            margin: 0;
            font-size: 18px;
            line-height: 1.25;
            font-weight: 900;
            color: var(--geo-ink);
        }

        .geo-panel-subtitle {
            margin: 5px 0 0;
            font-size: 13px;
            color: var(--geo-muted);
            line-height: 1.45;
        }

        .geo-panel-link {
            color: var(--geo-green-800);
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            white-space: nowrap;
        }

        .geo-panel-link:hover { text-decoration: underline; }

        .geo-quick-grid {
            padding: 18px 22px 22px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .geo-quick-link {
            min-height: 54px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px;
            border: 1px solid var(--geo-line);
            border-radius: 10px;
            background: #fbfdfc;
            color: var(--geo-green-900);
            text-decoration: none;
            font-size: 13px;
            font-weight: 900;
            transition: 160ms ease;
        }

        .geo-quick-link:hover {
            background: var(--geo-green-50);
            border-color: #bbf7d0;
        }

        .geo-quick-link i {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            border: 1px solid #bbf7d0;
            background: #effaf2;
            color: var(--geo-green-800);
            flex: 0 0 auto;
            font-size: 12px;
        }

        .geo-status-list,
        .geo-muni-list {
            padding: 18px 22px 22px;
            display: grid;
            gap: 12px;
        }

        .geo-status-row,
        .geo-muni-row {
            display: grid;
            gap: 6px;
        }

        .geo-status-top,
        .geo-muni-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            font-size: 13px;
        }

        .geo-status-label,
        .geo-muni-label {
            font-weight: 900;
            color: #344054;
        }

        .geo-status-count,
        .geo-muni-count {
            font-weight: 900;
            color: var(--geo-ink);
        }

        .geo-progress {
            height: 8px;
            border-radius: 999px;
            overflow: hidden;
            background: #eef2f0;
        }

        .geo-progress-fill {
            height: 100%;
            border-radius: 999px;
            background: var(--geo-green-800);
        }

        .geo-table-wrap {
            padding: 16px 22px 22px;
            overflow-x: auto;
        }

        .geo-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .geo-table th {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #d1d5db;
            font-size: 11px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #667085;
            white-space: nowrap;
        }

        .geo-table td {
            padding: 13px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            vertical-align: top;
        }

        .geo-code {
            color: var(--geo-green-900);
            font-weight: 900;
            text-decoration: none;
            white-space: nowrap;
        }

        .geo-code:hover { text-decoration: underline; }

        .geo-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 11px;
            line-height: 1;
            font-weight: 900;
            border: 1px solid #d7ded9;
            background: #f8faf9;
            color: #344054;
            white-space: nowrap;
        }

        .geo-badge-has-geometry { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
        .geo-badge-pending { background: #ffedd5; border-color: #fed7aa; color: #c2410c; }
        .geo-badge-not { background: #fee2e2; border-color: #fecaca; color: #b91c1c; }
        .geo-badge-needs-geometry { background: #f1f5f9; border-color: #e2e8f0; color: #475569; }

        .geo-empty {
            padding: 22px;
            color: var(--geo-muted);
            font-size: 13px;
        }

        @media (max-width: 1200px) {
            .geo-dashboard-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .geo-dashboard-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 720px) {
            .geo-dashboard-kpis,
            .geo-quick-grid { grid-template-columns: 1fr; }
        }
    </style>

    <section class="geo-dashboard-kpis">
        @foreach ($dashboardCards as $card)
            <article class="geo-kpi-card">
                <div>
                    <p class="geo-kpi-label">{{ $card['label'] }}</p>
                    <p class="geo-kpi-value">{{ $card['value'] }}</p>
                    <p class="geo-kpi-description">{{ $card['description'] }}</p>
                </div>

                <div class="geo-kpi-icon {{ $card['tone'] ?? 'green' }}">
                    <i class="fa-solid {{ $card['icon'] }}"></i>
                </div>
            </article>
        @endforeach
    </section>

    <section class="geo-dashboard-grid">
        <div class="geo-stack">
            <article class="geo-panel">
                <div class="geo-panel-header">
                    <div>
                        <h2 class="geo-panel-title">Recent Parcel References</h2>
                        <p class="geo-panel-subtitle">Latest parcel records available for read-only technical review.</p>
                    </div>
                    <a class="geo-panel-link" href="{{ route('geodetic.parcels.index') }}">View Parcels →</a>
                </div>

                @if ($recentParcels->isEmpty())
                    <div class="geo-empty">No parcel references found yet.</div>
                @else
                    <div class="geo-table-wrap">
                        <table class="geo-table">
                            <thead>
                                <tr>
                                    <th>Parcel Code</th>
                                    <th>Title No.</th>
                                    <th>Location</th>
                                    <th>Area</th>
                                    <th>Geometry</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentParcels as $parcel)
                                    <tr>
                                        <td>
                                            <a class="geo-code" href="{{ route('geodetic.parcels.show', $parcel) }}">
                                                {{ $parcel->parcel_code }}
                                            </a>
                                        </td>
                                        <td>{{ $parcel->title_no ?? 'N/A' }}</td>
                                        <td>{{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }}</td>
                                        <td>{{ number_format((float) $parcel->area_hectares, 4) }} ha</td>
                                        <td>
                                            <span class="geo-badge {{ $parcel->geometry_geojson ? 'geo-badge-has-geometry' : 'geo-badge-needs-geometry' }}">
                                                {{ $parcel->geometry_geojson ? 'Mapped' : 'No Geometry' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>
        </div>

        <aside class="geo-stack">
            <article class="geo-panel">
                <div class="geo-panel-header">
                    <div>
                        <h2 class="geo-panel-title">Quick Reference</h2>
                        <p class="geo-panel-subtitle">Geodetic review shortcuts.</p>
                    </div>
                </div>

                <div class="geo-quick-grid">
                    <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-quick-link">
                        <span>Open Map</span>
                        <i class="fa-solid fa-map-location-dot"></i>
                    </a>

                    <a href="{{ route('geodetic.parcels.index') }}" class="geo-quick-link">
                        <span>Parcel List</span>
                        <i class="fa-solid fa-draw-polygon"></i>
                    </a>

                    <a href="{{ route('geodetic.dashboard') }}" class="geo-quick-link">
                        <span>Refresh View</span>
                        <i class="fa-solid fa-rotate"></i>
                    </a>
                </div>
            </article>

            <article class="geo-panel">
                <div class="geo-panel-header">
                    <div>
                        <h2 class="geo-panel-title">Municipality Coverage</h2>
                        <p class="geo-panel-subtitle">Top parcel reference locations.</p>
                    </div>
                </div>

                <div class="geo-muni-list">
                    @forelse ($municipalityBreakdown as $row)
                        <div class="geo-muni-row">
                            <div class="geo-muni-top">
                                <span class="geo-muni-label">{{ $row['municipality'] }}</span>
                                <span class="geo-muni-count">{{ $row['total'] }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="geo-empty" style="padding: 0;">No municipality records yet.</div>
                    @endforelse
                </div>
            </article>
        </aside>
    </section>
</x-geodetic-shell>
