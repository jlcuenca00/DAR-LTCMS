import '../css/onboarding-tour.css';
import '../css/onboarding-tour-v3.css';

const tourDefinitions = {
    landowner_portal: {
        roleRoot: '.lo-shell',
        statusUrl: '/onboarding-tours/landowner_portal',
        autoPromptPath: /\/landowner\/dashboard\/?$/,
        helpMount: '.lo-topbar-right',
        helpLabel: 'Portal Tour',
        welcomeTitle: 'Welcome to DAR-LTCMS',
        welcomeCopy: 'Take a guided tour of the Landowner Portal. The tour will move between your dashboard, parcel records, map, applications, and notifications so you can see how the portal works.',
        steps: [
            {
                path: '/landowner/dashboard',
                selectors: ['.lo-dashboard-hero'],
                title: 'Your Dashboard',
                copy: 'This is your main portal overview. It summarizes only the DAR-LTCMS records and clearance activity linked to your landowner account.',
            },
            {
                path: '/landowner/dashboard',
                selectors: ['.lo-dashboard-grid'],
                title: 'Your Activity at a Glance',
                copy: 'Use these panels to quickly review recent application status, linked parcel references, and the current application-stage overview.',
            },
            {
                path: '/landowner/parcels',
                selectors: ['.lo-parcel-panel', '.lo-parcel-page'],
                title: 'My Parcel Records',
                copy: 'This page shows parcel and landholding references connected to your account, including title references, location, linked area, record state, and map availability.',
            },
            {
                path: '/landowner/parcel-map',
                selectors: ['.lo-map-sidebar'],
                title: 'Find and Review Mapped Parcels',
                copy: 'Use the parcel search and map tools to find mapped parcel references associated with your records.',
            },
            {
                path: '/landowner/parcel-map',
                selectors: ['#parcel-map', '.lo-map-panel'],
                title: 'My Parcel Map',
                copy: 'The map displays available parcel geometry and location references linked to your account. Map information supports review and monitoring only.',
            },
            {
                path: '/landowner/applications',
                selectors: ['.lo-app-card', '.lo-app-empty', '.lo-app-overview'],
                title: 'My Clearance Applications',
                copy: 'Here you can monitor applications linked to your landowner record, see their current status, and open the decision output when a finalized clearance result is available.',
            },
            {
                path: '/landowner/applications',
                selectors: ['.notification-dropdown-panel'],
                prepare: 'open-notifications',
                title: 'Notifications',
                copy: 'Important application updates and other relevant system notifications appear here. Opening the panel lets you review recent notices without leaving the page.',
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

const routeFadeStorageKey = 'darltcms:onboarding:route-fade';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function normalizedPath(path = window.location.pathname) {
    const normalized = path.replace(/\/+$/, '');
    return normalized || '/';
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

async function animateElement(element, keyframes, options = {}) {
    if (!element) return;

    const finalFrame = keyframes[keyframes.length - 1] || {};
    if (reducedMotion() || typeof element.animate !== 'function') {
        Object.entries(finalFrame).forEach(([property, value]) => {
            if (property !== 'offset' && property !== 'easing' && property !== 'composite') {
                element.style[property] = value;
            }
        });
        return;
    }

    const animation = element.animate(keyframes, {
        duration: 320,
        easing: 'cubic-bezier(.22, 1, .36, 1)',
        fill: 'forwards',
        ...options,
    });

    try {
        await animation.finished;
    } catch {
        // A newer animation may intentionally replace this one.
    }
}

function progressStorageKey(key) {
    return `darltcms:onboarding:${key}`;
}

function readProgress(key, version) {
    try {
        const raw = window.sessionStorage.getItem(progressStorageKey(key));
        if (!raw) return null;
        const progress = JSON.parse(raw);
        if (!progress?.active || Number(progress.version) !== Number(version)) {
            window.sessionStorage.removeItem(progressStorageKey(key));
            return null;
        }
        return progress;
    } catch {
        return null;
    }
}

function saveProgress(key, version, index) {
    try {
        window.sessionStorage.setItem(progressStorageKey(key), JSON.stringify({
            active: true,
            version: Number(version),
            index: Number(index),
        }));
    } catch {
        // Keep the tour usable even when session storage is unavailable.
    }
}

function clearProgress(key) {
    try {
        window.sessionStorage.removeItem(progressStorageKey(key));
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

function createRouteCurtain(opacity = '0') {
    const curtain = createElement('div', 'onboarding-v3-route-curtain', {
        'aria-hidden': 'true',
    });
    curtain.style.opacity = opacity;
    document.body.appendChild(curtain);
    return curtain;
}

function rememberRouteFade() {
    try {
        window.sessionStorage.setItem(routeFadeStorageKey, String(Date.now()));
    } catch {
        // The outgoing fade still works even if the arrival fade cannot be remembered.
    }
}

function consumeRouteFade() {
    try {
        const raw = window.sessionStorage.getItem(routeFadeStorageKey);
        if (!raw) return false;
        window.sessionStorage.removeItem(routeFadeStorageKey);
        return Date.now() - Number(raw) < 10000;
    } catch {
        return false;
    }
}

async function fadeAndNavigate(targetPath) {
    rememberRouteFade();
    const curtain = createRouteCurtain('0');
    await nextFrame();
    await animateElement(curtain, [
        { opacity: 0 },
        { opacity: 1 },
    ], { duration: 480, easing: 'ease-in-out' });
    window.location.assign(targetPath);
}

function buildWelcome(definition) {
    const layer = createElement('div', 'onboarding-welcome-layer');
    layer.hidden = false;
    layer.style.opacity = '0';
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
    return layer;
}

async function revealWelcome(layer, focusTarget) {
    const card = layer.querySelector('.onboarding-welcome-card');
    await nextFrame();
    await Promise.all([
        animateElement(layer, [{ opacity: 0 }, { opacity: 1 }], { duration: 360, easing: 'ease-out' }),
        animateElement(card, [
            { opacity: 0, transform: 'translateY(14px) scale(.975)' },
            { opacity: 1, transform: 'translateY(0) scale(1)' },
        ], { duration: 420 }),
    ]);
    focusTarget?.focus();
}

async function dismissWelcome(layer) {
    const card = layer.querySelector('.onboarding-welcome-card');
    await Promise.all([
        animateElement(layer, [{ opacity: 1 }, { opacity: 0 }], { duration: 300, easing: 'ease-in' }),
        animateElement(card, [
            { opacity: 1, transform: 'translateY(0) scale(1)' },
            { opacity: 0, transform: 'translateY(10px) scale(.98)' },
        ], { duration: 300, easing: 'ease-in' }),
    ]);
    layer.remove();
}

function mountHelpButton(key, definition) {
    const mount = document.querySelector(definition.helpMount);
    if (!mount || mount.querySelector(`[data-onboarding-help="${key}"]`)) return null;

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

function buildTourLayer() {
    const layer = createElement('div', 'onboarding-tour-layer onboarding-v3');
    layer.hidden = false;
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
            <div class="onboarding-step-actions">
                <button type="button" class="onboarding-skip-link" data-onboarding-skip>Skip Tour</button>
                <div class="onboarding-step-nav">
                    <button type="button" class="onboarding-button onboarding-button-secondary" data-onboarding-back>Back</button>
                    <button type="button" class="onboarding-button onboarding-button-primary" data-onboarding-next>Next</button>
                </div>
            </div>
        </section>
        <div class="onboarding-v3-step-curtain" aria-hidden="true"></div>
    `;
    layer.style.opacity = '0';
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

async function prepareStep(step) {
    if (step.prepare !== 'open-notifications') return null;

    // Wait until the click that advanced the tour has completely finished.
    await wait(0);
    const dropdown = document.querySelector('[data-notification-dropdown]');
    if (!dropdown) return null;

    dropdown.dataset.onboardingForcedOpen = 'true';
    dropdown.open = true;
    await nextFrame();
    await wait(80);
    return dropdown;
}

function closePreparedDropdown(dropdown) {
    if (!dropdown) return;
    dropdown.dataset.readTriggered = 'true';
    dropdown.removeAttribute('data-onboarding-forced-open');
    dropdown.open = false;
    window.setTimeout(() => {
        delete dropdown.dataset.readTriggered;
    }, 150);
}

function positionTour(layer, target) {
    const popover = layer.querySelector('.onboarding-popover');
    const ring = layer.querySelector('.onboarding-focus-ring');
    const blocker = layer.querySelector('.onboarding-target-blocker');
    const topShade = layer.querySelector('.onboarding-shade-top');
    const leftShade = layer.querySelector('.onboarding-shade-left');
    const rightShade = layer.querySelector('.onboarding-shade-right');
    const bottomShade = layer.querySelector('.onboarding-shade-bottom');

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

    topShade.style.cssText = `left:0;top:0;width:100vw;height:${rect.top}px`;
    bottomShade.style.cssText = `left:0;top:${rect.bottom}px;width:100vw;height:${Math.max(0, window.innerHeight - rect.bottom)}px`;
    leftShade.style.cssText = `left:0;top:${rect.top}px;width:${rect.left}px;height:${rect.height}px`;
    rightShade.style.cssText = `left:${rect.right}px;top:${rect.top}px;width:${Math.max(0, window.innerWidth - rect.right)}px;height:${rect.height}px`;
    ring.style.cssText = `left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px`;
    blocker.style.cssText = `left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px`;

    const popRect = popover.getBoundingClientRect();
    const margin = 14;
    const gap = 14;
    let popTop = rect.bottom + gap;
    if (popTop + popRect.height > window.innerHeight - margin) {
        popTop = rect.top - popRect.height - gap;
    }
    popTop = Math.max(margin, Math.min(popTop, window.innerHeight - popRect.height - margin));
    const popLeft = Math.max(margin, Math.min(rect.left, window.innerWidth - popRect.width - margin));
    popover.style.left = `${popLeft}px`;
    popover.style.top = `${popTop}px`;
}

async function scrollTargetIntoView(target) {
    target.scrollIntoView({
        behavior: reducedMotion() ? 'auto' : 'smooth',
        block: 'center',
        inline: 'nearest',
    });
    await wait(340);
}

async function beginTour(key, definition, version, initialIndex = 0) {
    const firstStep = definition.steps[initialIndex] || definition.steps[0];
    saveProgress(key, version, initialIndex);

    if (normalizedPath() !== normalizedPath(firstStep.path)) {
        await fadeAndNavigate(normalizedPath(firstStep.path));
        return;
    }

    await runTour(key, definition, version, initialIndex);
}

async function runTour(key, definition, version, initialIndex) {
    let index = initialIndex;
    let activeTarget = null;
    let preparedDropdown = null;
    let moving = false;
    let closing = false;

    const layer = buildTourLayer();
    const popover = layer.querySelector('.onboarding-popover');
    const ring = layer.querySelector('.onboarding-focus-ring');
    const stepCurtain = layer.querySelector('.onboarding-v3-step-curtain');
    const back = layer.querySelector('[data-onboarding-back]');
    const next = layer.querySelector('[data-onboarding-next]');

    const persist = (status) => fetch(definition.statusUrl, {
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

    const refreshPosition = () => {
        if (activeTarget && !moving && !closing) {
            window.requestAnimationFrame(() => positionTour(layer, activeTarget));
        }
    };

    const setControlsDisabled = (disabled) => {
        back.disabled = disabled || index === 0;
        next.disabled = disabled;
    };

    const updateContent = (resolved, step) => {
        index = resolved;
        layer.querySelector('.onboarding-step-count').textContent = `Step ${index + 1} of ${definition.steps.length}`;
        layer.querySelector('#onboarding-step-title').textContent = step.title;
        layer.querySelector('.onboarding-step-copy').textContent = step.copy;
        back.disabled = index === 0;
        next.textContent = index === definition.steps.length - 1 ? 'Finish' : 'Next';
    };

    const fadeStepOut = async () => {
        await Promise.all([
            animateElement(popover, [
                { opacity: 1, transform: 'translateY(0) scale(1)' },
                { opacity: 0, transform: 'translateY(12px) scale(.97)' },
            ], { duration: 260, easing: 'ease-in' }),
            animateElement(ring, [
                { opacity: 1, transform: 'scale(1)' },
                { opacity: 0, transform: 'scale(.96)' },
            ], { duration: 240, easing: 'ease-in' }),
            animateElement(stepCurtain, [
                { opacity: 0 },
                { opacity: 0.46 },
            ], { duration: 280, easing: 'ease-in-out' }),
        ]);
    };

    const fadeStepIn = async () => {
        await Promise.all([
            animateElement(popover, [
                { opacity: 0, transform: 'translateY(12px) scale(.97)' },
                { opacity: 1, transform: 'translateY(0) scale(1)' },
            ], { duration: 340 }),
            animateElement(ring, [
                { opacity: 0, transform: 'scale(.96)' },
                { opacity: 1, transform: 'scale(1)' },
            ], { duration: 340 }),
            animateElement(stepCurtain, [
                { opacity: 0.46 },
                { opacity: 0 },
            ], { duration: 380, easing: 'ease-out' }),
        ]);
    };

    const showStep = async (requestedIndex, initial = false) => {
        if (moving || closing) return;
        const resolved = Math.max(0, Math.min(requestedIndex, definition.steps.length - 1));
        const step = definition.steps[resolved];
        saveProgress(key, version, resolved);

        if (normalizedPath() !== normalizedPath(step.path)) {
            moving = true;
            setControlsDisabled(true);
            await fadeStepOut();
            closePreparedDropdown(preparedDropdown);
            preparedDropdown = null;
            await fadeAndNavigate(normalizedPath(step.path));
            return;
        }

        moving = true;
        setControlsDisabled(true);
        if (!initial) await fadeStepOut();

        closePreparedDropdown(preparedDropdown);
        preparedDropdown = await prepareStep(step);
        updateContent(resolved, step);
        activeTarget = resolveTarget(step);

        if (!activeTarget) {
            moving = false;
            return;
        }

        await scrollTargetIntoView(activeTarget);
        positionTour(layer, activeTarget);

        if (initial) {
            popover.style.opacity = '1';
            ring.style.opacity = '1';
            stepCurtain.style.opacity = '0';
            await animateElement(layer, [{ opacity: 0 }, { opacity: 1 }], { duration: 360, easing: 'ease-out' });
        } else {
            await fadeStepIn();
        }

        moving = false;
        setControlsDisabled(false);
        next.focus({ preventScroll: true });
    };

    const closeTour = async (status = null) => {
        if (closing) return;
        closing = true;
        moving = true;
        setControlsDisabled(true);
        closePreparedDropdown(preparedDropdown);
        preparedDropdown = null;
        clearProgress(key);
        if (status) persist(status);

        await Promise.all([
            animateElement(layer, [{ opacity: 1 }, { opacity: 0 }], { duration: 300, easing: 'ease-in' }),
            animateElement(stepCurtain, [{ opacity: 0 }, { opacity: 0.5 }], { duration: 260, easing: 'ease-in' }),
        ]);

        layer.remove();
        document.body.classList.remove('onboarding-tour-active');
        window.removeEventListener('resize', refreshPosition);
        window.removeEventListener('scroll', refreshPosition);
        document.removeEventListener('keydown', escapeHandler, true);
    };

    back.addEventListener('click', () => {
        if (!moving) void showStep(index - 1);
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

    let status;
    try {
        const response = await fetch(definition.statusUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (!response.ok) return;
        status = await response.json();
    } catch {
        return;
    }

    const version = Number(status.version || 1);
    helpButton.addEventListener('click', () => {
        void beginTour(key, definition, version, 0);
    });

    const progress = readProgress(key, version);
    if (progress) {
        await beginTour(key, definition, version, Number(progress.index || 0));
        return;
    }

    if (status.seen || !definition.autoPromptPath.test(window.location.pathname)) return;

    const welcome = buildWelcome(definition);
    const start = welcome.querySelector('[data-onboarding-welcome-start]');
    const skip = welcome.querySelector('[data-onboarding-welcome-skip]');

    const persistSkip = () => fetch(definition.statusUrl, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ version, status: 'skipped' }),
    }).catch(() => undefined);

    start.addEventListener('click', async () => {
        await dismissWelcome(welcome);
        await beginTour(key, definition, version, 0);
    });

    skip.addEventListener('click', async () => {
        clearProgress(key);
        persistSkip();
        await dismissWelcome(welcome);
    });

    void revealWelcome(welcome, start);
}

async function initOnboardingTours() {
    let incomingCurtain = null;
    if (consumeRouteFade()) {
        incomingCurtain = createRouteCurtain('1');
    }

    await Promise.all(
        Object.entries(tourDefinitions).map(([key, definition]) => initTour(key, definition))
    );

    if (incomingCurtain) {
        await wait(80);
        await animateElement(incomingCurtain, [
            { opacity: 1 },
            { opacity: 0 },
        ], { duration: 500, easing: 'ease-in-out' });
        incomingCurtain.remove();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        void initOnboardingTours();
    }, { once: true });
} else {
    void initOnboardingTours();
}
