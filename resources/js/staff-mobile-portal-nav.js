const staffMobilePortalQuery = window.matchMedia('(max-width: 1100px)');

function initStaffMobilePortalNavigation() {
    const shell = document.querySelector('.staff-shell');
    const sidebar = shell?.querySelector('.staff-sidebar');
    const brand = sidebar?.querySelector('.staff-brand');
    const desktopTopbar = shell?.querySelector('.staff-topbar');
    const desktopActions = desktopTopbar?.querySelector('.staff-topbar-actions');

    if (!shell || !sidebar || !brand || !desktopTopbar || !desktopActions || shell.querySelector('[data-staff-mobile-portal-header]')) {
        return;
    }

    const sidebarLinks = Array.from(sidebar.querySelectorAll('.staff-side-link[href]'));
    const activeSidebarLink = sidebarLinks.find((link) => link.classList.contains('active'));
    const activeLabel = activeSidebarLink?.querySelector('span')?.textContent?.trim() || '';

    const findSidebarLink = (label) => sidebarLinks.find((link) => link.querySelector('span')?.textContent?.trim() === label) || null;
    const dashboardLink = findSidebarLink('Staff Dashboard');

    const header = document.createElement('header');
    header.className = 'staff-mobile-portal-header';
    header.dataset.staffMobilePortalHeader = 'true';
    header.setAttribute('aria-label', 'Staff mobile navigation');

    const topRow = document.createElement('div');
    topRow.className = 'staff-mobile-portal-top';

    const brandLink = document.createElement('a');
    brandLink.className = 'staff-mobile-portal-brand';
    brandLink.href = dashboardLink?.href || '/staff/dashboard';
    brandLink.setAttribute('aria-label', 'Go to Staff Dashboard');

    const brandMark = brand.querySelector('.staff-brand-mark')?.cloneNode(true);
    if (brandMark) {
        brandMark.classList.add('staff-mobile-portal-brand-mark');
        brandLink.appendChild(brandMark);
    }

    const brandCopy = document.createElement('span');
    brandCopy.className = 'staff-mobile-portal-brand-copy';
    brandCopy.innerHTML = '<strong>DAR LTCMS</strong><small>Negros Oriental</small>';
    brandLink.appendChild(brandCopy);

    const controlCluster = document.createElement('div');
    controlCluster.className = 'staff-mobile-portal-actions';

    topRow.append(brandLink, controlCluster);

    const navRow = document.createElement('nav');
    navRow.className = 'staff-mobile-portal-nav';
    navRow.setAttribute('aria-label', 'Primary Staff modules');

    const createPrimaryLink = ({ sourceLabel, displayLabel, icon, activeLabels = [sourceLabel] }) => {
        const source = findSidebarLink(sourceLabel);
        if (!source) return null;

        const link = document.createElement('a');
        link.href = source.href;
        link.className = 'staff-mobile-portal-nav-item';
        link.dataset.mobilePortalKey = sourceLabel;
        link.innerHTML = `<i class="fa-solid ${icon}" aria-hidden="true"></i><span>${displayLabel}</span>`;

        if (activeLabels.includes(activeLabel)) {
            link.classList.add('is-active');
            link.setAttribute('aria-current', 'page');
        }

        return link;
    };

    [
        createPrimaryLink({ sourceLabel: 'Staff Dashboard', displayLabel: 'Dashboard', icon: 'fa-house' }),
        createPrimaryLink({ sourceLabel: 'Applications', displayLabel: 'Applications', icon: 'fa-file-lines' }),
        createPrimaryLink({ sourceLabel: 'Landowner Records', displayLabel: 'Landowners', icon: 'fa-users' }),
        createPrimaryLink({ sourceLabel: 'Parcel Records', displayLabel: 'Parcels', icon: 'fa-map-location-dot', activeLabels: ['Parcel Records', 'Parcel Map'] }),
    ].filter(Boolean).forEach((link) => navRow.appendChild(link));

    const moreDetails = document.createElement('details');
    moreDetails.className = 'staff-mobile-portal-more';

    const moreSummary = document.createElement('summary');
    moreSummary.className = 'staff-mobile-portal-nav-item staff-mobile-portal-more-trigger';
    moreSummary.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i><span>More</span>';
    moreSummary.setAttribute('aria-label', 'Open more Staff modules');

    const currentPath = window.location.pathname.replace(/\/+$/, '');
    const moreActive = ['Source Records', 'Monitoring Reports', 'Audit Logs'].includes(activeLabel)
        || currentPath.startsWith('/staff/users');

    if (moreActive) {
        moreSummary.classList.add('is-active');
    }

    const morePanel = document.createElement('div');
    morePanel.className = 'staff-mobile-portal-more-panel';

    const moreTitle = document.createElement('div');
    moreTitle.className = 'staff-mobile-portal-more-title';
    moreTitle.innerHTML = '<strong>More Staff Modules</strong><span>Administrative and reference tools</span>';
    morePanel.appendChild(moreTitle);

    const moreList = document.createElement('div');
    moreList.className = 'staff-mobile-portal-more-list';

    const moreItems = [
        { label: 'Parcel Map', icon: 'fa-map' },
        { label: 'Source Records', icon: 'fa-box-archive' },
        { label: 'Monitoring Reports', icon: 'fa-chart-column' },
        { label: 'Audit Logs', icon: 'fa-clipboard-list' },
    ];

    moreItems.forEach(({ label, icon }) => {
        const source = findSidebarLink(label);
        if (!source) return;

        const link = document.createElement('a');
        link.href = source.href;
        link.className = 'staff-mobile-portal-more-link';
        link.innerHTML = `<span class="staff-mobile-portal-more-icon"><i class="fa-solid ${icon}" aria-hidden="true"></i></span><span>${label}</span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>`;
        if (activeLabel === label) link.classList.add('is-active');
        link.addEventListener('click', () => moreDetails.removeAttribute('open'));
        moreList.appendChild(link);
    });

    const userManagementSource = document.querySelector('a[href*="/staff/users"]');
    const userManagementLink = document.createElement('a');
    userManagementLink.href = userManagementSource?.href || '/staff/users';
    userManagementLink.className = 'staff-mobile-portal-more-link';
    userManagementLink.innerHTML = '<span class="staff-mobile-portal-more-icon"><i class="fa-solid fa-user-gear" aria-hidden="true"></i></span><span>User Management</span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>';
    if (currentPath.startsWith('/staff/users')) userManagementLink.classList.add('is-active');
    userManagementLink.addEventListener('click', () => moreDetails.removeAttribute('open'));
    moreList.appendChild(userManagementLink);

    morePanel.appendChild(moreList);
    moreDetails.append(moreSummary, morePanel);
    navRow.appendChild(moreDetails);

    header.append(topRow, navRow);
    desktopTopbar.parentNode.insertBefore(header, desktopTopbar);

    const notification = desktopActions.querySelector(':scope > .notification-dropdown');
    const accountCluster = desktopActions.querySelector(':scope > .account-topbar-cluster');

    const notificationPlaceholder = document.createElement('span');
    notificationPlaceholder.hidden = true;
    notificationPlaceholder.dataset.mobilePortalPlaceholder = 'notifications';

    const accountPlaceholder = document.createElement('span');
    accountPlaceholder.hidden = true;
    accountPlaceholder.dataset.mobilePortalPlaceholder = 'account';

    if (notification) desktopActions.insertBefore(notificationPlaceholder, notification);
    if (accountCluster) desktopActions.insertBefore(accountPlaceholder, accountCluster);

    const hasPageActions = () => Array.from(desktopActions.children).some((child) => {
        if (child.matches('[data-mobile-portal-placeholder]')) return false;
        if (child === notification || child === accountCluster) return false;
        return true;
    });

    const closeTransientMenus = () => {
        moreDetails.removeAttribute('open');
        controlCluster.querySelectorAll('.notification-dropdown[open], .account-menu[open]').forEach((menu) => menu.removeAttribute('open'));
    };

    const syncLayout = () => {
        if (staffMobilePortalQuery.matches) {
            sidebar.classList.remove('is-mobile-open');
            if (notification && notification.parentElement !== controlCluster) controlCluster.appendChild(notification);
            if (accountCluster && accountCluster.parentElement !== controlCluster) controlCluster.appendChild(accountCluster);
            desktopTopbar.classList.toggle('staff-mobile-no-page-actions', !hasPageActions());
            return;
        }

        closeTransientMenus();
        if (notification && notificationPlaceholder.parentNode) notificationPlaceholder.after(notification);
        if (accountCluster && accountPlaceholder.parentNode) accountPlaceholder.after(accountCluster);
        desktopTopbar.classList.remove('staff-mobile-no-page-actions');
    };

    moreDetails.addEventListener('toggle', () => {
        if (!moreDetails.open) return;
        controlCluster.querySelectorAll('.notification-dropdown[open], .account-menu[open]').forEach((menu) => menu.removeAttribute('open'));
    });

    controlCluster.addEventListener('click', () => moreDetails.removeAttribute('open'), true);

    document.addEventListener('click', (event) => {
        if (moreDetails.open && !moreDetails.contains(event.target)) {
            moreDetails.removeAttribute('open');
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && moreDetails.open) {
            moreDetails.removeAttribute('open');
            moreSummary.focus();
        }
    });

    if (typeof staffMobilePortalQuery.addEventListener === 'function') {
        staffMobilePortalQuery.addEventListener('change', syncLayout);
    } else {
        staffMobilePortalQuery.addListener(syncLayout);
    }

    syncLayout();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStaffMobilePortalNavigation, { once: true });
} else {
    initStaffMobilePortalNavigation();
}
