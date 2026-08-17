const STAFF_LIST_FILTER_CONFIG = {
    '/staff/applications': {
        form: '.application-filter-grid',
        secondary: ['document_reference_number'],
    },
    '/staff/records/landowners': {
        form: '.landowner-filter-grid',
        secondary: [],
    },
    '/staff/records/parcels': {
        form: '.parcel-filter-grid',
        secondary: [],
    },
    '/staff/users': {
        form: 'form[method="GET"][action*="/staff/users"]',
        secondary: [],
    },
    '/staff/legacy-records': {
        form: '.source-filter-grid',
        secondary: [],
    },
};

function normalizedPath() {
    const path = window.location.pathname.replace(/\/+$/, '');
    return path || '/';
}

function fieldWrapper(control, form) {
    let node = control.parentElement;

    while (node && node.parentElement !== form) {
        node = node.parentElement;
    }

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
    const explicit = control.id ? wrapper?.querySelector(`label[for="${CSS.escape(control.id)}"]`) : null;
    const label = explicit || wrapper?.querySelector('label');
    const text = label?.textContent?.trim();

    if (text) return text.replace(/\s+/g, ' ');

    return control.name
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function addSearchIcon(form) {
    const search = form.querySelector('input[name="search"]');
    if (!search || search.closest('.staff-list-search-control')) return;

    const wrapper = fieldWrapper(search, form);
    wrapper?.classList.add('staff-list-search-field');

    const control = document.createElement('div');
    control.className = 'staff-list-search-control';
    search.parentNode.insertBefore(control, search);
    control.appendChild(search);

    const icon = document.createElement('span');
    icon.className = 'staff-list-search-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i>';
    control.insertBefore(icon, search);
}

function actionContainer(form) {
    const submit = form.querySelector('button[type="submit"], input[type="submit"]');
    if (!submit) return null;

    const wrapper = fieldWrapper(submit, form);
    wrapper?.classList.add('staff-list-filter-actions');
    return wrapper;
}

function secondaryPanel(form, names, actions) {
    if (!names.length) return null;

    const wrappers = names
        .map((name) => form.querySelector(`[name="${CSS.escape(name)}"]`))
        .filter(Boolean)
        .map((control) => ({ control, wrapper: fieldWrapper(control, form) }))
        .filter(({ wrapper }) => wrapper && wrapper !== actions);

    if (!wrappers.length) return null;

    const panel = document.createElement('div');
    panel.className = 'staff-filter-secondary-panel';
    panel.hidden = true;

    wrappers.forEach(({ wrapper }) => panel.appendChild(wrapper));
    form.insertBefore(panel, actions || null);

    const hasActiveSecondary = wrappers.some(({ control }) => displayValue(control) !== '');

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'staff-button staff-filter-more';
    toggle.setAttribute('aria-expanded', hasActiveSecondary ? 'true' : 'false');
    toggle.innerHTML = '<i class="fa-solid fa-sliders"></i><span>More Filters</span>';

    if (hasActiveSecondary) {
        panel.hidden = false;
        form.classList.add('show-secondary-filters');
    }

    toggle.addEventListener('click', () => {
        const willOpen = panel.hidden;
        panel.hidden = !willOpen;
        form.classList.toggle('show-secondary-filters', willOpen);
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });

    actions?.insertBefore(toggle, actions.firstChild);
    return panel;
}

function standardizeActions(form, actions) {
    const submit = form.querySelector('button[type="submit"]');
    if (submit) {
        submit.classList.add('staff-filter-apply');

        const textNodes = Array.from(submit.childNodes).filter((node) => node.nodeType === Node.TEXT_NODE);
        if (textNodes.length) {
            textNodes.forEach((node) => {
                if (node.textContent.trim()) node.textContent = ' Apply Filters';
            });
        } else if (!submit.querySelector('span')) {
            submit.append(document.createTextNode(' Apply Filters'));
        }
    }

    const reset = Array.from(actions?.querySelectorAll('a') || []).find((link) => {
        return /^(reset|clear)$/i.test(link.textContent.trim());
    });

    if (reset) {
        reset.textContent = 'Clear';
        reset.classList.add('staff-filter-clear');
    }

    return reset;
}

function activeControls(form) {
    return Array.from(form.elements).filter((control) => {
        if (!(control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement)) return false;
        if (!control.name || control.disabled) return false;
        if (control.type === 'hidden' || control.type === 'submit' || control.type === 'button') return false;
        return displayValue(control) !== '';
    });
}

function chipHref(control) {
    const url = new URL(window.location.href);
    url.searchParams.delete(control.name);
    return `${url.pathname}${url.search}${url.hash}`;
}

function renderActiveFilters(form, resetLink) {
    form.parentElement?.querySelector(':scope > .staff-active-filters')?.remove();

    const controls = activeControls(form);
    form.classList.toggle('has-active-filters', controls.length > 0);
    if (!controls.length) return;

    const row = document.createElement('div');
    row.className = 'staff-active-filters';
    row.setAttribute('aria-label', 'Active filters');

    const label = document.createElement('span');
    label.className = 'staff-active-filters-label';
    label.textContent = 'Active';
    row.appendChild(label);

    controls.forEach((control) => {
        const wrapper = fieldWrapper(control, form);
        const chip = document.createElement('a');
        chip.className = 'staff-filter-chip';
        chip.href = chipHref(control);
        chip.title = `Remove ${fieldLabel(control, wrapper)} filter`;
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

    form.insertAdjacentElement('afterend', row);
}

function setGridColumns(form, secondaryNames) {
    const primaryControls = Array.from(form.elements).filter((control) => {
        if (!(control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement)) return false;
        if (!control.name || control.type === 'hidden') return false;
        if (secondaryNames.includes(control.name)) return false;
        return !['submit', 'button'].includes(control.type);
    });

    const hasSearch = primaryControls.some((control) => control.name === 'search');
    const nonSearchCount = Math.max(0, primaryControls.length - (hasSearch ? 1 : 0));
    form.style.setProperty('--staff-filter-columns', String(Math.max(1, nonSearchCount)));
}

function enhanceStaffListFilters() {
    const config = STAFF_LIST_FILTER_CONFIG[normalizedPath()];
    if (!config) return;

    const form = document.querySelector(config.form);
    if (!form || form.dataset.staffListEnhanced === 'true') return;

    form.dataset.staffListEnhanced = 'true';
    form.classList.add('staff-list-filter');

    const actions = actionContainer(form);
    addSearchIcon(form);
    setGridColumns(form, config.secondary);
    secondaryPanel(form, config.secondary, actions);
    const reset = standardizeActions(form, actions);
    renderActiveFilters(form, reset);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', enhanceStaffListFilters, { once: true });
} else {
    enhanceStaffListFilters();
}
