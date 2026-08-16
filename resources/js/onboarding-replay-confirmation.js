function createElement(tag, className, attributes = {}) {
    const element = document.createElement(tag);
    if (className) element.className = className;

    Object.entries(attributes).forEach(([name, value]) => {
        if (name === 'text') element.textContent = value;
        else element.setAttribute(name, value);
    });

    return element;
}

function dismissConfirmation(layer) {
    if (!layer) return;
    layer.classList.remove('is-visible');
    window.setTimeout(() => layer.remove(), 180);
}

function openReplayConfirmation(helpButton) {
    if (document.querySelector('[data-onboarding-replay-confirmation]')) return;

    const layer = createElement('div', 'onboarding-welcome-layer', {
        'data-onboarding-replay-confirmation': '',
    });

    layer.innerHTML = `
        <section class="onboarding-welcome-card" role="dialog" aria-modal="true" aria-labelledby="onboarding-replay-title">
            <div class="onboarding-welcome-icon" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></div>
            <h2 id="onboarding-replay-title">Start Portal Tour?</h2>
            <p class="onboarding-welcome-copy">This will restart the guided tour from the beginning. You can skip the tour at any time.</p>
            <div class="onboarding-welcome-actions">
                <button type="button" class="onboarding-button onboarding-button-secondary" data-onboarding-replay-cancel>Cancel</button>
                <button type="button" class="onboarding-button onboarding-button-primary" data-onboarding-replay-start>Start Tour</button>
            </div>
        </section>
    `;

    document.body.appendChild(layer);
    requestAnimationFrame(() => requestAnimationFrame(() => layer.classList.add('is-visible')));

    const cancel = layer.querySelector('[data-onboarding-replay-cancel]');
    const start = layer.querySelector('[data-onboarding-replay-start]');

    const close = () => dismissConfirmation(layer);

    cancel.addEventListener('click', close);
    layer.addEventListener('click', (event) => {
        if (event.target === layer) close();
    });

    const escapeHandler = (event) => {
        if (event.key !== 'Escape') return;
        document.removeEventListener('keydown', escapeHandler, true);
        close();
    };
    document.addEventListener('keydown', escapeHandler, true);

    start.addEventListener('click', () => {
        document.removeEventListener('keydown', escapeHandler, true);
        dismissConfirmation(layer);
        helpButton.dataset.onboardingReplayConfirmed = 'true';
        window.setTimeout(() => helpButton.click(), 180);
    });

    start.focus();
}

document.addEventListener('click', (event) => {
    const helpButton = event.target.closest?.('[data-onboarding-help]');
    if (!helpButton) return;

    if (helpButton.dataset.onboardingReplayConfirmed === 'true') {
        delete helpButton.dataset.onboardingReplayConfirmed;
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    openReplayConfirmation(helpButton);
}, true);
