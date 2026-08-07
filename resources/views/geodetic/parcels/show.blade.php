<x-geodetic-shell title="Parcel Reference Details" active="parcels">
    <style>
        .geo-detail-page { display: grid; gap: 18px; }

        .geo-detail-hero {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 14px;
            padding: 22px 24px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 22px;
            align-items: center;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
        }

        .geo-detail-kicker { margin: 0; color: var(--geo-green-800); font-size: 10px; font-weight: 900; letter-spacing: .16em; text-transform: uppercase; }
        .geo-detail-code { margin: 7px 0 0; color: var(--geo-ink); font-size: clamp(28px, 4vw, 42px); line-height: 1; font-weight: 900; letter-spacing: .03em; }
        .geo-detail-location { margin: 9px 0 0; color: var(--geo-muted); font-size: 13px; line-height: 1.45; }
        .geo-detail-badges { margin-top: 15px; display: flex; flex-wrap: wrap; gap: 7px; }

        .geo-detail-badge {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            padding: 0 9px;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            background: #f8fafc;
            color: #475569;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }
        .geo-detail-badge.is-green { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
        .geo-detail-badge.is-blue { background: #dbeafe; border-color: #bfdbfe; color: #1d4ed8; }

        .geo-detail-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(115px, 1fr));
            border: 1px solid #d7ded9;
            border-radius: 12px;
            overflow: hidden;
            min-width: 410px;
        }
        .geo-detail-summary-item { padding: 14px; border-right: 1px solid #e5e7eb; background: #fbfcfb; }
        .geo-detail-summary-item:last-child { border-right: 0; }
        .geo-detail-summary-value { display: block; color: var(--geo-ink); font-size: 18px; font-weight: 900; line-height: 1.15; }
        .geo-detail-summary-label { display: block; margin-top: 5px; color: #667085; font-size: 9px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }

        .geo-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(340px, .8fr);
            gap: 18px;
            align-items: start;
        }

        .geo-detail-panel {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
            overflow: hidden;
        }
        .geo-detail-panel-header {
            padding: 18px 20px 15px;
            border-bottom: 1px solid #e8eeea;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }
        .geo-detail-panel-title { margin: 0; color: var(--geo-ink); font-size: 17px; font-weight: 900; }
        .geo-detail-panel-copy { margin: 4px 0 0; color: var(--geo-muted); font-size: 12px; line-height: 1.45; }
        .geo-detail-panel-body { padding: 18px 20px 20px; }

        .geo-info-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 24px; }
        .geo-info-row { display: grid; grid-template-columns: minmax(120px, .7fr) minmax(0, 1fr); gap: 12px; padding: 11px 0; border-bottom: 1px solid #edf1ee; }
        .geo-info-row.full { grid-column: 1 / -1; }
        .geo-info-label { color: #667085; font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .geo-info-value { color: #1f2937; font-size: 13px; line-height: 1.45; font-weight: 750; overflow-wrap: anywhere; }

        .geo-geometry-stack { display: grid; gap: 12px; }
        .geo-geometry-summary {
            border: 1px solid #bbf7d0;
            border-radius: 11px;
            background: var(--geo-green-50);
            padding: 14px;
        }
        .geo-geometry-label { margin: 0; color: #166534; font-size: 10px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .geo-geometry-value { margin: 6px 0 0; color: var(--geo-green-900); font-size: 16px; font-weight: 900; }
        .geo-geometry-copy { margin: 5px 0 0; color: #166534; font-size: 11px; line-height: 1.45; }

        details.geo-raw-data { border: 1px solid #d7ded9; border-radius: 10px; overflow: hidden; background: #fbfcfb; }
        details.geo-raw-data summary {
            cursor: pointer;
            list-style: none;
            padding: 12px 13px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            color: var(--geo-green-900);
            font-size: 11px;
            font-weight: 900;
        }
        details.geo-raw-data summary::-webkit-details-marker { display: none; }
        .geo-raw-code { margin: 0; max-height: 240px; overflow: auto; border-top: 1px solid #e5e7eb; padding: 13px; color: #334155; font-size: 11px; line-height: 1.5; white-space: pre-wrap; word-break: break-word; }

        .geo-detail-table-wrap { overflow-x: auto; }
        .geo-detail-table { width: 100%; min-width: 980px; border-collapse: collapse; font-size: 13px; }
        .geo-detail-table th {
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
        .geo-detail-table td { padding: 15px; border-bottom: 1px solid #edf1ee; color: #344054; vertical-align: top; }
        .geo-detail-table tbody tr:last-child td { border-bottom: 0; }
        .geo-table-strong { color: #0f172a; font-weight: 900; }
        .geo-table-sub { margin-top: 3px; color: #667085; font-size: 11px; line-height: 1.4; }
        .geo-detail-empty { padding: 24px 20px; color: var(--geo-muted); font-size: 13px; }

        @media (max-width: 1120px) {
            .geo-detail-hero,
            .geo-detail-grid { grid-template-columns: 1fr; }
            .geo-detail-summary { min-width: 0; }
        }

        @media (max-width: 760px) {
            .geo-detail-summary,
            .geo-info-list { grid-template-columns: 1fr; }
            .geo-detail-summary-item { border-right: 0; border-bottom: 1px solid #e5e7eb; }
            .geo-detail-summary-item:last-child { border-bottom: 0; }
            .geo-info-row { grid-template-columns: 1fr; gap: 5px; }
        }
    </style>

    <section class="geo-detail-page">
        <article class="geo-detail-hero">
            <div>
                <p class="geo-detail-kicker">Main Parcel Reference</p>
                <h2 class="geo-detail-code">{{ $parcel->parcel_code }}</h2>
                <p class="geo-detail-location">{{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }}, {{ $parcel->province ?? 'Negros Oriental' }}</p>
                <div class="geo-detail-badges">
                    <span class="geo-detail-badge {{ $parcel->status === 'active' ? 'is-green' : '' }}">{{ $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'Reference Record' }}</span>
                    <span class="geo-detail-badge {{ $parcel->geometry_geojson ? 'is-blue' : '' }}">{{ $parcel->geometry_geojson ? 'Mapped Geometry' : 'No Geometry' }}</span>
                    <span class="geo-detail-badge">Read-Only Review</span>
                </div>
            </div>

            <div class="geo-detail-summary" aria-label="Parcel technical summary">
                <div class="geo-detail-summary-item">
                    <span class="geo-detail-summary-value">{{ number_format((float) $parcel->landholdings->where('status', 'active')->sum('area_hectares'), 4) }} ha</span>
                    <span class="geo-detail-summary-label">Active linked area</span>
                </div>
                <div class="geo-detail-summary-item">
                    <span class="geo-detail-summary-value">{{ $parcel->landholdings->count() }}</span>
                    <span class="geo-detail-summary-label">Landholdings</span>
                </div>
                <div class="geo-detail-summary-item">
                    <span class="geo-detail-summary-value">{{ $parcel->geometry_geojson['type'] ?? 'None' }}</span>
                    <span class="geo-detail-summary-label">Geometry type</span>
                </div>
            </div>
        </article>

        <section class="geo-detail-grid">
            <article class="geo-detail-panel">
                <header class="geo-detail-panel-header">
                    <div>
                        <h2 class="geo-detail-panel-title">Parcel Reference Information</h2>
                        <p class="geo-detail-panel-copy">Encoded reference values used for technical checking, map display, and administrative clearance context.</p>
                    </div>
                </header>
                <div class="geo-detail-panel-body">
                    <dl class="geo-info-list">
                        <div class="geo-info-row"><dt class="geo-info-label">Title Type</dt><dd class="geo-info-value">{{ $parcel->title_type ?? 'N/A' }}</dd></div>
                        <div class="geo-info-row"><dt class="geo-info-label">Title Number</dt><dd class="geo-info-value">{{ $parcel->title_no ?? 'N/A' }}</dd></div>
                        <div class="geo-info-row"><dt class="geo-info-label">Tax Declaration</dt><dd class="geo-info-value">{{ $parcel->tax_decl_no ?? 'N/A' }}</dd></div>
                        <div class="geo-info-row"><dt class="geo-info-label">Lot Number</dt><dd class="geo-info-value">{{ $parcel->lot_number ?? 'N/A' }}</dd></div>
                        <div class="geo-info-row"><dt class="geo-info-label">Survey Plan</dt><dd class="geo-info-value">{{ $parcel->survey_plan_number ?? 'N/A' }}</dd></div>
                        <div class="geo-info-row"><dt class="geo-info-label">ROD Office</dt><dd class="geo-info-value">{{ $parcel->rod_office ?? 'N/A' }}</dd></div>
                        <div class="geo-info-row"><dt class="geo-info-label">Parcel Area</dt><dd class="geo-info-value">{{ $parcel->area_hectares ? number_format((float) $parcel->area_hectares, 4).' ha' : 'N/A' }}</dd></div>
                        <div class="geo-info-row"><dt class="geo-info-label">Square Meters</dt><dd class="geo-info-value">{{ $parcel->area_square_meters ? number_format((float) $parcel->area_square_meters, 2).' sq. m.' : 'N/A' }}</dd></div>
                        <div class="geo-info-row"><dt class="geo-info-label">DAR Clearance Scope</dt><dd class="geo-info-value">Agricultural land record</dd></div>
                        <div class="geo-info-row"><dt class="geo-info-label">Parcel Status</dt><dd class="geo-info-value">{{ $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'N/A' }}</dd></div>
                        <div class="geo-info-row full"><dt class="geo-info-label">Remarks</dt><dd class="geo-info-value">{{ $parcel->remarks ?? 'No remarks recorded.' }}</dd></div>
                    </dl>
                </div>
            </article>

            <aside class="geo-detail-panel">
                <header class="geo-detail-panel-header">
                    <div>
                        <h2 class="geo-detail-panel-title">Geometry Reference</h2>
                        <p class="geo-detail-panel-copy">Stored geometry used by the parcel map viewer.</p>
                    </div>
                </header>
                <div class="geo-detail-panel-body">
                    <div class="geo-geometry-stack">
                        <div class="geo-geometry-summary">
                            <p class="geo-geometry-label">Map Availability</p>
                            <p class="geo-geometry-value">{{ $parcel->geometry_geojson ? 'Available' : 'Not encoded' }}</p>
                            <p class="geo-geometry-copy">Geometry supports reference visualization only and does not establish legal boundaries or ownership.</p>
                        </div>

                        <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-button geo-button-primary">
                            <i class="fa-solid fa-map-location-dot"></i>
                            Open Parcel Map
                        </a>

                        <details class="geo-raw-data">
                            <summary><span><i class="fa-solid fa-code"></i> Raw GeoJSON</span><span>{{ $parcel->geometry_geojson['type'] ?? 'None' }}</span></summary>
                            <pre class="geo-raw-code">{{ json_encode($parcel->geometry_geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    </div>
                </div>
            </aside>
        </section>

        <article class="geo-detail-panel">
            <header class="geo-detail-panel-header">
                <div>
                    <h2 class="geo-detail-panel-title">Linked Landholding Records</h2>
                    <p class="geo-detail-panel-copy">Read-only administrative references connected to this parcel for hectare and record checking.</p>
                </div>
                <span class="geo-detail-badge is-green">{{ $parcel->landholdings->where('status', 'active')->count() }} active</span>
            </header>

            @if ($parcel->landholdings->isEmpty())
                <div class="geo-detail-empty">No landholding records are currently linked to this parcel.</div>
            @else
                <div class="geo-detail-table-wrap">
                    <table class="geo-detail-table">
                        <thead>
                            <tr>
                                <th>Landowner</th>
                                <th>Recorded Area</th>
                                <th>Status</th>
                                <th>Record Dates</th>
                                <th>Source Application</th>
                                <th>Source Reference</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($parcel->landholdings as $landholding)
                                <tr>
                                    <td><div class="geo-table-strong">{{ $landholding->landowner?->full_name ?? 'N/A' }}</div></td>
                                    <td><div class="geo-table-strong">{{ number_format((float) $landholding->area_hectares, 4) }} ha</div></td>
                                    <td><span class="geo-detail-badge {{ $landholding->status === 'active' ? 'is-green' : '' }}">{{ $landholding->status ? ucwords(str_replace('_', ' ', $landholding->status)) : 'N/A' }}</span></td>
                                    <td>
                                        <div class="geo-table-strong">{{ $landholding->date_acquired?->format('M d, Y') ?? 'No acquisition date' }}</div>
                                        <div class="geo-table-sub">Transferred: {{ $landholding->date_transferred?->format('M d, Y') ?? 'N/A' }}</div>
                                    </td>
                                    <td>{{ $landholding->sourceApplication?->application_code ?? 'N/A' }}</td>
                                    <td>{{ $landholding->source_reference_number ?? 'N/A' }}</td>
                                    <td>{{ $landholding->remarks ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>
</x-geodetic-shell>
