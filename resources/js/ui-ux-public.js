/* Public/auth-only UI context hooks. */

function initPublicUiUx() {
    const body = document.body;
    if (!body) return;

    if (document.querySelector('.login-page')) {
        body.classList.add('dar-login-page');
    }

    const landing = document.querySelector('.site-header + .hero, .sticky-shell + .hero');
    if (landing && document.querySelector('.site-header .brand')) {
        body.classList.add('dar-public-landing');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPublicUiUx, { once: true });
} else {
    initPublicUiUx();
}
