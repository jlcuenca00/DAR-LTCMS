function initRegistrationWizard() {
    const root = document.querySelector('[data-registration-wizard]');
    if (!root) return;
    const form = root.querySelector('[data-signup-form]');
    const steps = [...form.querySelectorAll('[data-signup-step]')];
    const markers = [...root.querySelectorAll('[data-step-marker]')];
    const progress = root.querySelector('[data-signup-progress]');
    const next = form.querySelector('[data-signup-next]');
    const back = form.querySelector('[data-signup-back]');
    const submit = form.querySelector('[data-signup-submit]');
    const method = document.querySelector('[data-signup-method]');
    const review = form.querySelector('[data-signup-review]');
    let current = 0;
    let submitting = false;
    form.noValidate = true;
    const controls = step => [...step.querySelectorAll('input,select,textarea')];
    const validate = step => {
        const invalid = controls(step).find(input => !input.disabled && !input.checkValidity());
        if (invalid) { invalid.focus(); invalid.reportValidity(); }
        return !invalid;
    };
    const labels = { username: 'Username', email: 'Email address', name: 'Full legal name', contact_number: 'Contact number', address: 'Current address', date_of_birth: 'Date of birth', registered_owner_status: 'Civil / registered owner status', spouse_name: 'Spouse name' };
    function buildReview() {
        review.replaceChildren();
        ['Your details', 'Valid ID', 'Selfie'].forEach((title, index) => {
            const section = document.createElement('section');
            section.className = 'signup-review-section';
            const heading = document.createElement('h3'); heading.textContent = title;
            const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'signup-text-button'; edit.textContent = 'Edit'; edit.setAttribute('aria-label', `Edit ${title.toLowerCase()}`);
            edit.addEventListener('click', () => show(index));
            const header = document.createElement('div'); header.className = 'signup-review-heading'; header.append(heading, edit); section.append(header);
            if (index === 0) {
                const list = document.createElement('dl');
                Object.entries(labels).forEach(([name, label]) => {
                    const input = form.elements.namedItem(name);
                    if (!input || input.disabled) return;
                    const row = document.createElement('div');
                    const term = document.createElement('dt'); term.textContent = label;
                    const value = document.createElement('dd'); value.textContent = input.value ? (input.tagName === 'SELECT' ? input.selectedOptions[0].text : input.value) : 'Not provided';
                    row.append(term, value); list.append(row);
                });
                const google = form.querySelector('[data-google-email]');
                if (google) { const email = document.createElement('p'); email.textContent = `Google account: ${google.textContent}`; section.append(email); }
                section.append(list); // Passwords are deliberately excluded from the review.
            } else {
                if (index === 1) { const type = document.createElement('p'); type.textContent = form.elements.id_type.selectedOptions[0]?.text || ''; section.append(type); }
                const photos = document.createElement('div'); photos.className = 'signup-review-photos';
                (index === 1 ? ['id_front', 'id_back'] : ['selfie']).forEach(kind => {
                    const source = form.querySelector(`[data-evidence-preview="${kind}"]`);
                    if (!source?.getAttribute('src') || source.hidden) return;
                    const image = document.createElement('img'); image.src = source.src; image.alt = kind === 'selfie' ? 'Submitted selfie preview' : `${kind === 'id_front' ? 'Front' : 'Back'} of ID preview`; photos.append(image);
                });
                section.append(photos);
            }
            review.append(section);
        });
    }
    function show(index, focus = true) {
        current = index;
        steps.forEach((step, i) => { step.hidden = i !== current; });
        markers.forEach((marker, i) => {
            marker.dataset.state = i < current ? 'complete' : i === current ? 'current' : 'pending';
            if (i === current) marker.setAttribute('aria-current', 'step'); else marker.removeAttribute('aria-current');
            marker.querySelector('span').textContent = i < current ? '✓' : String(i + 1);
        });
        root.querySelector('[data-step-status]').textContent = `Step ${current + 1} of ${steps.length} · ${steps[current].dataset.stepTitle}`;
        back.hidden = !current && !method;
        back.textContent = current ? 'Back' : 'Change signup method';
        next.hidden = current === steps.length - 1;
        submit.hidden = current !== steps.length - 1;
        if (current === steps.length - 1) buildReview();
        form.dispatchEvent(new Event('signup:stepchange')); // Close a camera when leaving its step.
        if (focus) { steps[current].querySelector('h2').focus(); root.scrollIntoView({ block: 'start', behavior: 'instant' }); }
    }
    function begin(focus = true) {
        if (method) method.hidden = true;
        root.hidden = false; progress.hidden = false;
        show(0, focus);
    }
    next.addEventListener('click', () => { if (validate(steps[current])) show(current + 1); });
    back.addEventListener('click', () => {
        if (current) show(current - 1);
        else if (method) { root.hidden = true; method.hidden = false; method.querySelector('[data-signup-email]').focus(); }
    });
    form.addEventListener('input', event => {
        if (['password', 'password_confirmation'].includes(event.target.name)) {
            const password = form.elements.namedItem('password');
            const confirmation = form.elements.namedItem('password_confirmation');
            confirmation.setCustomValidity(confirmation.value && password.value !== confirmation.value ? 'Passwords must match.' : '');
        }
    });
    form.addEventListener('submit', event => {
        if (submitting) { event.preventDefault(); return; }
        if (current !== steps.length - 1) { event.preventDefault(); if (validate(steps[current])) show(current + 1); return; }
        const invalidStep = steps.findIndex(step => controls(step).some(input => !input.disabled && !input.checkValidity()));
        if (invalidStep !== -1) { event.preventDefault(); show(invalidStep); validate(steps[invalidStep]); return; }
        submitting = true; submit.disabled = true; submit.textContent = 'Submitting…'; next.disabled = true; back.disabled = true;
    });
    window.addEventListener('pageshow', () => { if (current === steps.length - 1) buildReview(); submitting = false; submit.disabled = false; submit.textContent = 'Submit for verification'; next.disabled = false; back.disabled = false; });
    const emailChoice = method?.querySelector('[data-signup-email]');
    if (emailChoice) { emailChoice.hidden = false; emailChoice.addEventListener('click', () => begin()); }
    if (method && root.dataset.hasErrors !== 'true') root.hidden = true;
    else {
        begin(false);
        if (root.dataset.hasErrors === 'true') {
            const firstError = steps.findIndex(step => step.querySelector('.text-red-600, .text-red-500'));
            show(firstError < 0 ? 0 : firstError, false);
        }
    }
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initRegistrationWizard, { once: true });
else initRegistrationWizard();
