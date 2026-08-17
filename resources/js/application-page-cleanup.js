function normalizedPath() {
    const path = window.location.pathname.replace(/\/+$/, '');
    return path || '/';
}

function simplifyApplicationsIndex() {
    const intakeCard = document.querySelector('[data-application-intake-entry]');
    if (!intakeCard) return;

    intakeCard.querySelector('.intake-entry-actions')?.remove();
    intakeCard.classList.add('intake-entry-card-guidance-only');
}

function itemLabel(item) {
    return item.querySelector('strong')?.textContent.trim() || '';
}

function compactFiveHectareValidation() {
    const root = document.querySelector('.application-review-page');
    if (!root) return;

    const panel = Array.from(root.querySelectorAll('.review-panel')).find((candidate) => {
        return candidate.querySelector('.review-panel-title')?.textContent.trim() === '5-Hectare Validation (Assistive)';
    });

    if (!panel || panel.dataset.compactFiveHectare === 'true') return;

    const body = panel.querySelector('.review-panel-body');
    if (!body) return;

    panel.dataset.compactFiveHectare = 'true';
    panel.classList.add('five-hectare-compact');

    const subtitle = panel.querySelector('.review-panel-subtitle');
    if (subtitle) {
        subtitle.textContent = 'Assistive projection using encoded landholding records and recorded parcel shares.';
    }

    const grids = Array.from(body.children).filter((child) => child.classList.contains('validation-grid'));
    const contextGrid = grids.find((grid) => {
        return Array.from(grid.querySelectorAll('.validation-item')).some((item) => itemLabel(item) === 'Transfer Nature');
    });
    const transfereeGrids = grids.filter((grid) => grid !== contextGrid);

    const details = document.createElement('details');
    details.className = 'five-hectare-details';
    details.innerHTML = `
        <summary>
            <span><i class="fa-solid fa-calculator" aria-hidden="true"></i> View calculation details</span>
            <i class="fa-solid fa-chevron-down five-hectare-details-chevron" aria-hidden="true"></i>
        </summary>
        <div class="five-hectare-details-body"></div>
    `;
    const detailsBody = details.querySelector('.five-hectare-details-body');

    const primaryLabels = new Set([
        'Transferee',
        'This Application Share',
        'Projected Total',
        'Review Status',
    ]);

    transfereeGrids.forEach((grid, index) => {
        grid.classList.add('five-hectare-primary-grid');

        const items = Array.from(grid.querySelectorAll('.validation-item'));
        const transfereeItem = items.find((item) => itemLabel(item) === 'Transferee');
        const transfereeName = transfereeItem
            ? transfereeItem.textContent.replace('Transferee', '').trim()
            : `Transferee ${index + 1}`;

        const detailItems = items.filter((item) => !primaryLabels.has(itemLabel(item)));
        if (!detailItems.length) return;

        const group = document.createElement('section');
        group.className = 'five-hectare-detail-group';

        const heading = document.createElement('p');
        heading.className = 'five-hectare-detail-heading';
        heading.textContent = transfereeName;
        group.appendChild(heading);

        const detailGrid = document.createElement('div');
        detailGrid.className = 'validation-grid five-hectare-detail-grid';
        detailItems.forEach((item) => detailGrid.appendChild(item));
        group.appendChild(detailGrid);
        detailsBody.appendChild(group);
    });

    if (contextGrid) {
        const contextGroup = document.createElement('section');
        contextGroup.className = 'five-hectare-detail-group';

        const heading = document.createElement('p');
        heading.className = 'five-hectare-detail-heading';
        heading.textContent = 'Review context';
        contextGroup.appendChild(heading);

        contextGrid.classList.add('five-hectare-detail-grid');
        contextGroup.appendChild(contextGrid);
        detailsBody.appendChild(contextGroup);
    }

    const note = Array.from(body.children).find((child) => child.classList.contains('review-note-box'));
    if (detailsBody.children.length) {
        if (note) {
            body.insertBefore(details, note);
        } else {
            body.appendChild(details);
        }
    }

    if (note) {
        note.classList.add('five-hectare-assistive-note');
    }
}

function initApplicationPageCleanup() {
    const path = normalizedPath();

    if (path === '/staff/applications') {
        simplifyApplicationsIndex();
    }

    if (/^\/staff\/applications\/\d+$/.test(path)) {
        compactFiveHectareValidation();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApplicationPageCleanup, { once: true });
} else {
    initApplicationPageCleanup();
}
