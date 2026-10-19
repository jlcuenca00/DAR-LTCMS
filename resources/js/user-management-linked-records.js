function initUserManagementLinkedRecords() {
    const table = document.querySelector('.user-management-table');

    if (!table) {
        return;
    }

    table.querySelectorAll('tbody tr').forEach((row) => {
        const cells = Array.from(row.querySelectorAll('td'));
        const linkedRecordCell = cells[2];

        if (linkedRecordCell && !linkedRecordCell.querySelector('[data-landowner-record-link]')) {
            const match = linkedRecordCell.textContent?.match(/Landowner ID\s+(\d+)/i);
            const primary = linkedRecordCell.querySelector('.user-management-primary');
            const secondary = linkedRecordCell.querySelector('.user-management-secondary');

            if (match && primary) {
                const landownerId = match[1];
                const link = document.createElement('a');

                link.href = `/staff/records/landowners/${encodeURIComponent(landownerId)}`;
                link.className = 'user-management-linked-record';
                link.dataset.landownerRecordLink = landownerId;
                link.setAttribute('aria-label', `Open landowner record ${primary.textContent?.trim() || landownerId}`);
                link.title = 'Open landowner record';

                linkedRecordCell.insertBefore(link, primary);
                link.appendChild(primary);

                if (secondary) {
                    link.appendChild(secondary);
                }

                const cue = document.createElement('i');
                cue.className = 'fa-solid fa-arrow-up-right-from-square user-management-linked-record-icon';
                cue.setAttribute('aria-hidden', 'true');
                link.appendChild(cue);
            }
        }

        const manageLink = row.querySelector('[data-account-manage-link]');

        if (manageLink) {
            const userName = cells[0]?.querySelector('.user-management-primary')?.textContent?.trim() || 'user';

            row.dataset.manageUrl = manageLink.href;
            row.tabIndex = 0;
            row.setAttribute('aria-label', `Manage ${userName}`);
            row.title = `Manage ${userName}`;
        }
    });

    const visibleColumnCount = table.querySelectorAll('thead th').length;
    table.querySelectorAll('tbody td[colspan]').forEach((cell) => {
        cell.colSpan = visibleColumnCount;
    });

    table.addEventListener('click', (event) => {
        const interactive = event.target.closest('a, button, input, select, textarea, label, summary, details, [data-prevent-row-navigation]');

        if (interactive) {
            return;
        }

        const row = event.target.closest('tbody tr[data-manage-url]');

        if (row?.dataset.manageUrl) {
            window.location.assign(row.dataset.manageUrl);
        }
    });

    table.addEventListener('keydown', (event) => {
        const row = event.target.closest('tbody tr[data-manage-url]');

        if (!row || event.target !== row || !['Enter', ' '].includes(event.key)) {
            return;
        }

        event.preventDefault();
        window.location.assign(row.dataset.manageUrl);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUserManagementLinkedRecords, { once: true });
} else {
    initUserManagementLinkedRecords();
}
