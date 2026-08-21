function isInteractiveTarget(target) {
    return Boolean(target.closest('a, button, input, select, textarea, label, summary, details, [role="button"], [data-row-navigation-ignore]'));
}

function makeRowNavigable(row, href, label = 'Open record') {
    if (!row || !href || row.dataset.recordRowNavigation === 'ready') return;

    row.dataset.recordRowNavigation = 'ready';
    row.dataset.recordRowHref = href;
    row.classList.add('staff-record-row-link');
    row.setAttribute('role', 'link');
    row.setAttribute('tabindex', '0');
    row.setAttribute('aria-label', label);

    row.addEventListener('click', (event) => {
        if (isInteractiveTarget(event.target)) return;
        if (window.getSelection()?.toString()) return;
        window.location.assign(href);
    });

    row.addEventListener('keydown', (event) => {
        if (event.target !== row || (event.key !== 'Enter' && event.key !== ' ')) return;
        event.preventDefault();
        window.location.assign(href);
    });
}

function removeActionColumn(table) {
    if (!table) return;

    const headerCells = Array.from(table.querySelectorAll('thead th'));
    const actionIndex = headerCells.findIndex((cell) => cell.textContent.trim().toLowerCase() === 'action');
    if (actionIndex < 0) return;

    table.querySelectorAll('tr').forEach((row) => {
        const cells = Array.from(row.children);
        const cell = cells[actionIndex];
        if (cell) cell.remove();
    });

    table.querySelectorAll('tbody td[colspan]').forEach((cell) => {
        const span = Number.parseInt(cell.getAttribute('colspan') || '', 10);
        if (Number.isFinite(span) && span > 1) cell.setAttribute('colspan', String(span - 1));
    });
}

function enhanceTable(table, linkSelector, labelBuilder) {
    if (!table) return;

    removeActionColumn(table);

    table.querySelectorAll('tbody tr').forEach((row) => {
        if (row.querySelector('td[colspan]')) return;
        const link = row.querySelector(linkSelector);
        if (!link?.href) return;
        makeRowNavigable(row, link.href, labelBuilder?.(row, link) || 'Open record');
    });
}

function enhanceLandowners() {
    const table = document.querySelector('.staff-table');
    enhanceTable(table, 'a[href*="/staff/records/landowners/"]', (row, link) => `Open landowner record ${link.textContent.trim()}`);
}

function enhanceParcels() {
    const table = document.querySelector('.staff-table');
    enhanceTable(table, '.parcel-code[href]', (row, link) => `Open parcel record ${link.textContent.trim()}`);
}

function enhanceSourceRecords() {
    const table = document.querySelector('.source-view-card .staff-table');
    enhanceTable(table, '.source-record-main[href]', (row, link) => `Open source record ${link.textContent.trim()}`);

    document.querySelectorAll('.source-package-row').forEach((row) => {
        const openButton = row.querySelector('a[href*="/source-record-packages/"]');
        if (!openButton?.href) return;
        const href = openButton.href;
        const code = row.querySelector('.source-package-code')?.textContent?.trim() || 'source package';
        openButton.remove();
        makeRowNavigable(row, href, `Open source package ${code}`);
    });
}

function enhanceApplications() {
    const table = document.querySelector('.application-desktop-table .staff-table');
    enhanceTable(table, '.staff-link[href]', (row, link) => `Open clearance application ${link.textContent.trim()}`);

    document.querySelectorAll('.application-mobile-card').forEach((card) => {
        const link = card.querySelector('.application-mobile-code[href]');
        if (!link?.href) return;
        card.querySelector('.application-mobile-action')?.remove();
        makeRowNavigable(card, link.href, `Open clearance application ${link.textContent.trim()}`);
    });
}

function enhanceDashboardApplications() {
    const table = document.querySelector('.dashboard-table');
    if (!table) return;

    table.querySelectorAll('tbody tr').forEach((row) => {
        if (row.hidden || row.matches('[data-dashboard-filter-empty]') || row.querySelector('td[colspan]')) return;
        const link = row.querySelector('.application-link[href]');
        if (!link?.href) return;
        makeRowNavigable(row, link.href, `Open clearance application ${link.textContent.trim()}`);
    });
}

function enhanceMatchedApplicationSources() {
    document.querySelectorAll('.application-review-page .source-table-wrap .staff-table').forEach((table) => {
        table.querySelectorAll('tbody tr').forEach((row) => {
            if (row.querySelector('td[colspan]')) return;

            const openLink = row.querySelector(
                'a[href*="/staff/source-record-packages/"], a[href*="/staff/legacy-records/"]'
            );
            if (!openLink?.href) return;

            const href = openLink.href;
            const recordLabel = row.querySelector('td strong')?.textContent?.trim() || 'matched source record';
            makeRowNavigable(row, href, `Open ${recordLabel}`);
        });

        removeActionColumn(table);
    });
}

function enhanceParcelEditReviewFlag() {
    const match = window.location.pathname.match(/^\/staff\/records\/parcels\/([^/]+)\/edit\/?$/);
    if (!match) return;

    const statusSelect = document.querySelector('select[name="status"]');
    statusSelect?.querySelectorAll('option').forEach((option) => {
        if (!['active', 'inactive'].includes(option.value)) option.remove();
    });

    const aside = document.querySelector('.parcel-edit-aside');
    if (!aside || aside.querySelector('[data-parcel-review-shortcut]')) return;

    const parcelId = encodeURIComponent(match[1]);
    const section = document.createElement('section');
    section.className = 'parcel-review-shortcut-card';
    section.dataset.parcelReviewShortcut = 'true';
    section.innerHTML = `
        <div class="parcel-review-shortcut-heading">
            <span class="parcel-review-shortcut-icon" aria-hidden="true"><i class="fa-solid fa-flag"></i></span>
            <div>
                <h3>Review Flag</h3>
                <p>Mark this parcel for administrative or technical verification, update its review reason, or resolve an existing flag.</p>
            </div>
        </div>
        <div class="parcel-review-shortcut-note">
            Review flags do not change parcel ownership, landholding, application decisions, or registry records.
        </div>
        <a class="staff-button staff-button-light justify-center parcel-review-shortcut-action" href="/staff/records/parcels/${parcelId}/review-flag">
            <i class="fa-solid fa-flag"></i>
            Flag / Resolve Review
        </a>
    `;

    const saveCard = aside.querySelector('.parcel-save-card');
    if (saveCard) aside.insertBefore(section, saveCard);
    else aside.appendChild(section);
}

function initStaffRecordRowNavigation() {
    const path = window.location.pathname.replace(/\/+$/, '');

    if (path === '/staff/records/landowners') enhanceLandowners();
    if (path === '/staff/records/parcels') enhanceParcels();
    if (path === '/staff/legacy-records') enhanceSourceRecords();
    if (path === '/staff/applications') enhanceApplications();
    if (path === '/staff/dashboard') enhanceDashboardApplications();
    if (/^\/staff\/applications\/[^/]+$/.test(path)) enhanceMatchedApplicationSources();

    enhanceParcelEditReviewFlag();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStaffRecordRowNavigation, { once: true });
} else {
    initStaffRecordRowNavigation();
}
