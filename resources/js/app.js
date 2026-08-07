import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// DAR-LTCMS Patch 1: account menu, tooltips, and live password validation
function initDarLtcmsPatchOneUi() {
    document.querySelectorAll('button[aria-label], a[aria-label], summary[aria-label]').forEach((control) => {
        if (!control.hasAttribute('title')) {
            control.setAttribute('title', control.getAttribute('aria-label'));
        }
    });

    document.querySelectorAll('[data-account-menu]').forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (!menu.open) return;
            document.querySelectorAll('[data-account-menu][open]').forEach((other) => {
                if (other !== menu) other.removeAttribute('open');
            });
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-account-menu][open]').forEach((menu) => {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-account-menu][open]').forEach((menu) => menu.removeAttribute('open'));
    });

    document.querySelectorAll('[data-password-checklist]').forEach((checklist) => {
        const password = document.getElementById(checklist.dataset.passwordInput || '');
        const confirmation = checklist.dataset.passwordConfirmation
            ? document.getElementById(checklist.dataset.passwordConfirmation)
            : null;

        if (!password) return;

        const tests = {
            length: (value) => value.length >= 8,
            lower: (value) => /[a-z]/.test(value),
            upper: (value) => /[A-Z]/.test(value),
            number: (value) => /\d/.test(value),
            symbol: (value) => /[^A-Za-z0-9]/.test(value),
            match: (value) => Boolean(value) && confirmation && value === confirmation.value,
        };

        const update = () => {
            const value = password.value || '';
            checklist.querySelectorAll('[data-password-rule]').forEach((rule) => {
                const key = rule.dataset.passwordRule;
                const valid = Boolean(tests[key]?.(value));
                rule.classList.toggle('is-valid', valid);
                const icon = rule.querySelector('.password-rule-icon');
                if (icon) icon.textContent = valid ? '✓' : '○';
            });
        };

        password.addEventListener('input', update);
        confirmation?.addEventListener('input', update);
        update();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDarLtcmsPatchOneUi, { once: true });
} else {
    initDarLtcmsPatchOneUi();
}
