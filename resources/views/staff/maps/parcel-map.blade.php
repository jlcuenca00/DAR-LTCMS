@php
    $mappedParcelCount = count($parcelGeoJson['features'] ?? []);
@endphp

<x-staff-shell title="Parcel Map Viewer" active="parcel-map" maxWidth="">
    <x-slot name="head">
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
            crossorigin=""
        />
    </x-slot>

    <x-slot name="styles">
        <style>
            .map-card,
            .map-workspace > .panel,
            .map-sidebar > .panel {
                background: #ffffff;
                border: 1px solid var(--border);
                border-radius: 14px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                overflow: hidden;
            }

            .map-sidebar > .panel {
                min-width: 0;
            }

            .map-sidebar .panel + .panel {
                margin-top: 0;
            }

            .map-workspace {
                display: grid;
                grid-template-columns: 320px minmax(0, 1fr);
                gap: 18px;
                align-items: stretch;
            }

            .map-sidebar {
                display: grid;
                gap: 14px;
                align-content: start;
                min-width: 0;
            }

            .panel-pad {
                padding: 18px 20px;
            }

            .panel-copy {
                margin: 5px 0 0;
                font-size: 12.5px;
                line-height: 1.55;
                color: #6b7280;
            }

            .map-sidebar .panel-title {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 16px;
                font-weight: 900;
                color: #111827;
            }

            .map-sidebar .legend-item {
                font-weight: 800;
            }

            .tool-list {
                margin-top: 14px;
                display: grid;
                gap: 10px;
            }

            .tool-button,
            .tool-link {
                width: 100%;
                min-height: 42px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 10px 12px;
                font-size: 12px;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                text-decoration: none;
                cursor: pointer;
                transition: 160ms ease;
            }

            .tool-button.primary {
                border: 1px solid #166534;
                background: #166534;
                color: #ffffff;
            }

            .tool-button.primary:hover {
                background: #14532d;
            }

            .tool-button.secondary,
            .tool-link.secondary {
                border: 1px solid #d1d5db;
                background: #ffffff;
                color: #374151;
            }

            .tool-button.secondary:hover,
            .tool-link.secondary:hover {
                border-color: #86efac;
                background: #f0fdf4;
                color: #14532d;
            }

            .legend-list,
            .access-list {
                margin-top: 14px;
                display: grid;
                gap: 11px;
            }

            .legend-item {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 12.5px;
                font-weight: 700;
                color: #4b5563;
            }

            .legend-dot {
                width: 11px;
                height: 11px;
                border-radius: 999px;
                flex: 0 0 auto;
                box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.06);
            }


            .access-row {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                border-bottom: 1px solid #eef2f7;
                padding-bottom: 10px;
                font-size: 12.5px;
            }

            .access-row:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .access-label {
                color: #6b7280;
                font-weight: 700;
            }

            .access-value {
                text-align: right;
                color: #111827;
                font-weight: 900;
            }

            .access-value.locked {
                color: #b91c1c;
            }

            .map-panel {
                min-width: 0;
            }

            .map-panel-header {
                padding: 18px 20px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                background: #ffffff;
            }

            .map-panel-title {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 16px;
                font-weight: 900;
            }

            .map-panel-subtitle {
                margin: 4px 0 0;
                font-size: 12.5px;
                color: #6b7280;
                font-weight: 600;
            }

            .map-count {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #14532d;
                border-radius: 999px;
                padding: 8px 12px;
                font-size: 12px;
                font-weight: 900;
                white-space: nowrap;
            }

            .map-frame {
                padding: 12px;
                background: #ffffff;
            }

            #parcel-map {
                height: calc(100vh - 212px);
                min-height: 590px;
                width: 100%;
                border-radius: 12px;
                border: 1px solid #d1d5db;
                overflow: hidden;
                box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.03);
                background: #eef2f0;
            }

            .leaflet-container {
                background: #eef2f0;
            }

            /* Neutral CARTO basemap keeps landmarks readable without heavy water/boundary lines. */
            .leaflet-tile-pane {
                opacity: 0.92;
            }

            .leaflet-control-zoom {
                border: 1px solid #d1d5db !important;
                border-radius: 10px !important;
                overflow: hidden;
                box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10) !important;
            }

            .leaflet-control-zoom a {
                background: #ffffff !important;
                color: #14532d !important;
                border-color: #e5e7eb !important;
                font-weight: 900;
            }

            .leaflet-control-zoom a:hover {
                background: #f0fdf4 !important;
                color: #166534 !important;
            }

            .leaflet-control-attribution {
                background: rgba(255, 255, 255, 0.92) !important;
                color: #6b7280 !important;
                border-radius: 0.5rem 0 0 0;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.10);
            }

            .leaflet-control-attribution a {
                color: #166534 !important;
            }

            .leaflet-popup-content-wrapper,
            .leaflet-popup-tip {
                background: #ffffff;
                color: #111827;
                border: 1px solid #d1d5db;
                box-shadow: 0 14px 30px rgba(15, 23, 42, 0.16);
            }

            .leaflet-popup-content-wrapper {
                border-radius: 0.9rem;
            }

            .leaflet-popup-content {
                margin: 14px 16px;
                font-family: inherit;
            }

            .leaflet-popup-content p {
                color: #4b5563 !important;
            }

            .parcel-tooltip {
                background: rgba(255, 255, 255, 0.98);
                color: #111827;
                border: 1px solid #bbf7d0;
                border-radius: 0.75rem;
                padding: 0;
                box-shadow: 0 15px 30px rgba(15, 23, 42, 0.18);
            }

            .parcel-tooltip::before {
                border-top-color: rgba(255, 255, 255, 0.98);
            }

            .parcel-tooltip-card {
                min-width: 230px;
                padding: 0.85rem;
            }

            .parcel-tooltip-title {
                font-size: 0.85rem;
                font-weight: 800;
                color: #14532d;
                margin-bottom: 0.35rem;
            }

            .parcel-tooltip-row {
                font-size: 0.75rem;
                color: #374151;
                margin-top: 0.2rem;
            }

            .parcel-tooltip-label {
                color: #6b7280;
            }

            @media (max-width: 1180px) {
                .map-workspace {
                    grid-template-columns: 1fr;
                }

                .map-sidebar {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }


                #parcel-map {
                    height: 620px;
                    min-height: 520px;
                }
            }

            @media (max-width: 900px) {
                .map-sidebar {
                    grid-template-columns: 1fr;
                }

                .map-panel-header {
                    flex-direction: column;
                    align-items: flex-start;
                }

                #parcel-map {
                    height: 520px;
                    min-height: 460px;
                }
            }

            @media (max-width: 560px) {
                #parcel-map {
                    height: 440px;
                    min-height: 400px;
                }
            }
        </style>
    </x-slot>

    <section class="map-workspace">
        <aside class="map-sidebar">
            <div class="panel map-card">
                <div class="panel-pad">
                    <h3 class="panel-title">Map Tools</h3>
                    <p class="panel-copy">
                        Reset the map to the full Negros Oriental provincial view or open the parcel records list.
                    </p>

                    <div class="tool-list">
                        <button type="button" id="reset-map-view" class="tool-button primary">
                            <i class="fa-solid fa-expand"></i>
                            Reset View
                        </button>


                        <a href="{{ route('staff.records.parcels.index') }}" class="tool-link secondary">
                            <i class="fa-solid fa-list"></i>
                            Parcel List
                        </a>
                    </div>
                </div>
            </div>

            <div class="panel map-card">
                <div class="panel-pad">
                    <h3 class="panel-title">Legend</h3>
                    <p class="panel-copy">
                        Colors represent parcel record states used for monitoring display.
                    </p>

                    <div class="legend-list">
                        <div class="legend-item">
                            <span class="legend-dot" style="background:#22c55e;"></span>
                            Active parcel record
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot" style="background:#f59e0b;"></span>
                            Pending review reference
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot" style="background:#2563eb;"></span>
                            Linked to application
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot" style="background:#dc2626;"></span>
                            Flagged record
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel map-card">
                <div class="panel-pad">
                    <h3 class="panel-title">Access Control</h3>
                    <p class="panel-copy">
                        Staff may review broad parcel references. The map itself remains non-mutating.
                    </p>

                    <div class="access-list">
                        <div class="access-row">
                            <span class="access-label">Role</span>
                            <span class="access-value">Staff</span>
                        </div>
                        <div class="access-row">
                            <span class="access-label">Access Level</span>
                            <span class="access-value">Broad parcel viewing</span>
                        </div>
                        <div class="access-row">
                            <span class="access-label">Editing</span>
                            <span class="access-value locked">Not allowed on map</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <section class="panel map-card map-panel">
            <div class="map-panel-header">
                <div>
                    <h3 class="map-panel-title">Mapped Main Parcel Records</h3>
                    <p class="map-panel-subtitle">Click a parcel to open its staff parcel record. Source records do not appear unless linked to a main parcel record.</p>
                </div>

                <div class="map-count">
                    <i class="fa-solid fa-draw-polygon"></i>
                    {{ number_format($mappedParcelCount) }} mapped parcel{{ $mappedParcelCount === 1 ? '' : 's' }}
                </div>
            </div>

            <div class="map-frame">
                <div id="parcel-map"></div>
            </div>
        </section>
    </section>

    <x-slot name="scripts">
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin="">
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const negrosOrientalCenter = [9.3068, 123.3054];
                const parcelGeoJson = @json($parcelGeoJson);

                const map = L.map('parcel-map', {
                    zoomControl: false,
                    scrollWheelZoom: true,
                    minZoom: 7,
                    maxZoom: 20
                }).setView(negrosOrientalCenter, 12);

                L.control.zoom({
                    position: 'topright'
                }).addTo(map);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    subdomains: 'abcd',
                    maxZoom: 20,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
                }).addTo(map);

                function escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function getParcelColor(status) {
                    if (status === 'pending_legal_review') {
                        return '#f59e0b';
                    }

                    if (status === 'linked_application') {
                        return '#2563eb';
                    }

                    if (status === 'flagged') {
                        return '#dc2626';
                    }

                    return '#22c55e';
                }

                function getParcelStyle(feature) {
                    const color = getParcelColor(feature.properties.status);

                    return {
                        color: color,
                        weight: 2.5,
                        opacity: 0.98,
                        fillColor: color,
                        fillOpacity: 0.38
                    };
                }

                function getParcelHoverStyle(feature) {
                    const color = getParcelColor(feature.properties.status);

                    return {
                        color: color,
                        weight: 5,
                        opacity: 1,
                        fillColor: color,
                        fillOpacity: 0.68
                    };
                }

                function buildTooltipContent(properties) {
                    return `
                        <div class="parcel-tooltip-card">
                            <div class="parcel-tooltip-title">
                                ${escapeHtml(properties.parcel_code)}
                            </div>

                            <div class="parcel-tooltip-row">
                                <span class="parcel-tooltip-label">Landowner:</span>
                                ${escapeHtml(properties.landowner)}
                            </div>

                            <div class="parcel-tooltip-row">
                                <span class="parcel-tooltip-label">Location:</span>
                                ${escapeHtml(properties.barangay)}, ${escapeHtml(properties.municipality)}
                            </div>

                            <div class="parcel-tooltip-row">
                                <span class="parcel-tooltip-label">Area:</span>
                                ${escapeHtml(properties.area_hectares)} hectares
                            </div>

                            <div class="parcel-tooltip-row">
                                <span class="parcel-tooltip-label">Title No.:</span>
                                ${escapeHtml(properties.title_no)}
                            </div>

                            <div class="parcel-tooltip-row">
                                <span class="parcel-tooltip-label">Tax Declaration:</span>
                                ${escapeHtml(properties.tax_decl_no)}
                            </div>

                            <div class="parcel-tooltip-row">
                                <span class="parcel-tooltip-label">Click:</span>
                                open parcel record
                            </div>
                        </div>
                    `;
                }
                let parcelLayer = null;

                function onEachParcel(feature, layer) {
                    layer.bindTooltip(buildTooltipContent(feature.properties), {
                        sticky: true,
                        direction: 'top',
                        opacity: 1,
                        className: 'parcel-tooltip'
                    });

                    layer.on({
                        mouseover: function (event) {
                            const hoveredLayer = event.target;
                            hoveredLayer.setStyle(getParcelHoverStyle(feature));

                            if (hoveredLayer.setRadius) {
                                hoveredLayer.setRadius(10);
                            }

                            hoveredLayer.bringToFront();
                            hoveredLayer.openTooltip();
                        },

                        mouseout: function (event) {
                            if (parcelLayer) {
                                parcelLayer.resetStyle(event.target);
                            }

                            if (event.target.setRadius) {
                                event.target.setRadius(7);
                            }

                            event.target.closeTooltip();
                        },

                        click: function () {
                            if (feature.properties.details_url) {
                                window.location.href = feature.properties.details_url;
                            }
                        }
                    });
                }

                if (parcelGeoJson.features && parcelGeoJson.features.length > 0) {
                    parcelLayer = L.geoJSON(parcelGeoJson, {
                        style: getParcelStyle,
                        pointToLayer: function (feature, latlng) {
                            const color = getParcelColor(feature.properties.status);

                            return L.circleMarker(latlng, {
                                radius: 7,
                                color: color,
                                weight: 2.5,
                                opacity: 1,
                                fillColor: color,
                                fillOpacity: 0.62
                            });
                        },
                        onEachFeature: onEachParcel
                    }).addTo(map);

                    setTimeout(function () {
                        map.invalidateSize();
                        map.fitBounds(parcelLayer.getBounds(), {
                            padding: [40, 40],
                            animate: true,
                            duration: 0.75
                        });
                    }, 120);
                } else {
                    L.popup()
                        .setLatLng(negrosOrientalCenter)
                        .setContent(`
                            <strong>No mapped parcels yet.</strong><br>
                            Encode parcel geometry to display parcels on this map.
                        `)
                        .openOn(map);
                }

                document.getElementById('reset-map-view').addEventListener('click', function () {
                    if (parcelLayer) {
                        map.fitBounds(parcelLayer.getBounds(), {
                            padding: [40, 40],
                            animate: true,
                            duration: 0.65
                        });
                    } else {
                        map.setView(negrosOrientalCenter, 12);
                    }
                });
            });
        </script>
    </x-slot>
</x-staff-shell>
