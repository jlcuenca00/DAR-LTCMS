<x-geodetic-shell
    title="Parcel Map Viewer"
    active="parcel-map"
>
    @push('styles')
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
            crossorigin=""
        />

        <style>
            .geo-map-layout {
                display: grid;
                grid-template-columns: 320px minmax(0, 1fr);
                gap: 18px;
                align-items: stretch;
            }

            .geo-map-sidebar {
                display: grid;
                gap: 14px;
                align-content: start;
            }

            .geo-map-card {
                background: #ffffff;
                border: 1px solid var(--geo-line);
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                padding: 18px;
            }

            .geo-map-title {
                margin: 0;
                font-size: 17px;
                font-weight: 900;
                color: var(--geo-ink);
            }

            .geo-map-subtitle {
                margin: 6px 0 0;
                font-size: 13px;
                color: var(--geo-muted);
                line-height: 1.45;
            }

            .geo-map-tools {
                margin-top: 14px;
                display: grid;
                gap: 10px;
            }

            .geo-map-button {
                width: 100%;
                min-height: 42px;
                border: 1px solid var(--geo-line);
                border-radius: 10px;
                background: #ffffff;
                color: #374151;
                font: inherit;
                font-size: 12px;
                font-weight: 900;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                text-decoration: none;
                cursor: pointer;
                display: inline-flex;
                justify-content: center;
                align-items: center;
                gap: 8px;
                padding: 10px 12px;
                transition: 160ms ease;
            }

            .geo-map-button:hover {
                background: #f8faf9;
                border-color: #c7d2cc;
            }

            .geo-map-button.primary {
                background: var(--geo-green-800);
                border-color: var(--geo-green-800);
                color: #ffffff;
            }

            .geo-map-button.primary:hover {
                background: var(--geo-green-900);
                border-color: var(--geo-green-900);
            }

            .geo-legend-list {
                margin-top: 14px;
                display: grid;
                gap: 10px;
                color: #344054;
                font-size: 13px;
            }

            .geo-legend-row {
                display: flex;
                align-items: center;
                gap: 9px;
                line-height: 1.35;
            }

            .geo-legend-dot {
                width: 11px;
                height: 11px;
                border-radius: 999px;
                flex: 0 0 auto;
            }

            .geo-access-list {
                margin-top: 14px;
                display: grid;
                gap: 10px;
                font-size: 13px;
            }

            .geo-access-row {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                border-bottom: 1px solid #edf0ee;
                padding-bottom: 8px;
            }

            .geo-access-row:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .geo-access-label {
                color: var(--geo-muted);
            }

            .geo-access-value {
                color: var(--geo-ink);
                font-weight: 900;
                text-align: right;
            }

            .geo-map-panel {
                background: #ffffff;
                border: 1px solid var(--geo-line);
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                padding: 12px;
                min-width: 0;
            }

            #parcel-map {
                height: calc(100vh - 212px);
                min-height: 590px;
                width: 100%;
                border-radius: 10px;
                border: 1px solid #d7ded9;
                overflow: hidden;
                background: #eef2f0;
            }

            .leaflet-control-zoom a {
                background: #ffffff !important;
                color: var(--geo-green-900) !important;
                border-color: #d7ded9 !important;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
            }

            .leaflet-control-zoom a:hover {
                background: var(--geo-green-50) !important;
            }

            .leaflet-control-attribution {
                background: rgba(255, 255, 255, 0.92) !important;
                color: #475569 !important;
                border-radius: 0.5rem 0 0 0;
            }

            .leaflet-control-attribution a {
                color: var(--geo-green-800) !important;
            }

            .leaflet-popup-content-wrapper,
            .leaflet-popup-tip {
                background: #ffffff;
                color: #111827;
                border: 1px solid #d7ded9;
                box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
            }

            .leaflet-popup-content {
                margin: 14px 16px;
                font-family: inherit;
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
                border-top-color: #ffffff;
            }

            .parcel-tooltip-card {
                min-width: 240px;
                padding: 0.85rem;
            }

            .parcel-tooltip-title {
                font-size: 0.86rem;
                font-weight: 900;
                color: var(--geo-green-900);
                margin-bottom: 0.4rem;
            }

            .parcel-tooltip-row {
                font-size: 0.76rem;
                color: #344054;
                margin-top: 0.24rem;
                line-height: 1.4;
            }

            .parcel-tooltip-label {
                color: #667085;
                font-weight: 800;
            }

            @media (max-width: 1100px) {
                .geo-map-layout {
                    grid-template-columns: 1fr;
                }

                #parcel-map {
                    height: 580px;
                    min-height: 480px;
                }
            }
        </style>
    @endpush

    <section class="geo-map-layout">
        <aside class="geo-map-sidebar">
            <article class="geo-map-card">
                <h2 class="geo-map-title">Map Tools</h2>
                <p class="geo-map-subtitle">Reset the map to the full Negros Oriental provincial view or open the parcel records list.</p>

                <div class="geo-map-tools">
                    <button type="button" id="reset-map-view" class="geo-map-button primary">
                        <i class="fa-solid fa-expand"></i>
                        Reset View
                    </button>

                    <a href="{{ route('geodetic.parcels.index') }}" class="geo-map-button">
                        <i class="fa-solid fa-list"></i>
                        Parcel List
                    </a>
                </div>
            </article>

            <article class="geo-map-card">
                <h2 class="geo-map-title">Legend</h2>
                <p class="geo-map-subtitle">Colors represent parcel record states used for monitoring display.</p>

                <div class="geo-legend-list">
                    <div class="geo-legend-row">
                        <span class="geo-legend-dot" style="background:#22c55e;"></span>
                        <strong>Active parcel record</strong>
                    </div>
                    <div class="geo-legend-row">
                        <span class="geo-legend-dot" style="background:#f59e0b;"></span>
                        <strong>Pending review reference</strong>
                    </div>
                    <div class="geo-legend-row">
                        <span class="geo-legend-dot" style="background:#2563eb;"></span>
                        <strong>Linked to application</strong>
                    </div>
                    <div class="geo-legend-row">
                        <span class="geo-legend-dot" style="background:#dc2626;"></span>
                        <strong>Flagged record</strong>
                    </div>
                </div>
            </article>

            <article class="geo-map-card">
                <h2 class="geo-map-title">Current Viewer Access</h2>
                <div class="geo-access-list">
                    <div class="geo-access-row">
                        <span class="geo-access-label">Role</span>
                        <span class="geo-access-value">Geodetic Personnel</span>
                    </div>
                    <div class="geo-access-row">
                        <span class="geo-access-label">Access Level</span>
                        <span class="geo-access-value">Read-only</span>
                    </div>
                    <div class="geo-access-row">
                        <span class="geo-access-label">Record Editing</span>
                        <span class="geo-access-value">Not allowed</span>
                    </div>
                    <div class="geo-access-row">
                        <span class="geo-access-label">Ownership / Registry Changes</span>
                        <span class="geo-access-value">Not performed by system</span>
                    </div>
                </div>
            </article>
        </aside>

        <section class="geo-map-panel">
            <div id="parcel-map"></div>
        </section>
    </section>

    @push('scripts')
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
                    scrollWheelZoom: true
                }).setView(negrosOrientalCenter, 12);

                L.control.zoom({ position: 'topright' }).addTo(map);

                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    subdomains: 'abcd',
                    maxZoom: 20,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
                }).addTo(map);

                function getParcelColor(status) {
                    if (status === 'pending_legal_review') {
                        return '#ea580c';
                    }

                    if (status === 'linked_reference') {
                        return '#334155';
                    }

                    if (status === 'flagged') {
                        return '#dc2626';
                    }

                    return '#15803d';
                }

                function getParcelStyle(feature) {
                    const color = getParcelColor(feature.properties.status);

                    return {
                        color: color,
                        weight: 2,
                        opacity: 0.95,
                        fillColor: color,
                        fillOpacity: 0.34
                    };
                }

                function getParcelHoverStyle(feature) {
                    const color = getParcelColor(feature.properties.status);

                    return {
                        color: color,
                        weight: 5,
                        opacity: 1,
                        fillColor: color,
                        fillOpacity: 0.62
                    };
                }

                function buildTooltipContent(properties) {
                    return `
                        <div class="parcel-tooltip-card">
                            <div class="parcel-tooltip-title">${properties.parcel_code}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Landowner:</span> ${properties.landowner}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Location:</span> ${properties.barangay}, ${properties.municipality}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Area:</span> ${properties.area_hectares} hectares</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Title No.:</span> ${properties.title_no}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Tax Declaration:</span> ${properties.tax_decl_no}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Click:</span> open parcel reference</div>
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

                if (parcelGeoJson.features.length > 0) {
                    parcelLayer = L.geoJSON(parcelGeoJson, {
                        style: getParcelStyle,
                        pointToLayer: function (feature, latlng) {
                            const color = getParcelColor(feature.properties.status);

                            return L.circleMarker(latlng, {
                                radius: 7,
                                color: color,
                                weight: 2,
                                opacity: 1,
                                fillColor: color,
                                fillOpacity: 0.56
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
                        .setContent('<strong>No mapped parcels yet.</strong><br>Encode parcel geometry from staff-side records to display parcels on this map.')
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
    @endpush
</x-geodetic-shell>
