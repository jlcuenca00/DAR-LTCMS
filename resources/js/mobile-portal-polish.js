import '../css/mobile-portal-polish.css';

const compactPortalQuery = window.matchMedia('(max-width: 1100px)');

const portalConfigs = [
    {
        key: 'staff',
        shell: '.staff-shell',
        actions: '.staff-topbar-actions',
        chip: null,
        help: '[data-onboarding-help="staff_portal"]',
        compactLabel: null,
    },
    {
        key: 'landowner',
        shell: '.lo-shell',
        actions: '.lo-topbar-right',
        chip: '.lo-access-chip',
        help: '[data-onboarding-help="landowner_portal"]',
        compactLabel: 'Own Only',
    },
    {
        key: 'geodetic',
        shell: '.geo-shell',
        actions: '.geo-topbar-right',
        chip: '.geo-access-chip',
        help: '[data-onboarding-help="geodetic_portal"]',
        compactLabel: 'Read Only',
    },
];

function ensurePlaceholder(container, element, key, type) {
    if (!container || !element) return null;

    let placeholder = container.querySelector(
        `[data-dar-mobile-${type}-placeholder="${key}"]`
    );

    if (!placeholder) {
        placeholder = document.createElement('span');
        placeholder.hidden = true;
        placeholder.dataset[`darMobile${type.charAt(0).toUpperCase()}${type.slice(1)}Placeholder`] = key;
        container.insertBefore(placeholder, element);
    }

    return placeholder;
}

function ensurePortalControlsPlacement(config) {
    const shell = document.querySelector(config.shell);
    const legacyActions = shell?.querySelector(config.actions);
    const chip = config.chip ? shell?.querySelector(config.chip) : null;
    const help = config.help ? shell?.querySelector(config.help) : null;
    const mobileActions = shell?.querySelector(
        `[data-dar-mobile-portal="${config.key}"] .dar-mobile-portal-actions`
    );

    if (!shell || !legacyActions || !mobileActions) return;

    const chipPlaceholder = chip ? ensurePlaceholder(legacyActions, chip, config.key, 'access') : null;
    const helpPlaceholder = help ? ensurePlaceholder(legacyActions, help, config.key, 'help') : null;

    if (chip && config.compactLabel) {
        chip.dataset.mobileAccessLabel = config.compactLabel;
    }

    if (compactPortalQuery.matches) {
        const notification = mobileActions.querySelector(':scope > .notification-dropdown');
        const account = mobileActions.querySelector(':scope > .account-topbar-cluster');

        if (help && help.parentElement !== mobileActions) {
            if (notification) mobileActions.insertBefore(help, notification);
            else mobileActions.prepend(help);
        }

        if (chip && chip.parentElement !== mobileActions) {
            if (account) mobileActions.insertBefore(chip, account);
            else mobileActions.appendChild(chip);
        }
        return;
    }

    if (help && helpPlaceholder?.parentNode && help.parentElement !== legacyActions) {
        helpPlaceholder.after(help);
    }

    if (chip && chipPlaceholder?.parentNode && chip.parentElement !== legacyActions) {
        chipPlaceholder.after(chip);
    }
}

function syncPortalControls() {
    portalConfigs.forEach(ensurePortalControlsPlacement);
}

function initMobilePortalPolish() {
    syncPortalControls();

    if (typeof compactPortalQuery.addEventListener === 'function') {
        compactPortalQuery.addEventListener('change', syncPortalControls);
    } else {
        compactPortalQuery.addListener(syncPortalControls);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobilePortalPolish, { once: true });
} else {
    initMobilePortalPolish();
}
