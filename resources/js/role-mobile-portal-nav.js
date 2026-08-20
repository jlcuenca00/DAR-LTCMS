const roleMobilePortalQuery = window.matchMedia('(max-width: 1100px)');

const portalConfigs = [
    {
        shellSelector: '.lo-shell',
        sidebarSelector: '.lo-sidebar',
        brandSelector: '.lo-brand',
        brandMarkSelector: '.lo-brand-mark',
        topbarSelector: '.lo-topbar',
        actionsSelector: '.lo-topbar-right',
        linkSelector: '.lo-nav-link[href]',
        headerClass: 'lo-mobile-portal-header',
        navClass: 'lo-mobile-portal-nav',
        portalKey: 'landowner',
        ariaLabel: 'Landowner mobile navigation',
        dashboardLabel: 'Dashboard',
        dashboardFallback: '/landowner/dashboard',
        primaryItems: [
            { sourceLabel: 'Dashboard', displayLabel: 'Dashboard', icon: 'fa-house' },
            { sourceLabel: 'My Parcel Map', displayLabel: 'Parcel Map', icon: 'fa-map-location-dot' },
            { sourceLabel: 'My Parcel Records', displayLabel: 'Parcels', icon: 'fa-draw-polygon' },
            { sourceLabel: 'My Applications', displayLabel: 'Applications', icon: 'fa-file-lines' },
        ],
    },
    {
        shellSelector: '.geo-shell',
        sidebarSelector: '.geo-sidebar',
        brandSelector: '.geo-brand',
        brandMarkSelector: '.geo-brand-mark',
        topbarSelector: '.geo-topbar',
        actionsSelector: '.geo-topbar-right',
        linkSelector: '.geo-nav-link[href]',
        headerClass: 'geo-mobile-portal-header',
        navClass: 'geo-mobile-portal-nav',
        portalKey: 'geodetic',
        ariaLabel: 'Geodetic mobile navigation',
        dashboardLabel: 'Dashboard',
        dashboardFallback: '/geodetic/dashboard',
        primaryItems: [
            { sourceLabel: 'Dashboard', displayLabel: 'Dashboard', icon: 'fa-house' },
            { sourceLabel: 'Parcel Map', displayLabel: 'Parcel Map', icon: 'fa-map-location-dot' },
            { sourceLabel: 'Parcel References', displayLabel: 'Parcels', icon: 'fa-draw-polygon' },
        ],
    },
];

function normalizedLinkLabel(link) {
    if (!link) return '';
    return link.textContent.replace(/\s+/g, ' ').trim();
}

function initRoleMobilePortal(config) {
    const shell = document.querySelector(config.shellSelector);
    const sidebar = shell?.querySelector(config.sidebarSelector);
    const brand = sidebar?.querySelector(config.brandSelector);
    const desktopTopbar = shell?.querySelector(config.topbarSelector);
    const desktopActions = desktopTopbar?.querySelector(config.actionsSelector);

    if (!shell || !sidebar || !brand || !desktopTopbar || !desktopActions || shell.querySelector(`[data-role-mobile-portal="${config.portalKey}"]`)) {
        return;
    }

    const sidebarLinks = Array.from(sidebar.querySelectorAll(config.linkSelector));
    const findSidebarLink = (label) => sidebarLinks.find((link) => normalizedLinkLabel(link) === label) || null;
    const activeSidebarLink = sidebarLinks.find((link) => link.classList.contains('active')) || null;
    const activeLabel = normalizedLinkLabel(activeSidebarLink);
    const dashboardLink = findSidebarLink(config.dashboardLabel);

    const header = document.createElement('header');
    header.className = `role-mobile-portal-header ${config.headerClass}`;
    header.dataset.roleMobilePortal = config.portalKey;
    header.setAttribute('aria-label', config.ariaLabel);

    const topRow = document.createElement('div');
    topRow.className = 'role-mobile-portal-top';

    const brandLink = document.createElement('a');
    brandLink.className = 'role-mobile-portal-brand';
    brandLink.href = dashboardLink?.href || config.dashboardFallback;
    brandLink.setAttribute('aria-label', `Go to ${config.dashboardLabel}`);

    const brandMark = brand.querySelector(config.brandMarkSelector)?.cloneNode(true);
    if (brandMark) {
        brandMark.classList.add('role-mobile-portal-brand-mark');
        brandLink.appendChild(brandMark);
    }

    const brandCopy = document.createElement('span');
    brandCopy.className = 'role-mobile-portal-brand-copy';
    brandCopy.innerHTML = '<strong>DAR LTCMS</strong><small>Negros Oriental</small>';
    brandLink.appendChild(brandCopy);

    const controlCluster = document.createElement('div');
    controlCluster.className = 'role-mobile-portal-actions';

    topRow.append(brandLink, controlCluster);

    const navRow = document.createElement('nav');
    navRow.className = `role-mobile-portal-nav ${config.navClass}`;
    navRow.setAttribute('aria-label', config.ariaLabel);

    config.primaryItems.forEach(({ sourceLabel, displayLabel, icon }) => {
        const source = findSidebarLink(sourceLabel);
        if (!source) return;

        const link = document.createElement('a');
        link.href = source.href;
        link.className = 'role-mobile-portal-nav-item';
        link.innerHTML = `<i class="fa-solid ${icon}" aria-hidden="true"></i><span>${displayLabel}</span>`;

        if (activeLabel === sourceLabel) {
            link.classList.add('is-active');
            link.setAttribute('aria-current', 'page');
        }

        navRow.appendChild(link);
    });

    header.append(topRow, navRow);
    desktopTopbar.parentNode.insertBefore(header, desktopTopbar);

    const notification = desktopActions.querySelector(':scope > .notification-dropdown');
    const accountCluster = desktopActions.querySelector(':scope > .account-topbar-cluster');

    const notificationPlaceholder = document.createElement('span');
    notificationPlaceholder.hidden = true;
    notificationPlaceholder.dataset.roleMobilePortalPlaceholder = `${config.portalKey}-notifications`;

    const accountPlaceholder = document.createElement('span');
    accountPlaceholder.hidden = true;
    accountPlaceholder.dataset.roleMobilePortalPlaceholder = `${config.portalKey}-account`;

    if (notification) desktopActions.insertBefore(notificationPlaceholder, notification);
    if (accountCluster) desktopActions.insertBefore(accountPlaceholder, accountCluster);

    const closeTransientMenus = () => {
        controlCluster.querySelectorAll('.notification-dropdown[open], .account-menu[open]').forEach((menu) => menu.removeAttribute('open'));
    };

    const syncLayout = () => {
        if (roleMobilePortalQuery.matches) {
            sidebar.classList.remove('is-mobile-open');

            if (notification && notification.parentElement !== controlCluster) {
                controlCluster.appendChild(notification);
            }

            // Account is appended last so the profile/avatar is always the rightmost top-row control.
            if (accountCluster && accountCluster.parentElement !== controlCluster) {
                controlCluster.appendChild(accountCluster);
            }

            return;
        }

        closeTransientMenus();

        if (notification && notificationPlaceholder.parentNode) {
            notificationPlaceholder.after(notification);
        }

        if (accountCluster && accountPlaceholder.parentNode) {
            accountPlaceholder.after(accountCluster);
        }
    };

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeTransientMenus();
    });

    if (typeof roleMobilePortalQuery.addEventListener === 'function') {
        roleMobilePortalQuery.addEventListener('change', syncLayout);
    } else {
        roleMobilePortalQuery.addListener(syncLayout);
    }

    syncLayout();
}

function initRoleMobilePortalNavigation() {
    portalConfigs.forEach(initRoleMobilePortal);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRoleMobilePortalNavigation, { once: true });
} else {
    initRoleMobilePortalNavigation();
}
