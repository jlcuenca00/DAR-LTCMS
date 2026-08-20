@php
    $mappedParcelCount = count($parcelGeoJson['features'] ?? []);
@endphp

<x-staff-shell title="Parcel Map Viewer" active="parcel-map" maxWidth="">
    <x-slot name="head">
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9D/miZyoHS5obTRR9BMY="
            crossorigin=""
        />
    </x-slot>

    <x-slot name="styles">
        <style>
            .map-workspace {
                display: grid;
                grid-template-columns: 300px minmax(0, 1fr);
                gap: 18px;
                align-items: stretch;
            }

            .map-sidebar {
                display: grid;
                gap: 14px;
                align-content: start;
                min-width: 0;
            }

            .map-card {
                min-width: 0;
                overflow: hidden;
                border: 1px solid var(--border);
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
            }

            .panel-pad { padding: 18px 20px; }
            .panel-title { margin: 0; color: #111827; font-size: 16px; font-weight: 900; }
            .panel-copy { margin: 5px 0 0; color: #6b7280; font-size: 12.5px; line-height: 1.55; }

            .parcel-search-input-wrap { position: relative; margin-top: 14px; }
            .parcel-search-input-wrap i {
                position: absolute;
                left: 13px;
                top: 50%;
                transform: translateY(-50%);
                color: #64748b;
                font-size: 13px;
                pointer-events: none;
            }

            .parcel-search-input {
                width: 100%;
                min-height: 42px;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                background: #ffffff;
                padding: 9px 11px 9px 36px;
                color: #0f172a;
                font-size: 13px;
            }

            .parcel-search-input:focus {
                outline: none;
                border-color: #15803d;
                box-shadow: 0 0 0 3px rgba(21, 128, 61, .12);
            }

            .parcel-search-results {
                display: grid;
                gap: 7px;
                margin-top: 10px;
                max-height: 350px;
                overflow-y: auto;
            }

            .parcel-search-result {
                width: 100%;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f8fafc;
                padding: 10px 11px;
                text-align: left;
                cursor: pointer;
                transition: 140ms ease;
            }

            .parcel-search-result:hover,
            .parcel-search-result:focus {
                outline: none;
                border-color: #86efac;
                background: #f0fdf4;
            }

            .parcel-search-result-code {
                display: block;
                color: #065f46;
                font-size: 12px;
                font-weight: 900;
            }

            .parcel-search-result-meta {
                display: block;
                margin-top: 3px;
                color: #64748b;
                font-size: 11px;
                line-height: 1.35;
            }

            .parcel-search-empty {
                border: 1px dashed #cbd5e1;
                border-radius: 10px;
                padding: 12px;
                color: #64748b;
                font-size: 12px;
                text-align: center;
            }

            .legend-list { display: grid; gap: 11px; margin-top: 14px; }
            .legend-item {
                display: flex;
                align-items: center;
                gap: 10px;
                color: #4b5563;
                font-size: 12.5px;
                font-weight: 800;
            }
            .legend-dot {
                width: 11px;
                height: 11px;
                flex: 0 0 auto;
                border-radius: 999px;
                box-shadow: 0 0 0 3px rgba(15, 23, 42, .06);
            }

            .map-panel { min-width: 0; }
            .map-panel-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                padding: 18px 20px;
                border-bottom: 1px solid #e5e7eb;
                background: #ffffff;
            }
            .map-panel-title { margin: 0; color: #111827; font-size: 16px; font-weight: 900; }
            .map-panel-subtitle { margin: 4px 0 0; color: #6b7280; font-size: 12.5px; font-weight: 600; }
            .map-header-actions { display: flex; align-items: center; justify-content: flex-end; gap: 9px; flex-wrap: wrap; }
            .map-count {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                border: 1px solid #bbf7d0;
                border-radius: 999px;
                background: #f0fdf4;
                color: #14532d;
                font-size: 12px;
                font-weight: 900;
                white-space: nowrap;
            }

            .map-frame { padding: 12px; background: #ffffff; }
            #parcel-map {
                width: 100%;
                height: calc(100vh - 212px);
                min-height: 590px;
                overflow: hidden;
                border: 1px solid #d1d5db;
                border-radius: 12px;
                background: #eef2f0;
                box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .03);
            }

            .leaflet-container { background: #eef2f0; }
            .leaflet-control-zoom {
                overflow: hidden;
                border: 1px solid #d1d5db !important;
                border-radius: 10px !important;
                box-shadow: 0 8px 18px rgba(15, 23, 42, .10) !important;
            }
            .leaflet-control-zoom a {
                border-color: #e5e7eb !important;
                background: #ffffff !important;
                color: #14532d !important;
                font-weight: 900;
            }
            .leaflet-control-zoom a:hover { background: #f0fdf4 !important; }
            .leaflet-control-attribution { background: rgba(255,255,255,.92) !important; color: #6b7280 !important; }
            .leaflet-control-attribution a { color: #166534 !important; }

            .parcel-tooltip {
                padding: 0;
                border: 1px solid #bbf7d0;
                border-radius: 12px;
                background: rgba(255,255,255,.98);
                color: #111827;
                box-shadow: 0 15px 30px rgba(15,23,42,.18);
            }
            .parcel-tooltip::before { border-top-color: #ffffff; }
            .parcel-tooltip-card { min-width: 230px; padding: 13px; }
            .parcel-tooltip-title { margin-bottom: 6px; color: #14532d; font-size: 13px; font-weight: 900; }
            .parcel-tooltip-row { margin-top: 4px; color: #374151; font-size: 11px; line-height: 1.4; }
            .parcel-tooltip-label { color: #6b7280; font-weight: 800; }
            .parcel-tooltip-row.is-flagged { color: #b91c1c; font-weight: 800; }

            @media (max-width: 1180px) {
                .map-workspace { grid-template-columns: 1fr; }
                .map-sidebar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                #parcel-map { height: 620px; min-height: 520px; }
            }

            @media (max-width: 900px) {
                .map-sidebar { grid-template-columns: 1fr; }
                .map-panel-header { flex-direction: column; align-items: flex-start; }
                #parcel-map { height: 520px; min-height: 460px; }
            }

            @media (max-width: 560px) {
                #parcel-map { height: 440px; min-height: 400px; }
            }
        </style>
    </x-slot>

    <section class="map-workspace">
        <aside class="map-sidebar">
            <div class="map-card">
                <div class="panel-pad">
                    <h3 class="panel-title">Find a Parcel</h3>
                    <p class="panel-copy">Search mapped parcels by parcel code, title number, landowner, or location.</p>

                    <div class="parcel-search-input-wrap">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input id="parcel-map-search" type="search" class="parcel-search-input" placeholder="Search mapped parcels" autocomplete="off">
                    </div>
                    <div id="parcel-search-results" class="parcel-search-results" aria-live="polite"></div>
                </div>
            </div>

            <div class="map-card">
                <div class="panel-pad">
                    <h3 class="panel-title">Map Legend</h3>
                    <p class="panel-copy">Review flags identify records that require additional administrative or technical verification.</p>
                    <div class="legend-list">
                        <div class="legend-item"><span class="legend-dot" style="background:#22c55e;"></span>Mapped parcel record</div>
                        <div class="legend-item"><span class="legend-dot" style="background:#dc2626;"></span>Flagged for review</div>
                    </div>
                </div>
            </div>
        </aside>

        <section class="map-card map-panel">
            <div class="map-panel-header">
                <div>
                    <h3 class="map-panel-title">Mapped Parcel Records</h3>
                    <p class="map-panel-subtitle">Select a search result to focus the map, or click a parcel boundary to open its record.</p>
                </div>
                <div class="map-header-actions">
                    <div class="map-count">
                        <i class="fa-solid fa-draw-polygon"></i>
                        {{ number_format($mappedParcelCount) }} mapped parcel{{ $mappedParcelCount === 1 ? '' : 's' }}
                    </div>
                    <button type="button" id="reset-map-view" class="staff-button staff-button-light">
                        <i class="fa-solid fa-expand"></i>
                        Reset View
                    </button>
                </div>
            </div>
            <div class="map-frame"><div id="parcel-map"></div></div>
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
                const mapContainer = document.getElementById('parcel-map');
                if (!mapContainer) return;

                if (typeof window.L === 'undefined') {
                    mapContainer.innerHTML = '<div style="height:100%;min-height:360px;display:grid;place-items:center;padding:24px;text-align:center;color:#475569;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;"><div><strong style="display:block;color:#0f172a;margin-bottom:6px;">Map resources could not be loaded.</strong><span>Check the internet connection, then refresh the page. Parcel records remain available in the list and detail views.</span></div></div>';
                    return;
                }

                const negrosOrientalCenter = [9.3068, 123.3054];
                const parcelGeoJson = @json($parcelGeoJson);
                const searchInput = document.getElementById('parcel-map-search');
                const searchResults = document.getElementById('parcel-search-results');
                const parcelLayers = new Map();

                const map = L.map('parcel-map', {
                    zoomControl: false,
                    scrollWheelZoom: true,
                    minZoom: 7,
                    maxZoom: 20
                }).setView(negrosOrientalCenter, 12);

                L.control.zoom({ position: 'topright' }).addTo(map);
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
                    return status === 'flagged' ? '#dc2626' : '#22c55e';
                }

                function getParcelStyle(feature) {
                    const color = getParcelColor(feature.properties.status);
                    return { color, weight: 2.5, opacity: .98, fillColor: color, fillOpacity: .38 };
                }

                function getParcelHoverStyle(feature) {
                    const color = getParcelColor(feature.properties.status);
                    return { color, weight: 5, opacity: 1, fillColor: color, fillOpacity: .68 };
                }

                function buildTooltipContent(properties) {
                    const flagRow = properties.is_flagged
                        ? `<div class="parcel-tooltip-row is-flagged"><span class="parcel-tooltip-label">Review flag:</span> ${escapeHtml(properties.flag_reason || 'Requires verification')}</div>`
                        : '';

                    return `
                        <div class="parcel-tooltip-card">
                            <div class="parcel-tooltip-title">${escapeHtml(properties.parcel_code)}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Landowner:</span> ${escapeHtml(properties.landowner)}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Location:</span> ${escapeHtml(properties.barangay)}, ${escapeHtml(properties.municipality)}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Area:</span> ${escapeHtml(properties.area_hectares)} hectares</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Title No.:</span> ${escapeHtml(properties.title_no)}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Tax Declaration:</span> ${escapeHtml(properties.tax_decl_no)}</div>
                            ${flagRow}
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Click:</span> open parcel record</div>
                        </div>`;
                }

                let parcelLayer = null;

                function onEachParcel(feature, layer) {
                    parcelLayers.set(String(feature.properties.id), layer);
                    layer.bindTooltip(buildTooltipContent(feature.properties), {
                        sticky: true,
                        direction: 'top',
                        opacity: 1,
                        className: 'parcel-tooltip'
                    });

                    layer.on({
                        mouseover: function (event) {
                            event.target.setStyle(getParcelHoverStyle(feature));
                            event.target.bringToFront();
                            event.target.openTooltip();
                        },
                        mouseout: function (event) {
                            if (parcelLayer) parcelLayer.resetStyle(event.target);
                            event.target.closeTooltip();
                        },
                        click: function () {
                            if (feature.properties.details_url) window.location.href = feature.properties.details_url;
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
                                color,
                                weight: 2.5,
                                opacity: 1,
                                fillColor: color,
                                fillOpacity: .62
                            });
                        },
                        onEachFeature: onEachParcel
                    }).addTo(map);

                    setTimeout(function () {
                        map.invalidateSize();
                        map.fitBounds(parcelLayer.getBounds(), { padding: [40, 40], animate: true, duration: .75 });
                    }, 120);
                } else {
                    L.popup()
                        .setLatLng(negrosOrientalCenter)
                        .setContent('<strong>No mapped parcels yet.</strong><br>Encode parcel geometry to display parcels on this map.')
                        .openOn(map);
                }

                function featureSearchText(feature) {
                    const p = feature.properties || {};
                    return [p.parcel_code, p.title_no, p.tax_decl_no, p.landowner, p.municipality, p.barangay].join(' ').toLowerCase();
                }

                function focusParcel(feature) {
                    const layer = parcelLayers.get(String(feature.properties.id));
                    if (!layer) return;

                    if (typeof layer.getBounds === 'function') {
                        map.fitBounds(layer.getBounds(), { padding: [70, 70], maxZoom: 17, animate: true, duration: .55 });
                    } else if (typeof layer.getLatLng === 'function') {
                        map.setView(layer.getLatLng(), 17, { animate: true });
                    }

                    setTimeout(() => layer.openTooltip(), 450);
                }

                function renderSearchResults(query = '') {
                    if (!searchResults) return;

                    const normalized = query.trim().toLowerCase();
                    const features = (parcelGeoJson.features || [])
                        .filter(feature => !normalized || featureSearchText(feature).includes(normalized))
                        .slice(0, 8);

                    searchResults.innerHTML = '';

                    if (!features.length) {
                        searchResults.innerHTML = '<div class="parcel-search-empty">No mapped parcels match this search.</div>';
                        return;
                    }

                    features.forEach(function (feature) {
                        const p = feature.properties || {};
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'parcel-search-result';
                        button.innerHTML = `<span class="parcel-search-result-code">${escapeHtml(p.parcel_code || 'Parcel record')}</span><span class="parcel-search-result-meta">${escapeHtml(p.barangay || 'N/A')}, ${escapeHtml(p.municipality || 'N/A')} · ${escapeHtml(p.area_hectares || 'N/A')} ha</span>`;
                        button.addEventListener('click', () => focusParcel(feature));
                        searchResults.appendChild(button);
                    });
                }

                document.getElementById('reset-map-view')?.addEventListener('click', function () {
                    if (parcelLayer) {
                        map.fitBounds(parcelLayer.getBounds(), { padding: [40, 40], animate: true, duration: .65 });
                    } else {
                        map.setView(negrosOrientalCenter, 12);
                    }
                });

                searchInput?.addEventListener('input', event => renderSearchResults(event.target.value));
                renderSearchResults();
            });
        </script>
    </x-slot>
</x-staff-shell>
