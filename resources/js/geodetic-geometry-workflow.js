function makeElement(tag, className, text) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined && text !== null) element.textContent = text;
    return element;
}

function initializeGeodeticDashboardQueue() {
    const dashboard = document.querySelector('.geo-dashboard-stack');
    if (!dashboard || document.querySelector('[data-geodetic-mapping-queue]')) return;

    const hero = dashboard.querySelector('.geo-dashboard-hero');
    if (!hero) return;

    const heroCopy = hero.querySelector('.geo-dashboard-copy');
    if (heroCopy) {
        heroCopy.textContent = 'Review parcel references and map geometry. Administrative, ownership, landholding, application, and clearance data remain read-only in the Geodetic workspace.';
    }

    const panel = makeElement('article', 'geo-mapping-queue');
    panel.dataset.geodeticMappingQueue = 'true';

    const header = makeElement('header', 'geo-mapping-queue-header');
    const headingGroup = makeElement('div');
    headingGroup.appendChild(makeElement('h2', 'geo-mapping-queue-title', 'Parcels Awaiting Mapping'));
    headingGroup.appendChild(makeElement('p', 'geo-mapping-queue-copy', 'Parcel records without GeoJSON geometry, prioritized oldest first.'));
    header.appendChild(headingGroup);

    const countBadge = makeElement('span', 'geo-mapping-queue-count', '…');
    countBadge.setAttribute('aria-label', 'Parcels awaiting mapping');
    header.appendChild(countBadge);
    panel.appendChild(header);

    const body = makeElement('div', 'geo-mapping-queue-body');
    body.appendChild(makeElement('div', 'geo-mapping-queue-loading', 'Loading mapping queue…'));
    panel.appendChild(body);

    hero.insertAdjacentElement('afterend', panel);

    fetch('/geodetic/parcels/awaiting-geometry', {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    })
        .then((response) => {
            if (!response.ok) throw new Error(`Queue request failed with ${response.status}`);
            return response.json();
        })
        .then((payload) => {
            const count = Number(payload.count || 0);
            const parcels = Array.isArray(payload.parcels) ? payload.parcels : [];

            countBadge.textContent = String(count);
            body.replaceChildren();

            if (count === 0) {
                const empty = makeElement('div', 'geo-mapping-queue-empty');
                empty.appendChild(makeElement('strong', '', 'All current parcels are mapped.'));
                empty.appendChild(makeElement('span', '', ' New unmapped parcel records will appear here automatically.'));
                body.appendChild(empty);
                return;
            }

            const list = makeElement('div', 'geo-mapping-queue-list');

            parcels.forEach((parcel) => {
                const row = makeElement('div', 'geo-mapping-queue-row');

                const identity = makeElement('div', 'geo-mapping-queue-identity');
                const code = makeElement('a', 'geo-mapping-queue-code', parcel.parcel_code || 'Parcel');
                code.href = parcel.details_url;
                identity.appendChild(code);
                identity.appendChild(makeElement('div', 'geo-mapping-queue-sub', `${parcel.title_no} · ${parcel.tax_decl_no}`));

                const location = makeElement('div', 'geo-mapping-queue-location');
                location.appendChild(makeElement('strong', '', `${parcel.barangay}, ${parcel.municipality}`));
                location.appendChild(makeElement('span', '', parcel.area_hectares || 'N/A'));

                const status = makeElement('span', 'geo-mapping-queue-state', 'Awaiting GeoJSON');

                const action = makeElement('a', 'geo-mapping-queue-action', 'Map Parcel');
                action.href = parcel.edit_url;

                row.append(identity, location, status, action);
                list.appendChild(row);
            });

            body.appendChild(list);

            if (count > parcels.length) {
                const footer = makeElement('div', 'geo-mapping-queue-footer');
                footer.textContent = `Showing the oldest ${parcels.length} of ${count} unmapped parcels.`;
                body.appendChild(footer);
            }
        })
        .catch(() => {
            countBadge.textContent = '—';
            body.replaceChildren(makeElement('div', 'geo-mapping-queue-error', 'The mapping queue could not be loaded. Refresh the page to try again.'));
        });
}

function initializeGeodeticParcelGeometryAction() {
    const detailPage = document.querySelector('.geo-detail-page');
    if (!detailPage) return;

    detailPage.querySelectorAll('.geo-detail-badge').forEach((badge) => {
        if (badge.textContent.trim() === 'Read-Only Review') {
            badge.textContent = 'Reference Fields Read-Only';
        }
    });

    const geometryPanel = Array.from(detailPage.querySelectorAll('.geo-detail-panel')).find((panel) => {
        return panel.querySelector('.geo-detail-panel-title')?.textContent.trim() === 'Geometry Reference';
    });

    if (!geometryPanel) return;

    const header = geometryPanel.querySelector('.geo-detail-panel-header');
    if (!header || header.querySelector('[data-geodetic-edit-geometry]')) return;

    const editLink = makeElement('a', 'geo-geometry-edit-link', 'Edit Geometry');
    editLink.dataset.geodeticEditGeometry = 'true';
    editLink.href = `${window.location.pathname.replace(/\/$/, '')}/geometry/edit`;
    header.appendChild(editLink);
}

function initializeGeodeticGeometryPointPrefill() {
    const page = document.querySelector('.geo-map-editor-page');
    if (!page) return;

    const editor = page.querySelector('[data-geojson-helper]');
    if (!editor) return;

    const target = document.getElementById(editor.dataset.target);
    const pointsWrap = editor.querySelector('[data-geojson-points]');
    const message = editor.querySelector('[data-geojson-message]');

    if (!target || !pointsWrap || !target.value.trim()) return;

    let geometry;
    try {
        geometry = JSON.parse(target.value);
    } catch (error) {
        return;
    }

    if (geometry?.type !== 'Polygon' || !Array.isArray(geometry.coordinates?.[0])) return;

    let points = geometry.coordinates[0].filter((point) => {
        return Array.isArray(point)
            && point.length >= 2
            && point[0] !== null
            && point[1] !== null
            && Number.isFinite(Number(point[0]))
            && Number.isFinite(Number(point[1]));
    });

    if (points.length > 1) {
        const first = points[0];
        const last = points[points.length - 1];
        if (Number(first[0]) === Number(last[0]) && Number(first[1]) === Number(last[1])) {
            points = points.slice(0, -1);
        }
    }

    if (points.length === 0) return;

    const createPointRow = (index) => {
        const row = document.createElement('div');
        row.className = 'geojson-point-row';
        row.innerHTML = '<span>Point ' + index + '</span>'
            + '<input type="number" step="0.000001" placeholder="Longitude / X" data-geojson-lng>'
            + '<input type="number" step="0.000001" placeholder="Latitude / Y" data-geojson-lat>';
        return row;
    };

    let rows = Array.from(pointsWrap.querySelectorAll('.geojson-point-row'));
    while (rows.length < points.length) {
        pointsWrap.appendChild(createPointRow(rows.length + 1));
        rows = Array.from(pointsWrap.querySelectorAll('.geojson-point-row'));
    }

    rows.forEach((row, index) => {
        const lngInput = row.querySelector('[data-geojson-lng]');
        const latInput = row.querySelector('[data-geojson-lat]');
        const label = row.querySelector('span');

        if (label) label.textContent = `Point ${index + 1}`;

        if (index < points.length) {
            if (lngInput) lngInput.value = points[index][0];
            if (latInput) latInput.value = points[index][1];
        } else {
            if (lngInput) lngInput.value = '';
            if (latInput) latInput.value = '';
        }
    });

    const syncGeometryFromPointFields = () => {
        const coordinates = [];

        pointsWrap.querySelectorAll('.geojson-point-row').forEach((row) => {
            const lng = row.querySelector('[data-geojson-lng]')?.value;
            const lat = row.querySelector('[data-geojson-lat]')?.value;

            if (lng !== '' && lat !== '') {
                coordinates.push([Number(lng), Number(lat)]);
            }
        });

        if (coordinates.length < 3) return;

        const first = coordinates[0];
        const last = coordinates[coordinates.length - 1];
        if (first[0] !== last[0] || first[1] !== last[1]) {
            coordinates.push([...first]);
        }

        target.value = JSON.stringify({ type: 'Polygon', coordinates: [coordinates] }, null, 2);
    };

    pointsWrap.addEventListener('input', (event) => {
        if (event.target.matches('[data-geojson-lng], [data-geojson-lat]')) {
            syncGeometryFromPointFields();
        }
    });

    editor.closest('form')?.addEventListener('submit', syncGeometryFromPointFields);

    if (message) {
        message.textContent = `${points.length} saved polygon point${points.length === 1 ? '' : 's'} loaded. Adjust only the coordinates that need changes.`;
        message.classList.remove('is-error');
    }
}

function initializeGeodeticAccessScopeLabel() {
    const accessChip = document.querySelector('.geo-access-chip');
    if (accessChip && accessChip.textContent.trim() === 'Read-only Access') {
        accessChip.textContent = 'Limited Mapping Access';
    }
}

function initializeGeodeticGeometryWorkflow() {
    initializeGeodeticAccessScopeLabel();
    initializeGeodeticDashboardQueue();
    initializeGeodeticParcelGeometryAction();
    initializeGeodeticGeometryPointPrefill();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeGeodeticGeometryWorkflow, { once: true });
} else {
    initializeGeodeticGeometryWorkflow();
}
