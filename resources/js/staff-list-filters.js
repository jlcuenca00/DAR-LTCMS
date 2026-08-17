const STAFF_LIST_FILTER_CONFIG = {
    '/staff/records/landowners': '.landowner-filter-grid',
    '/staff/records/parcels': '.parcel-filter-grid',
    '/staff/users': 'form[method="GET"][action*="/staff/users"]',
    '/staff/legacy-records': '.source-filter-grid',
};

function normalizedPath() {
    const path = window.location.pathname.replace(/\/+$/, '');
    return path || '/';
}

function fieldWrapper(control, form) {
    const marked = control.closest('[data-staff-filter-field="true"]');
    if (marked) return marked;

    let node = control.parentElement;
    while (node && node.parentElement !== form) node = node.parentElement;
    return node && node.parentElement === form ? node : control.parentElement;
}

function displayValue(control) {
    if (control instanceof HTMLSelectElement) {
        const option = control.selectedOptions?.[0];
        return option && control.value !== '' ? option.textContent.trim() : '';
    }
    return String(control.value || '').trim();
}

function fieldLabel(control, wrapper) {
    const label = wrapper?.querySelector('label');
    const text = label?.textContent?.trim();
    if (text) return text.replace(/\s+/g, ' ');

    return control.name
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function namedControls(form) {
    return Array.from(form.elements).filter((control) => {
        if (!(control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement)) return false;
        if (!control.name || control.disabled) return false;
        return !['hidden', 'submit', 'button'].includes(control.type);
    });
}

function activeControls(form) {
    return namedControls(form).filter((control) => displayValue(control) !== '');
}

function chipHref(control) {
    const url = new URL(window.location.href);
    url.searchParams.delete(control.name);
    return `${url.pathname}${url.search}${url.hash}`;
}

function addSearchIcon(search) {
    if (!search || search.closest('.staff-list-search-control')) return;

    const holder = document.createElement('div');
    holder.className = 'staff-list-search-control';
    search.parentNode.insertBefore(holder, search);
    holder.appendChild(search);

    const icon = document.createElement('span');
    icon.className = 'staff-list-search-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i>';
    holder.insertBefore(icon, search);
}

function findResetLink(form) {
    return Array.from(form.querySelectorAll('a')).find((link) => /^(reset|clear|clear all)$/i.test(link.textContent.trim()));
}

function renderActiveFilters(form, toolbar, drawer, resetLink) {
    form.querySelector('.staff-active-filters')?.remove();

    const controls = activeControls(form);
    const filterControls = controls.filter((control) => control.name !== 'search');

    form.classList.toggle('has-active-filters', controls.length > 0);

    const badge = toolbar.querySelector('.staff-filter-toggle-count');
    if (badge) {
        badge.textContent = String(filterControls.length);
        badge.hidden = filterControls.length === 0;
    }

    const drawerCount = drawer.querySelector('.staff-filter-drawer-count');
    if (drawerCount) {
        drawerCount.textContent = filterControls.length
            ? `${filterControls.length} active`
            : 'No active filters';
    }

    if (resetLink) resetLink.hidden = controls.length === 0;
    if (!controls.length) return;

    const row = document.createElement('div');
    row.className = 'staff-active-filters';
    row.setAttribute('aria-label', 'Active search and filters');

    controls.forEach((control) => {
        const wrapper = fieldWrapper(control, form);
        const chip = document.createElement('a');
        chip.className = 'staff-filter-chip';
        chip.href = chipHref(control);
        chip.title = `Remove ${fieldLabel(control, wrapper)}`;
        chip.innerHTML = `<span>${fieldLabel(control, wrapper)}: <strong>${displayValue(control)}</strong></span><i class="fa-solid fa-xmark" aria-hidden="true"></i>`;
        row.appendChild(chip);
    });

    if (resetLink?.href) {
        const clear = document.createElement('a');
        clear.className = 'staff-filter-clear-all';
        clear.href = resetLink.href;
        clear.textContent = 'Clear all';
        row.appendChild(clear);
    }

    form.insertBefore(row, drawer);
}

function renderApplicationActiveFilters(form, toolbar, resetLink) {
    form.querySelector('.application-active-filters')?.remove();

    const controls = activeControls(form);
    const filters = controls.filter((control) => control.name !== 'search');
    const badge = toolbar.querySelector('.application-filter-count');

    if (badge) {
        badge.textContent = String(filters.length);
        badge.hidden = filters.length === 0;
    }

    if (!controls.length) return;

    const row = document.createElement('div');
    row.className = 'application-active-filters';
    row.setAttribute('aria-label', 'Active application search and filters');

    controls.forEach((control) => {
        const wrapper = fieldWrapper(control, form);
        const chip = document.createElement('a');
        chip.className = 'staff-filter-chip';
        chip.href = chipHref(control);
        chip.title = `Remove ${fieldLabel(control, wrapper)}`;
        chip.innerHTML = `<span>${fieldLabel(control, wrapper)}: <strong>${displayValue(control)}</strong></span><i class="fa-solid fa-xmark" aria-hidden="true"></i>`;
        row.appendChild(chip);
    });

    if (resetLink?.href) {
        const clear = document.createElement('a');
        clear.className = 'staff-filter-clear-all';
        clear.href = resetLink.href;
        clear.textContent = 'Clear all';
        row.appendChild(clear);
    }

    toolbar.insertAdjacentElement('afterend', row);
}

function enhanceApplicationTableToolbar() {
    if (normalizedPath() !== '/staff/applications') return;

    const form = document.querySelector('.application-filter-grid');
    const desktopTable = document.querySelector('.application-desktop-table');
    const listPanel = desktopTable?.closest('section.staff-panel');
    const sourcePanel = form?.closest('section.staff-panel');
    if (!form || !listPanel || !sourcePanel || sourcePanel === listPanel) return;

    const sourceToolbar = sourcePanel.querySelector('.records-toolbar');
    const titleBlock = sourceToolbar?.firstElementChild;
    const primaryActions = sourceToolbar?.querySelector('.records-toolbar-actions');
    const listHeader = listPanel.querySelector(':scope > .staff-panel-pad');

    if (listHeader) {
        const listSubtitle = listHeader.querySelector('.staff-panel-subtitle')?.textContent?.trim();
        listHeader.replaceChildren();
        listHeader.classList.add('application-records-header');

        if (titleBlock) {
            const subtitle = titleBlock.querySelector('.staff-panel-subtitle');
            if (subtitle && listSubtitle) subtitle.textContent = listSubtitle;
            listHeader.appendChild(titleBlock);
        }

        if (primaryActions) listHeader.appendChild(primaryActions);
    }

    listPanel.classList.add('application-records-panel');
    form.classList.remove('mt-5');
    form.classList.add('application-records-toolbar-form');
    listPanel.insertBefore(form, desktopTable);
    sourcePanel.remove();

    const controls = namedControls(form);
    const search = controls.find((control) => control.name === 'search');
    const filters = controls.filter((control) => control.name !== 'search');
    const originalSubmit = form.querySelector('button[type="submit"]');
    const resetLink = findResetLink(form);
    const oldActions = originalSubmit ? fieldWrapper(originalSubmit, form) : null;

    const wrappers = new Map();
    controls.forEach((control) => {
        const wrapper = fieldWrapper(control, form);
        if (wrapper) {
            wrapper.dataset.staffFilterField = 'true';
            wrappers.set(control, wrapper);
        }
    });

    const toolbar = document.createElement('div');
    toolbar.className = 'application-table-toolbar';
    form.insertBefore(toolbar, form.firstChild);

    if (search && wrappers.get(search)) {
        const searchWrapper = wrappers.get(search);
        searchWrapper.classList.add('application-table-search');
        searchWrapper.querySelector('label')?.classList.add('staff-list-search-label');
        toolbar.appendChild(searchWrapper);
        addSearchIcon(search);
    }

    const searchButton = document.createElement('button');
    searchButton.type = 'submit';
    searchButton.className = 'staff-button application-search-button';
    searchButton.innerHTML = '<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Search</span>';
    toolbar.appendChild(searchButton);

    const menu = document.createElement('div');
    menu.className = 'application-filter-menu';
    toolbar.appendChild(menu);

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'staff-button application-filter-toggle';
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Filters</span><span class="application-filter-count" hidden>0</span>';
    menu.appendChild(toggle);

    const popover = document.createElement('div');
    popover.className = 'application-filter-popover';
    popover.hidden = true;
    popover.innerHTML = `
        <div class="application-filter-popover-head">
            <div>
                <p class="application-filter-popover-title">Filter applications</p>
                <p class="application-filter-popover-hint">Use one or more criteria to narrow the list.</p>
            </div>
        </div>
        <div class="application-filter-popover-grid"></div>
        <div class="application-filter-popover-actions"></div>
    `;
    menu.appendChild(popover);

    const grid = popover.querySelector('.application-filter-popover-grid');
    const uniqueWrappers = [];
    filters.forEach((control) => {
        const wrapper = wrappers.get(control);
        if (wrapper && !uniqueWrappers.includes(wrapper)) uniqueWrappers.push(wrapper);
    });
    uniqueWrappers.forEach((wrapper) => grid.appendChild(wrapper));

    const footer = popover.querySelector('.application-filter-popover-actions');
    if (resetLink) {
        resetLink.textContent = 'Clear filters';
        resetLink.classList.add('application-filter-clear');
        footer.appendChild(resetLink);
    }
    if (originalSubmit) {
        originalSubmit.classList.add('application-filter-apply');
        originalSubmit.innerHTML = '<i class="fa-solid fa-filter" aria-hidden="true"></i><span>Apply Filters</span>';
        footer.appendChild(originalSubmit);
    }
    oldActions?.remove();

    const setOpen = (open) => {
        popover.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menu.classList.toggle('is-open', open);
    };

    toggle.addEventListener('click', () => setOpen(popover.hidden));

    document.addEventListener('click', (event) => {
        if (!popover.hidden && !menu.contains(event.target)) setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !popover.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });

    renderApplicationActiveFilters(form, toolbar, resetLink);
}

function enhanceStaffListFilters() {
    const selector = STAFF_LIST_FILTER_CONFIG[normalizedPath()];
    if (!selector) return;

    const form = document.querySelector(selector);
    if (!form || form.dataset.staffListEnhanced === 'compact-drawer') return;

    form.dataset.staffListEnhanced = 'compact-drawer';
    form.classList.add('staff-list-filter', 'staff-list-filter-compact');

    const controls = namedControls(form);
    const search = controls.find((control) => control.name === 'search');
    const filterControls = controls.filter((control) => control.name !== 'search');

    const wrappers = new Map();
    controls.forEach((control) => {
        const wrapper = fieldWrapper(control, form);
        if (wrapper) {
            wrapper.dataset.staffFilterField = 'true';
            wrappers.set(control, wrapper);
        }
    });

    const originalSubmit = form.querySelector('button[type="submit"]');
    const actionWrapper = originalSubmit ? fieldWrapper(originalSubmit, form) : null;
    if (actionWrapper) actionWrapper.dataset.staffFilterActions = 'true';

    const resetLink = findResetLink(form);
    if (resetLink) {
        resetLink.textContent = 'Clear all';
        resetLink.classList.add('staff-filter-clear');
    }

    const toolbar = document.createElement('div');
    toolbar.className = 'staff-filter-toolbar';
    form.insertBefore(toolbar, form.firstChild);

    if (search && wrappers.get(search)) {
        const searchWrapper = wrappers.get(search);
        searchWrapper.classList.add('staff-list-search-field');
        searchWrapper.querySelector('label')?.classList.add('staff-list-search-label');
        toolbar.appendChild(searchWrapper);
        addSearchIcon(search);
    }

    const searchSubmit = document.createElement('button');
    searchSubmit.type = 'submit';
    searchSubmit.className = 'staff-button staff-filter-search-submit';
    searchSubmit.innerHTML = '<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Search</span>';
    toolbar.appendChild(searchSubmit);

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'staff-button staff-filter-toggle';
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Filters</span><span class="staff-filter-toggle-count" hidden>0</span>';
    toolbar.appendChild(toggle);

    const drawer = document.createElement('section');
    drawer.className = 'staff-filter-drawer';
    drawer.hidden = true;
    drawer.innerHTML = `
        <div class="staff-filter-drawer-header">
            <div>
                <p class="staff-filter-drawer-title">Filter records</p>
                <p class="staff-filter-drawer-hint">Narrow the working list, then apply the selected criteria.</p>
            </div>
            <span class="staff-filter-drawer-count">No active filters</span>
        </div>
        <div class="staff-filter-drawer-grid"></div>
    `;

    const drawerGrid = drawer.querySelector('.staff-filter-drawer-grid');
    const uniqueWrappers = [];
    filterControls.forEach((control) => {
        const wrapper = wrappers.get(control);
        if (wrapper && !uniqueWrappers.includes(wrapper)) uniqueWrappers.push(wrapper);
    });
    uniqueWrappers.forEach((wrapper) => drawerGrid.appendChild(wrapper));

    if (originalSubmit) {
        originalSubmit.classList.add('staff-filter-apply');
        originalSubmit.innerHTML = '<i class="fa-solid fa-filter" aria-hidden="true"></i><span>Apply Filters</span>';
    }

    if (actionWrapper) {
        actionWrapper.classList.add('staff-filter-drawer-actions');
        if (resetLink && originalSubmit) actionWrapper.insertBefore(resetLink, originalSubmit);
        drawer.appendChild(actionWrapper);
    }

    form.appendChild(drawer);

    if (!filterControls.length) toggle.hidden = true;

    toggle.addEventListener('click', () => {
        const opening = drawer.hidden;
        drawer.hidden = !opening;
        toggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
        form.classList.toggle('filters-open', opening);
    });

    renderActiveFilters(form, toolbar, drawer, resetLink);
}

function initializeStaffListFilters() {
    enhanceApplicationTableToolbar();
    enhanceStaffListFilters();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeStaffListFilters, { once: true });
} else {
    initializeStaffListFilters();
}
