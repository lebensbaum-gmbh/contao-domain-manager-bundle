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
        const selectVisible = root.querySelector('[data-dm-select-visible]');
        const selectedCount = root.querySelector('[data-dm-selected-count]');
        const clearSelection = root.querySelector('[data-dm-clear-selection]');
        const rowCheckboxes = Array.from(root.querySelectorAll('[data-dm-update-select]'));
        let filter = 'all';

        const updateSelection = () => {
            const selected = rowCheckboxes.filter((checkbox) => checkbox.checked);

            rows.forEach((row) => {
                const checkbox = row.querySelector('[data-dm-update-select]');
                row.classList.toggle('is-selected', Boolean(checkbox?.checked));
            });

            if (selectedCount) {
                selectedCount.textContent = String(selected.length);
            }

            if (clearSelection) {
                clearSelection.disabled = selected.length === 0;
            }

            if (selectVisible) {
                const visibleCheckboxes = rowCheckboxes.filter((checkbox) => {
                    const row = checkbox.closest('[data-dm-update-row]');
                    return row && !row.hidden && !checkbox.disabled;
                });
                const selectedVisible = visibleCheckboxes.filter((checkbox) => checkbox.checked).length;

                selectVisible.disabled = visibleCheckboxes.length === 0;
                selectVisible.checked = visibleCheckboxes.length > 0 && selectedVisible === visibleCheckboxes.length;
                selectVisible.indeterminate = selectedVisible > 0 && selectedVisible < visibleCheckboxes.length;
            }
        };

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

            updateSelection();
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

        rowCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', updateSelection);
        });

        selectVisible?.addEventListener('change', () => {
            rowCheckboxes.forEach((checkbox) => {
                const row = checkbox.closest('[data-dm-update-row]');
                if (row && !row.hidden && !checkbox.disabled) {
                    checkbox.checked = selectVisible.checked;
                }
            });

            updateSelection();
        });

        clearSelection?.addEventListener('click', () => {
            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });

            updateSelection();
        });

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
                if (selectVisible) {
                    selectVisible.disabled = true;
                }
                if (clearSelection) {
                    clearSelection.disabled = true;
                }
                rowCheckboxes.forEach((checkbox) => { checkbox.disabled = true; });

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
