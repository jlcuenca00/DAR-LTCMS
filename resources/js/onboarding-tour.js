import '../css/onboarding-tour.css';

const tourDefinitions = {
    landowner_portal: {
        roleRoot: '.lo-shell',
        statusUrl: '/onboarding-tours/landowner_portal',
        autoPromptPath: /\/landowner\/dashboard\/?$/,
        helpMount: '.lo-topbar-right',
        helpLabel: 'Portal Tour',
        welcomeTitle: 'Welcome to DAR-LTCMS',
        welcomeCopy: 'Take a quick tour of the Landowner Portal to learn where to view your parcels, application progress, notifications, and clearance results.',
        steps: [
            {
                selector: '.lo-nav a[href$="/landowner/dashboard"]',
                title: 'Your Dashboard',
                copy: 'This is your main portal overview for your own DAR-LTCMS records and clearance activity.',
            },
            {
                selector: '.lo-nav a[href$="/landowner/parcels"]',
                title: 'My Parcel Records',
                copy: 'View parcel records associated with your landowner account. Other landowners’ records are not shown here.',
            },
            {
                selector: '.lo-nav a[href$="/landowner/parcel-map"]',
                title: 'My Parcel Map',
                copy: 'Open the map to view available mapped geometry and location information for parcels associated with your records.',
            },
            {
                selector: '.lo-nav a[href$="/landowner/applications"]',
                title: 'My Applications',
                copy: 'Monitor the status and progress of clearance applications associated with your landowner record.',
            },
            {
                selector: '.notification-bell-link',
                title: 'Notifications',
                copy: 'Application updates and other relevant system notifications appear here.',
            },
            {
                selector: '[data-onboarding-help="landowner_portal"]',
                title: 'Replay This Tour Anytime',
                copy: 'Use this information button whenever you want to view the Landowner Portal guide again.',
            },
        ],
    },
};

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
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

function startConfiguredTour(key, definition, version) {
    let index = 0;
    let activeTarget = null;
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

    const close = (status = null) => {
        layer.remove();
        document.body.classList.remove('onboarding-tour-active');
        activeTarget = null;
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
        let resolved = requestedIndex;
        let target = null;

        while (resolved >= 0 && resolved < steps.length) {
            target = document.querySelector(steps[resolved].selector);
            if (target) break;
            resolved += requestedIndex >= index ? 1 : -1;
        }

        if (!target) {
            close('completed');
            return;
        }

        index = resolved;
        activeTarget = target;
        const step = steps[index];

        layer.querySelector('.onboarding-step-count').textContent = `Step ${index + 1} of ${steps.length}`;
        layer.querySelector('#onboarding-step-title').textContent = step.title;
        layer.querySelector('.onboarding-step-copy').textContent = step.copy;

        const back = layer.querySelector('[data-onboarding-back]');
        const next = layer.querySelector('[data-onboarding-next]');
        back.disabled = index === 0;
        next.textContent = index === steps.length - 1 ? 'Finish' : 'Next';

        target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        window.setTimeout(() => {
            position();
            next.focus({ preventScroll: true });
        }, 220);
    };

    layer.querySelector('[data-onboarding-back]').addEventListener('click', () => showStep(index - 1));
    layer.querySelector('[data-onboarding-next]').addEventListener('click', () => {
        if (index >= definition.steps.length - 1) {
            close('completed');
            return;
        }
        showStep(index + 1);
    });
    layer.querySelector('[data-onboarding-skip]').addEventListener('click', () => close('skipped'));

    const refreshPosition = () => {
        if (!layer.hidden) window.requestAnimationFrame(position);
    };
    window.addEventListener('resize', refreshPosition, { passive: true });
    window.addEventListener('scroll', refreshPosition, { passive: true });

    document.addEventListener('keydown', function escapeHandler(event) {
        if (event.key !== 'Escape' || layer.hidden) return;
        close();
        document.removeEventListener('keydown', escapeHandler);
    });

    document.body.classList.add('onboarding-tour-active');
    layer.hidden = false;
    showStep(0);
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
    helpButton.addEventListener('click', () => startConfiguredTour(key, definition, version));

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
        startConfiguredTour(key, definition, version);
    });

    skip.addEventListener('click', () => {
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
