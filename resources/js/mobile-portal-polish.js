import '../css/mobile-portal-polish.css';

const compactPortalQuery = window.matchMedia('(max-width: 1100px)');

const portalAccessConfigs = [
    {
        key: 'landowner',
        shell: '.lo-shell',
        actions: '.lo-topbar-right',
        chip: '.lo-access-chip',
        compactLabel: 'Own Only',
    },
    {
        key: 'geodetic',
        shell: '.geo-shell',
        actions: '.geo-topbar-right',
        chip: '.geo-access-chip',
        compactLabel: 'Read Only',
    },
];

function ensureAccessChipPlacement(config) {
    const shell = document.querySelector(config.shell);
    const legacyActions = shell?.querySelector(config.actions);
    const chip = shell?.querySelector(config.chip);
    const mobileActions = shell?.querySelector(
        `[data-dar-mobile-portal="${config.key}"] .dar-mobile-portal-actions`
    );

    if (!shell || !legacyActions || !chip || !mobileActions) return;

    let placeholder = legacyActions.querySelector(
        `[data-dar-mobile-access-placeholder="${config.key}"]`
    );

    if (!placeholder) {
        placeholder = document.createElement('span');
        placeholder.hidden = true;
        placeholder.dataset.darMobileAccessPlaceholder = config.key;
        legacyActions.insertBefore(placeholder, chip);
    }

    chip.dataset.mobileAccessLabel = config.compactLabel;

    if (compactPortalQuery.matches) {
        const account = mobileActions.querySelector(':scope > .account-topbar-cluster');
        if (chip.parentElement !== mobileActions) {
            if (account) mobileActions.insertBefore(chip, account);
            else mobileActions.appendChild(chip);
        }
        return;
    }

    if (placeholder.parentNode && chip.parentElement !== legacyActions) {
        placeholder.after(chip);
    }
}

function syncPortalAccessChips() {
    portalAccessConfigs.forEach(ensureAccessChipPlacement);
}

function initMobilePortalPolish() {
    syncPortalAccessChips();

    if (typeof compactPortalQuery.addEventListener === 'function') {
        compactPortalQuery.addEventListener('change', syncPortalAccessChips);
    } else {
        compactPortalQuery.addListener(syncPortalAccessChips);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobilePortalPolish, { once: true });
} else {
    initMobilePortalPolish();
}
