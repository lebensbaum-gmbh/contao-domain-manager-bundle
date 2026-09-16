(() => {
    const initialize = () => {
        const root = document.querySelector('[data-dm-updates]');
        if (!root || root.dataset.dmUpdatesInitialized === '1') {
            return;
        }

        root.dataset.dmUpdatesInitialized = '1';

        const rows = Array.from(root.querySelectorAll('[data-dm-update-row]'));
        const search = root.querySelector('[data-dm-update-search]');
        const filterButtons = Array.from(root.querySelectorAll('[data-dm-update-filter]'));
        const empty = root.querySelector('[data-dm-update-empty]');
        let filter = 'all';

        const apply = () => {
            const term = String(search?.value || '').toLocaleLowerCase('de-DE').trim();
            let visible = 0;

            rows.forEach((row) => {
                const stateMatches = 'all' === filter || row.dataset.updateState === filter;
                const searchMatches = '' === term || String(row.dataset.search || '').includes(term);
                const show = stateMatches && searchMatches;
                row.hidden = !show;

                if (show) {
                    visible += 1;
                }
            });

            if (empty) {
                empty.hidden = visible > 0;
            }
        };

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) {
                    return;
                }

                filter = button.dataset.dmUpdateFilter || 'all';
                filterButtons.forEach((candidate) => candidate.classList.toggle('is-active', candidate === button));
                apply();
            });
        });

        search?.addEventListener('input', apply);

        root.querySelectorAll('.dm-updates-action-form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const button = form.querySelector('button[type="submit"]');
                if (!button) {
                    return;
                }

                const confirmText = button.dataset.dmConfirm || '';
                if (confirmText && !window.confirm(confirmText)) {
                    event.preventDefault();
                    return;
                }

                const row = form.closest('[data-dm-update-row]');
                rows.forEach((candidate) => {
                    candidate.hidden = candidate !== row;
                });
                filterButtons.forEach((candidate) => { candidate.disabled = true; });
                if (search) {
                    search.disabled = true;
                }

                const label = button.dataset.dmActionLabel || button.textContent.trim();
                button.disabled = true;
                button.textContent = `${label} …`;

                form.closest('.dm-update-actions')?.querySelectorAll('button, a').forEach((action) => {
                    if (action !== button) {
                        action.setAttribute('aria-disabled', 'true');
                        if ('BUTTON' === action.tagName) {
                            action.disabled = true;
                        }
                    }
                });
            });
        });

        apply();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
