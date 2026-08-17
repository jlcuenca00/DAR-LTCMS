const DEVELOPMENT_NOTICE_STORAGE_KEY = 'darltcms-development-notice-dismissed';

function isDevelopmentNoticeDismissed() {
    try {
        return window.sessionStorage.getItem(DEVELOPMENT_NOTICE_STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

function rememberDevelopmentNoticeDismissal() {
    try {
        window.sessionStorage.setItem(DEVELOPMENT_NOTICE_STORAGE_KEY, '1');
    } catch {
        // The notice can still be dismissed for the current page when storage is unavailable.
    }
}

function buildDevelopmentNotice() {
    const notice = document.createElement('div');
    notice.className = 'system-development-notice';
    notice.setAttribute('role', 'status');
    notice.setAttribute('data-system-development-notice', '');

    notice.innerHTML = `
        <div class="system-development-message">
            <svg class="system-development-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"></circle>
                <path d="M12 7.5V13" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                <circle cx="12" cy="16.5" r="1" fill="currentColor"></circle>
            </svg>
            <p>This site is still undergoing development. Please bear with us.</p>
        </div>
        <button type="button"
                class="system-development-dismiss"
                data-system-development-dismiss
                aria-label="Dismiss development notice">&times;</button>
    `;

    return notice;
}

function initAuthenticatedDevelopmentNotice() {
    const workspace = [
        ['.staff-shell', '.staff-main'],
        ['.lo-shell', '.lo-main'],
        ['.geo-shell', '.geo-main'],
    ].find(([shellSelector]) => document.querySelector(shellSelector));

    if (!workspace || isDevelopmentNoticeDismissed()) {
        return;
    }

    const [, mainSelector] = workspace;
    const main = document.querySelector(mainSelector);

    if (!main || main.querySelector('[data-system-development-notice]')) {
        return;
    }

    const notice = buildDevelopmentNotice();
    main.insertBefore(notice, main.firstElementChild);

    notice.querySelector('[data-system-development-dismiss]')?.addEventListener('click', () => {
        notice.hidden = true;
        rememberDevelopmentNoticeDismissal();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuthenticatedDevelopmentNotice, { once: true });
} else {
    initAuthenticatedDevelopmentNotice();
}
