function initResponsiveGeodeticNavigation() {
    const sidebar = document.querySelector('.geo-sidebar');
    const brand = sidebar?.querySelector('.geo-brand');
    const navigationSection = sidebar?.querySelector('.geo-side-section');

    if (!sidebar || !brand || !navigationSection || brand.querySelector('.geo-mobile-nav-toggle')) {
        return;
    }

    const navigationId = navigationSection.id || 'geodetic-mobile-navigation';
    navigationSection.id = navigationId;

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'geo-mobile-nav-toggle';
    toggle.setAttribute('aria-controls', navigationId);
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open Geodetic navigation');
    toggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
    brand.appendChild(toggle);

    const setOpen = (open) => {
        sidebar.classList.toggle('is-mobile-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close Geodetic navigation' : 'Open Geodetic navigation');
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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initResponsiveGeodeticNavigation, { once: true });
} else {
    initResponsiveGeodeticNavigation();
}
