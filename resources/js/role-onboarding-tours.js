import '../css/onboarding-tour.css';

const TOUR_VERSION = 1;
const PROGRESS_PREFIX = 'darltcms:onboarding:';

const roleTours = {
    staff_portal: {
        roleRoot: '.staff-shell',
        fallbackTarget: '.staff-content-inner',
        statusUrl: '/onboarding-tours/staff_portal',
        autoPromptPath: /\/staff\/dashboard\/?$/,
        helpMount: '.staff-topbar-actions',
        helpLabel: 'Staff Portal Tour',
        welcomeTitle: 'Welcome to the Staff Portal',
        welcomeCopy: 'Take a quick tour of the pages DAR Staff use to process and monitor land transfer clearance applications.',
        steps: [
            {
                path: '/staff/dashboard',
                selectors: ['.dashboard-hero', '.staff-dashboard', '.staff-content-inner'],
                title: 'Staff Dashboard',
                copy: 'This page gives you a quick view of current clearance work and the applications that need attention.',
                side: 'bottom',
            },
            {
                path: '/staff/dashboard',
                selectors: ['.hero-queue', '.today-strip', '.staff-dashboard'],
                title: 'Current Work',
                copy: 'Use these summaries to see what needs follow-up and to open a new clearance application when needed.',
                nextLabel: 'Open Applications',
                side: 'top',
            },
            {
                path: '/staff/applications',
                selectors: ['.staff-panel', '.staff-content-inner'],
                title: 'Clearance Applications',
                copy: 'This is where Staff encode, review, process, and monitor land transfer clearance applications through the DAR office stages.',
                nextLabel: 'Open Landowner Records',
                side: 'top',
            },
            {
                path: '/staff/records/landowners',
                selectors: ['.staff-panel', '.staff-content-inner'],
                title: 'Landowner Records',
                copy: 'Manage the landowner records used by clearance applications and keep their linked information accurate.',
                nextLabel: 'Open Parcel Records',
                side: 'top',
            },
            {
                path: '/staff/records/parcels',
                selectors: ['.staff-panel', '.staff-content-inner'],
                title: 'Parcel Records',
                copy: 'Review parcel references, locations, linked landholdings, and map information used during clearance processing.',
                nextLabel: 'Open Parcel Map',
                side: 'top',
            },
            {
                path: '/staff/parcel-map',
                selectors: ['#parcel-map', '#map', '.staff-content-inner'],
                title: 'Parcel Map',
                copy: 'Use the map to review parcel locations and mapped boundaries as supporting reference information.',
                nextLabel: 'Open Monitoring Reports',
                side: 'left',
            },
            {
                path: '/staff/reports/monitoring',
                selectors: ['.staff-panel', '.staff-content-inner'],
                title: 'Monitoring Reports',
                copy: 'Use this page to review application totals, progress, and other monitoring information for the DAR office.',
                side: 'top',
            },
            {
                path: '/staff/reports/monitoring',
                selectors: ['.notification-dropdown-panel'],
                prepare: 'notifications',
                noScroll: true,
                title: 'Notifications',
                copy: 'Recent system notices appear here so Staff can see important application updates.',
                side: 'left',
            },
            {
                path: '/staff/reports/monitoring',
                selectors: ['[data-onboarding-help="staff_portal"]'],
                noScroll: true,
                title: 'Replay the Tour Anytime',
                copy: 'Use this information button whenever you want to run the Staff Portal tour again.',
                side: 'bottom',
            },
        ],
    },
    geodetic_portal: {
        roleRoot: '.geo-shell',
        fallbackTarget: '.geo-content',
        statusUrl: '/onboarding-tours/geodetic_portal',
        autoPromptPath: /\/geodetic\/dashboard\/?$/,
        helpMount: '.geo-topbar-right',
        helpLabel: 'Geodetic Portal Tour',
        welcomeTitle: 'Welcome to the Geodetic Portal',
        welcomeCopy: 'Take a quick tour of the parcel and map information available for geodetic review.',
        steps: [
            {
                path: '/geodetic/dashboard',
                selectors: ['.geo-dashboard-hero', '.geo-dashboard-stack', '.geo-content'],
                title: 'Geodetic Dashboard',
                copy: 'This page summarizes parcel references and map coverage available for technical review.',
                side: 'bottom',
            },
            {
                path: '/geodetic/dashboard',
                selectors: ['.geo-dashboard-grid', '.geo-recent-list', '.geo-content'],
                title: 'Recent Parcel Records',
                copy: 'Review recently updated parcel references and open a record to see its available details.',
                nextLabel: 'Open Parcel References',
                side: 'top',
            },
            {
                path: '/geodetic/parcels',
                selectors: ['.geo-record-table-wrap', '.geo-content'],
                title: 'Parcel References',
                copy: 'This list provides the parcel, landowner, area, location, and map information available for geodetic review.',
                nextLabel: 'Open Parcel Map',
                side: 'top',
            },
            {
                path: '/geodetic/parcel-map',
                selectors: ['#parcel-map', '#map', '.geo-content'],
                title: 'Parcel Map',
                copy: 'Use the map to review parcel locations and mapped boundaries. This information supports technical review and does not change land ownership.',
                side: 'left',
            },
            {
                path: '/geodetic/parcel-map',
                selectors: ['.notification-dropdown-panel'],
                prepare: 'notifications',
                noScroll: true,
                title: 'Notifications',
                copy: 'Recent system notices appear here when there is information relevant to your account.',
                side: 'left',
            },
            {
                path: '/geodetic/parcel-map',
                selectors: ['[data-onboarding-help="geodetic_portal"]'],
                noScroll: true,
                title: 'Replay the Tour Anytime',
                copy: 'Use this information button whenever you want to run the Geodetic Portal tour again.',
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

function nextTask() {
    return new Promise((resolve) => window.setTimeout(resolve, 0));
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
        // The current page remains usable if session storage is unavailable.
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
        <section class="onboarding-welcome-card" role="dialog" aria-modal="true" aria-labelledby="role-onboarding-welcome-title">
            <div class="onboarding-welcome-icon" aria-hidden="true"><i class="fa-solid fa-compass"></i></div>
            <h2 id="role-onboarding-welcome-title"></h2>
            <p class="onboarding-welcome-copy"></p>
            <div class="onboarding-welcome-actions">
                <button type="button" class="onboarding-button onboarding-button-secondary" data-role-onboarding-welcome-skip>Skip Tour</button>
                <button type="button" class="onboarding-button onboarding-button-primary" data-role-onboarding-welcome-start>Start Tour</button>
            </div>
        </section>
    `;

    layer.querySelector('#role-onboarding-welcome-title').textContent = definition.welcomeTitle;
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
        <div class="onboarding-target-blocker" aria-hidden="true"></div>
        <section class="onboarding-popover" role="dialog" aria-modal="true" aria-labelledby="role-onboarding-step-title">
            <div class="onboarding-step-count"></div>
            <h2 id="role-onboarding-step-title"></h2>
            <p class="onboarding-step-copy"></p>
            <div class="onboarding-step-actions">
                <button type="button" class="onboarding-skip-link" data-role-onboarding-skip>Skip Tour</button>
                <div class="onboarding-step-nav">
                    <button type="button" class="onboarding-button onboarding-button-secondary" data-role-onboarding-back>Back</button>
                    <button type="button" class="onboarding-button onboarding-button-primary" data-role-onboarding-next>Next</button>
                </div>
            </div>
        </section>
    `;

    document.body.appendChild(layer);
    return layer;
}

function resolveTarget(step, definition) {
    for (const selector of step.selectors || []) {
        const target = document.querySelector(selector);
        if (target) return target;
    }

    return document.querySelector(definition.fallbackTarget) || document.querySelector(definition.roleRoot);
}

function suppressNotificationRead(dropdown) {
    if (dropdown) dropdown.dataset.readTriggered = 'true';
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
        }, 300);
    });
}

async function prepareStep(step) {
    closeTourOpenedNotifications();
    if (step.prepare !== 'notifications') return null;

    await nextTask();

    const dropdown = document.querySelector('[data-notification-dropdown]');
    if (!dropdown) return null;

    suppressNotificationRead(dropdown);
    dropdown.dataset.onboardingTourOpen = 'true';
    dropdown.setAttribute('open', '');

    await nextFrame();
    await wait(120);

    if (dropdown.hasAttribute('data-onboarding-tour-open')) {
        dropdown.setAttribute('open', '');
    }

    await nextFrame();
    const panel = dropdown.querySelector('.notification-dropdown-panel');
    return panel && panel.getClientRects().length > 0 ? panel : null;
}

function persistStatus(definition, version, status) {
    return fetch(definition.statusUrl, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
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
    layer.querySelector('.onboarding-shade-top').style.cssText = `left:0;top:0;width:100vw;height:${rect.top}px`;
    layer.querySelector('.onboarding-shade-bottom').style.cssText = `left:0;top:${rect.bottom}px;width:100vw;height:${Math.max(0, window.innerHeight - rect.bottom)}px`;
    layer.querySelector('.onboarding-shade-left').style.cssText = `left:0;top:${rect.top}px;width:${rect.left}px;height:${rect.height}px`;
    layer.querySelector('.onboarding-shade-right').style.cssText = `left:${rect.right}px;top:${rect.top}px;width:${Math.max(0, window.innerWidth - rect.right)}px;height:${rect.height}px`;

    [layer.querySelector('.onboarding-focus-ring'), layer.querySelector('.onboarding-target-blocker')].forEach((element) => {
        element.style.left = `${rect.left}px`;
        element.style.top = `${rect.top}px`;
        element.style.width = `${rect.width}px`;
        element.style.height = `${rect.height}px`;
    });
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
    const order = [preferredSide, 'bottom', 'top', 'right', 'left']
        .filter((side, index, array) => array.indexOf(side) === index);

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
    if (activeTour?.close) await activeTour.close(null);

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
    const back = layer.querySelector('[data-role-onboarding-back]');
    const next = layer.querySelector('[data-role-onboarding-next]');
    const skip = layer.querySelector('[data-role-onboarding-skip]');

    const refreshPosition = () => {
        if (!activeTarget || !activeStep || moving || closed) return;
        window.requestAnimationFrame(() => positionTour(layer, activeTarget, activeStep));
    };

    const escapeHandler = (event) => {
        if (event.key !== 'Escape' || closed) return;
        event.stopPropagation();
        void closeTour();
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
        if (activeTour?.close === closeTour) activeTour = null;
    };

    const showStep = async (requestedIndex, initial = false) => {
        if (moving || closed) return;

        const nextIndex = clamp(Number(requestedIndex), 0, definition.steps.length - 1);
        const step = definition.steps[nextIndex];
        saveProgress(key, version, nextIndex);

        if (normalizedPath() !== normalizedPath(step.path)) {
            moving = true;
            closeTourOpenedNotifications();
            layer.classList.add('is-leaving');
            await wait(120);
            window.location.assign(step.path);
            return;
        }

        moving = true;
        layer.classList.add('is-moving');
        const preparedTarget = await prepareStep(step);
        index = nextIndex;
        activeStep = step;
        activeTarget = preparedTarget || resolveTarget(step, definition);

        if (!activeTarget) {
            moving = false;
            layer.classList.remove('is-moving');
            return;
        }

        layer.querySelector('.onboarding-step-count').textContent = `Step ${index + 1} of ${definition.steps.length}`;
        layer.querySelector('#role-onboarding-step-title').textContent = step.title;
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
            await wait(initial ? 40 : 100);
        }

        positionTour(layer, activeTarget, activeStep);
        await nextFrame();
        if (initial) layer.classList.add('is-ready');
        layer.classList.remove('is-moving');
        moving = false;
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

    document.body.classList.add('onboarding-tour-active');
    window.addEventListener('resize', refreshPosition, { passive: true });
    window.addEventListener('scroll', refreshPosition, { passive: true });
    document.addEventListener('keydown', escapeHandler, true);
    activeTour = { close: closeTour };
    await showStep(index, true);
}

async function initTour(key, definition) {
    if (!document.querySelector(definition.roleRoot)) return;

    const helpButton = mountHelpButton(key, definition);
    if (!helpButton) return;

    let version = TOUR_VERSION;
    let status = null;

    helpButton.addEventListener('click', () => {
        void beginTour(key, definition, version, 0);
    });

    try {
        const response = await fetch(definition.statusUrl, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            status = await response.json();
            version = Number(status.version || TOUR_VERSION);
        }
    } catch {
        // Replay still works if the status request is temporarily unavailable.
    }

    const progress = readProgress(key, version);
    if (progress) {
        await beginTour(key, definition, version, Number(progress.index || 0));
        return;
    }

    if (!status || status.seen || !definition.autoPromptPath.test(window.location.pathname)) return;

    const welcome = buildWelcome(definition);
    const start = welcome.querySelector('[data-role-onboarding-welcome-start]');
    const skip = welcome.querySelector('[data-role-onboarding-welcome-skip]');

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

async function initRoleOnboardingTours() {
    await Promise.all(Object.entries(roleTours).map(([key, definition]) => initTour(key, definition)));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        void initRoleOnboardingTours();
    }, { once: true });
} else {
    void initRoleOnboardingTours();
}
