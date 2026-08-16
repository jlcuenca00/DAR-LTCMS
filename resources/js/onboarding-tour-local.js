import '../css/onboarding-tour.css';
import '../css/onboarding-tour-local.css';

const DEFAULT_TOUR_VERSION = 2;

const tours = {
    landowner_portal: {
        roleRoot: '.lo-shell',
        statusUrl: '/onboarding-tours/landowner_portal',
        autoPromptPath: /\/landowner\/dashboard\/?$/,
        helpMount: '.lo-topbar-right',
        helpLabel: 'Portal Tour',
        welcomeTitle: 'Welcome to DAR-LTCMS',
        welcomeCopy: 'Take a quick guided tour of the Landowner Portal. You will move through the actual dashboard, parcel records, map, applications, and notifications.',
        steps: [
            {
                path: '/landowner/dashboard',
                selectors: ['.lo-dashboard-hero', '.lo-content'],
                title: 'Your Dashboard',
                copy: 'This is your main overview. It summarizes only the DAR-LTCMS records and clearance activity linked to your landowner account.',
            },
            {
                path: '/landowner/dashboard',
                selectors: ['.lo-dashboard-grid', '.lo-content'],
                title: 'Activity at a Glance',
                copy: 'These panels help you quickly review recent application status, linked parcel references, and current application activity.',
                nextLabel: 'Open Parcel Records',
            },
            {
                path: '/landowner/parcels',
                selectors: ['.lo-parcel-panel', '.lo-parcel-page', '.lo-content'],
                title: 'My Parcel Records',
                copy: 'Here you can review parcel and landholding references connected to your account, including location, area, record state, and map availability.',
                nextLabel: 'Open Parcel Map',
            },
            {
                path: '/landowner/parcel-map',
                selectors: ['.lo-map-sidebar', '.lo-content'],
                title: 'Map Tools',
                copy: 'Use these controls to find and review mapped parcel references associated with your records.',
            },
            {
                path: '/landowner/parcel-map',
                selectors: ['#parcel-map', '.lo-map-panel', '.lo-content'],
                title: 'My Parcel Map',
                copy: 'The map displays available parcel geometry and location references linked to your account. Map information supports review and monitoring only.',
                nextLabel: 'Open Applications',
            },
            {
                path: '/landowner/applications',
                selectors: ['.lo-app-card', '.lo-app-empty', '.lo-app-overview', '.lo-content'],
                title: 'My Clearance Applications',
                copy: 'Monitor applications linked to your landowner record, see their current status, and open the decision output when a finalized clearance result is available.',
            },
            {
                path: '/landowner/applications',
                selectors: ['.notification-bell-link'],
                title: 'Notifications',
                copy: 'Important application updates and other relevant notices appear here.',
                interaction: 'notification-bell',
            },
            {
                path: '/landowner/applications',
                selectors: ['.notification-dropdown-panel'],
                title: 'Recent Notifications',
                copy: 'This panel shows your latest system notifications so you can review important updates without leaving the page.',
                prepare: 'open-notifications',
            },
            {
                path: '/landowner/applications',
                selectors: ['[data-onboarding-help="landowner_portal"]'],
                title: 'Replay This Tour Anytime',
                copy: 'Use this information button whenever you want to run the Landowner Portal guide again.',
            },
        ],
    },
};

function normalizedPath(path = window.location.pathname) {
    const normalized = path.replace(/\/+$/, '');
    return normalized || '/';
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function reducedMotion() {
    return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
}

function wait(milliseconds) {
    return new Promise((resolve) => window.setTimeout(resolve, reducedMotion() ? 0 : milliseconds));
}

function nextFrame() {
    return new Promise((resolve) => window.requestAnimationFrame(() => window.requestAnimationFrame(resolve)));
}

function progressKey(key) {
    return `darltcms:onboarding:${key}`;
}

function readProgress(key, version) {
    try {
        const raw = window.sessionStorage.getItem(progressKey(key));
        if (!raw) return null;
        const progress = JSON.parse(raw);

        if (!progress?.active || Number(progress.version) !== Number(version)) {
            window.sessionStorage.removeItem(progressKey(key));
            return null;
        }

        return progress;
    } catch {
        return null;
    }
}

function saveProgress(key, version, index) {
    try {
        window.sessionStorage.setItem(progressKey(key), JSON.stringify({
            active: true,
            version: Number(version),
            index: Number(index),
        }));
    } catch {
        // Current-page guidance still works if sessionStorage is unavailable.
    }
}

function clearProgress(key) {
    try {
        window.sessionStorage.removeItem(progressKey(key));
    } catch {
        // No action required.
    }
}

function createElement(tag, className, attributes = {}) {
    const element = document.createElement(tag);
    if (className) element.className = className;

    Object.entries(attributes).forEach(([key, value]) => {
        if (key === 'text') element.textContent = value;
        else element.setAttribute(key, value);
    });

    return element;
}

function mountHelpButton(key, definition) {
    const mount = document.querySelector(definition.helpMount);
    if (!mount) return null;

    const existing = mount.querySelector(`[data-onboarding-help="${key}"]`);
    if (existing) return existing;

    const button = createElement('button', 'onboarding-help-button', {
        type: 'button',
        title: definition.helpLabel,
        'aria-label': `Start ${definition.helpLabel}`,
        'data-onboarding-help': key,
    });
    button.innerHTML = '<i class="fa-solid fa-circle-info" aria-hidden="true"></i>';
    mount.insertBefore(button, mount.firstChild);
    return button;
}

function buildWelcome(definition) {
    const layer = createElement('div', 'onboarding-welcome-layer');
    layer.innerHTML = `
        <section class="onboarding-welcome-card" role="dialog" aria-modal="true" aria-labelledby="onboarding-welcome-title">
            <div class="onboarding-welcome-icon" aria-hidden="true"><i class="fa-solid fa-compass"></i></div>
            <h2 id="onboarding-welcome-title"></h2>
            <p class="onboarding-welcome-copy"></p>
            <div class="onboarding-welcome-actions">
                <button type="button" class="onboarding-button onboarding-button-secondary" data-onboarding-welcome-skip>Skip Tour</button>
                <button type="button" class="onboarding-button onboarding-button-primary" data-onboarding-welcome-start>Start Tour</button>
            </div>
        </section>
    `;
    layer.querySelector('#onboarding-welcome-title').textContent = definition.welcomeTitle;
    layer.querySelector('.onboarding-welcome-copy').textContent = definition.welcomeCopy;
    document.body.appendChild(layer);
    requestAnimationFrame(() => requestAnimationFrame(() => layer.classList.add('is-visible')));
    return layer;
}

async function dismissWelcome(layer) {
    layer.classList.remove('is-visible');
    await wait(220);
    layer.remove();
}

function buildTourLayer() {
    const layer = createElement('div', 'onboarding-tour-layer onboarding-local is-entering');
    layer.innerHTML = `
        <div class="onboarding-shade onboarding-shade-top" aria-hidden="true"></div>
        <div class="onboarding-shade onboarding-shade-left" aria-hidden="true"></div>
        <div class="onboarding-shade onboarding-shade-right" aria-hidden="true"></div>
        <div class="onboarding-shade onboarding-shade-bottom" aria-hidden="true"></div>
        <div class="onboarding-focus-ring" aria-hidden="true"></div>
        <div class="onboarding-target-blocker" aria-hidden="true"></div>
        <section class="onboarding-popover" role="dialog" aria-modal="true" aria-labelledby="onboarding-step-title">
            <div class="onboarding-step-count"></div>
            <h2 id="onboarding-step-title"></h2>
            <p class="onboarding-step-copy"></p>
            <div class="onboarding-interaction-note" data-onboarding-interaction-note hidden>
                <i class="fa-solid fa-hand-pointer" aria-hidden="true"></i>
                <span>Click the highlighted notification bell to continue.</span>
            </div>
            <div class="onboarding-step-actions">
                <button type="button" class="onboarding-skip-link" data-onboarding-skip>Skip Tour</button>
                <div class="onboarding-step-nav">
                    <button type="button" class="onboarding-button onboarding-button-secondary" data-onboarding-back>Back</button>
                    <button type="button" class="onboarding-button onboarding-button-primary" data-onboarding-next>Next</button>
                </div>
            </div>
        </section>
    `;
    document.body.appendChild(layer);
    return layer;
}

function resolveTarget(step) {
    for (const selector of step.selectors || []) {
        const target = document.querySelector(selector);
        if (target) return target;
    }

    return document.querySelector('.lo-content');
}

function suppressNotificationRead(dropdown) {
    if (!dropdown) return;
    dropdown.dataset.readTriggered = 'true';
    window.setTimeout(() => delete dropdown.dataset.readTriggered, 500);
}

function closeNotificationsQuietly(except = null) {
    document.querySelectorAll('[data-notification-dropdown][open]').forEach((dropdown) => {
        if (dropdown === except) return;
        suppressNotificationRead(dropdown);
        dropdown.removeAttribute('data-onboarding-forced-open');
        dropdown.removeAttribute('open');
    });
}

async function prepareStep(step) {
    if (step.prepare !== 'open-notifications') return null;

    const dropdown = document.querySelector('[data-notification-dropdown]');
    if (!dropdown) return null;

    dropdown.dataset.onboardingForcedOpen = 'true';
    dropdown.setAttribute('open', '');
    await nextFrame();
    return dropdown;
}

function positionTour(layer, target) {
    const popover = layer.querySelector('.onboarding-popover');
    const ring = layer.querySelector('.onboarding-focus-ring');
    const blocker = layer.querySelector('.onboarding-target-blocker');
    const shades = {
        top: layer.querySelector('.onboarding-shade-top'),
        left: layer.querySelector('.onboarding-shade-left'),
        right: layer.querySelector('.onboarding-shade-right'),
        bottom: layer.querySelector('.onboarding-shade-bottom'),
    };

    const raw = target.getBoundingClientRect();
    const pad = 7;
    const rect = {
        top: Math.max(6, raw.top - pad),
        left: Math.max(6, raw.left - pad),
        right: Math.min(window.innerWidth - 6, raw.right + pad),
        bottom: Math.min(window.innerHeight - 6, raw.bottom + pad),
    };
    rect.width = Math.max(1, rect.right - rect.left);
    rect.height = Math.max(1, rect.bottom - rect.top);

    shades.top.style.cssText = `left:0;top:0;width:100vw;height:${rect.top}px`;
    shades.bottom.style.cssText = `left:0;top:${rect.bottom}px;width:100vw;height:${Math.max(0, window.innerHeight - rect.bottom)}px`;
    shades.left.style.cssText = `left:0;top:${rect.top}px;width:${rect.left}px;height:${rect.height}px`;
    shades.right.style.cssText = `left:${rect.right}px;top:${rect.top}px;width:${Math.max(0, window.innerWidth - rect.right)}px;height:${rect.height}px`;
    ring.style.cssText = `left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px`;
    blocker.style.left = `${rect.left}px`;
    blocker.style.top = `${rect.top}px`;
    blocker.style.width = `${rect.width}px`;
    blocker.style.height = `${rect.height}px`;

    const popRect = popover.getBoundingClientRect();
    const margin = 14;
    const gap = 14;
    let top = rect.bottom + gap;

    if (top + popRect.height > window.innerHeight - margin) {
        top = rect.top - popRect.height - gap;
    }

    top = Math.max(margin, Math.min(top, window.innerHeight - popRect.height - margin));
    const left = Math.max(margin, Math.min(rect.left, window.innerWidth - popRect.width - margin));
    popover.style.left = `${left}px`;
    popover.style.top = `${top}px`;
}

function persistStatus(definition, version, status) {
    return fetch(definition.statusUrl, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ version, status }),
    }).catch(() => undefined);
}

async function beginTour(key, definition, version, initialIndex = 0) {
    const step = definition.steps[initialIndex] || definition.steps[0];
    saveProgress(key, version, initialIndex);

    if (normalizedPath() !== normalizedPath(step.path)) {
        window.location.assign(step.path);
        return;
    }

    await runTour(key, definition, version, initialIndex);
}

async function runTour(key, definition, version, initialIndex = 0) {
    let index = Math.max(0, Math.min(Number(initialIndex) || 0, definition.steps.length - 1));
    let activeTarget = null;
    let preparedDropdown = null;
    let interactionCleanup = null;
    let moving = false;
    let closing = false;

    const layer = buildTourLayer();
    const popover = layer.querySelector('.onboarding-popover');
    const blocker = layer.querySelector('.onboarding-target-blocker');
    const back = layer.querySelector('[data-onboarding-back]');
    const next = layer.querySelector('[data-onboarding-next]');
    const interactionNote = layer.querySelector('[data-onboarding-interaction-note]');

    const cleanupInteraction = () => {
        interactionCleanup?.();
        interactionCleanup = null;
        blocker.style.pointerEvents = 'auto';
        interactionNote.hidden = true;
        next.hidden = false;
    };

    const refreshPosition = () => {
        if (!activeTarget || moving || closing) return;
        window.requestAnimationFrame(() => positionTour(layer, activeTarget));
    };

    const closeTour = async (status = null) => {
        if (closing) return;
        closing = true;
        cleanupInteraction();
        closeNotificationsQuietly();
        clearProgress(key);
        if (status) persistStatus(definition, version, status);

        layer.classList.add('is-leaving');
        await wait(180);
        layer.remove();
        document.body.classList.remove('onboarding-tour-active');
        window.removeEventListener('resize', refreshPosition);
        window.removeEventListener('scroll', refreshPosition);
        document.removeEventListener('keydown', escapeHandler, true);
    };

    const showStep = async (requestedIndex, initial = false) => {
        if (moving || closing) return;

        const resolved = Math.max(0, Math.min(Number(requestedIndex), definition.steps.length - 1));
        const step = definition.steps[resolved];
        saveProgress(key, version, resolved);

        if (normalizedPath() !== normalizedPath(step.path)) {
            moving = true;
            cleanupInteraction();
            closeNotificationsQuietly();
            window.location.assign(step.path);
            return;
        }

        moving = true;
        cleanupInteraction();

        if (preparedDropdown && step.prepare !== 'open-notifications') {
            closeNotificationsQuietly();
            preparedDropdown = null;
        }

        if (step.prepare === 'open-notifications') {
            preparedDropdown = await prepareStep(step);
        }

        index = resolved;
        activeTarget = resolveTarget(step);

        if (!activeTarget) {
            moving = false;
            return;
        }

        layer.querySelector('.onboarding-step-count').textContent = `Step ${index + 1} of ${definition.steps.length}`;
        layer.querySelector('#onboarding-step-title').textContent = step.title;
        layer.querySelector('.onboarding-step-copy').textContent = step.copy;

        const isLast = index === definition.steps.length - 1;
        const isInteractive = step.interaction === 'notification-bell';
        back.disabled = index === 0;
        next.textContent = isLast ? 'Finish' : (step.nextLabel || 'Next');
        next.hidden = isInteractive;
        interactionNote.hidden = !isInteractive;
        blocker.style.pointerEvents = isInteractive ? 'none' : 'auto';

        activeTarget.scrollIntoView({
            behavior: reducedMotion() ? 'auto' : 'smooth',
            block: 'center',
            inline: 'nearest',
        });

        await wait(initial ? 80 : 260);
        positionTour(layer, activeTarget);

        if (initial) {
            await nextFrame();
            layer.classList.remove('is-entering');
        }

        if (isInteractive) {
            const bell = activeTarget;
            const handleBellClick = () => {
                window.setTimeout(() => {
                    if (!closing) void showStep(index + 1);
                }, 80);
            };
            bell.addEventListener('click', handleBellClick, { once: true });
            interactionCleanup = () => bell.removeEventListener('click', handleBellClick);
        }

        moving = false;
        if (!isInteractive) next.focus({ preventScroll: true });
    };

    back.addEventListener('click', () => {
        if (!moving && index > 0) void showStep(index - 1);
    });

    next.addEventListener('click', () => {
        if (moving) return;
        if (index >= definition.steps.length - 1) {
            void closeTour('completed');
            return;
        }
        void showStep(index + 1);
    });

    layer.querySelector('[data-onboarding-skip]').addEventListener('click', () => {
        if (!moving) void closeTour('skipped');
    });

    const escapeHandler = (event) => {
        if (event.key !== 'Escape' || closing) return;
        event.stopPropagation();
        void closeTour();
    };

    window.addEventListener('resize', refreshPosition, { passive: true });
    window.addEventListener('scroll', refreshPosition, { passive: true });
    document.addEventListener('keydown', escapeHandler, true);
    document.body.classList.add('onboarding-tour-active');

    await showStep(index, true);
}

async function initTour(key, definition) {
    if (!document.querySelector(definition.roleRoot)) return;

    const helpButton = mountHelpButton(key, definition);
    if (!helpButton) return;

    let version = DEFAULT_TOUR_VERSION;

    // Attach replay immediately. Even if the status endpoint is temporarily unavailable,
    // the permanent help button remains functional.
    helpButton.addEventListener('click', () => {
        void beginTour(key, definition, version, 0);
    });

    let status = null;
    try {
        const response = await fetch(definition.statusUrl, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            status = await response.json();
            version = Number(status.version || DEFAULT_TOUR_VERSION);
        }
    } catch {
        // Replay remains available using the current known tour version.
    }

    const progress = readProgress(key, version);
    if (progress) {
        await beginTour(key, definition, version, Number(progress.index || 0));
        return;
    }

    if (!status || status.seen || !definition.autoPromptPath.test(window.location.pathname)) return;

    const welcome = buildWelcome(definition);
    const start = welcome.querySelector('[data-onboarding-welcome-start]');
    const skip = welcome.querySelector('[data-onboarding-welcome-skip]');

    start.addEventListener('click', async () => {
        await dismissWelcome(welcome);
        await beginTour(key, definition, version, 0);
    });

    skip.addEventListener('click', async () => {
        clearProgress(key);
        persistStatus(definition, version, 'skipped');
        await dismissWelcome(welcome);
    });

    start.focus();
}

async function initOnboardingTours() {
    await Promise.all(
        Object.entries(tours).map(([key, definition]) => initTour(key, definition))
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        void initOnboardingTours();
    }, { once: true });
} else {
    void initOnboardingTours();
}
