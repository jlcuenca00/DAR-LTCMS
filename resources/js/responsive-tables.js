// DAR-LTCMS Phase 3: convert normal portal data tables into labeled mobile record cards.
// The table remains semantically intact and returns to its normal desktop presentation above the CSS breakpoint.
function initDarLtcmsResponsiveTables() {
    const portalTables = document.querySelectorAll([
        '.staff-shell table:not(.no-responsive-table)',
        '.geo-shell table:not(.no-responsive-table)',
        '.lo-shell table:not(.no-responsive-table)',
    ].join(','));

    portalTables.forEach((table) => {
        if (table.dataset.responsiveCardsReady === 'true') return;

        const headerCells = Array.from(table.querySelectorAll('thead tr:first-child th'));
        const bodyRows = Array.from(table.querySelectorAll('tbody > tr'));

        if (!headerCells.length || !bodyRows.length) return;

        const labels = headerCells.map((header) => header.textContent.trim().replace(/\s+/g, ' '));

        table.classList.add('responsive-card-table');
        table.dataset.responsiveCardsReady = 'true';

        bodyRows.forEach((row) => {
            const cells = Array.from(row.children).filter((cell) => cell.matches('td, th'));

            if (!cells.length) return;

            if (cells.length === 1 && Number.parseInt(cells[0].getAttribute('colspan') || '1', 10) > 1) {
                row.classList.add('responsive-card-empty-row');
                cells[0].setAttribute('data-mobile-label', '');
                return;
            }

            cells.forEach((cell, index) => {
                if (!cell.hasAttribute('data-mobile-label')) {
                    cell.setAttribute('data-mobile-label', labels[index] || 'Details');
                }
            });
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDarLtcmsResponsiveTables, { once: true });
} else {
    initDarLtcmsResponsiveTables();
}
