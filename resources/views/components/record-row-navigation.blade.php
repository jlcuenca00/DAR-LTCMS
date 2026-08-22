<style>
    [data-record-row-href] {
        cursor: pointer;
        transition: background-color .15s ease, box-shadow .15s ease;
    }

    tr[data-record-row-href]:hover > td,
    [data-record-row-href]:not(tr):hover {
        background: #f6faf7;
    }

    tr[data-record-row-href]:focus-visible > td,
    [data-record-row-href]:not(tr):focus-visible {
        background: #f2f8f4;
        box-shadow: inset 0 0 0 2px #166534;
        outline: none;
    }
</style>

<script>
    (() => {
        const interactiveSelector = 'a, button, input, select, textarea, label, summary, details, [role="button"], [data-row-navigation-ignore]';

        document.querySelectorAll('[data-record-row-href]').forEach((row) => {
            const href = row.dataset.recordRowHref;

            if (!href || row.dataset.recordRowNavigation === 'ready') {
                return;
            }

            row.dataset.recordRowNavigation = 'ready';
            row.setAttribute('role', 'link');
            row.setAttribute('tabindex', '0');

            row.addEventListener('click', (event) => {
                if (event.target.closest(interactiveSelector)) return;
                if (window.getSelection()?.toString()) return;
                window.location.assign(href);
            });

            row.addEventListener('keydown', (event) => {
                if (event.target !== row || (event.key !== 'Enter' && event.key !== ' ')) return;

                event.preventDefault();
                window.location.assign(href);
            });
        });
    })();
</script>
