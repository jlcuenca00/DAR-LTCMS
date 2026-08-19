function initializeAccountPanelProfileAnchors() {
    const profilePanels = Array.from(document.querySelectorAll('.profile-panel'));
    if (profilePanels.length === 0) return;

    profilePanels.forEach((panel) => {
        const title = panel.querySelector('.profile-panel-title')?.textContent.trim();

        if (title === 'Profile Information') {
            panel.id = 'profile-information';
        }

        if (title === 'Change Password') {
            panel.id = 'account-security';
        }
    });

    if (!['#profile-information', '#account-security'].includes(window.location.hash)) return;

    const target = document.querySelector(window.location.hash);
    if (!target) return;

    requestAnimationFrame(() => {
        target.scrollIntoView({ behavior: 'auto', block: 'start' });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAccountPanelProfileAnchors, { once: true });
} else {
    initializeAccountPanelProfileAnchors();
}
