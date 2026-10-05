function initializeParcelMapViewer() {
    const container = document.querySelector('[data-parcel-map-viewer]');
    const configNode = document.querySelector('[data-parcel-map-config]');
    if (!container || !configNode) return;

    const config = JSON.parse(configNode.textContent);
    const search = document.getElementById('parcel-map-search') || document.getElementById('parcel-search');
    const results = document.getElementById('parcel-search-results');
    const searchStatus = document.getElementById('parcel-search-status');
    const pages = document.getElementById('parcel-search-pages');
    const mapStatus = document.getElementById('parcel-map-status');
    const prefix = config.role === 'staff' ? 'parcel-search-result' : config.role === 'geodetic' ? 'geo-search' : 'lo-search';
    const buttonClass = config.role === 'staff' ? prefix : prefix + '-result';
    const codeClass = config.role === 'staff' ? prefix + '-code' : prefix + '-code';
    const metaClass = config.role === 'staff' ? prefix + '-meta' : prefix + '-meta';
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    let map = null;
    let parcelLayer = null;
    let activeTooltip = null;
    let selectedFeature = null;
    let viewportRequest = null;
    let searchRequest = null;
    let focusRequest = null;
    let searchTimer;
    let mapTimer;
    let focusGeneration = 0;

    async function getJson(url, signal) {
        const response = await fetch(url, { signal, credentials: 'same-origin', headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Parcel data could not be loaded.');
        return response.json();
    }

    function renderSearchResults(data) {
        results.replaceChildren();
        pages.replaceChildren();
        searchStatus.textContent = data.total ? 'Page ' + data.page + ' of ' + data.last_page + ' · ' + data.total + ' matching parcels' : 'No mapped parcels match this search.';
        for (const record of data.items) {
            const item = document.createElement('div');
            item.className = buttonClass;
            item.dataset.parcelSearchRow = '';
            const code = document.createElement('span');
            code.className = codeClass;
            code.textContent = record.parcel_code || 'Parcel record';
            const meta = document.createElement('span');
            meta.className = metaClass;
            meta.textContent = [record.barangay, record.municipality].filter(Boolean).join(', ') || 'Location not recorded';
            const area = document.createElement('div');
            area.className = 'parcel-search-area';
            const areaLabel = document.createElement('span');
            areaLabel.textContent = 'Parcel area';
            const areaValue = document.createElement('strong');
            areaValue.textContent = record.area_hectares == null ? 'Not recorded' : String(record.area_hectares) + ' ha';
            area.append(areaLabel, areaValue);
            const show = document.createElement('button');
            show.type = 'button';
            show.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>';
            show.setAttribute('aria-label', 'Show ' + (record.parcel_code || 'parcel') + ' on map');
            show.title = 'Show on map';
            show.dataset.mapFocus = 'true';
            show.hidden = !map;
            show.className = 'parcel-search-map-target';
            show.addEventListener('click', () => focusParcel(record));
            const link = document.createElement('a');
            link.href = record.details_url;
            link.setAttribute('aria-label', 'Open ' + (record.parcel_code || 'parcel') + ' record');
            link.title = 'Open record';
            link.className = 'parcel-search-record';
            const arrow = document.createElement('span');
            arrow.className = 'parcel-search-record-arrow';
            arrow.textContent = '↗';
            arrow.setAttribute('aria-hidden', 'true');
            code.appendChild(arrow);
            link.append(code, meta, area);
            item.append(link, show);
            results.appendChild(item);
        }
        for (const [label, page, disabled] of [
            ['Previous', data.page - 1, data.page <= 1],
            ['Next', data.page + 1, data.page >= data.last_page],
        ]) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = label;
            button.disabled = disabled;
            button.className = 'parcel-search-page-button';
            button.setAttribute('aria-label', label + ' parcel search page');
            button.addEventListener('click', () => loadSearch(page));
            pages.appendChild(button);
        }
    }

    async function loadSearch(page = 1) {
        searchRequest?.abort();
        const request = new AbortController();
        searchRequest = request;
        const url = new URL(config.search_url);
        url.searchParams.set('q', (search?.value || '').trim());
        url.searchParams.set('page', String(page));
        searchStatus.textContent = 'Searching parcel records…';
        try {
            const data = await getJson(url, request.signal);
            if (searchRequest !== request) return;
            renderSearchResults(data);
        } catch (error) {
            if (error.name !== 'AbortError') searchStatus.textContent = 'Search could not be loaded. The parcel list remains available. Try the search again.';
        }
    }

    function tooltip(record) {
        const row = (label, value) => '<div class="parcel-tooltip-row"><span class="parcel-tooltip-label">' + label + ':</span> ' + esc(value) + '</div>';
        return '<div class="parcel-tooltip-card"><div class="parcel-tooltip-title">' + esc(record.parcel_code) + '</div>' +
            row('Landowner reference', record.landowner) + row('Reference scope', record.reference_scope) +
            row('Location', record.barangay + ', ' + record.municipality) +
            row('Parcel area', (record.area_hectares ?? 'N/A') + ' ha') +
            row(config.role === 'landowner' ? 'Your current active linked area' : 'Current active linked area', record.active_linked_area_hectares + ' ha') +
            row('Historical/non-active holding records', record.historical_holding_count) +
            row('Title No.', record.title_no) + row('Tax Declaration', record.tax_decl_no) +
            (record.is_flagged ? row('Review flag', record.flag_reason) : '') +
            row('Click', 'Open parcel record') + '</div>';
    }

    function draw(features) {
        // Close the overlay before replacing its source layers, including while
        // a pointer remains over a parcel during an asynchronous viewport redraw.
        if (activeTooltip) {
            map.closeTooltip(activeTooltip);
            activeTooltip = null;
        }
        if (parcelLayer) {
            parcelLayer.eachLayer(layer => layer.closeTooltip());
            map.removeLayer(parcelLayer);
        }
        parcelLayer = window.L.geoJSON([], {
            style: feature => {
                const color = feature.properties.is_flagged ? '#dc2626' : '#22c55e';
                return { color, weight: 2.5, fillColor: color, fillOpacity: 0.38 };
            },
            onEachFeature(feature, layer) {
                layer.bindTooltip(tooltip(feature.properties), { sticky: true, direction: 'top', opacity: 1, className: 'parcel-tooltip' });
                layer.on('click', () => { window.location.href = feature.properties.details_url; });
            },
        }).addTo(map);
        let invalid = 0;
        const bounded = features.slice(0, 50);
        if (selectedFeature && !bounded.some(feature => feature.properties.id === selectedFeature.properties.id)) bounded.push(selectedFeature);
        for (const feature of bounded) {
            try { parcelLayer.addData(feature); } catch { invalid++; }
        }
        if (invalid) mapStatus.textContent += ' ' + invalid + ' geometry records could not be displayed.';
    }

    async function loadViewport() {
        if (!map) return;
        viewportRequest?.abort();
        const request = new AbortController();
        viewportRequest = request;
        const bounds = map.getBounds();
        const url = new URL(config.features_url);
        // Clamp world-wrap coordinates; this system's parcels are in Negros.
        const values = { west: Math.max(-180, bounds.getWest()), south: Math.max(-90, bounds.getSouth()), east: Math.min(180, bounds.getEast()), north: Math.min(90, bounds.getNorth()) };
        for (const [key, value] of Object.entries(values)) url.searchParams.set(key, String(value));
        mapStatus.textContent = 'Loading parcels in this view…';
        try {
            const data = await getJson(url, request.signal);
            if (viewportRequest !== request) return;
            mapStatus.textContent = data.returned + ' of ' + data.total + ' parcels in this view.' +
                (data.limited ? ' Display is limited. Zoom in or use search to locate any mapped parcel.' : '') +
                (data.skipped ? ' ' + data.skipped + ' geometry records exceeded display limits or require review.' : '') +
                (config.unavailable_total ? ' ' + config.unavailable_total + ' geometry records have unavailable display bounds; open parcel references for review.' : '');
            draw(data.features);
        } catch (error) {
            if (error.name !== 'AbortError') mapStatus.textContent = 'Map data could not be loaded. The parcel list remains available. Move the map or refresh to retry.';
        }
    }

    async function focusParcel(record) {
        focusRequest?.abort();
        const request = new AbortController();
        focusRequest = request;
        const generation = ++focusGeneration;
        try {
            const feature = await getJson(config.focus_url.replace('__ID__', String(record.id)), request.signal);
            if (generation !== focusGeneration) return;
            selectedFeature = feature;
            const [west, south, east, north] = record.bounds;
            map.fitBounds([[south, west], [north, east]], { padding: [40, 40], maxZoom: 17 });
            await loadViewport();
        } catch (error) {
            if (error.name !== 'AbortError') {
                mapStatus.replaceChildren(document.createTextNode('This geometry could not be displayed. '));
                const link = document.createElement('a');
                link.href = record.details_url;
                link.textContent = 'Open parcel record';
                mapStatus.appendChild(link);
            }
        }
    }

    function resetView() {
        selectedFeature = null;
        focusRequest?.abort();
        focusGeneration++;
        if (config.bounds) {
            const [west, south, east, north] = config.bounds;
            map.fitBounds([[south, west], [north, east]], { padding: [40, 40], maxZoom: 16 });
        } else {
            map.setView([9.3068, 123.3054], 12);
        }
        loadViewport();
    }

    // Record links are usable even when Leaflet fails.
    renderSearchResults(config.initial_search);
    search?.addEventListener('input', () => {
        searchRequest?.abort();
        searchRequest = null;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadSearch(1), 250);
    });
    if (typeof window.L === 'undefined') {
        container.textContent = 'Map resources could not be initialized. The parcel list remains available.';
        return;
    }
    container.replaceChildren();
    map = window.L.map(container, { zoomControl: false, minZoom: 7, maxZoom: 20 }).setView([9.3068, 123.3054], 12);
    results.querySelectorAll('[data-map-focus]').forEach(button => { button.hidden = false; });
    // Track actual overlays on this map rather than relying on a global
    // Leaflet prototype patch (the page and bundle may load separate instances).
    map.on('tooltipopen', ({ tooltip }) => {
        if (activeTooltip && activeTooltip !== tooltip) map.closeTooltip(activeTooltip);
        activeTooltip = tooltip;
    });
    map.on('tooltipclose', ({ tooltip }) => {
        // Leaflet otherwise retains a closed overlay for its 200ms fade-out.
        // Remove this parcel overlay immediately so redraws cannot leave ghosts.
        tooltip.getElement()?.remove();
        if (activeTooltip === tooltip) activeTooltip = null;
    });
    window.L.control.zoom({ position: 'topright' }).addTo(map);
    window.L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        subdomains: 'abcd', maxZoom: 20,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
    }).addTo(map);
    map.on('moveend', () => {
        viewportRequest?.abort();
        viewportRequest = null;
        clearTimeout(mapTimer);
        mapTimer = setTimeout(loadViewport, 150);
    });
    document.getElementById('reset-map-view')?.addEventListener('click', resetView);
    resetView();
}

document.addEventListener('DOMContentLoaded', initializeParcelMapViewer);
