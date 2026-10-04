// Drafts preserve form structure and selected record context; server validation remains authoritative.
const draftPrefix = 'dar_ltcms_form_draft:';
const ignoredDraftTypes = new Set(['file', 'password', 'submit', 'button', 'reset', 'hidden']);

function draftFields(form) {
    return Array.from(form.querySelectorAll('input[name], select[name], textarea[name]'))
        .filter(field => !field.disabled && !ignoredDraftTypes.has(field.type)
            && !field.name.endsWith('__ui'));
}

function readDraft(form) {
    const data = {};
    const records = {};
    draftFields(form).forEach(field => {
        if (field.type === 'checkbox') data[field.name] = field.checked;
        else if (field.type === 'radio') {
            if (field.checked) data[field.name] = field.value;
        } else if (field instanceof HTMLSelectElement && field.multiple) {
            data[field.name] = Array.from(field.selectedOptions, option => option.value);
        } else data[field.name] = field.value;

        if (field.matches('[data-remote-record-control]') && field.value) {
            const option = field.selectedOptions[0];
            if (option) records[field.name] = {
                value: option.value, text: option.textContent, meta: { ...option.dataset },
            };
        }
    });
    const packageMode = form.querySelector('[data-source-package-mode]');
    return {
        version: 2, savedAt: new Date().toISOString(), path: window.location.pathname, data, records,
        package: packageMode ? {
            mode: packageMode.value,
            sections: Array.from(form.querySelectorAll('[data-combined-toggle]:checked'), field => field.value),
            scope: form.querySelector('[data-scope-value]')?.value,
        } : null,
    };
}

function rebuildDraftRows(form, data) {
    for (const type of ['transferors', 'transferees']) {
        const list = form.querySelector('[data-party-list="' + type + '"]');
        const add = form.querySelector('[data-add-party="' + type + '"]');
        if (!list || !add) continue;
        const indices = Object.keys(data).map(name => name.match(new RegExp('^' + type + '\\[(\\d+)\\]')))
            .filter(Boolean).map(match => Number(match[1]));
        const count = Math.min(100, Math.max(1, ...indices.map(index => index + 1)));
        while (list.querySelectorAll('[data-party-item]').length < count) {
            const before = list.children.length;
            add.click();
            if (list.children.length === before) break;
        }
    }
    const instruments = form.querySelector('[data-instrument-list]');
    const addInstrument = form.querySelector('[data-add-instrument]');
    if (instruments && addInstrument) {
        const indices = Object.keys(data).map(name => name.match(/^transfer_instruments\[(\d+)\]/))
            .filter(Boolean).map(match => Number(match[1]) + 1);
        const count = Math.min(100, Math.max(1, ...indices));
        while (instruments.querySelectorAll('[data-instrument-item]').length < count) {
            const before = instruments.children.length;
            addInstrument.click();
            if (instruments.children.length === before) break;
        }
    }
}

function restoreDraft(form, draft) {
    if (!draft || !draft.data || draft.path !== window.location.pathname) return;
    rebuildDraftRows(form, draft.data);
    if (draft.package) {
        const radio = Array.from(form.querySelectorAll('input[name="source_type_choice"]'))
            .find(field => field.value === draft.package.mode);
        radio?.click();
        if (draft.package.mode === 'combined') {
            form.querySelectorAll('[data-combined-toggle]').forEach(field => {
                field.checked = (draft.package.sections || []).includes(field.value);
            });
            form.querySelector('[data-combined-toggle]')?.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (radio) {
            form.querySelector('[data-source-continue]')?.click();
        }
        form.querySelectorAll('[data-scope-select], [data-scope-mirror]').forEach(field => {
            if (draft.package.scope) field.value = draft.package.scope;
            field.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    draftFields(form).forEach(field => {
        if (!Object.prototype.hasOwnProperty.call(draft.data, field.name)) return;
        const value = draft.data[field.name];
        if (field.type === 'checkbox') field.checked = Boolean(value);
        else if (field.type === 'radio') field.checked = field.value === value;
        else if (field instanceof HTMLSelectElement && field.multiple && Array.isArray(value)) {
            Array.from(field.options).forEach(option => { option.selected = value.includes(option.value); });
        } else {
            if (field.matches('[data-remote-record-control]') && value) {
                const saved = draft.records?.[field.name];
                if (saved && String(saved.value) === String(value)
                    && !Array.from(field.options).some(option => option.value === String(value))) {
                    const option = document.createElement('option');
                    option.value = String(value);
                    option.textContent = String(saved.text || 'Record #' + value);
                    Object.entries(saved.meta || {}).forEach(([key, entry]) => {
                        if (/^[a-zA-Z][a-zA-Z0-9]*$/.test(key)) option.dataset[key] = String(entry);
                    });
                    field.appendChild(option);
                }
            }
            field.value = value ?? '';
        }
        field.dispatchEvent(new Event('change', { bubbles: true }));
        field.dispatchEvent(new Event('input', { bubbles: true }));
    });
}

function initFormDrafts() {
    document.querySelectorAll('form[data-autosave-key]').forEach(form => {
        if (form.dataset.draftReady) return;
        form.dataset.draftReady = 'true';
        const key = draftPrefix + form.dataset.autosaveKey;
        let timer;
        let submitting = false;
        const save = () => {
            if (submitting) return;
            try { localStorage.setItem(key, JSON.stringify(readDraft(form))); } catch { /* Storage is optional. */ }
        };
        try {
            const raw = localStorage.getItem(key);
            if (raw && !window.darFormDraftContext?.hasOldInput) restoreDraft(form, JSON.parse(raw));
        } catch { /* Ignore unavailable storage or malformed drafts. */ }

        const schedule = () => {
            clearTimeout(timer);
            if (!submitting) timer = window.setTimeout(save, 450);
        };
        form.addEventListener('input', schedule);
        form.addEventListener('change', schedule);
        form.addEventListener('submit', event => {
            // Wait for all synchronous validation/confirmation listeners.
            queueMicrotask(() => {
                if (event.defaultPrevented) return;
                clearTimeout(timer);
                submitting = true;
                try { localStorage.removeItem(key); } catch { /* Storage is optional. */ }
            });
        });
        window.addEventListener('pageshow', event => {
            if (event.persisted) submitting = false;
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFormDrafts, { once: true });
} else initFormDrafts();

export { initFormDrafts, readDraft, restoreDraft };
