function initializeStaffDashboardHeroContext() {
    const hero = document.querySelector('.staff-dashboard .dashboard-hero');
    if (!hero) return;

    const intro = hero.firstElementChild;
    const title = intro?.querySelector('.hero-title');
    const queue = hero.querySelector('.hero-queue');
    const queueTitle = queue?.querySelector('.hero-queue-title');

    if (intro && title && !intro.querySelector('.hero-eyebrow')) {
        const eyebrow = document.createElement('p');
        eyebrow.className = 'hero-eyebrow';
        eyebrow.textContent = 'DAR Staff Workspace';
        intro.insertBefore(eyebrow, title);
    }

    if (intro && title && !intro.querySelector('.hero-copy')) {
        const copy = document.createElement('p');
        copy.className = 'hero-copy';
        copy.textContent = 'Here is the current land transfer clearance processing workload requiring staff attention.';
        title.insertAdjacentElement('afterend', copy);
    }

    if (queue && queueTitle && !queue.querySelector('.hero-queue-summary')) {
        const total = Array.from(queue.querySelectorAll('.queue-value'))
            .reduce((sum, value) => {
                const parsed = Number.parseInt(value.textContent.replace(/[^0-9-]/g, ''), 10);
                return sum + (Number.isFinite(parsed) ? parsed : 0);
            }, 0);

        const summary = document.createElement('p');
        summary.className = 'hero-queue-summary';
        summary.id = 'staff-work-queue-summary';
        summary.textContent = `${total.toLocaleString()} application${total === 1 ? '' : 's'} across the active processing queues. Select a queue to filter the preview below.`;
        queueTitle.insertAdjacentElement('afterend', summary);
        queue.setAttribute('aria-describedby', summary.id);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeStaffDashboardHeroContext, { once: true });
} else {
    initializeStaffDashboardHeroContext();
}
