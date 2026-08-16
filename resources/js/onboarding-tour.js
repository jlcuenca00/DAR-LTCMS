import '../css/onboarding-tour.css';

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

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function normalizedPath(path = window.location.pathname) {
    const normalized = path.replace(/\/+$/, '');
    return normalized || '/';
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
        // The tour still works on the current page if session storage is unavailable.
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
        if (key === 'text') {
            element.textContent = value;
        } else {
            element.setAttribute(key, value);
        }
    });

    return element;
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
    layer.hidden = true;
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

function buildTourLayer() {
    const layer = createElement('div', 'onboarding-tour-layer');
    layer.hidden = true;
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
    `;
    document.body.appendChild(layer);
    return layer;
}

function resolveTarget(step) {
    const selectors = step.selectors || (step.selector ? [step.selector] : []);

    for (const selector of selectors) {
        const target = document.querySelector(selector);
        if (target) return target;
    }

    return document.querySelector('.lo-content');
}

function prepareStep(step) {
    document.querySelectorAll('.notification-dropdown[open]').forEach((dropdown) => {
        if (step.prepare !== 'open-notifications') dropdown.removeAttribute('open');
    });

    if (step.prepare === 'open-notifications') {
        const dropdown = document.querySelector('[data-notification-dropdown]');
        if (dropdown) dropdown.setAttribute('open', '');
    }
}

function beginTour(key, definition, version, index = 0) {
    const step = definition.steps[index] || definition.steps[0];
    const targetPath = normalizedPath(step.path || window.location.pathname);

    saveProgress(key, version, index);

    if (normalizedPath() !== targetPath) {
        window.location.assign(targetPath);
        return;
    }

    startConfiguredTour(key, definition, version, index);
}

function startConfiguredTour(key, definition, version, initialIndex = 0) {
    let index = Number(initialIndex) || 0;
    let activeTarget = null;
    let preparedNotificationDropdown = null;
    const layer = buildTourLayer();
    const popover = layer.querySelector('.onboarding-popover');
    const ring = layer.querySelector('.onboarding-focus-ring');
    const blocker = layer.querySelector('.onboarding-target-blocker');
    const shades = {
        top: layer.querySelector('.onboarding-shade-top'),
        left: layer.querySelector('.onboarding-shade-left'),
        right: layer.querySelector('.onboarding-shade-right'),
        bottom: layer.querySelector('.onboarding-shade-bottom'),
    };

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

    const cleanupPreparedState = () => {
        if (preparedNotificationDropdown) {
            preparedNotificationDropdown.removeAttribute('open');
            preparedNotificationDropdown = null;
        }
    };

    const close = (status = null) => {
        cleanupPreparedState();
        clearProgress(key);
        layer.remove();
        document.body.classList.remove('onboarding-tour-active');
        activeTarget = null;
        window.removeEventListener('resize', refreshPosition);
        window.removeEventListener('scroll', refreshPosition);
        if (status) persist(status);
    };

    const position = () => {
        if (!activeTarget || !document.body.contains(activeTarget)) return;

        const raw = activeTarget.getBoundingClientRect();
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
        blocker.style.cssText = `left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px`;

        const popRect = popover.getBoundingClientRect();
        const gap = 14;
        const margin = 14;
        let top = rect.bottom + gap;

        if (top + popRect.height > window.innerHeight - margin) {
            top = rect.top - popRect.height - gap;
        }
        top = Math.max(margin, Math.min(top, window.innerHeight - popRect.height - margin));

        let left = rect.left;
        left = Math.max(margin, Math.min(left, window.innerWidth - popRect.width - margin));
        popover.style.left = `${left}px`;
        popover.style.top = `${top}px`;
    };

    const showStep = (requestedIndex) => {
        const steps = definition.steps;
        const resolved = Math.max(0, Math.min(Number(requestedIndex), steps.length - 1));
        const step = steps[resolved];
        const targetPath = normalizedPath(step.path || window.location.pathname);

        saveProgress(key, version, resolved);

        if (normalizedPath() !== targetPath) {
            cleanupPreparedState();
            window.location.assign(targetPath);
            return;
        }

        cleanupPreparedState();
        prepareStep(step);
        if (step.prepare === 'open-notifications') {
            preparedNotificationDropdown = document.querySelector('[data-notification-dropdown]');
        }

        index = resolved;
        activeTarget = resolveTarget(step);

        if (!activeTarget) {
            close();
            return;
        }

        layer.querySelector('.onboarding-step-count').textContent = `Step ${index + 1} of ${steps.length}`;
        layer.querySelector('#onboarding-step-title').textContent = step.title;
        layer.querySelector('.onboarding-step-copy').textContent = step.copy;

        const back = layer.querySelector('[data-onboarding-back]');
        const next = layer.querySelector('[data-onboarding-next]');
        back.disabled = index === 0;
        next.textContent = index === steps.length - 1 ? 'Finish' : 'Next';

        activeTarget.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        window.setTimeout(() => {
            position();
            next.focus({ preventScroll: true });
        }, 260);
    };

    const moveToStep = (requestedIndex) => {
        if (requestedIndex < 0) return;
        if (requestedIndex >= definition.steps.length) {
            close('completed');
            return;
        }
        showStep(requestedIndex);
    };

    layer.querySelector('[data-onboarding-back]').addEventListener('click', () => moveToStep(index - 1));
    layer.querySelector('[data-onboarding-next]').addEventListener('click', () => {
        if (index >= definition.steps.length - 1) {
            close('completed');
            return;
        }
        moveToStep(index + 1);
    });
    layer.querySelector('[data-onboarding-skip]').addEventListener('click', () => close('skipped'));

    function refreshPosition() {
        if (!layer.hidden) window.requestAnimationFrame(position);
    }

    window.addEventListener('resize', refreshPosition, { passive: true });
    window.addEventListener('scroll', refreshPosition, { passive: true });

    document.addEventListener('keydown', function escapeHandler(event) {
        if (event.key !== 'Escape' || layer.hidden) return;
        close();
        document.removeEventListener('keydown', escapeHandler);
    });

    document.body.classList.add('onboarding-tour-active');
    layer.hidden = false;
    showStep(index);
}

async function initTour(key, definition) {
    if (!document.querySelector(definition.roleRoot)) return;

    const helpButton = mountHelpButton(key, definition);
    if (!helpButton) return;

    let status;
    try {
        const response = await fetch(definition.statusUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok) return;
        status = await response.json();
    } catch {
        return;
    }

    const version = Number(status.version || 1);
    helpButton.addEventListener('click', () => beginTour(key, definition, version, 0));

    const progress = readProgress(key, version);
    if (progress) {
        beginTour(key, definition, version, Number(progress.index || 0));
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

    start.addEventListener('click', () => {
        welcome.remove();
        beginTour(key, definition, version, 0);
    });

    skip.addEventListener('click', () => {
        clearProgress(key);
        persistSkip();
        welcome.remove();
    });

    welcome.hidden = false;
    window.setTimeout(() => start.focus(), 0);
}

function initOnboardingTours() {
    Object.entries(tourDefinitions).forEach(([key, definition]) => initTour(key, definition));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOnboardingTours, { once: true });
} else {
    initOnboardingTours();
}
