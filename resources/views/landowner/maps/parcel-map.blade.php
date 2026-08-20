@php
    $mappedParcelCount = count($parcelGeoJson['features'] ?? []);
@endphp

<x-landowner-shell title="My Parcel Map" active="parcel-map">
    @push('styles')
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9D/miZyoHS5obTRR9BMY="
            crossorigin=""
        />

        <style>
            .lo-map-layout {
                display: grid;
                grid-template-columns: 310px minmax(0, 1fr);
                gap: 18px;
                align-items: stretch;
            }

            .lo-map-sidebar { display: grid; gap: 14px; align-content: start; min-width: 0; }

            .lo-map-card,
            .lo-map-panel {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
            }

            .lo-map-card { padding: 17px; }
            .lo-map-panel { min-width: 0; padding: 11px; }
            .lo-map-title { margin: 0; color: var(--lo-ink); font-size: 16px; font-weight: 900; }
            .lo-map-subtitle { margin: 5px 0 0; color: var(--lo-muted); font-size: 12px; line-height: 1.45; }

            .lo-map-count {
                margin-top: 12px;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                min-height: 28px;
                padding: 0 9px;
                border: 1px solid #bbf7d0;
                border-radius: 999px;
                background: var(--lo-green-50);
                color: var(--lo-green-900);
                font-size: 10px;
                font-weight: 900;
            }

            .lo-search-wrap { position: relative; margin-top: 13px; }
            .lo-search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #667085; font-size: 12px; pointer-events: none; }
            .lo-search-input {
                width: 100%;
                min-height: 40px;
                border: 1px solid #cbd5d1;
                border-radius: 9px;
                background: #ffffff;
                padding: 8px 10px 8px 34px;
                color: #111827;
                font: inherit;
                font-size: 12px;
            }
            .lo-search-input:focus { outline: none; border-color: var(--lo-green-700); box-shadow: 0 0 0 3px rgba(21, 128, 61, .12); }

            .lo-search-results { margin-top: 9px; display: grid; gap: 6px; max-height: 310px; overflow-y: auto; }
            .lo-search-result {
                width: 100%;
                border: 1px solid #e2e8f0;
                border-radius: 9px;
                background: #f8faf9;
                padding: 9px 10px;
                text-align: left;
                cursor: pointer;
                transition: 140ms ease;
            }
            .lo-search-result:hover,
            .lo-search-result:focus { outline: none; border-color: #86efac; background: var(--lo-green-50); }
            .lo-search-code { display: block; color: var(--lo-green-900); font-size: 11px; font-weight: 900; }
            .lo-search-meta { display: block; margin-top: 3px; color: #667085; font-size: 10px; line-height: 1.35; }
            .lo-search-empty { border: 1px dashed #cbd5d1; border-radius: 9px; padding: 11px; color: #667085; font-size: 11px; text-align: center; }

            .lo-map-tools { margin-top: 13px; display: grid; gap: 8px; }
            .lo-map-button {
                width: 100%;
                min-height: 39px;
                border: 1px solid #d7ded9;
                border-radius: 9px;
                background: #ffffff;
                color: #344054;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 8px 11px;
                font: inherit;
                font-size: 11px;
                font-weight: 900;
                text-decoration: none;
                cursor: pointer;
            }
            .lo-map-button:hover { background: #f8faf9; border-color: #bbf7d0; color: var(--lo-green-900); }
            .lo-map-button.primary { border-color: var(--lo-green-800); background: var(--lo-green-800); color: #ffffff; }
            .lo-map-button.primary:hover { background: var(--lo-green-900); }

            .lo-legend-list { margin-top: 12px; display: grid; gap: 9px; }
            .lo-legend-row { display: flex; align-items: center; gap: 9px; color: #475569; font-size: 11px; font-weight: 750; }
            .lo-legend-dot { width: 10px; height: 10px; border-radius: 999px; flex: 0 0 auto; }

            #parcel-map {
                width: 100%;
                height: calc(100vh - 180px);
                min-height: 620px;
                border: 1px solid #d7ded9;
                border-radius: 11px;
                overflow: hidden;
                background: #eef2f0;
            }

            .leaflet-control-zoom a { background: #ffffff !important; color: var(--lo-green-900) !important; border-color: #d7ded9 !important; }
            .leaflet-control-zoom a:hover { background: var(--lo-green-50) !important; }
            .leaflet-control-attribution { background: rgba(255,255,255,.92) !important; color: #475569 !important; }
            .leaflet-control-attribution a { color: var(--lo-green-800) !important; }
            .leaflet-popup-content-wrapper,
            .leaflet-popup-tip { background: #ffffff; color: #111827; border: 1px solid #d7ded9; box-shadow: 0 18px 40px rgba(15,23,42,.18); }
            .leaflet-popup-content { margin: 14px 16px; font-family: inherit; }

            .parcel-tooltip { background: rgba(255,255,255,.98); color: #111827; border: 1px solid #bbf7d0; border-radius: 10px; padding: 0; box-shadow: 0 15px 30px rgba(15,23,42,.18); }
            .parcel-tooltip::before { border-top-color: #ffffff; }
            .parcel-tooltip-card { min-width: 230px; padding: 12px; }
            .parcel-tooltip-title { color: var(--lo-green-900); font-size: 12px; font-weight: 900; margin-bottom: 6px; }
            .parcel-tooltip-row { margin-top: 4px; color: #344054; font-size: 10px; line-height: 1.4; }
            .parcel-tooltip-label { color: #667085; font-weight: 900; }

            @media (max-width: 1100px) {
                .lo-map-layout { grid-template-columns: 1fr; }
                #parcel-map { height: 580px; min-height: 480px; }
            }
        </style>
    @endpush

    <section class="lo-map-layout">
        <aside class="lo-map-sidebar">
            <article class="lo-map-card">
                <h2 class="lo-map-title">Find My Parcel</h2>
                <p class="lo-map-subtitle">Search the parcel code, title reference, tax declaration, or location.</p>
                <span class="lo-map-count"><i class="fa-solid fa-map-location-dot"></i>{{ $mappedParcelCount }} mapped</span>

                <div class="lo-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="parcel-search" type="search" class="lo-search-input" placeholder="Search linked parcels" autocomplete="off">
                </div>
                <div id="parcel-search-results" class="lo-search-results" aria-live="polite"></div>
            </article>

            <article class="lo-map-card">
                <h2 class="lo-map-title">Map Tools</h2>
                <p class="lo-map-subtitle">Return to the full linked-parcel view or open the records list.</p>
                <div class="lo-map-tools">
                    <button type="button" id="reset-map-view" class="lo-map-button primary"><i class="fa-solid fa-expand"></i>Reset View</button>
                    <a href="{{ route('landowner.parcels.index') }}" class="lo-map-button"><i class="fa-solid fa-list"></i>Parcel List</a>
                </div>
            </article>

            <article class="lo-map-card">
                <h2 class="lo-map-title">Legend</h2>
                <div class="lo-legend-list">
                    <div class="lo-legend-row"><span class="lo-legend-dot" style="background:#15803d;"></span>Your mapped parcel record</div>
                </div>
            </article>
        </aside>

        <section class="lo-map-panel">
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
                const mapContainer = document.getElementById('parcel-map');

                if (typeof window.L === 'undefined') {
                    if (mapContainer) {
                        mapContainer.innerHTML = '<div style="height:100%;min-height:360px;display:grid;place-items:center;padding:24px;text-align:center;color:#475569;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;"><div><strong style="display:block;color:#0f172a;margin-bottom:6px;">Map resources could not be loaded.</strong><span>Check the internet connection, then refresh the page. Parcel records remain available in the list and detail views.</span></div></div>';
                    }
                    return;
                }
                const negrosOrientalCenter = [9.3068, 123.3054];
                const parcelGeoJson = @json($parcelGeoJson);
                const parcelLayersById = {};
                const searchInput = document.getElementById('parcel-search');
                const searchResults = document.getElementById('parcel-search-results');

                const map = L.map('parcel-map', { zoomControl: false, scrollWheelZoom: true }).setView(negrosOrientalCenter, 12);
                L.control.zoom({ position: 'topright' }).addTo(map);

                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    subdomains: 'abcd',
                    maxZoom: 20,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
                }).addTo(map);

                function escapeHtml(value) {
                    return String(value ?? '').replace(/[&<>'"]/g, function (character) {
                        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character];
                    });
                }

                function parcelColor() {
                    return '#15803d';
                }

                function parcelStyle(feature) {
                    const color = parcelColor(feature.properties.status);
                    return { color, weight: 2, opacity: .95, fillColor: color, fillOpacity: .34 };
                }

                function hoverStyle(feature) {
                    const color = parcelColor(feature.properties.status);
                    return { color, weight: 5, opacity: 1, fillColor: color, fillOpacity: .62 };
                }

                function tooltipContent(properties) {
                    return `
                        <div class="parcel-tooltip-card">
                            <div class="parcel-tooltip-title">${escapeHtml(properties.parcel_code)}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Location:</span> ${escapeHtml(properties.barangay)}, ${escapeHtml(properties.municipality)}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Area:</span> ${escapeHtml(properties.area_hectares)} hectares</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Title:</span> ${escapeHtml(properties.title_no)}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Tax declaration:</span> ${escapeHtml(properties.tax_decl_no)}</div>
                            <div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Select:</span> open parcel details</div>
                        </div>`;
                }

                let parcelLayer = null;

                function onEachParcel(feature, layer) {
                    parcelLayersById[String(feature.properties.id)] = layer;
                    layer.bindTooltip(tooltipContent(feature.properties), { sticky: true, direction: 'top', opacity: 1, className: 'parcel-tooltip' });
                    layer.on({
                        mouseover: function (event) {
                            event.target.setStyle(hoverStyle(feature));
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

                if (parcelGeoJson.features.length > 0) {
                    parcelLayer = L.geoJSON(parcelGeoJson, {
                        style: parcelStyle,
                        pointToLayer: function (feature, latlng) {
                            const color = parcelColor(feature.properties.status);
                            return L.circleMarker(latlng, { radius: 7, color, weight: 2, opacity: 1, fillColor: color, fillOpacity: .56 });
                        },
                        onEachFeature: onEachParcel
                    }).addTo(map);

                    setTimeout(function () {
                        map.invalidateSize();
                        map.fitBounds(parcelLayer.getBounds(), { padding: [40, 40], animate: true, duration: .75 });
                    }, 120);
                } else {
                    L.popup().setLatLng(negrosOrientalCenter).setContent('<strong>No mapped parcels are linked to your account.</strong>').openOn(map);
                }

                function focusFeature(feature) {
                    const layer = parcelLayersById[String(feature.properties.id)];
                    if (!layer) return;
                    if (typeof layer.getBounds === 'function') {
                        map.fitBounds(layer.getBounds(), { padding: [70, 70], maxZoom: 17, animate: true, duration: .55 });
                    } else if (typeof layer.getLatLng === 'function') {
                        map.setView(layer.getLatLng(), 17, { animate: true });
                    }
                    layer.openTooltip();
                }

                function searchText(feature) {
                    const p = feature.properties || {};
                    return [p.parcel_code, p.title_no, p.tax_decl_no, p.barangay, p.municipality].join(' ').toLowerCase();
                }

                function renderResults(query = '') {
                    const normalized = query.trim().toLowerCase();
                    const matches = parcelGeoJson.features.filter(feature => !normalized || searchText(feature).includes(normalized)).slice(0, 8);
                    searchResults.innerHTML = '';

                    if (!matches.length) {
                        searchResults.innerHTML = '<div class="lo-search-empty">No linked parcel matches the search.</div>';
                        return;
                    }

                    matches.forEach(function (feature) {
                        const p = feature.properties;
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'lo-search-result';
                        button.innerHTML = `<span class="lo-search-code">${escapeHtml(p.parcel_code)}</span><span class="lo-search-meta">${escapeHtml(p.barangay)}, ${escapeHtml(p.municipality)} · ${escapeHtml(p.area_hectares)} ha</span>`;
                        button.addEventListener('click', function () { focusFeature(feature); });
                        searchResults.appendChild(button);
                    });
                }

                searchInput.addEventListener('input', function () { renderResults(searchInput.value); });
                renderResults();

                document.getElementById('reset-map-view').addEventListener('click', function () {
                    if (parcelLayer) {
                        map.fitBounds(parcelLayer.getBounds(), { padding: [40, 40], animate: true, duration: .65 });
                    } else {
                        map.setView(negrosOrientalCenter, 12);
                    }
                });
            });
        </script>
    @endpush
</x-landowner-shell>
