<x-geodetic-shell
    title="Parcel Reference Details"
    subtitle="Read-only parcel, geometry, and landholding reference view."
    active="parcels"
>
    <style>
        .geo-detail-hero {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            padding: 24px;
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.9fr);
            gap: 24px;
            align-items: stretch;
        }

        .geo-eyebrow-local {
            margin: 0;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #667085;
        }

        .geo-parcel-code {
            margin: 10px 0 0;
            font-size: clamp(32px, 4vw, 52px);
            line-height: 0.98;
            letter-spacing: 0.04em;
            font-weight: 900;
            color: var(--geo-ink);
        }

        .geo-hero-meta {
            margin: 12px 0 0;
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            color: var(--geo-muted);
            font-size: 13px;
        }

        .geo-chip-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .geo-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 11px;
            line-height: 1;
            font-weight: 900;
            border: 1px solid #d7ded9;
            background: #f8faf9;
            color: #344054;
            white-space: nowrap;
        }

        .geo-badge-green { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
        .geo-badge-gray { background: #f1f5f9; border-color: #e2e8f0; color: #475569; }

        .geo-summary-stack {
            display: grid;
            gap: 10px;
            align-content: start;
        }

        .geo-summary-card {
            border: 1px solid var(--geo-line);
            border-radius: 10px;
            background: #fbfdfc;
            padding: 13px 14px;
        }

        .geo-summary-card.strong {
            background: var(--geo-green-50);
            border-color: #bbf7d0;
        }

        .geo-summary-label {
            margin: 0;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.17em;
            text-transform: uppercase;
            color: #667085;
        }

        .geo-summary-value {
            margin: 7px 0 0;
            font-size: 17px;
            line-height: 1.1;
            font-weight: 900;
            color: var(--geo-ink);
        }

        .geo-summary-note {
            margin: 5px 0 0;
            font-size: 12px;
            color: var(--geo-muted);
            line-height: 1.35;
        }

        .geo-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 2.1fr) minmax(330px, 0.9fr);
            gap: 18px;
            align-items: start;
        }

        .geo-panel {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .geo-panel-inner {
            padding: 22px 24px;
        }

        .geo-panel-title {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            color: var(--geo-ink);
        }

        .geo-panel-subtitle {
            margin: 6px 0 0;
            font-size: 13px;
            color: var(--geo-muted);
            line-height: 1.45;
        }

        .geo-field-grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .geo-field {
            min-height: 72px;
            border: 1px solid var(--geo-line);
            border-radius: 10px;
            background: #fbfdfc;
            padding: 13px 14px;
        }

        .geo-field.full { grid-column: 1 / -1; }

        .geo-field-label {
            margin: 0;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.17em;
            text-transform: uppercase;
            color: #667085;
        }

        .geo-field-value {
            margin: 8px 0 0;
            font-size: 14px;
            line-height: 1.45;
            font-weight: 900;
            color: var(--geo-ink);
        }

        .geo-field-value.normal {
            font-weight: 500;
            color: #1f2937;
        }

        .geo-geometry-actions {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .geo-map-button {
            min-height: 58px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            border: 1px solid #bbf7d0;
            background: var(--geo-green-50);
            color: var(--geo-green-900);
            border-radius: 10px;
            padding: 12px 14px;
            text-decoration: none;
            font-weight: 900;
        }

        .geo-map-button:hover { background: #dcfce7; }

        .geo-map-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .geo-map-icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: #ffffff;
            border: 1px solid #bbf7d0;
            color: var(--geo-green-800);
        }

        .geo-map-title { display: block; font-size: 14px; }
        .geo-map-sub { display: block; margin-top: 2px; font-size: 12px; color: #166534; font-weight: 800; }

        details.geo-raw {
            border: 1px solid var(--geo-line);
            border-radius: 10px;
            background: #fbfdfc;
            overflow: hidden;
        }

        details.geo-raw summary {
            cursor: pointer;
            list-style: none;
            padding: 13px 14px;
            font-size: 13px;
            font-weight: 900;
            color: var(--geo-green-900);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        details.geo-raw summary::-webkit-details-marker { display: none; }

        .geo-raw-code {
            margin: 0;
            padding: 14px;
            border-top: 1px solid var(--geo-line);
            max-height: 230px;
            overflow: auto;
            font-size: 12px;
            line-height: 1.5;
            color: #334155;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .geo-table-wrap {
            padding: 16px 24px 24px;
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
            padding: 14px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            vertical-align: top;
        }

        .geo-owner {
            color: var(--geo-green-900);
            font-weight: 900;
        }

        .geo-empty {
            padding: 20px 24px 24px;
            color: var(--geo-muted);
            font-size: 13px;
        }

        .geo-header-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        @media (max-width: 1180px) {
            .geo-detail-hero,
            .geo-detail-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 760px) {
            .geo-field-grid { grid-template-columns: 1fr; }
        }
    </style>

    <section class="geo-detail-hero">
        <div>
            <p class="geo-eyebrow-local">Main Parcel Reference</p>
            <h2 class="geo-parcel-code">{{ $parcel->parcel_code }}</h2>
            <div class="geo-hero-meta">
                <span>Record ID: {{ $parcel->id }}</span>
                <span>•</span>
                <span>{{ $parcel->municipality ?? 'N/A' }}, {{ $parcel->barangay ?? 'N/A' }}</span>
            </div>

            <div class="geo-chip-row">
                <span class="geo-badge geo-badge-green">
                    {{ $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'Reference Record' }}
                </span>
                <span class="geo-badge {{ $parcel->geometry_geojson ? 'geo-badge-green' : 'geo-badge-gray' }}">
                    {{ $parcel->geometry_geojson ? 'Mapped Geometry' : 'No Geometry' }}
                </span>
            </div>

            <div class="geo-header-actions">
                <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-button geo-button-primary">
                    <i class="fa-solid fa-map-location-dot"></i>
                    Back to Map
                </a>

                <a href="{{ route('geodetic.parcels.index') }}" class="geo-button">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Parcel References
                </a>
            </div>
        </div>

        <aside class="geo-summary-stack">
            <div class="geo-summary-card strong">
                <p class="geo-summary-label">Linked Active Area</p>
                <p class="geo-summary-value">
                    {{ number_format((float) $parcel->landholdings->where('status', 'active')->sum('area_hectares'), 4) }} ha
                </p>
                <p class="geo-summary-note">Computed from active landholding records linked to this parcel.</p>
            </div>

            <div class="geo-summary-card">
                <p class="geo-summary-label">Landholding Records</p>
                <p class="geo-summary-value">{{ $parcel->landholdings->count() }} linked</p>
                <p class="geo-summary-note">{{ $parcel->landholdings->where('status', 'active')->count() }} active record(s)</p>
            </div>
        </aside>
    </section>

    <section class="geo-detail-grid">
        <article class="geo-panel">
            <div class="geo-panel-inner">
                <h2 class="geo-panel-title">Parcel Reference Information</h2>
                <p class="geo-panel-subtitle">Core encoded reference values used for technical review and map display.</p>

                <dl class="geo-field-grid">
                    <div class="geo-field">
                        <dt class="geo-field-label">Title Number</dt>
                        <dd class="geo-field-value">{{ $parcel->title_no ?? 'N/A' }}</dd>
                    </div>

                    <div class="geo-field">
                        <dt class="geo-field-label">Tax Declaration Number</dt>
                        <dd class="geo-field-value">{{ $parcel->tax_decl_no ?? 'N/A' }}</dd>
                    </div>

                    <div class="geo-field">
                        <dt class="geo-field-label">Area</dt>
                        <dd class="geo-field-value">{{ number_format((float) $parcel->area_hectares, 4) }} hectares</dd>
                    </div>

                    <div class="geo-field">
                        <dt class="geo-field-label">Province</dt>
                        <dd class="geo-field-value">{{ $parcel->province ?? 'N/A' }}</dd>
                    </div>

                    <div class="geo-field">
                        <dt class="geo-field-label">Municipality</dt>
                        <dd class="geo-field-value">{{ $parcel->municipality ?? 'N/A' }}</dd>
                    </div>

                    <div class="geo-field">
                        <dt class="geo-field-label">Barangay</dt>
                        <dd class="geo-field-value">{{ $parcel->barangay ?? 'N/A' }}</dd>
                    </div>

                    <div class="geo-field full">
                        <dt class="geo-field-label">Remarks</dt>
                        <dd class="geo-field-value normal">{{ $parcel->remarks ?? 'No remarks recorded.' }}</dd>
                    </div>
                </dl>
            </div>
        </article>

        <aside class="geo-panel">
            <div class="geo-panel-inner">
                <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                    <div>
                        <h2 class="geo-panel-title">Geometry Reference</h2>
                        <p class="geo-panel-subtitle">Stored reference geometry for map visualization and parcel checking.</p>
                    </div>
                    <span class="geo-badge {{ $parcel->geometry_geojson ? 'geo-badge-green' : 'geo-badge-gray' }}">
                        {{ $parcel->geometry_geojson['type'] ?? 'N/A' }}
                    </span>
                </div>

                <dl class="geo-field-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <div class="geo-field">
                        <dt class="geo-field-label">Geometry Type</dt>
                        <dd class="geo-field-value">{{ $parcel->geometry_geojson['type'] ?? 'N/A' }}</dd>
                    </div>

                    <div class="geo-field">
                        <dt class="geo-field-label">Map Display</dt>
                        <dd class="geo-field-value">{{ $parcel->geometry_geojson ? 'Available' : 'Not encoded' }}</dd>
                    </div>
                </dl>

                <div class="geo-geometry-actions">
                    <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-map-button">
                        <span class="geo-map-left">
                            <span class="geo-map-icon"><i class="fa-solid fa-map-location-dot"></i></span>
                            <span>
                                <span class="geo-map-title">Open Parcel Map</span>
                                <span class="geo-map-sub">View this parcel in the map viewer</span>
                            </span>
                        </span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <details class="geo-raw">
                        <summary>
                            <span><i class="fa-solid fa-code"></i> View raw GeoJSON</span>
                            <span>Reference data</span>
                        </summary>
                        <pre class="geo-raw-code">{{ json_encode($parcel->geometry_geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                </div>
            </div>
        </aside>
    </section>

    <section class="geo-panel">
        <div class="geo-panel-inner" style="padding-bottom: 0;">
            <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                <div>
                    <h2 class="geo-panel-title">Linked Landholding Records</h2>
                    <p class="geo-panel-subtitle">Administrative landholding references linked to this parcel. These records support hectare monitoring only.</p>
                </div>
                <span class="geo-badge geo-badge-green">{{ $parcel->landholdings->where('status', 'active')->count() }} active</span>
            </div>
        </div>

        @if ($parcel->landholdings->isEmpty())
            <div class="geo-empty">No landholding records are currently linked to this parcel.</div>
        @else
            <div class="geo-table-wrap">
                <table class="geo-table">
                    <thead>
                        <tr>
                            <th>Landowner</th>
                            <th>Area</th>
                            <th>Status</th>
                            <th>Dates</th>
                            <th>Source Reference</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parcel->landholdings as $landholding)
                            <tr>
                                <td class="geo-owner">{{ $landholding->landowner?->full_name ?? 'N/A' }}</td>
                                <td><strong>{{ number_format((float) $landholding->area_hectares, 4) }} ha</strong></td>
                                <td>
                                    <span class="geo-badge {{ $landholding->status === 'active' ? 'geo-badge-green' : 'geo-badge-gray' }}">
                                        {{ $landholding->status ? ucwords(str_replace('_', ' ', $landholding->status)) : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <div>Acquired: {{ $landholding->date_acquired ?? 'N/A' }}</div>
                                    <div>Transferred: {{ $landholding->date_transferred ?? 'N/A' }}</div>
                                </td>
                                <td>{{ $landholding->source_reference_number ?? 'N/A' }}</td>
                                <td style="max-width: 520px; white-space: normal; line-height: 1.45;">{{ $landholding->remarks ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-geodetic-shell>
