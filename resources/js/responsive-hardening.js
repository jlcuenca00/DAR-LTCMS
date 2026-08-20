const compactPortalQuery = window.matchMedia('(max-width: 1100px)');

const portalConfigs = [
    {
        key: 'staff',
        shell: '.staff-shell',
        sidebar: '.staff-sidebar',
        brand: '.staff-brand',
        brandMark: '.staff-brand-mark',
        topbar: '.staff-topbar',
        actions: '.staff-topbar-actions',
        links: '.staff-side-link[href]',
        dashboardLabel: 'Staff Dashboard',
        dashboardFallback: '/staff/dashboard',
        ariaLabel: 'Staff portal navigation',
        sentinelClass: 'staff-mobile-nav-toggle',
        primary: [
            { source: 'Staff Dashboard', label: 'Dashboard', icon: 'fa-house' },
            { source: 'Applications', label: 'Applications', icon: 'fa-file-lines' },
            { source: 'Landowner Records', label: 'Landowners', icon: 'fa-users' },
            { source: 'Parcel Records', label: 'Parcels', icon: 'fa-map-location-dot', activeAlso: ['Parcel Map'] },
        ],
        more: [
            { source: 'Parcel Map', icon: 'fa-map' },
            { source: 'Source Records', icon: 'fa-box-archive' },
            { source: 'Monitoring Reports', icon: 'fa-chart-column' },
            { source: 'Audit Logs', icon: 'fa-clipboard-list' },
        ],
    },
    {
        key: 'landowner',
        shell: '.lo-shell',
        sidebar: '.lo-sidebar',
        brand: '.lo-brand',
        brandMark: '.lo-brand-mark',
        topbar: '.lo-topbar',
        actions: '.lo-topbar-right',
        links: '.lo-nav-link[href]',
        dashboardLabel: 'Dashboard',
        dashboardFallback: '/landowner/dashboard',
        ariaLabel: 'Landowner portal navigation',
        sentinelClass: 'lo-mobile-nav-toggle',
        primary: [
            { source: 'Dashboard', label: 'Dashboard', icon: 'fa-house' },
            { source: 'My Parcel Map', label: 'Parcel Map', icon: 'fa-map-location-dot' },
            { source: 'My Parcel Records', label: 'Parcels', icon: 'fa-draw-polygon' },
            { source: 'My Applications', label: 'Applications', icon: 'fa-file-lines' },
        ],
    },
    {
        key: 'geodetic',
        shell: '.geo-shell',
        sidebar: '.geo-sidebar',
        brand: '.geo-brand',
        brandMark: '.geo-brand-mark',
        topbar: '.geo-topbar',
        actions: '.geo-topbar-right',
        links: '.geo-nav-link[href]',
        dashboardLabel: 'Dashboard',
        dashboardFallback: '/geodetic/dashboard',
        ariaLabel: 'Geodetic portal navigation',
        sentinelClass: 'geo-mobile-nav-toggle',
        primary: [
            { source: 'Dashboard', label: 'Dashboard', icon: 'fa-house' },
            { source: 'Parcel Map', label: 'Parcel Map', icon: 'fa-map-location-dot' },
            { source: 'Parcel References', label: 'Parcels', icon: 'fa-draw-polygon' },
        ],
    },
];

function normalizedLabel(node) {
    return node?.textContent?.replace(/\s+/g, ' ').trim() || '';
}

function stripDuplicateIds(root) {
    if (root.id) root.removeAttribute('id');
    root.removeAttribute('aria-controls');
    root.removeAttribute('aria-describedby');
    root.removeAttribute('aria-labelledby');

    root.querySelectorAll('[id]').forEach((node) => node.removeAttribute('id'));
    root.querySelectorAll('[aria-controls], [aria-describedby], [aria-labelledby]').forEach((node) => {
        node.removeAttribute('aria-controls');
        node.removeAttribute('aria-describedby');
        node.removeAttribute('aria-labelledby');
    });
}

function markNotificationDropdownAsRead(dropdown) {
    if (!dropdown || dropdown.dataset.readTriggered === 'true') return;

    const hasUnread = dropdown.querySelector('.notification-badge')
        || dropdown.querySelector('.notification-dropdown-item.is-unread');

    if (!hasUnread) return;

    dropdown.dataset.readTriggered = 'true';
    dropdown.querySelectorAll('.notification-badge').forEach((badge) => badge.remove());
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
        // Server state is authoritative; a later page load restores the real count.
    });
}

function wireNotificationClone(dropdown) {
    if (!dropdown || dropdown.dataset.responsiveNotificationWired === 'true') return;
    dropdown.dataset.responsiveNotificationWired = 'true';

    dropdown.addEventListener('toggle', () => {
        if (!dropdown.open) markNotificationDropdownAsRead(dropdown);
    });
}

function closeMobileTransientUi(except = null) {
    document.querySelectorAll('.dar-mobile-portal-header details[open]').forEach((details) => {
        if (details !== except) details.removeAttribute('open');
    });
}

function makeBrand(config, brand, dashboardHref) {
    const link = document.createElement('a');
    link.className = 'dar-mobile-portal-brand';
    link.href = dashboardHref || config.dashboardFallback;
    link.setAttribute('aria-label', `Go to ${config.dashboardLabel}`);

    const mark = brand.querySelector(config.brandMark)?.cloneNode(true);
    if (mark) {
        mark.classList.add('dar-mobile-portal-brand-mark');
        stripDuplicateIds(mark);
        link.appendChild(mark);
    }

    const copy = document.createElement('span');
    copy.className = 'dar-mobile-portal-brand-copy';
    copy.innerHTML = '<strong>DAR LTCMS</strong><small>Negros Oriental</small>';
    link.appendChild(copy);
    return link;
}

function makePortalLink(sourceLink, item, activeLabel) {
    if (!sourceLink) return null;

    const link = document.createElement('a');
    link.href = sourceLink.href;
    link.className = 'dar-mobile-portal-nav-item';
    link.innerHTML = `<i class="fa-solid ${item.icon}" aria-hidden="true"></i><span>${item.label}</span>`;

    const activeLabels = [item.source, ...(item.activeAlso || [])];
    if (activeLabels.includes(activeLabel)) {
        link.classList.add('is-active');
        link.setAttribute('aria-current', 'page');
    }

    return link;
}

function makeStaffMore(config, findLink, activeLabel) {
    const details = document.createElement('details');
    details.className = 'dar-mobile-portal-more';

    const summary = document.createElement('summary');
    summary.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i><span>More</span>';
    summary.setAttribute('aria-label', 'Open more Staff modules');

    const currentPath = window.location.pathname.replace(/\/+$/, '');
    const moreLabels = config.more.map((item) => item.source);
    if (moreLabels.includes(activeLabel) || currentPath.startsWith('/staff/users')) {
        summary.classList.add('is-active');
    }

    const panel = document.createElement('div');
    panel.className = 'dar-mobile-portal-more-panel';

    const title = document.createElement('div');
    title.className = 'dar-mobile-portal-more-title';
    title.innerHTML = '<strong>More Staff Modules</strong><span>Administrative and reference tools</span>';
    panel.appendChild(title);

    const list = document.createElement('div');
    list.className = 'dar-mobile-portal-more-list';

    config.more.forEach((item) => {
        const source = findLink(item.source);
        if (!source) return;

        const link = document.createElement('a');
        link.href = source.href;
        link.className = 'dar-mobile-portal-more-link';
        link.innerHTML = `<span><i class="fa-solid ${item.icon}" aria-hidden="true"></i></span><span>${item.source}</span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>`;
        if (activeLabel === item.source) link.classList.add('is-active');
        link.addEventListener('click', () => details.removeAttribute('open'));
        list.appendChild(link);
    });

    const userManagementSource = document.querySelector('a[href*="/staff/users"]');
    if (userManagementSource) {
        const link = document.createElement('a');
        link.href = userManagementSource.href;
        link.className = 'dar-mobile-portal-more-link';
        link.innerHTML = '<span><i class="fa-solid fa-user-gear" aria-hidden="true"></i></span><span>User Management</span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>';
        if (currentPath.startsWith('/staff/users')) link.classList.add('is-active');
        link.addEventListener('click', () => details.removeAttribute('open'));
        list.appendChild(link);
    }

    panel.appendChild(list);
    details.append(summary, panel);

    details.addEventListener('toggle', () => {
        if (details.open) closeMobileTransientUi(details);
    });

    return details;
}

function cloneMobileControl(actions, selector) {
    const original = actions.querySelector(`:scope > ${selector}`);
    if (!original) return null;

    const clone = original.cloneNode(true);
    clone.dataset.responsiveClone = 'true';
    stripDuplicateIds(clone);
    return clone;
}

function addLegacySentinel(brand, className) {
    if (!className || brand.querySelector(`.${className}`)) return;
    const sentinel = document.createElement('span');
    sentinel.className = `${className} dar-legacy-responsive-sentinel`;
    sentinel.hidden = true;
    sentinel.setAttribute('aria-hidden', 'true');
    brand.appendChild(sentinel);
}

function initPortal(config) {
    const shell = document.querySelector(config.shell);
    const sidebar = shell?.querySelector(config.sidebar);
    const brand = sidebar?.querySelector(config.brand);
    const topbar = shell?.querySelector(config.topbar);
    const actions = topbar?.querySelector(config.actions);

    if (!shell || !sidebar || !brand || !topbar || !actions) return;
    if (shell.querySelector(`[data-dar-mobile-portal="${config.key}"]`)) return;

    addLegacySentinel(brand, config.sentinelClass);

    const sourceLinks = Array.from(sidebar.querySelectorAll(config.links));
    const findLink = (label) => sourceLinks.find((link) => normalizedLabel(link) === label) || null;
    const activeLink = sourceLinks.find((link) => link.classList.contains('active')) || null;
    const activeLabel = normalizedLabel(activeLink);
    const dashboardLink = findLink(config.dashboardLabel);

    const header = document.createElement('header');
    header.className = 'dar-mobile-portal-header';
    header.dataset.darMobilePortal = config.key;
    header.setAttribute('aria-label', config.ariaLabel);

    const top = document.createElement('div');
    top.className = 'dar-mobile-portal-top';
    top.appendChild(makeBrand(config, brand, dashboardLink?.href));

    const controls = document.createElement('div');
    controls.className = 'dar-mobile-portal-actions';

    const notification = cloneMobileControl(actions, '.notification-dropdown');
    if (notification) {
        wireNotificationClone(notification);
        controls.appendChild(notification);
    }

    const account = cloneMobileControl(actions, '.account-topbar-cluster');
    if (account) {
        // Account is always appended last so profile/avatar remains the rightmost control.
        controls.appendChild(account);
    }

    top.appendChild(controls);

    const nav = document.createElement('nav');
    nav.className = `dar-mobile-portal-nav ${config.key}`;
    nav.setAttribute('aria-label', config.ariaLabel);

    config.primary.forEach((item) => {
        const link = makePortalLink(findLink(item.source), item, activeLabel);
        if (link) nav.appendChild(link);
    });

    if (config.key === 'staff') {
        nav.appendChild(makeStaffMore(config, findLink, activeLabel));
    }

    header.append(top, nav);
    topbar.parentNode.insertBefore(header, topbar);
    shell.classList.add('dar-responsive-ready');
}

function initDenseTableAffordances() {
    const selectors = '.staff-table-wrap, .report-table-wrap, .table-wrap, .source-table-wrap, .timeline-table-wrap';

    document.querySelectorAll(selectors).forEach((region) => {
        const denseTable = region.querySelector('.no-responsive-table');
        if (!denseTable) return;

        region.classList.add('responsive-local-scroll');
        if (!region.hasAttribute('tabindex')) region.tabIndex = 0;
        if (!region.hasAttribute('role')) region.setAttribute('role', 'region');
        if (!region.hasAttribute('aria-label')) region.setAttribute('aria-label', 'Scrollable data table');

        if (!region.previousElementSibling?.classList.contains('responsive-scroll-hint')) {
            const hint = document.createElement('p');
            hint.className = 'responsive-scroll-hint';
            hint.textContent = 'Swipe horizontally to view all columns.';
            region.parentNode.insertBefore(hint, region);
        }
    });
}

function initViewportGuards() {
    const close = () => closeMobileTransientUi();

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.dar-mobile-portal-header details[open]').forEach((details) => {
            if (!details.contains(event.target)) details.removeAttribute('open');
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        close();
    });

    window.addEventListener('orientationchange', close);

    if (typeof compactPortalQuery.addEventListener === 'function') {
        compactPortalQuery.addEventListener('change', close);
    } else {
        compactPortalQuery.addListener(close);
    }
}

function initResponsiveHardening() {
    document.documentElement.dataset.responsiveHardening = 'true';
    portalConfigs.forEach(initPortal);
    initDenseTableAffordances();
    initViewportGuards();
}

function bootResponsiveHardening() {
    // Initialize the controller immediately so legacy DOMContentLoaded handlers
    // see the sentinels/new portal markup before they can inject competing UI.
    initResponsiveHardening();

    // Load the canonical CSS after legacy static responsive assets so this contract
    // becomes the final screen-layout authority while older files are retired safely.
    import('../css/responsive-hardening.css').catch(() => null);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootResponsiveHardening, { once: true });
} else {
    bootResponsiveHardening();
}
