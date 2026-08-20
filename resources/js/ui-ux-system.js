/* DAR-LTCMS progressive UI/UX enhancements.
   The backend, permissions, statuses, and final-decision rules remain authoritative.
   These helpers improve semantics and interaction while preserving no-JS fallbacks. */

const safeId = (value) => String(value || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');

function ensureLabelAssociations(root = document) {
    let generated = 0;

    root.querySelectorAll('label:not([for])').forEach((label) => {
        if (label.querySelector('input, select, textarea')) return;

        const container = label.closest('.field-group, .user-field, .parcel-create-field, .profile-field, .staff-filter-field, .form-group');
        if (!container) return;

        const control = container.querySelector('input:not([type="hidden"]), select, textarea');
        if (!control) return;

        if (!control.id) {
            generated += 1;
            const base = safeId(control.name || label.textContent) || 'field';
            control.id = `ui-${base}-${generated}`;
        }

        label.htmlFor = control.id;
    });
}

function setCurrentNavigation() {
    document.querySelectorAll('.staff-side-link.active, .lo-nav-link.active, .geo-nav-link.active, .dar-mobile-portal-nav-item.active').forEach((link) => {
        link.setAttribute('aria-current', 'page');
    });
}

function enhanceAlerts() {
    const errorSelectors = [
        '.review-alert-error',
        '.error-box',
        '.rounded-xl.border-red-200.bg-red-50',
        '.rounded-2xl.border-red-200.bg-red-50',
        '.rounded-lg.border-red-200.bg-red-50',
    ].join(',');

    const successSelectors = [
        '.review-alert-success',
        '.rounded-xl.border-green-200.bg-green-50',
        '.rounded-2xl.border-green-200.bg-green-50',
        '.rounded-lg.border-green-200.bg-green-50',
    ].join(',');

    document.querySelectorAll(errorSelectors).forEach((alert) => {
        alert.classList.add('ui-error-summary');
        alert.setAttribute('role', 'alert');
        alert.setAttribute('aria-live', 'assertive');
        alert.setAttribute('tabindex', '-1');
    });

    document.querySelectorAll(successSelectors).forEach((alert) => {
        if (alert.classList.contains('ui-error-summary')) return;
        alert.classList.add('ui-status-message');
        alert.setAttribute('role', 'status');
        alert.setAttribute('aria-live', 'polite');
    });

    const firstError = document.querySelector('.ui-error-summary');
    if (firstError) {
        requestAnimationFrame(() => firstError.focus({ preventScroll: false }));
    }
}

function selectToRadio(select, config = {}) {
    if (!select || select.dataset.uiRadioEnhanced === 'true') return;

    const field = select.closest('.field-group, .user-field, .parcel-create-field, .profile-field');
    if (!field) return;

    const label = field.querySelector(`label[for="${CSS.escape(select.id)}"], label`);
    const legendText = config.legend || label?.textContent?.replace('*', '').trim() || 'Choose an option';
    const fieldset = document.createElement('fieldset');
    fieldset.className = 'ui-radio-group';

    const legend = document.createElement('legend');
    legend.textContent = legendText;
    fieldset.appendChild(legend);

    const options = document.createElement('div');
    options.className = 'ui-radio-options';

    Array.from(select.options).forEach((option, index) => {
        if (config.skipEmpty && option.value === '') return;

        const optionLabel = document.createElement('label');
        optionLabel.className = 'ui-radio-option';

        const radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = `${select.name}__ui`;
        radio.value = option.value;
        radio.checked = option.selected;
        radio.id = `${select.id}-ui-${index}`;

        const text = document.createElement('span');
        text.textContent = config.labels?.[option.value] || option.textContent.trim();

        radio.addEventListener('change', () => {
            if (!radio.checked) return;
            select.value = radio.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });

        optionLabel.append(radio, text);
        options.appendChild(optionLabel);
    });

    fieldset.appendChild(options);
    select.dataset.uiRadioEnhanced = 'true';
    select.classList.add('ui-native-select-fallback');
    select.hidden = true;
    if (label) label.hidden = true;
    select.before(fieldset);
}

function enhanceBinaryQuestions() {
    selectToRadio(document.getElementById('has_special_power_of_attorney'), {
        labels: {
            '0': 'Not applicable / not indicated',
            '1': 'SPA presented / indicated',
        },
    });

    selectToRadio(document.getElementById('is_succession_case'), {
        labels: {
            '0': 'No / not indicated',
            '1': 'Yes, succession / inheritance context',
        },
    });

    selectToRadio(document.getElementById('retention_certificate_required'), {
        labels: {
            '0': 'Not required / not indicated',
            '1': 'Required for this review',
        },
    });
}

function syncConditionalFields() {
    const applicantType = document.getElementById('applicant_type');
    const representative = document.getElementById('authorized_representative_name');
    const spa = document.getElementById('has_special_power_of_attorney');

    if (applicantType && representative) {
        const repField = representative.closest('.field-group');
        const spaField = spa?.closest('.field-group');
        const update = () => {
            const show = applicantType.value === 'authorized_representative';
            [repField, spaField].filter(Boolean).forEach((field) => {
                field.classList.add('ui-conditional-field');
                field.hidden = !show;
            });
        };
        applicantType.addEventListener('change', update);
        update();
    }

    const retention = document.getElementById('retention_certificate_required');
    const retentionReference = document.getElementById('retention_certificate_reference');
    if (retention && retentionReference) {
        const referenceField = retentionReference.closest('.field-group');
        const update = () => {
            const show = retention.value === '1';
            referenceField?.classList.add('ui-conditional-field');
            if (referenceField) referenceField.hidden = !show;
        };
        retention.addEventListener('change', update);
        update();
    }
}

function addApplicationSectionNavigator() {
    const page = document.querySelector('.application-create-page');
    const form = page?.querySelector('form.form-shell');
    if (!page || !form || page.querySelector('.ui-section-nav')) return;

    const sections = Array.from(form.querySelectorAll('.form-section')).filter((section) => section.querySelector('.section-title'));
    if (sections.length < 3) return;

    const nav = document.createElement('nav');
    nav.className = 'ui-section-nav';
    nav.setAttribute('aria-label', 'Application form sections');

    sections.forEach((section, index) => {
        const heading = section.querySelector('.section-title');
        if (!section.id) section.id = `application-section-${index + 1}-${safeId(heading.textContent)}`;

        const link = document.createElement('a');
        link.href = `#${section.id}`;
        link.textContent = `${index + 1}. ${heading.textContent.trim()}`;
        nav.appendChild(link);
    });

    form.before(nav);
}

function enhanceReviewDisclosures() {
    const page = document.querySelector('.application-review-page');
    if (!page) return;

    const panels = Array.from(page.querySelectorAll('.review-panel'));
    const formPanels = panels.filter((panel) => {
        const heading = panel.querySelector('.review-panel-title, h2, h3, h4');
        const title = heading?.textContent?.trim() || '';
        return /LTC\s*Form\s*(No\.?\s*)?3|acknowledg(e)?ment receipt|LTC\s*Form\s*(No\.?\s*)?4|attestation.*recommendation/i.test(title);
    });

    formPanels.forEach((panel, index) => {
        if (panel.dataset.uiDisclosureEnhanced === 'true') return;
        const header = panel.querySelector('.review-panel-header');
        const body = panel.querySelector('.review-panel-body');
        if (!header || !body) return;

        panel.dataset.uiDisclosureEnhanced = 'true';
        panel.classList.add('ui-review-disclosure');
        if (!body.id) body.id = `ui-review-form-panel-${index + 1}`;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ui-review-disclosure-toggle';
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-controls', body.id);
        button.innerHTML = '<span>Show details</span><span aria-hidden="true">⌄</span>';

        body.hidden = true;
        header.appendChild(button);

        button.addEventListener('click', () => {
            const willOpen = body.hidden;
            body.hidden = !willOpen;
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            button.querySelector('span:first-child').textContent = willOpen ? 'Hide details' : 'Show details';
        });

        if (window.location.hash && panel.querySelector(window.location.hash)) {
            body.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            button.querySelector('span:first-child').textContent = 'Hide details';
        }
    });
}

function enhanceUserRoleDisclosure() {
    const editor = document.querySelector('.user-editor-wrap');
    const roleSelect = editor?.querySelector('select[name="role"]');
    const landownerSelect = editor?.querySelector('select[name="landowner_id"]');
    const disclosure = landownerSelect?.closest('details.user-disclosure');
    if (!roleSelect || !disclosure) return;

    const update = () => {
        const isLandowner = roleSelect.value === 'landowner';
        disclosure.hidden = !isLandowner;
        disclosure.open = isLandowner;
        if (!isLandowner && landownerSelect) landownerSelect.value = '';
    };

    roleSelect.addEventListener('change', update);
    update();
}

function enhanceLogin() {
    const page = document.querySelector('.login-page');
    if (!page) return;

    const submit = page.querySelector('.login-button');
    if (submit && submit.textContent.trim().toLowerCase() === 'login') {
        submit.textContent = 'Sign in';
    }

    if (document.title.startsWith('Login |')) {
        document.title = document.title.replace('Login |', 'Sign in |');
    }

    const help = page.querySelector('.forgot-link');
    if (help) help.textContent = 'Need help signing in?';
}

function addSubmitState() {
    const forms = document.querySelectorAll([
        '.application-create-page form',
        '.parcel-create-layout',
        '.user-editor-wrap form',
        '.profile-form',
    ].join(','));

    forms.forEach((form) => {
        if (form.dataset.uiSubmitState === 'true') return;
        form.dataset.uiSubmitState = 'true';

        form.addEventListener('submit', () => {
            if (!form.checkValidity()) return;
            const submit = form.querySelector('button[type="submit"]');
            if (!submit || submit.dataset.uiBusy === 'true') return;

            submit.dataset.uiBusy = 'true';
            submit.setAttribute('aria-busy', 'true');
            submit.setAttribute('aria-disabled', 'true');
            submit.dataset.uiOriginalText = submit.innerHTML;

            const label = /create|add/i.test(submit.textContent) ? 'Creating…'
                : /upload/i.test(submit.textContent) ? 'Uploading…'
                : /change password/i.test(submit.textContent) ? 'Updating…'
                : 'Saving…';

            submit.innerHTML = `<span aria-hidden="true">⏳</span><span>${label}</span>`;
            submit.addEventListener('click', (event) => {
                if (submit.dataset.uiBusy === 'true') event.preventDefault();
            });
        });
    });
}

function enhanceReadOnlyContext() {
    document.querySelectorAll('.application-review-page').forEach((page) => {
        const lockedControl = page.querySelector('[disabled], [readonly]');
        const finalText = Array.from(page.querySelectorAll('.staff-badge, .dashboard-status, .review-alert, h2, h3'))
            .some((node) => /approved|not approved|denied|final decision/i.test(node.textContent || ''));

        if (!lockedControl || !finalText || page.querySelector('.ui-locked-state')) return;

        const banner = document.createElement('div');
        banner.className = 'ui-locked-state';
        banner.setAttribute('role', 'status');
        banner.innerHTML = '<strong>Final decision recorded — editing is locked.</strong><span>Application data and uploads remain available for authorized viewing and archival actions only. The clearance decision does not itself execute or finalize land ownership transfer or registry mutation.</span>';
        page.prepend(banner);
    });
}

function observeDynamicFormControls() {
    const root = document.querySelector('.application-create-page, .user-editor-wrap');
    if (!root || typeof MutationObserver === 'undefined') return;

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof Element)) return;
                ensureLabelAssociations(node);
            });
        });
    });

    observer.observe(root, { childList: true, subtree: true });
}

/* Existing role shells mark every unread dropdown item read when the dropdown
   closes. Block that close-triggered side effect. Individual notification open
   routes and the explicit “Mark all as read” action remain authoritative. */
document.addEventListener('toggle', (event) => {
    const dropdown = event.target;
    if (!(dropdown instanceof HTMLDetailsElement)) return;
    if (!dropdown.matches('[data-notification-dropdown]')) return;
    if (dropdown.open) return;
    event.stopImmediatePropagation();
}, true);

function initUiUxSystem() {
    document.documentElement.classList.add('dar-ui-ux-ready');
    ensureLabelAssociations();
    setCurrentNavigation();
    enhanceAlerts();
    enhanceBinaryQuestions();
    syncConditionalFields();
    addApplicationSectionNavigator();
    enhanceReviewDisclosures();
    enhanceUserRoleDisclosure();
    enhanceLogin();
    addSubmitState();
    enhanceReadOnlyContext();
    observeDynamicFormControls();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUiUxSystem, { once: true });
} else {
    initUiUxSystem();
}

export { initUiUxSystem };
