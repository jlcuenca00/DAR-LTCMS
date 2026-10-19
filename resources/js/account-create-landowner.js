function initAccountLandownerCreation() {
    const dialog = document.querySelector('[data-create-landowner-dialog]');
    const opener = document.querySelector('[data-create-landowner-open]');
    const select = document.querySelector('#field-default-landowner_id');
    if (!dialog || !opener || !select) return;
    const form = dialog.querySelector('form');
    const errors = dialog.querySelector('[data-create-landowner-errors]');
    const status = document.querySelector('[data-create-landowner-status]');
    const ownerStatus = form.elements.registered_owner_status;
    const spouse = form.elements.spouse_name;
    let saving = false;
    opener.addEventListener('click', () => dialog.showModal());
    dialog.querySelector('[data-create-landowner-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('cancel', (event) => { if (saving) event.preventDefault(); });
    dialog.addEventListener('close', () => opener.focus());
    ownerStatus.addEventListener('change', () => {
        const married = ownerStatus.value === 'married';
        spouse.closest('[data-inline-spouse]').hidden = !married;
        spouse.disabled = !married;
        spouse.required = married;
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (saving || !form.reportValidity()) return;
        saving = true;
        errors.hidden = true;
        errors.replaceChildren();
        form.querySelectorAll('[aria-invalid]').forEach(field => {
            field.removeAttribute('aria-invalid');
            field.removeAttribute('aria-describedby');
        });
        const buttons = [...form.querySelectorAll('button')];
        buttons.forEach(button => { button.disabled = true; });
        form.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json();
            if (!response.ok) {
                const messages = payload.errors ? Object.values(payload.errors).flat() : [payload.message || 'Unable to create the record.'];
                errors.textContent = messages.join(' ');
                errors.id = 'inline-landowner-errors';
                Object.keys(payload.errors || {}).forEach(name => {
                    const field = form.elements.namedItem(name);
                    field?.setAttribute('aria-invalid', 'true');
                    field?.setAttribute('aria-describedby', errors.id);
                });
                errors.hidden = false;
                form.querySelector('[aria-invalid]')?.focus();
                return;
            }
            if (!Number.isSafeInteger(payload.id) || payload.id < 1 || typeof payload.text !== 'string') {
                throw new Error('Invalid record response');
            }
            select.add(new Option(payload.text, String(payload.id), true, true));
            select.value = String(payload.id);
            select.dispatchEvent(new Event('change', { bubbles: true }));
            status.hidden = false;
            status.textContent = 'Landowner record created and selected. Save Changes to link it to this account.';
            form.reset();
            ownerStatus.dispatchEvent(new Event('change'));
            dialog.close();
        } catch {
            errors.textContent = 'The result could not be confirmed. Check the existing-record search before trying again. If your session expired, sign in again.';
            errors.hidden = false;
        } finally {
            saving = false;
            form.removeAttribute('aria-busy');
            buttons.forEach(button => { button.disabled = false; });
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAccountLandownerCreation, { once: true });
} else {
    initAccountLandownerCreation();
}
