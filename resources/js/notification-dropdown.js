async function markDropdownAsRead(dropdown) {
    if (!dropdown || dropdown.dataset.readPending === 'true') return;
    const items = Array.from(dropdown.querySelectorAll('.notification-dropdown-item.is-unread[data-notification-id]'));
    const ids = items.map((item) => Number(item.dataset.notificationId)).filter(Number.isSafeInteger).slice(0, 5);
    const url = dropdown.dataset.readVisibleUrl;
    const token = dropdown.dataset.csrfToken;
    if (!ids.length || !url || !token) return;

    dropdown.dataset.readPending = 'true';
    const status = dropdown.querySelector('[data-notification-read-status]');
    try {
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ notification_ids: ids }),
        });
        if (!response.ok) throw new Error('Notification update failed');
        const result = await response.json();
        if (result.ok !== true || !Array.isArray(result.read_ids)
            || !Number.isSafeInteger(result.unread_count) || result.unread_count < 0) {
            throw new Error('Invalid notification response');
        }

        const readIds = new Set(result.read_ids.map(Number));
        items.forEach((item) => {
            if (readIds.has(Number(item.dataset.notificationId))) item.classList.remove('is-unread');
        });
        dropdown.querySelectorAll('.notification-badge').forEach((badge) => {
            if (result.unread_count === 0) badge.remove();
            else badge.textContent = result.unread_count > 99 ? '99+' : String(result.unread_count);
        });
        dropdown.querySelectorAll('.notification-dropdown-count').forEach((count) => {
            count.textContent = result.unread_count === 0 ? 'All caught up'
                : (result.unread_count > 99 ? '99+' : result.unread_count) + ' unread';
            count.classList.toggle('is-clear', result.unread_count === 0);
        });
        if (status) {
            status.textContent = '';
            status.hidden = true;
        }
    } catch {
        if (status) {
            status.textContent = 'Could not mark notifications as read. Open and close this panel to retry.';
            status.hidden = false;
        }
    } finally {
        dropdown.dataset.readPending = 'false';
    }
}

function initializeNotificationDropdowns() {
    document.querySelectorAll('[data-notification-dropdown]').forEach((dropdown) => {
        if (dropdown.dataset.notificationInitialized === 'true') return;
        dropdown.dataset.notificationInitialized = 'true';
        let wasOpened = dropdown.open;
        dropdown.addEventListener('toggle', () => {
            if (dropdown.open) {
                wasOpened = true;
            } else if (wasOpened) {
                wasOpened = false;
                markDropdownAsRead(dropdown);
            }
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.notification-dropdown[open]').forEach((dropdown) => {
            if (!dropdown.contains(event.target)) dropdown.removeAttribute('open');
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.notification-dropdown[open]').forEach((dropdown) => {
            dropdown.removeAttribute('open');
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeNotificationDropdowns, { once: true });
} else {
    initializeNotificationDropdowns();
}
