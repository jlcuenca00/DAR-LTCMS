function syncNotificationTourState() {
    document.querySelectorAll('.onboarding-tour-layer.onboarding-local').forEach((layer) => {
        const interactionNote = layer.querySelector('[data-onboarding-interaction-note]');
        const blocker = layer.querySelector('.onboarding-target-blocker');
        const isInteractiveBellStep = Boolean(interactionNote && !interactionNote.hidden);

        if (blocker) {
            blocker.toggleAttribute('data-onboarding-interaction-proxy', isInteractiveBellStep);

            if (isInteractiveBellStep) {
                blocker.style.setProperty('pointer-events', 'auto', 'important');
                blocker.style.cursor = 'pointer';
            } else {
                blocker.style.removeProperty('pointer-events');
                blocker.style.removeProperty('cursor');
            }
        }
    });

    document.querySelectorAll('[data-notification-dropdown][data-onboarding-forced-open]').forEach((dropdown) => {
        dropdown.open = true;

        const panel = dropdown.querySelector('.notification-dropdown-panel');
        if (panel) {
            panel.style.removeProperty('display');
            panel.style.removeProperty('visibility');
            panel.style.removeProperty('opacity');
        }
    });
}

function activateHighlightedNotificationBell(event) {
    const blocker = event.target.closest?.('.onboarding-target-blocker[data-onboarding-interaction-proxy]');
    if (!blocker) return;

    const layer = blocker.closest('.onboarding-tour-layer.onboarding-local');
    const interactionNote = layer?.querySelector('[data-onboarding-interaction-note]');
    if (!interactionNote || interactionNote.hidden) return;

    const bell = document.querySelector('.notification-bell-link');
    const dropdown = bell?.closest('[data-notification-dropdown]');
    if (!bell || !dropdown) return;

    // The transparent tour hit-area represents the highlighted bell. Keep the
    // original click from reaching the page-level outside-click handler, then
    // trigger the real <summary> so its normal details behavior still runs.
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    dropdown.dataset.readTriggered = 'true';
    bell.click();

    // Guarantee that the following tour step has a fully rendered real panel,
    // even if another click/toggle listener changed the details state.
    dropdown.dataset.onboardingForcedOpen = 'true';
    dropdown.open = true;

    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(syncNotificationTourState);
    });
}

function initNotificationTourSupport() {
    syncNotificationTourState();

    document.addEventListener('click', activateHighlightedNotificationBell, true);

    const observer = new MutationObserver(() => {
        syncNotificationTourState();
    });

    observer.observe(document.body, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['hidden', 'open', 'data-onboarding-forced-open'],
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotificationTourSupport, { once: true });
} else {
    initNotificationTourSupport();
}
