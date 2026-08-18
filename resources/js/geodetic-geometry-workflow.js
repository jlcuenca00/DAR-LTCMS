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
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeGeodeticGeometryWorkflow, { once: true });
} else {
    initializeGeodeticGeometryWorkflow();
}
