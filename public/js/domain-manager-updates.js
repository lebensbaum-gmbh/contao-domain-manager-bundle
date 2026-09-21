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
        const checkSelected = root.querySelector('[data-dm-check-selected]');
        const installSelected = root.querySelector('[data-dm-install-selected]');
        const rowCheckboxes = Array.from(root.querySelectorAll('[data-dm-update-select]'));
        const progress = root.querySelector('[data-dm-update-progress]');
        const progressTitle = root.querySelector('[data-dm-progress-title]');
        const progressCounter = root.querySelector('[data-dm-progress-counter]');
        const progressTrack = root.querySelector('[data-dm-progress-track]');
        const progressBar = root.querySelector('[data-dm-progress-bar]');
        const progressCurrent = root.querySelector('[data-dm-progress-current]');
        const progressSummary = root.querySelector('[data-dm-progress-summary]');
        const availableCount = root.querySelector('[data-dm-available-count]');
        const previousResult = root.querySelector('.dm-updates-result');
        let filter = 'all';
        let bulkRunning = false;

        const findActionForm = (row, name) => row?.querySelector(`.dm-updates-action-form[data-dm-action="${name}"]`) || null;

        const stateLabel = (state) => {
            switch (state) {
                case 'available':
                    return 'Update verfügbar';
                case 'current':
                    return 'Aktuell';
                case 'error':
                    return 'Prüfung fehlgeschlagen';
                case 'warning':
                    return 'Installiert · Synchronisation prüfen';
                case 'running':
                    return 'Prüfung läuft …';
                case 'locked':
                    return 'Pro-Funktion';
                default:
                    return 'Noch nicht geprüft';
            }
        };

        const setRowState = (row, state, label = stateLabel(state)) => {
            row.dataset.updateState = state;
            const element = row.querySelector('.dm-update-state');

            if (element) {
                element.className = `dm-update-state is-${state}`;
                element.textContent = label;
            }
        };

        const updateAvailableCount = () => {
            if (!availableCount) {
                return;
            }

            const count = rows.filter((row) => row.dataset.updateState === 'available').length;
            availableCount.textContent = String(count);

            const label = availableCount.nextElementSibling;
            if (label) {
                label.textContent = count === 1 ? 'bekanntes Update' : 'bekannte Updates';
            }
        };

        const selectedRows = () => rowCheckboxes
            .filter((checkbox) => checkbox.checked && !checkbox.disabled)
            .map((checkbox) => checkbox.closest('[data-dm-update-row]'))
            .filter(Boolean);

        const installableSelectedRows = () => selectedRows()
            .filter((row) => Boolean(findActionForm(row, 'update-install')));

        rowCheckboxes.forEach((checkbox) => {
            const row = checkbox.closest('[data-dm-update-row]');
            const eligible = Boolean(findActionForm(row, 'update-check'));
            checkbox.dataset.dmCheckEligible = eligible ? '1' : '0';
            checkbox.disabled = !eligible;

            if (!eligible) {
                checkbox.title = 'Update-Prüfung für diese Installation nicht verfügbar';
            }
        });

        const updateSelection = () => {
            const selected = selectedRows();
            const installable = selected.filter((row) => Boolean(findActionForm(row, 'update-install')));

            rows.forEach((row) => {
                const checkbox = row.querySelector('[data-dm-update-select]');
                row.classList.toggle('is-selected', Boolean(checkbox?.checked));
            });

            if (selectedCount) {
                selectedCount.textContent = String(selected.length);
            }

            if (clearSelection) {
                clearSelection.disabled = bulkRunning || selected.length === 0;
            }

            if (checkSelected) {
                checkSelected.disabled = bulkRunning || selected.length === 0;
            }

            if (installSelected) {
                installSelected.disabled = bulkRunning || installable.length === 0;
                installSelected.textContent = installable.length > 0
                    ? `${installable.length} ${installable.length === 1 ? 'Update' : 'Updates'} installieren`
                    : 'Ausgewählte installieren';
            }

            if (selectVisible) {
                const visibleCheckboxes = rowCheckboxes.filter((checkbox) => {
                    const row = checkbox.closest('[data-dm-update-row]');
                    return row && !row.hidden && checkbox.dataset.dmCheckEligible === '1';
                });
                const selectedVisible = visibleCheckboxes.filter((checkbox) => checkbox.checked).length;

                selectVisible.disabled = bulkRunning || visibleCheckboxes.length === 0;
                selectVisible.checked = visibleCheckboxes.length > 0 && selectedVisible === visibleCheckboxes.length;
                selectVisible.indeterminate = selectedVisible > 0 && selectedVisible < visibleCheckboxes.length;
            }
        };

        const apply = () => {
            if (bulkRunning) {
                return;
            }

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

        const updateProgress = (completed, total, currentText, summaryText = '') => {
            const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

            if (progressCounter) {
                progressCounter.textContent = `${completed} / ${total}`;
            }
            if (progressTrack) {
                progressTrack.setAttribute('aria-valuenow', String(percent));
            }
            if (progressBar) {
                progressBar.style.width = `${percent}%`;
            }
            if (progressCurrent) {
                progressCurrent.textContent = currentText;
            }
            if (progressSummary) {
                progressSummary.textContent = summaryText;
            }
        };

        const stateFromResponseUrl = (url, action = 'check') => {
            try {
                const params = new URL(url, window.location.href).searchParams;

                if (action === 'install') {
                    const status = params.get('dm_update_install');

                    if (status === 'success') {
                        return 'current';
                    }
                    if (status === 'success_sync_warning') {
                        return 'warning';
                    }
                    if (status === 'error') {
                        return 'error';
                    }

                    return 'error';
                }

                const status = params.get('dm_update');

                if (status === 'ready') {
                    return 'available';
                }
                if (status === 'up_to_date') {
                    return 'current';
                }
                if (status === 'blocked' || status === 'error') {
                    return 'error';
                }
            } catch (error) {
                return 'error';
            }

            return 'error';
        };

        const refreshRowFromResponse = (row, html, responseUrl, action = 'check') => {
            const installationId = String(row.dataset.installationId || '');
            let refreshed = false;

            if (/^\d+$/.test(installationId)) {
                const documentFromResponse = new DOMParser().parseFromString(html, 'text/html');
                const resultRow = documentFromResponse.querySelector(`[data-dm-update-row][data-installation-id="${installationId}"]`);

                if (resultRow) {
                    const resultState = resultRow.dataset.updateState || stateFromResponseUrl(responseUrl, action);
                    const resultStateElement = resultRow.querySelector('.dm-update-state');
                    const currentActions = row.querySelector('.dm-update-actions');
                    const resultActions = resultRow.querySelector('.dm-update-actions');
                    const currentVersion = row.querySelector('.dm-update-version');
                    const resultVersion = resultRow.querySelector('.dm-update-version');

                    setRowState(row, resultState, resultStateElement?.textContent?.trim() || stateLabel(resultState));

                    if (currentActions && resultActions) {
                        currentActions.innerHTML = resultActions.innerHTML;
                    }

                    if (currentVersion && resultVersion) {
                        currentVersion.innerHTML = resultVersion.innerHTML;
                    }

                    refreshed = true;
                }
            }

            if (!refreshed) {
                const state = stateFromResponseUrl(responseUrl, action);
                setRowState(row, state);
            }
        };

        const setBulkUiLocked = (locked) => {
            filterButtons.forEach((button) => { button.disabled = locked; });
            if (search) {
                search.disabled = locked;
            }
            if (selectVisible) {
                selectVisible.disabled = locked;
            }
            if (clearSelection) {
                clearSelection.disabled = locked;
            }
            if (checkSelected) {
                checkSelected.disabled = locked;
            }
            if (installSelected) {
                installSelected.disabled = locked;
            }
            rowCheckboxes.forEach((checkbox) => {
                checkbox.disabled = locked || checkbox.dataset.dmCheckEligible !== '1';
            });
        };

        const startBulkRun = (selected, title, startText) => {
            bulkRunning = true;
            root.classList.add('is-running');
            progress?.removeAttribute('hidden');
            progress?.classList.remove('is-complete', 'has-errors', 'has-warnings');
            progressTrack?.removeAttribute('data-dm-phase');
            progressBar?.removeAttribute('data-dm-phase');
            previousResult?.setAttribute('hidden', 'hidden');

            if (progressTitle) {
                progressTitle.textContent = title;
            }

            setBulkUiLocked(true);

            rows.forEach((row) => {
                row.hidden = !selected.includes(row);
                row.classList.remove('is-processing');
            });
            if (empty) {
                empty.hidden = true;
            }

            updateProgress(0, selected.length, startText);
        };

        const finishBulkRun = () => {
            bulkRunning = false;
            root.classList.remove('is-running');
            setBulkUiLocked(false);
            updateAvailableCount();
            apply();
        };

        const runBulkCheck = async () => {
            if (bulkRunning) {
                return;
            }

            const selected = selectedRows();
            if (selected.length === 0) {
                return;
            }

            startBulkRun(selected, 'Update-Prüfung läuft', 'Prüfung wird gestartet …');

            let completed = 0;
            let errors = 0;

            for (const row of selected) {
                const domain = row.querySelector('.dm-update-domain strong')?.textContent?.trim() || 'Installation';
                const form = findActionForm(row, 'update-check');

                row.classList.add('is-processing');
                setRowState(row, 'running');
                updateProgress(completed, selected.length, `${domain} wird geprüft …`);

                try {
                    if (!form) {
                        throw new Error('Keine Update-Prüfung verfügbar.');
                    }

                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        redirect: 'follow',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const html = await response.text();
                    refreshRowFromResponse(row, html, response.url, 'check');

                    if (row.dataset.updateState === 'error') {
                        errors += 1;
                    }
                } catch (error) {
                    setRowState(row, 'error');
                    errors += 1;
                }

                row.classList.remove('is-processing');
                completed += 1;
                updateAvailableCount();
                updateProgress(completed, selected.length, `${domain}: ${stateLabel(row.dataset.updateState)}`);
            }

            const available = selected.filter((row) => row.dataset.updateState === 'available').length;
            const current = selected.filter((row) => row.dataset.updateState === 'current').length;
            const summary = [
                `${available} ${available === 1 ? 'Update' : 'Updates'} verfügbar`,
                `${current} aktuell`,
                `${errors} Fehler`,
            ].join(' · ');

            if (progressTitle) {
                progressTitle.textContent = 'Update-Prüfung abgeschlossen';
            }
            progress?.classList.add('is-complete');
            if (errors > 0) {
                progress?.classList.add('has-errors');
            }
            updateProgress(selected.length, selected.length, 'Alle ausgewählten Installationen wurden geprüft.', summary);

            finishBulkRun();
        };

        const runBulkInstall = async () => {
            if (bulkRunning) {
                return;
            }

            const selected = installableSelectedRows();
            if (selected.length === 0) {
                return;
            }

            const noun = selected.length === 1 ? 'Update' : 'Updates';
            const confirmation = `${selected.length} ${noun} jetzt nacheinander installieren?\n\nUnmittelbar vor jeder einzelnen Installation wird ein vollständiges Sicherheitsbackup erstellt. Jede Zielinstallation prüft den vorbereiteten Composer-Plan vor dem Start erneut.`;

            if (!window.confirm(confirmation)) {
                return;
            }

            startBulkRun(selected, 'Update-Installation läuft', 'Installation wird gestartet …');

            let completed = 0;
            let installed = 0;
            let warnings = 0;
            let errors = 0;

            for (const row of selected) {
                const domain = row.querySelector('.dm-update-domain strong')?.textContent?.trim() || 'Installation';
                const form = findActionForm(row, 'update-install');

                row.classList.add('is-processing');
                setRowState(row, 'running', 'Installation läuft …');
                updateProgress(completed, selected.length, `${domain} wird aktualisiert …`);

                try {
                    if (!form) {
                        throw new Error('Kein gültig vorbereitetes Update verfügbar.');
                    }

                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        redirect: 'follow',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const html = await response.text();
                    refreshRowFromResponse(row, html, response.url, 'install');

                    if (row.dataset.updateState === 'current') {
                        installed += 1;
                    } else if (row.dataset.updateState === 'warning') {
                        warnings += 1;
                    } else {
                        errors += 1;
                    }
                } catch (error) {
                    setRowState(row, 'error', 'Installation fehlgeschlagen');
                    errors += 1;
                }

                row.classList.remove('is-processing');
                completed += 1;
                updateAvailableCount();

                const resultText = row.dataset.updateState === 'current'
                    ? 'Update installiert'
                    : stateLabel(row.dataset.updateState);
                updateProgress(completed, selected.length, `${domain}: ${resultText}`);
            }

            const summary = [
                `${installed} installiert`,
                `${warnings} ${warnings === 1 ? 'Warnung' : 'Warnungen'}`,
                `${errors} Fehler`,
            ].join(' · ');

            if (progressTitle) {
                progressTitle.textContent = 'Update-Installation abgeschlossen';
            }
            progress?.classList.add('is-complete');
            if (warnings > 0) {
                progress?.classList.add('has-warnings');
            }
            if (errors > 0) {
                progress?.classList.add('has-errors');
            }
            updateProgress(selected.length, selected.length, 'Alle ausgewählten Updates wurden verarbeitet.', summary);

            finishBulkRun();
        };

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled || bulkRunning) {
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
                if (row && !row.hidden && checkbox.dataset.dmCheckEligible === '1') {
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

        checkSelected?.addEventListener('click', runBulkCheck);
        installSelected?.addEventListener('click', runBulkInstall);

        root.addEventListener('submit', (event) => {
            const form = event.target.closest?.('.dm-updates-action-form');
            if (!form || bulkRunning) {
                return;
            }

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
            if (checkSelected) {
                checkSelected.disabled = true;
            }
            if (installSelected) {
                installSelected.disabled = true;
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

        updateAvailableCount();
        apply();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
