function initUserManagementLinkedRecords() {
    const table = document.querySelector('.user-management-table');

    if (!table) {
        return;
    }

    table.querySelectorAll('tbody tr').forEach((row) => {
        const cells = row.querySelectorAll('td');
        const linkedRecordCell = cells[2];

        if (!linkedRecordCell || linkedRecordCell.querySelector('[data-landowner-record-link]')) {
            return;
        }

        const match = linkedRecordCell.textContent?.match(/Landowner ID\s+(\d+)/i);
        const primary = linkedRecordCell.querySelector('.user-management-primary');
        const secondary = linkedRecordCell.querySelector('.user-management-secondary');

        if (!match || !primary) {
            return;
        }

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
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUserManagementLinkedRecords, { once: true });
} else {
    initUserManagementLinkedRecords();
}
