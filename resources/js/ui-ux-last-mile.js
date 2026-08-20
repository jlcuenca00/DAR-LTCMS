/* Focused UI/UX enhancements that sit on top of the unified design system. */

function fieldLabel(control) {
    if (!control) return 'this field';
    const id = control.id;
    const label = id ? document.querySelector(`label[for="${CSS.escape(id)}"]`) : null;
    return label?.textContent?.replace('*', '').trim() || control.getAttribute('aria-label') || control.name || 'this field';
}

function attachClientValidation() {
    document.addEventListener('invalid', (event) => {
        const control = event.target;
        if (!(control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement)) return;
        if (!control.closest('.staff-shell, .lo-shell, .geo-shell, .login-page, .auth-page')) return;

        control.setAttribute('aria-invalid', 'true');
        let error = control.parentElement?.querySelector(':scope > .ui-field-error[data-ui-client-error]');
        if (!error) {
            error = document.createElement('p');
            error.className = 'ui-field-error';
            error.dataset.uiClientError = 'true';
            error.id = `${control.id || control.name || 'field'}-ui-error`;
            control.insertAdjacentElement('afterend', error);
        }

        const label = fieldLabel(control);
        error.textContent = control.validity.valueMissing
            ? `Enter or select ${label.toLowerCase()}.`
            : control.validationMessage;

        const describedBy = new Set((control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
        describedBy.add(error.id);
        control.setAttribute('aria-describedby', Array.from(describedBy).join(' '));
    }, true);

    document.addEventListener('input', clearClientError, true);
    document.addEventListener('change', clearClientError, true);
}

function clearClientError(event) {
    const control = event.target;
    if (!(control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement)) return;
    if (!control.validity.valid) return;

    control.removeAttribute('aria-invalid');
    const error = control.parentElement?.querySelector(':scope > .ui-field-error[data-ui-client-error]');
    if (!error) return;

    const describedBy = (control.getAttribute('aria-describedby') || '')
        .split(/\s+/)
        .filter((id) => id && id !== error.id);
    if (describedBy.length) control.setAttribute('aria-describedby', describedBy.join(' '));
    else control.removeAttribute('aria-describedby');
    error.remove();
}

function enhanceLargeSelect(select) {
    if (!(select instanceof HTMLSelectElement)) return;
    if (select.dataset.uiSearchEnhanced === 'true') return;

    const isRecordSelect = select.matches('[data-party-landowner-select], #parcel_id, select[name="landowner_id"]');
    if (!isRecordSelect || select.options.length < 12) return;

    select.dataset.uiSearchEnhanced = 'true';
    const label = fieldLabel(select).replace(/record$/i, '').trim();
    const originalOptions = Array.from(select.options).map((option) => option.cloneNode(true));

    const searchWrap = document.createElement('div');
    searchWrap.className = 'ui-select-search';

    const search = document.createElement('input');
    search.type = 'search';
    search.className = 'ui-select-search-box';
    search.placeholder = `Search ${label.toLowerCase()}…`;
    search.setAttribute('aria-label', `Search options for ${label}`);
    search.setAttribute('autocomplete', 'off');

    const hint = document.createElement('span');
    hint.className = 'ui-select-search-hint';
    hint.setAttribute('role', 'status');
    hint.setAttribute('aria-live', 'polite');

    const render = () => {
        const query = search.value.trim().toLowerCase();
        const selectedValue = select.value;
        const matches = originalOptions.filter((option, index) => {
            if (index === 0) return true;
            if (option.value === selectedValue) return true;
            if (!query) return true;
            return option.textContent.toLowerCase().includes(query);
        });

        select.replaceChildren(...matches.map((option) => option.cloneNode(true)));
        if (selectedValue && Array.from(select.options).some((option) => option.value === selectedValue)) {
            select.value = selectedValue;
        }

        const available = Math.max(0, select.options.length - 1);
        hint.textContent = query ? `${available} matching option${available === 1 ? '' : 's'}` : '';
    };

    search.addEventListener('input', render);
    searchWrap.append(search, hint);
    select.before(searchWrap);
}

function enhanceRecordSelects(root = document) {
    root.querySelectorAll?.('[data-party-landowner-select], #parcel_id, select[name="landowner_id"]').forEach(enhanceLargeSelect);
}

function watchRecordSelects() {
    const root = document.querySelector('.application-create-page, .user-editor-wrap');
    if (!root || typeof MutationObserver === 'undefined') return;

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof Element)) return;
                if (node.matches('select')) enhanceLargeSelect(node);
                enhanceRecordSelects(node);
            });
        });
    });

    observer.observe(root, { childList: true, subtree: true });
}

function addDecisionScopeBoundary() {
    const modal = document.getElementById('decision-confirm-modal');
    const body = modal?.querySelector('.decision-modal-body');
    if (!modal || !body || body.querySelector('.ui-decision-scope-note')) return;

    const note = document.createElement('div');
    note.className = 'ui-decision-scope-note';
    note.innerHTML = '<strong>Clearance scope</strong><span>This final decision records the DAR clearance result and locks the application record. It does not itself execute or finalize legal land ownership transfer or registry mutation.</span>';
    body.appendChild(note);
}

function addAuditTimezoneNote() {
    const audit = document.querySelector('.audit-page');
    const recordsHeader = audit?.querySelector('.audit-records-header');
    if (!audit || !recordsHeader || audit.querySelector('.ui-timezone-note')) return;

    const note = document.createElement('p');
    note.className = 'ui-timezone-note';
    note.textContent = 'All timestamps are shown in Philippine Time (PHT).';
    recordsHeader.insertAdjacentElement('afterend', note);
}

function initUiUxLastMile() {
    attachClientValidation();
    enhanceRecordSelects();
    watchRecordSelects();
    addDecisionScopeBoundary();
    addAuditTimezoneNote();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUiUxLastMile, { once: true });
} else {
    initUiUxLastMile();
}
