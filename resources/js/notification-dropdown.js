function markDropdownAsRead(dropdown) {
    if (!dropdown || dropdown.dataset.readTriggered === 'true') return;

    const hasUnread = dropdown.querySelector('.notification-badge')
        || dropdown.querySelector('.notification-dropdown-item.is-unread');

    if (!hasUnread) return;

    dropdown.dataset.readTriggered = 'true';

    dropdown.querySelectorAll('.notification-badge').forEach((badge) => {
        badge.remove();
    });

    dropdown.querySelectorAll('.notification-dropdown-count').forEach((count) => {
        count.textContent = 'All caught up';
        count.classList.add('is-clear');
    });

    dropdown.querySelectorAll('.notification-dropdown-item.is-unread').forEach((item) => {
        item.classList.remove('is-unread');
    });

    const url = dropdown.dataset.readAllUrl;
    const token = dropdown.dataset.csrfToken;

    if (!url || !token) return;

    fetch(url, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    }).catch(() => {
        // The next page load restores the actual unread state if this request fails.
    });
}

function initializeNotificationDropdowns() {
    document.querySelectorAll('[data-notification-dropdown]').forEach((dropdown) => {
        if (dropdown.dataset.notificationInitialized === 'true') return;

        dropdown.dataset.notificationInitialized = 'true';

        dropdown.addEventListener('toggle', () => {
            // Keep unread highlighting while the user reviews the panel.
            // Mark the visible notifications read after the panel is closed.
            if (!dropdown.open) {
                markDropdownAsRead(dropdown);
            }
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.notification-dropdown[open]').forEach((dropdown) => {
            if (!dropdown.contains(event.target)) {
                dropdown.removeAttribute('open');
            }
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
