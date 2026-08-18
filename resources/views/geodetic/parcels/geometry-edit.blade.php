<x-geodetic-shell title="Parcel Geometry Mapping" active="parcels">
    <style>
        .geo-map-editor-page { display: grid; gap: 18px; }
        .geo-map-editor-hero,
        .geo-map-editor-panel {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
        }
        .geo-map-editor-hero {
            padding: 22px 24px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
        }
        .geo-map-editor-kicker { margin: 0; color: var(--geo-green-800); font-size: 10px; font-weight: 900; letter-spacing: .15em; text-transform: uppercase; }
        .geo-map-editor-title { margin: 7px 0 0; color: var(--geo-ink); font-size: 28px; line-height: 1.1; font-weight: 900; }
        .geo-map-editor-copy { margin: 8px 0 0; color: var(--geo-muted); font-size: 12px; line-height: 1.55; }
        .geo-map-editor-status {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 11px;
            border-radius: 999px;
            border: 1px solid {{ $parcel->geometry_geojson ? '#bbf7d0' : '#fed7aa' }};
            background: {{ $parcel->geometry_geojson ? '#dcfce7' : '#fff7ed' }};
            color: {{ $parcel->geometry_geojson ? '#166534' : '#c2410c' }};
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }
        .geo-map-editor-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(320px, .65fr); gap: 18px; align-items: start; }
        .geo-map-editor-panel-header { padding: 17px 20px 14px; border-bottom: 1px solid #e8eeea; }
        .geo-map-editor-panel-title { margin: 0; color: var(--geo-ink); font-size: 16px; font-weight: 900; }
        .geo-map-editor-panel-copy { margin: 4px 0 0; color: var(--geo-muted); font-size: 11px; line-height: 1.5; }
        .geo-map-editor-panel-body { padding: 18px 20px 20px; }
        .geo-map-editor-note {
            margin-top: 12px;
            padding: 12px 13px;
            border: 1px solid #dbe7df;
            border-radius: 9px;
            background: #f7fbf8;
            color: #3f5d4a;
            font-size: 11px;
            line-height: 1.5;
        }
        .geo-map-editor-actions { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 9px; }
        .geo-map-editor-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 14px;
            border-radius: 9px;
            border: 1px solid var(--geo-green-800);
            background: var(--geo-green-800);
            color: #ffffff;
            text-decoration: none;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
        }
        .geo-map-editor-button.secondary { background: #ffffff; color: var(--geo-green-900); border-color: #cfd8d2; }
        .geo-map-editor-info { display: grid; }
        .geo-map-editor-info-row { padding: 11px 0; border-bottom: 1px solid #edf1ee; }
        .geo-map-editor-info-row:last-child { border-bottom: 0; }
        .geo-map-editor-info-label { color: #667085; font-size: 9px; font-weight: 900; letter-spacing: .09em; text-transform: uppercase; }
        .geo-map-editor-info-value { margin-top: 4px; color: #1f2937; font-size: 12px; font-weight: 800; line-height: 1.45; }
        .geo-map-editor-scope {
            margin-top: 14px;
            padding: 13px;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 11px;
            line-height: 1.5;
        }

        /* Keep the shared Staff/Geodetic coordinate editor compact on this focused mapping page. */
        .geo-map-editor-panel .geojson-helper { border: 0; padding: 0; background: transparent; }
        .geo-map-editor-panel .geojson-textarea-wrap textarea { min-height: 120px; resize: vertical; }

        @media (max-width: 960px) {
            .geo-map-editor-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .geo-map-editor-hero { flex-direction: column; padding: 19px; }
            .geo-map-editor-panel-body { padding: 16px; }
        }
    </style>

    <section class="geo-map-editor-page">
        <article class="geo-map-editor-hero">
            <div>
                <p class="geo-map-editor-kicker">Geodetic Mapping Task</p>
                <h2 class="geo-map-editor-title">{{ $parcel->parcel_code }}</h2>
                <p class="geo-map-editor-copy">{{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }}, {{ $parcel->province ?? 'Negros Oriental' }}</p>
            </div>
            <span class="geo-map-editor-status">{{ $parcel->geometry_geojson ? 'Geometry encoded' : 'Awaiting GeoJSON' }}</span>
        </article>

        <section class="geo-map-editor-grid">
            <article class="geo-map-editor-panel">
                <header class="geo-map-editor-panel-header">
                    <h2 class="geo-map-editor-panel-title">Parcel Boundary Coordinates</h2>
                    <p class="geo-map-editor-panel-copy">Enter simple longitude and latitude points. The system builds the Polygon GeoJSON automatically, using the same mapping helper available to DAR Staff.</p>
                </header>
                <div class="geo-map-editor-panel-body">
                    <form method="POST" action="{{ route('geodetic.parcels.geometry.update', $parcel) }}">
                        @csrf
                        @method('PATCH')

                        @include('staff.partials.geojson-polygon-editor', [
                            'fieldName' => 'geometry_geojson',
                            'fieldId' => 'geometry_geojson',
                            'value' => old('geometry_geojson', $parcel->geometry_geojson ? json_encode($parcel->geometry_geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : ''),
                            'errorClass' => 'geo-map-editor-error',
                            'rows' => 5,
                        ])

                        <div class="geo-map-editor-note">
                            Use at least <strong>3 coordinate points</strong>. The first point is automatically repeated to close the polygon. Only the parcel's map geometry is saved; ownership, landholding, application, title, registry, and clearance records are not changed.
                        </div>

                        <div class="geo-map-editor-actions">
                            <button type="submit" class="geo-map-editor-button">
                                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                                Save Geometry
                            </button>
                            <a href="{{ route('geodetic.parcels.show', $parcel) }}" class="geo-map-editor-button secondary">Back to Parcel</a>
                            <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-map-editor-button secondary">Open Parcel Map</a>
                        </div>
                    </form>
                </div>
            </article>

            <aside class="geo-map-editor-panel">
                <header class="geo-map-editor-panel-header">
                    <h2 class="geo-map-editor-panel-title">Read-Only Parcel Reference</h2>
                    <p class="geo-map-editor-panel-copy">Use these values to confirm you are mapping the correct parcel.</p>
                </header>
                <div class="geo-map-editor-panel-body">
                    <div class="geo-map-editor-info">
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Title Number</div><div class="geo-map-editor-info-value">{{ $parcel->title_no ?? 'N/A' }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Tax Declaration</div><div class="geo-map-editor-info-value">{{ $parcel->tax_decl_no ?? 'N/A' }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Lot Number</div><div class="geo-map-editor-info-value">{{ $parcel->lot_number ?? 'N/A' }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Survey Plan</div><div class="geo-map-editor-info-value">{{ $parcel->survey_plan_number ?? 'N/A' }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Area</div><div class="geo-map-editor-info-value">{{ $parcel->area_hectares ? number_format((float) $parcel->area_hectares, 4).' ha' : 'N/A' }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Parcel Status</div><div class="geo-map-editor-info-value">{{ $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'N/A' }}</div></div>
                    </div>

                    <div class="geo-map-editor-scope">
                        <strong>Geodetic permission scope:</strong> map geometry only. All administrative, ownership, application, and clearance information remains protected from Geodetic edits.
                    </div>
                </div>
            </aside>
        </section>
    </section>
</x-geodetic-shell>
