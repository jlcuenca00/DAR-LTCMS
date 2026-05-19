@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const prefix = 'dar_ltcms_form_draft:';
            const ignoredTypes = new Set(['file', 'password', 'submit', 'button', 'reset', 'hidden']);
            const debounceTimers = new WeakMap();

            function serializableFields(form) {
                return Array.from(form.querySelectorAll('input[name], select[name], textarea[name]')).filter(function (field) {
                    if (field.disabled) return false;
                    if (ignoredTypes.has((field.type || '').toLowerCase())) return false;
                    if (field.name === '_token' || field.name === '_method') return false;
                    return true;
                });
            }

            function readForm(form) {
                const data = {};

                serializableFields(form).forEach(function (field) {
                    if (field.type === 'checkbox') {
                        data[field.name] = field.checked;
                    } else if (field.type === 'radio') {
                        if (field.checked) data[field.name] = field.value;
                    } else if (field.tagName === 'SELECT' && field.multiple) {
                        data[field.name] = Array.from(field.selectedOptions).map(function (option) { return option.value; });
                    } else {
                        data[field.name] = field.value;
                    }
                });

                return {
                    savedAt: new Date().toISOString(),
                    path: window.location.pathname,
                    data: data
                };
            }

            function restoreForm(form, draft) {
                if (!draft || !draft.data) return false;

                serializableFields(form).forEach(function (field) {
                    if (!Object.prototype.hasOwnProperty.call(draft.data, field.name)) return;

                    const value = draft.data[field.name];

                    if (field.type === 'checkbox') {
                        field.checked = Boolean(value);
                    } else if (field.type === 'radio') {
                        field.checked = field.value === value;
                    } else if (field.tagName === 'SELECT' && field.multiple && Array.isArray(value)) {
                        Array.from(field.options).forEach(function (option) {
                            option.selected = value.includes(option.value);
                        });
                    } else {
                        field.value = value ?? '';
                    }

                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                });

                return true;
            }

            function saveForm(form) {
                const key = prefix + form.dataset.autosaveKey;

                try {
                    localStorage.setItem(key, JSON.stringify(readForm(form)));
                } catch (error) {
                    // Browser storage may be unavailable. Silently ignore.
                }
            }

            document.querySelectorAll('form[data-autosave-key]').forEach(function (form) {
                const key = prefix + form.dataset.autosaveKey;

                try {
                    const raw = localStorage.getItem(key);
                    if (raw) restoreForm(form, JSON.parse(raw));
                } catch (error) {
                    // Ignore corrupted draft data.
                }

                form.addEventListener('input', function () {
                    clearTimeout(debounceTimers.get(form));
                    debounceTimers.set(form, setTimeout(function () { saveForm(form); }, 450));
                });

                form.addEventListener('change', function () {
                    clearTimeout(debounceTimers.get(form));
                    debounceTimers.set(form, setTimeout(function () { saveForm(form); }, 150));
                });

                form.addEventListener('submit', function () {
                    localStorage.removeItem(key);
                });
            });
        });
    </script>
@endonce
