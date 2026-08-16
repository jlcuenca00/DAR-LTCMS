import '../css/onboarding-tour.css';

const DEFAULT_TOUR_VERSION = 3;
const PROGRESS_PREFIX = 'darltcms:onboarding:';

const tours = {
    landowner_portal: {
        roleRoot: '.lo-shell',
        statusUrl: '/onboarding-tours/landowner_portal',
        autoPromptPath: /\/landowner\/dashboard\/?$/,
        helpMount: '.lo-topbar-right',
        helpLabel: 'Portal Tour',
        welcomeTitle: 'Welcome to DAR-LTCMS',
        welcomeCopy: 'Take a quick guided tour of the Landowner Portal. The guide will move through the actual pages so you can see where your records, map, applications, and notifications are located.',
        steps: [
            {
                path: '/landowner/dashboard',
                selectors: ['.lo-dashboard-hero', '.lo-content'],
                title: 'Your Dashboard',
                copy: 'This is your main overview. It summarizes only the DAR-LTCMS records and clearance activity linked to your landowner account.',
                side: 'bottom',
            },
            {
                path: '/landowner/dashboard',
                selectors: ['.lo-dashboard-grid', '.lo-content'],
                title: 'Activity at a Glance',
                copy: 'These panels show recent application activity, linked parcel references, and an overview of your application stages.',
                nextLabel: 'Open Parcel Records',
                side: 'top',
            },
            {
                path: '/landowner/parcels',
                selectors: ['.lo-parcel-panel', '.lo-parcel-page', '.lo-content'],
                title: 'My Parcel Records',
                copy: 'Review parcel and landholding references connected to your account, including location, area, record state, and map availability.',
                nextLabel: 'Open Parcel Map',
                side: 'top',
            },
            {
                path: '/landowner/parcel-map',
                selectors: ['.lo-map-sidebar', '.lo-content'],
                title: 'Map Tools',
                copy: 'Use these tools to search your linked parcels, reset the map view, or return to the parcel records list.',
                side: 'right',
            },
            {
                path: '/landowner/parcel-map',
                selectors: ['#parcel-map', '.lo-map-panel', '.lo-content'],
                title: 'My Parcel Map',
                copy: 'Mapped parcel geometry and location references appear here when geometry is available for records linked to your account.',
                nextLabel: 'Open Applications',
                side: 'left',
            },
            {
                path: '/landowner/applications',
                selectors: ['.lo-app-card', '.lo-app-overview', '.lo-app-page', '.lo-content'],
                title: 'My Clearance Applications',
                copy: 'Monitor applications linked to your landowner record, review their current status, and open a decision output when a finalized result is available.',
                side: 'top',
            },
            {
                path: '/landowner/applications',
                selectors: ['.notification-dropdown-panel'],
                prepare: 'notifications',
                noScroll: true,
                title: 'Notifications',
                copy: 'Recent system notices appear in this panel. The tour opens it automatically so you can see the actual notification area without having to click anything.',
                side: 'left',
            },
            {
                path: '/landowner/applications',
                selectors: ['[data-onboarding-help="landowner_portal"]'],
                noScroll: true,
                title: 'Replay the Tour Anytime',
                copy: 'Use this information button whenever you want to run the Landowner Portal guide again.',
                side: 'bottom',
            },
        ],
    },
};

let activeTour = null;

function normalizedPath(path = window.location.pathname) {
    const clean = String(path || '/').replace(/\/+$/, '');
    return clean || '/';
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

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function progressKey(key) {
    return `${PROGRESS_PREFIX}${key}`;
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
        // The current-page tour remains usable if session storage is unavailable.
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

    Object.entries(attributes).forEach(([name, value]) => {
        if (name === 'text') element.textContent = value;
        else element.setAttribute(name, value);
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
    await wait(180);
    layer.remove();
}

function buildTourLayer() {
    const layer = createElement('div', 'onboarding-tour-layer');
    layer.innerHTML = `
        <div class="onboarding-shade onboarding-shade-top" aria-hidden="true"></div>
        <div class="onboarding-shade onboarding-shade-left" aria-hidden="true"></div>
        <div class="onboarding-shade onboarding-shade-right" aria-hidden="true"></div>
        <div class="onboarding-shade onboarding-shade-bottom" aria-hidden="true"></div>
        <div class="onboarding-focus-ring" aria-hidden="true"></div>
        <section class="onboarding-popover" role="dialog" aria-modal="true" aria-labelledby="onboarding-step-title">
            <div class="onboarding-step-count"></div>
            <h2 id="onboarding-step-title"></h2>
            <p class="onboarding-step-copy"></p>
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
}

function closeTourOpenedNotifications() {
    document.querySelectorAll('[data-notification-dropdown][data-onboarding-tour-open]').forEach((dropdown) => {
        suppressNotificationRead(dropdown);
        dropdown.removeAttribute('data-onboarding-tour-open');
        dropdown.removeAttribute('open');
        window.setTimeout(() => {
            if (!dropdown.hasAttribute('data-onboarding-tour-open')) {
                delete dropdown.dataset.readTriggered;
            }
        }, 250);
    });
}

async function prepareStep(step) {
    closeTourOpenedNotifications();

    if (step.prepare !== 'notifications') return null;

    const dropdown = document.querySelector('[data-notification-dropdown]');
    if (!dropdown) return null;

    suppressNotificationRead(dropdown);
    dropdown.dataset.onboardingTourOpen = 'true';
    dropdown.setAttribute('open', '');

    await nextFrame();
    await wait(80);

    return dropdown.querySelector('.notification-dropdown-panel');
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

function clamp(value, minimum, maximum) {
    return Math.max(minimum, Math.min(value, maximum));
}

function targetRect(target) {
    const raw = target.getBoundingClientRect();
    const padding = 8;
    const margin = 6;

    const left = clamp(raw.left - padding, margin, window.innerWidth - margin);
    const top = clamp(raw.top - padding, margin, window.innerHeight - margin);
    const right = clamp(raw.right + padding, margin, window.innerWidth - margin);
    const bottom = clamp(raw.bottom + padding, margin, window.innerHeight - margin);

    return {
        left,
        top,
        right,
        bottom,
        width: Math.max(1, right - left),
        height: Math.max(1, bottom - top),
    };
}

function placeSpotlight(layer, rect) {
    const topShade = layer.querySelector('.onboarding-shade-top');
    const leftShade = layer.querySelector('.onboarding-shade-left');
    const rightShade = layer.querySelector('.onboarding-shade-right');
    const bottomShade = layer.querySelector('.onboarding-shade-bottom');
    const ring = layer.querySelector('.onboarding-focus-ring');

    topShade.style.cssText = `left:0;top:0;width:100vw;height:${rect.top}px`;
    bottomShade.style.cssText = `left:0;top:${rect.bottom}px;width:100vw;height:${Math.max(0, window.innerHeight - rect.bottom)}px`;
    leftShade.style.cssText = `left:0;top:${rect.top}px;width:${rect.left}px;height:${rect.height}px`;
    rightShade.style.cssText = `left:${rect.right}px;top:${rect.top}px;width:${Math.max(0, window.innerWidth - rect.right)}px;height:${rect.height}px`;

    ring.style.left = `${rect.left}px`;
    ring.style.top = `${rect.top}px`;
    ring.style.width = `${rect.width}px`;
    ring.style.height = `${rect.height}px`;
}

function placePopover(layer, rect, preferredSide = 'bottom') {
    const popover = layer.querySelector('.onboarding-popover');
    const popRect = popover.getBoundingClientRect();
    const margin = 14;
    const gap = 14;
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    const centeredLeft = clamp(rect.left + (rect.width - popRect.width) / 2, margin, viewportWidth - popRect.width - margin);
    const centeredTop = clamp(rect.top + (rect.height - popRect.height) / 2, margin, viewportHeight - popRect.height - margin);

    const candidates = {
        bottom: { top: rect.bottom + gap, left: centeredLeft },
        top: { top: rect.top - popRect.height - gap, left: centeredLeft },
        right: { top: centeredTop, left: rect.right + gap },
        left: { top: centeredTop, left: rect.left - popRect.width - gap },
    };

    const order = [preferredSide, 'bottom', 'top', 'right', 'left'].filter((side, index, array) => array.indexOf(side) === index);
    let chosen = null;

    for (const side of order) {
        const candidate = candidates[side];
        const fits = candidate.top >= margin
            && candidate.left >= margin
            && candidate.top + popRect.height <= viewportHeight - margin
            && candidate.left + popRect.width <= viewportWidth - margin;

        if (fits) {
            chosen = candidate;
            break;
        }
    }

    chosen ??= candidates[preferredSide] || candidates.bottom;
    popover.style.left = `${clamp(chosen.left, margin, viewportWidth - popRect.width - margin)}px`;
    popover.style.top = `${clamp(chosen.top, margin, viewportHeight - popRect.height - margin)}px`;
}

function positionTour(layer, target, step) {
    const rect = targetRect(target);
    placeSpotlight(layer, rect);
    placePopover(layer, rect, step.side || 'bottom');
}

async function beginTour(key, definition, version, initialIndex = 0) {
    if (activeTour?.close) {
        await activeTour.close(null);
    }

    const index = clamp(Number(initialIndex) || 0, 0, definition.steps.length - 1);
    const step = definition.steps[index];
    saveProgress(key, version, index);

    if (normalizedPath() !== normalizedPath(step.path)) {
        window.location.assign(step.path);
        return;
    }

    await runTour(key, definition, version, index);
}

async function runTour(key, definition, version, initialIndex) {
    let index = initialIndex;
    let activeTarget = null;
    let activeStep = null;
    let moving = false;
    let closed = false;

    const layer = buildTourLayer();
    const back = layer.querySelector('[data-onboarding-back]');
    const next = layer.querySelector('[data-onboarding-next]');
    const skip = layer.querySelector('[data-onboarding-skip]');

    const refreshPosition = () => {
        if (!activeTarget || !activeStep || moving || closed) return;
        window.requestAnimationFrame(() => positionTour(layer, activeTarget, activeStep));
    };

    const closeTour = async (status = null) => {
        if (closed) return;
        closed = true;
        moving = true;
        closeTourOpenedNotifications();
        clearProgress(key);

        if (status) persistStatus(definition, version, status);

        layer.classList.add('is-leaving');
        await wait(160);
        layer.remove();
        document.body.classList.remove('onboarding-tour-active');
        window.removeEventListener('resize', refreshPosition);
        window.removeEventListener('scroll', refreshPosition);
        document.removeEventListener('keydown', escapeHandler, true);

        if (activeTour?.layer === layer) activeTour = null;
    };

    activeTour = { layer, close: closeTour };

    const showStep = async (requestedIndex, initial = false) => {
        if (moving || closed) return;

        const resolved = clamp(Number(requestedIndex), 0, definition.steps.length - 1);
        const step = definition.steps[resolved];
        saveProgress(key, version, resolved);

        if (normalizedPath() !== normalizedPath(step.path)) {
            moving = true;
            closeTourOpenedNotifications();
            layer.classList.add('is-leaving');
            window.setTimeout(() => window.location.assign(step.path), reducedMotion() ? 0 : 90);
            return;
        }

        moving = true;
        back.disabled = true;
        next.disabled = true;
        layer.classList.add('is-moving');

        const preparedTarget = await prepareStep(step);
        index = resolved;
        activeStep = step;
        activeTarget = preparedTarget || resolveTarget(step);

        if (!activeTarget) {
            moving = false;
            layer.classList.remove('is-moving');
            return;
        }

        layer.querySelector('.onboarding-step-count').textContent = `Step ${index + 1} of ${definition.steps.length}`;
        layer.querySelector('#onboarding-step-title').textContent = step.title;
        layer.querySelector('.onboarding-step-copy').textContent = step.copy;

        back.disabled = index === 0;
        next.textContent = index === definition.steps.length - 1 ? 'Finish' : (step.nextLabel || 'Next');

        if (!step.noScroll) {
            activeTarget.scrollIntoView({
                behavior: reducedMotion() ? 'auto' : 'smooth',
                block: 'center',
                inline: 'nearest',
            });
            await wait(initial ? 80 : 260);
        } else {
            await wait(40);
        }

        positionTour(layer, activeTarget, step);
        await nextFrame();

        if (initial) layer.classList.add('is-ready');
        layer.classList.remove('is-moving');

        moving = false;
        back.disabled = index === 0;
        next.disabled = false;
        next.focus({ preventScroll: true });
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

    skip.addEventListener('click', () => {
        if (!moving) void closeTour('skipped');
    });

    const escapeHandler = (event) => {
        if (event.key !== 'Escape' || closed) return;
        event.preventDefault();
        event.stopPropagation();
        void closeTour(null);
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
        // The replay button remains available even if status lookup fails.
    }

    const progress = readProgress(key, version);
    if (progress) {
        await beginTour(key, definition, version, Number(progress.index || 0));
        return;
    }

    if (!status || status.seen || !definition.autoPromptPath.test(window.location.pathname)) return;

    const welcome = buildWelcome(definition);
    const start = welcome.querySelector('[data-onboarding-welcome-start]');
    const skipWelcome = welcome.querySelector('[data-onboarding-welcome-skip]');

    start.addEventListener('click', async () => {
        await dismissWelcome(welcome);
        await beginTour(key, definition, version, 0);
    });

    skipWelcome.addEventListener('click', async () => {
        clearProgress(key);
        persistStatus(definition, version, 'skipped');
        await dismissWelcome(welcome);
    });

    start.focus();
}

async function initOnboardingTours() {
    await Promise.all(Object.entries(tours).map(([key, definition]) => initTour(key, definition)));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        void initOnboardingTours();
    }, { once: true });
} else {
    void initOnboardingTours();
}
