import '../css/onboarding-tour-driver.css';

const DRIVER_VERSION = '1.8.0';
const DRIVER_SCRIPT = `https://cdn.jsdelivr.net/npm/driver.js@${DRIVER_VERSION}/dist/driver.js.iife.js`;
const DRIVER_STYLES = `https://cdn.jsdelivr.net/npm/driver.js@${DRIVER_VERSION}/dist/driver.css`;

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
                side: 'bottom',
            },
            {
                path: '/landowner/dashboard',
                selectors: ['.lo-dashboard-grid', '.lo-content'],
                title: 'Activity at a Glance',
                copy: 'These panels help you quickly review recent application status, linked parcel references, and current application activity.',
                nextLabel: 'Open Parcel Records',
                side: 'top',
            },
            {
                path: '/landowner/parcels',
                selectors: ['.lo-parcel-panel', '.lo-parcel-page', '.lo-content'],
                title: 'My Parcel Records',
                copy: 'Here you can review parcel and landholding references connected to your account, including location, area, record state, and map availability.',
                nextLabel: 'Open Parcel Map',
                side: 'top',
            },
            {
                path: '/landowner/parcel-map',
                selectors: ['.lo-map-sidebar', '.lo-content'],
                title: 'Map Tools',
                copy: 'Use these controls to find and review mapped parcel references associated with your records.',
                side: 'right',
            },
            {
                path: '/landowner/parcel-map',
                selectors: ['#parcel-map', '.lo-map-panel', '.lo-content'],
                title: 'My Parcel Map',
                copy: 'The map displays available parcel geometry and location references linked to your account. Map information supports review and monitoring only.',
                nextLabel: 'Open Applications',
                side: 'top',
            },
            {
                path: '/landowner/applications',
                selectors: ['.lo-app-card', '.lo-app-empty', '.lo-app-overview', '.lo-content'],
                title: 'My Clearance Applications',
                copy: 'Monitor applications linked to your landowner record, see their current status, and open the decision output when a finalized clearance result is available.',
                side: 'top',
            },
            {
                path: '/landowner/applications',
                selectors: ['.notification-bell-link'],
                title: 'Notifications',
                copy: 'Important application updates and other relevant notices appear here. Click the notification bell to open the panel and continue the tour.',
                interaction: 'notification-bell',
                side: 'bottom',
            },
            {
                path: '/landowner/applications',
                selectors: ['.notification-dropdown-panel'],
                title: 'Recent Notifications',
                copy: 'This panel shows your latest system notifications so you can review important updates without leaving the page.',
                prepare: 'open-notifications',
                side: 'bottom',
            },
            {
                path: '/landowner/applications',
                selectors: ['[data-onboarding-help="landowner_portal"]'],
                title: 'Replay This Tour Anytime',
                copy: 'Use this information button whenever you want to run the Landowner Portal guide again.',
                side: 'bottom',
            },
        ],
    },
};

let driverLoadPromise = null;

function normalizedPath(path = window.location.pathname) {
    const clean = path.replace(/\/+$/, '');
    return clean || '/';
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
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
        // The tour still works on the current page if session storage is unavailable.
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

function loadDriverLibrary() {
    if (window.driver?.js?.driver) return Promise.resolve(window.driver.js.driver);
    if (driverLoadPromise) return driverLoadPromise;

    driverLoadPromise = new Promise((resolve, reject) => {
        let stylesheet = document.querySelector(`link[data-driverjs-version="${DRIVER_VERSION}"]`);
        if (!stylesheet) {
            stylesheet = document.createElement('link');
            stylesheet.rel = 'stylesheet';
            stylesheet.href = DRIVER_STYLES;
            stylesheet.dataset.driverjsVersion = DRIVER_VERSION;
            document.head.appendChild(stylesheet);
        }

        const existing = document.querySelector(`script[data-driverjs-version="${DRIVER_VERSION}"]`);
        if (existing) {
            if (window.driver?.js?.driver) {
                resolve(window.driver.js.driver);
                return;
            }
            existing.addEventListener('load', () => resolve(window.driver?.js?.driver), { once: true });
            existing.addEventListener('error', reject, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = DRIVER_SCRIPT;
        script.dataset.driverjsVersion = DRIVER_VERSION;
        script.onload = () => {
            const factory = window.driver?.js?.driver;
            if (factory) resolve(factory);
            else reject(new Error('Driver.js loaded without exposing its driver factory.'));
        };
        script.onerror = () => reject(new Error('Unable to load Driver.js.'));
        document.head.appendChild(script);
    });

    return driverLoadPromise;
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

function dismissWelcome(layer, callback = null) {
    layer.classList.remove('is-visible');
    window.setTimeout(() => {
        layer.remove();
        callback?.();
    }, 220);
}

function resolveTarget(step) {
    for (const selector of step.selectors || []) {
        const element = document.querySelector(selector);
        if (element) return element;
    }
    return document.querySelector('.lo-content');
}

function suppressNotificationRead(dropdown) {
    if (!dropdown) return;
    dropdown.dataset.readTriggered = 'true';
    window.setTimeout(() => delete dropdown.dataset.readTriggered, 800);
}

function closeNotificationsQuietly() {
    document.querySelectorAll('[data-notification-dropdown][open]').forEach((dropdown) => {
        suppressNotificationRead(dropdown);
        dropdown.removeAttribute('data-onboarding-forced-open');
        dropdown.removeAttribute('open');
    });
}

function prepareStep(step) {
    if (step.prepare !== 'open-notifications') return;
    const dropdown = document.querySelector('[data-notification-dropdown]');
    if (!dropdown) return;
    dropdown.dataset.onboardingForcedOpen = 'true';
    dropdown.setAttribute('open', '');
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

async function startTour(key, definition, version, initialIndex = 0) {
    const steps = definition.steps;
    let index = Math.max(0, Math.min(Number(initialIndex) || 0, steps.length - 1));
    let driverObj = null;
    let bellCleanup = null;
    let closing = false;

    const finish = (status) => {
        if (closing) return;
        closing = true;
        bellCleanup?.();
        bellCleanup = null;
        closeNotificationsQuietly();
        clearProgress(key);
        if (status) persistStatus(definition, version, status);
        driverObj?.destroy();
    };

    const goTo = async (requestedIndex) => {
        if (closing) return;
        const nextIndex = Math.max(0, Math.min(Number(requestedIndex), steps.length - 1));
        const nextStep = steps[nextIndex];
        saveProgress(key, version, nextIndex);

        if (normalizedPath() !== normalizedPath(nextStep.path)) {
            bellCleanup?.();
            bellCleanup = null;
            closeNotificationsQuietly();
            driverObj?.destroy();
            window.location.assign(nextStep.path);
            return;
        }

        bellCleanup?.();
        bellCleanup = null;

        if (nextStep.interaction === 'notification-bell') {
            closeNotificationsQuietly();
        } else if (nextStep.prepare === 'open-notifications') {
            prepareStep(nextStep);
        } else {
            closeNotificationsQuietly();
        }

        index = nextIndex;
        const target = resolveTarget(nextStep);
        if (!target) {
            finish(null);
            return;
        }

        const isFirst = index === 0;
        const isLast = index === steps.length - 1;
        const isInteractiveBell = nextStep.interaction === 'notification-bell';
        const showButtons = [];
        if (!isFirst) showButtons.push('previous');
        if (!isInteractiveBell) showButtons.push('next');

        const popover = {
            title: nextStep.title,
            description: nextStep.copy,
            side: nextStep.side || 'bottom',
            align: 'start',
            popoverClass: 'dar-tour-popover',
            showButtons,
            nextBtnText: isLast ? 'Finish' : (nextStep.nextLabel || 'Next'),
            prevBtnText: 'Back',
            onNextClick: () => {
                if (isLast) finish('completed');
                else goTo(index + 1);
            },
            onPrevClick: () => goTo(index - 1),
            onPopoverRender: (popoverDom) => {
                popoverDom.progress.textContent = `Step ${index + 1} of ${steps.length}`;
                popoverDom.progress.classList.add('dar-tour-progress');

                const skip = createElement('button', 'dar-tour-skip', {
                    type: 'button',
                    text: 'Skip Tour',
                });
                skip.addEventListener('click', () => finish('skipped'));
                popoverDom.footer.insertBefore(skip, popoverDom.footerButtons);
            },
        };

        driverObj.highlight({
            element: target,
            disableActiveInteraction: !isInteractiveBell,
            popover,
        });

        if (isInteractiveBell) {
            const bell = target;
            const handleBellClick = () => {
                window.setTimeout(() => {
                    const dropdown = document.querySelector('[data-notification-dropdown]');
                    if (dropdown && !dropdown.open) dropdown.setAttribute('open', '');
                    goTo(index + 1);
                }, 80);
            };
            bell.addEventListener('click', handleBellClick, { once: true });
            bellCleanup = () => bell.removeEventListener('click', handleBellClick);
        }
    };

    const factory = await loadDriverLibrary().catch(() => null);
    if (!factory) return;

    driverObj = factory({
        animate: true,
        duration: 400,
        smoothScroll: true,
        overlayColor: '#0f172a',
        overlayOpacity: 0.50,
        stagePadding: 8,
        stageRadius: 12,
        popoverOffset: 12,
        allowClose: false,
        allowScroll: true,
        allowKeyboardControl: false,
    });

    saveProgress(key, version, index);
    const initialStep = steps[index];
    if (normalizedPath() !== normalizedPath(initialStep.path)) {
        window.location.assign(initialStep.path);
        return;
    }

    goTo(index);
}

async function initTour(key, definition) {
    if (!document.querySelector(definition.roleRoot)) return;

    const helpButton = mountHelpButton(key, definition);
    if (!helpButton) return;

    let status;
    try {
        const response = await fetch(definition.statusUrl, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (!response.ok) return;
        status = await response.json();
    } catch {
        return;
    }

    const version = Number(status.version || 1);
    helpButton.addEventListener('click', () => startTour(key, definition, version, 0));

    const progress = readProgress(key, version);
    if (progress) {
        startTour(key, definition, version, Number(progress.index || 0));
        return;
    }

    if (status.seen || !definition.autoPromptPath.test(window.location.pathname)) return;

    const welcome = buildWelcome(definition);
    const start = welcome.querySelector('[data-onboarding-welcome-start]');
    const skip = welcome.querySelector('[data-onboarding-welcome-skip]');

    start.addEventListener('click', () => {
        dismissWelcome(welcome, () => startTour(key, definition, version, 0));
    });

    skip.addEventListener('click', () => {
        clearProgress(key);
        persistStatus(definition, version, 'skipped');
        dismissWelcome(welcome);
    });

    start.focus();
}

function initOnboardingTours() {
    Object.entries(tours).forEach(([key, definition]) => initTour(key, definition));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOnboardingTours, { once: true });
} else {
    initOnboardingTours();
}
