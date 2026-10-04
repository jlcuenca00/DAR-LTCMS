function initRemoteRecordSelect(container) {
    if (!(container instanceof HTMLElement) || container.dataset.remoteRecordSelectReady === 'true') {
        return;
    }

    const searchInput = container.querySelector('[data-remote-record-search]');
    const select = container.querySelector('[data-remote-record-control]');
    const status = container.querySelector('[data-remote-record-status]');
    const lookupUrl = container.dataset.lookupUrl;

    if (!searchInput || !select || !lookupUrl) {
        return;
    }

    container.dataset.remoteRecordSelectReady = 'true';
    if (!searchInput.hasAttribute('aria-label') && !searchInput.hasAttribute('aria-labelledby') && !searchInput.labels?.length) {
        const label = select.labels?.[0] || container.parentElement?.querySelector('label');
        searchInput.setAttribute('aria-label', `Search ${label?.textContent.trim() || 'records'}`);
    }
    if (status) {
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
    }

    const placeholder = select.dataset.placeholder
        || select.querySelector('option[value=""]')?.textContent
        || 'Select record';

    let debounceTimer = null;
    let activeRequest = null;
    let hasLoaded = false;

    const setStatus = (message) => {
        if (status) status.textContent = message;
    };

    const buildOption = (result) => {
        const option = document.createElement('option');
        option.value = String(result.id);
        option.textContent = result.text || ('Record #' + result.id);

        Object.entries(result.meta || {}).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                option.dataset[key] = String(value);
            }
        });

        return option;
    };

    const renderResults = (results) => {
        const currentValue = select.value;
        const currentOption = currentValue
            ? Array.from(select.options).find((option) => option.value === currentValue)?.cloneNode(true)
            : null;

        select.replaceChildren();

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = placeholder;
        select.appendChild(emptyOption);

        if (currentOption) {
            select.appendChild(currentOption);
        }

        results.forEach((result) => {
            const value = String(result.id);

            if (value === currentValue) {
                if (currentOption) {
                    currentOption.textContent = result.text || currentOption.textContent;
                    Object.entries(result.meta || {}).forEach(([key, metaValue]) => {
                        if (metaValue !== null && metaValue !== undefined && metaValue !== '') {
                            currentOption.dataset[key] = String(metaValue);
                        }
                    });
                }

                return;
            }

            select.appendChild(buildOption(result));
        });

        if (currentValue) {
            select.value = currentValue;
        }

        setStatus(
            results.length > 0
                ? `Showing up to ${results.length} matching record${results.length === 1 ? '' : 's'}.`
                : 'No matching records found.'
        );
    };

    const load = async () => {
        const query = searchInput.value.trim();

        if (activeRequest) {
            activeRequest.abort();
        }

        activeRequest = new AbortController();
        setStatus('Searching records…');

        try {
            const url = new URL(lookupUrl, window.location.origin);

            if (query !== '') {
                url.searchParams.set('q', query);
            } else {
                url.searchParams.delete('q');
            }

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeRequest.signal,
            });

            if (!response.ok) {
                throw new Error('Lookup request failed.');
            }

            const payload = await response.json();
            renderResults(Array.isArray(payload.results) ? payload.results : []);
            hasLoaded = true;
        } catch (error) {
            if (error.name !== 'AbortError') {
                setStatus('Unable to load records. Try searching again.');
            }
        }
    };

    searchInput.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(load, 250);
    });

    searchInput.addEventListener('focus', () => {
        if (!hasLoaded) {
            load();
        }
    });
}

function initRemoteRecordSelects(root = document) {
    if (root instanceof HTMLElement && root.matches('[data-remote-record-select]')) {
        initRemoteRecordSelect(root);
    }

    root.querySelectorAll?.('[data-remote-record-select]').forEach(initRemoteRecordSelect);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initRemoteRecordSelects(), { once: true });
} else {
    initRemoteRecordSelects();
}

const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node instanceof HTMLElement) {
                initRemoteRecordSelects(node);
            }
        });
    });
});

observer.observe(document.documentElement, {
    childList: true,
    subtree: true,
});
