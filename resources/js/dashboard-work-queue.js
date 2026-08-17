function initializeDashboardWorkQueue() {
    const dashboard = document.querySelector('.staff-dashboard');
    if (!dashboard) return;

    const filterButtons = Array.from(dashboard.querySelectorAll('[data-dashboard-filter]'));
    const applicationRows = Array.from(dashboard.querySelectorAll('[data-dashboard-status]'));
    const emptyRow = dashboard.querySelector('[data-dashboard-filter-empty]');

    if (!filterButtons.length || !applicationRows.length) return;

    const statusGroups = {
        pending_legal_review: new Set([
            'pending_legal_review',
            'draft',
            'pending_review',
        ]),
        for_releasing: new Set(['for_releasing']),
    };

    const matchesFilter = (status, filter) => {
        if (filter === 'all') return true;
        const group = statusGroups[filter];
        return group ? group.has(status) : status === filter;
    };

    const applyFilter = (filter) => {
        let visibleCount = 0;

        filterButtons.forEach((button) => {
            const active = button.dataset.dashboardFilter === filter;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        applicationRows.forEach((row) => {
            const matches = matchesFilter(row.dataset.dashboardStatus, filter);
            const visible = matches && visibleCount < 6;
            row.hidden = !visible;
            if (visible) visibleCount += 1;
        });

        if (emptyRow) emptyRow.hidden = visibleCount !== 0;
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopImmediatePropagation();
            applyFilter(button.dataset.dashboardFilter || 'all');
        }, { capture: true });
    });

    applyFilter('all');
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDashboardWorkQueue, { once: true });
} else {
    initializeDashboardWorkQueue();
}
