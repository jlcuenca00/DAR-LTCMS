function initLandownerResponsiveNavigation() {
    const sidebar = document.querySelector('.lo-sidebar');
    const brand = sidebar?.querySelector('.lo-brand');
    const navigationSection = sidebar?.querySelector('.lo-side-section');

    if (!sidebar || !brand || !navigationSection || brand.querySelector('.lo-mobile-nav-toggle')) {
        return;
    }

    const navigationId = navigationSection.id || 'landowner-mobile-navigation';
    navigationSection.id = navigationId;

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'lo-mobile-nav-toggle';
    toggle.setAttribute('aria-controls', navigationId);
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open Landowner navigation');
    toggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
    brand.appendChild(toggle);

    const setOpen = (open) => {
        sidebar.classList.toggle('is-mobile-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close Landowner navigation' : 'Open Landowner navigation');
        toggle.innerHTML = open
            ? '<i class="fa-solid fa-xmark" aria-hidden="true"></i>'
            : '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
    };

    toggle.addEventListener('click', () => {
        setOpen(!sidebar.classList.contains('is-mobile-open'));
    });

    navigationSection.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('click', (event) => {
        if (window.innerWidth > 1100 || !sidebar.classList.contains('is-mobile-open')) {
            return;
        }

        if (!sidebar.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !sidebar.classList.contains('is-mobile-open')) {
            return;
        }

        setOpen(false);
        toggle.focus();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 1100 && sidebar.classList.contains('is-mobile-open')) {
            setOpen(false);
        }
    });
}

function initResponsiveViewportGuards() {
    document.querySelectorAll('.notification-dropdown[open], .account-menu[open]').forEach((details) => {
        details.removeAttribute('open');
    });

    window.addEventListener('orientationchange', () => {
        document.querySelectorAll('.notification-dropdown[open], .account-menu[open]').forEach((details) => {
            details.removeAttribute('open');
        });
    });
}

function initSiteResponsiveUi() {
    initLandownerResponsiveNavigation();
    initResponsiveViewportGuards();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSiteResponsiveUi, { once: true });
} else {
    initSiteResponsiveUi();
}
