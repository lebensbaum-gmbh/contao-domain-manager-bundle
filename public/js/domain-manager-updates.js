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
        const checkFailureCount = root.querySelector('[data-dm-check-failure-count]');
        const checkFailureSummary = root.querySelector('[data-dm-check-failure-summary]');
        const installFailureCount = root.querySelector('[data-dm-install-failure-count]');
        const installFailureSummary = root.querySelector('[data-dm-install-failure-summary]');
        const previousResult = root.querySelector('.dm-updates-result');
        let filter = rows.some(row => row.classList.contains('is-result') || row.querySelector('[data-dm-update-backups][open]')) ? 'all' : (root.dataset.dmWorkspaceDefault || 'all');
        let bulkRunning = false;
        let backupRunning = false;
        let restoreRunning = false;

        // Warn before a user closes the page while the remote restore is still active.
        const warnDuringRestore = (event) => {
            if (!restoreRunning) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        };
        window.addEventListener('beforeunload', warnDuringRestore);

        const clearTransientResultParams = () => {
            try {
                const url = new URL(window.location.href);
                const params = ['dm_update', 'dm_update_install', 'dm_installation'];
                let changed = false;

                params.forEach((name) => {
                    if (url.searchParams.has(name)) {
                        url.searchParams.delete(name);
                        changed = true;
                    }
                });

                if (changed && window.history?.replaceState) {
                    window.history.replaceState(window.history.state, '', url.href);
                }
            } catch (error) {
                // A stale result URL must never block the update interface.
            }
        };

        clearTransientResultParams();

        const findActionForm = (row, name) => row?.querySelector(`.dm-updates-action-form[data-dm-action="${name}"]`) || null;

        const stateLabel = (state) => {
            switch (state) {
                case 'available':
                    return 'Update verfügbar';
                case 'current':
                    return 'Aktuell';
                case 'error':
                    return 'Update fehlgeschlagen';
                case 'check_failed':
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
                element.className = `dm-update-state is-${state}` + (state === 'available' && Number(row.dataset.releaseGap) >= 5 ? ' has-backlog' : '');
                element.textContent = state === 'available' && Number(row.dataset.releaseGap) >= 5 ? `Wartungsrückstand · ${row.dataset.releaseGap} Versionen` : label;
            }
            window.MaintendWorkspace?.apply(root);
        };

        const updateAvailableCount = () => {
            const counts = {
                available: 0,
                check_failed: 0,
                error: 0,
            };

            rows.forEach((row) => {
                if (Object.prototype.hasOwnProperty.call(counts, row.dataset.updateState)) {
                    counts[row.dataset.updateState] += 1;
                }
            });

            if (availableCount) {
                availableCount.textContent = String(counts.available);

                const label = availableCount.nextElementSibling;
                if (label) {
                    label.textContent = counts.available === 1 ? 'bekanntes Update' : 'bekannte Updates';
                }
            }

            if (checkFailureCount) {
                checkFailureCount.textContent = String(counts.check_failed);
                const label = checkFailureCount.nextElementSibling;
                if (label) {
                    label.textContent = counts.check_failed === 1 ? 'Prüfung fehlgeschlagen' : 'Prüfungen fehlgeschlagen';
                }
            }

            if (installFailureCount) {
                installFailureCount.textContent = String(counts.error);
                const label = installFailureCount.nextElementSibling;
                if (label) {
                    label.textContent = counts.error === 1 ? 'Update fehlgeschlagen' : 'Updates fehlgeschlagen';
                }
            }

            if (checkFailureSummary) {
                checkFailureSummary.hidden = counts.check_failed === 0;
            }

            if (installFailureSummary) {
                installFailureSummary.hidden = counts.error === 0;
            }
        };

        const selectedRows = () => rowCheckboxes
            .filter((checkbox) => checkbox.checked && !checkbox.disabled)
            .map((checkbox) => checkbox.closest('[data-dm-update-row]'))
            .filter(Boolean)
            .sort((a, b) => (window.MaintendWorkspace?.priority(a) || 0) - (window.MaintendWorkspace?.priority(b) || 0));

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
                const stateMatches = 'all' === filter
                    || row.dataset.updateState === filter
                    || ('error' === filter && ['check_failed', 'warning'].includes(row.dataset.updateState))
                    || ('attention' === filter && ['available', 'unknown', 'error', 'check_failed', 'warning', 'running'].includes(row.dataset.updateState));
                const searchMatches = '' === term || String(row.dataset.search || '').includes(term);
                const show = stateMatches && searchMatches;
                row.dataset.dmExternalMatch = show ? '1' : '0';
                row.hidden = !show;

                if (show) {
                    visible += 1;
                }
            });

            if (empty) {
                empty.hidden = visible > 0;
            }

            root.dataset.dmWorkspaceScope = filter;
            window.MaintendWorkspace?.apply(root);
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
                    return 'check_failed';
                }
            } catch (error) {
                return action === 'install' ? 'error' : 'check_failed';
            }

            return action === 'install' ? 'error' : 'check_failed';
        };

        const refreshRowFromResponse = (row, html, responseUrl, action = 'check') => {
            const installationId = String(row.dataset.installationId || '');
            let refreshed = false;

            if (/^\d+$/.test(installationId)) {
                const documentFromResponse = new DOMParser().parseFromString(html, 'text/html');
                const resultRow = documentFromResponse.querySelector(`[data-dm-update-row][data-installation-id="${installationId}"]`);

                if (resultRow) {
                    row.dataset.releaseGap = resultRow.dataset.releaseGap || '';
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

                    // Bulk checks use AJAX. Update the saved plan and inline error
                    // along with the state so the operator never sees stale details.
                    const currentExtra = row.querySelector('[data-dm-update-extra]');
                    const resultExtra = resultRow.querySelector('[data-dm-update-extra]');

                    if (resultExtra && currentExtra) {
                        currentExtra.replaceWith(resultExtra.cloneNode(true));
                    } else if (resultExtra) {
                        row.appendChild(resultExtra.cloneNode(true));
                    } else if (currentExtra) {
                        currentExtra.remove();
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
            root.dataset.dmWorkspaceBusy = locked ? '1' : '0';
            root.querySelectorAll('[data-dm-workspace-scope], [data-dm-workspace-view], .dm-workspace-pages button, .dm-workspace-pages select').forEach(control => { control.disabled = locked; });
            rows.forEach(row => {
                row.dataset.dmMaintenanceRunning = row.querySelector('[data-dm-update-backups][aria-busy="true"]') ? '1' : '0';
            });
            window.MaintendWorkspace?.apply(root);
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
            window.MaintendWorkspace?.begin(root);
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

                    if (row.dataset.updateState === 'error' || row.dataset.updateState === 'check_failed') {
                        errors += 1;
                    }
                } catch (error) {
                    setRowState(row, 'check_failed');
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
                root.dataset.dmWorkspaceScope = filter;
                root.dispatchEvent(new CustomEvent('dm:workspace-filtered'));
                filterButtons.forEach((candidate) => candidate.classList.toggle('is-active', candidate === button));
                apply();
            });
        });

        search?.addEventListener('input', () => { if (search.value.trim()) filter = 'all'; apply(); });
        root.addEventListener('dm:workspace-visible', updateSelection);
        root.addEventListener('dm:workspace-scope', event => { filter = event.detail; apply(); });

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

        const runSingleAction = async (form, row, actionName) => {
            const domain = row.querySelector('.dm-update-domain strong')?.textContent?.trim() || 'Installation';
            const isInstall = actionName === 'update-install';
            const title = isInstall ? 'Update-Installation läuft' : 'Update-Prüfung läuft';
            const startText = isInstall
                ? `${domain} wird aktualisiert …`
                : `${domain} wird geprüft …`;

            startBulkRun([row], title, startText);
            row.classList.add('is-processing');
            setRowState(row, 'running', isInstall ? 'Installation läuft …' : 'Prüfung läuft …');

            let errors = 0;
            let warnings = 0;

            try {
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
                refreshRowFromResponse(row, html, response.url, isInstall ? 'install' : 'check');

                if (row.dataset.updateState === 'error') {
                    errors = 1;
                } else if (row.dataset.updateState === 'warning') {
                    warnings = 1;
                }
            } catch (error) {
                setRowState(
                    row,
                    'error',
                    isInstall ? 'Installation fehlgeschlagen' : 'Prüfung fehlgeschlagen'
                );
                errors = 1;
            }

            row.classList.remove('is-processing');
            updateAvailableCount();

            if (progressTitle) {
                progressTitle.textContent = isInstall
                    ? 'Update-Installation abgeschlossen'
                    : 'Update-Prüfung abgeschlossen';
            }

            progress?.classList.add('is-complete');
            if (warnings > 0) {
                progress?.classList.add('has-warnings');
            }
            if (errors > 0) {
                progress?.classList.add('has-errors');
            }

            const resultText = isInstall
                ? (row.dataset.updateState === 'current' ? 'Update installiert' : stateLabel(row.dataset.updateState))
                : stateLabel(row.dataset.updateState);
            const summary = errors > 0
                ? '1 Fehler'
                : warnings > 0
                    ? '1 Warnung'
                    : isInstall
                        ? 'Update erfolgreich verarbeitet'
                        : 'Prüfung erfolgreich abgeschlossen';

            updateProgress(1, 1, `${domain}: ${resultText}`, summary);

            if (progressCounter && errors === 0) {
                progressCounter.textContent = isInstall ? 'Schritt 6 / 6' : 'Abgeschlossen';
            }

            finishBulkRun();
        };


        const restoreBackup = async (form, row) => {
            const panel = form.closest('[data-dm-update-backups]');
            const progressElement = panel?.querySelector('[data-dm-backup-progress]');
            const button = form.querySelector('button[type="submit"]');
            const installationId = String(row.dataset.installationId || '');

            if (!panel || !progressElement || !button || !/^\d+$/.test(installationId)) {
                // If progressive enhancement is unavailable, preserve the native POST.
                form.submit();
                return;
            }

            // Capture the operator-confirmed domain and acknowledgement before
            // disabling inputs. Browser constraint validation ran at submit time.
            const payload = new FormData(form);
            const originalLabel = button.textContent || 'Diese Sicherung wiederherstellen';
            const domainInput = form.querySelector('input[name="confirm_domain"]');
            const acknowledgeInput = form.querySelector('input[name="acknowledge_restore"]');
            const start = Date.now();
            const remoteStatusUrl = panel.dataset.dmRestoreProgressUrl || '';
            let currentPhase = '';
            let statusPolling = false;
            let statusUnknown = false;

            const pollRestore = async () => {
                if (!restoreRunning || !remoteStatusUrl || statusPolling) {
                    return;
                }

                statusPolling = true;
                try {
                    const response = await fetch(remoteStatusUrl, {
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!response.ok) {
                        return; // Temporary unavailability during cache rebuild.
                    }
                    const progress = await response.json();
                    if (['pending', 'running', 'success', 'error'].includes(progress.status)
                        && typeof progress.message === 'string'
                        && progress.message.trim() !== '') {
                        currentPhase = progress.message.slice(0, 300);
                        updateRestoreTime();
                    }
                } catch (error) {
                    // Restore may rebuild the remote kernel; polling errors
                    // are not proof of failure. Only the POST result is final.
                } finally {
                    statusPolling = false;
                }
            };

            restoreRunning = true;
            backupRunning = true;
            panel.open = true;
            panel.setAttribute('aria-busy', 'true');
            button.disabled = true;
            button.textContent = 'Wiederherstellung läuft …';
            if (domainInput) {
                domainInput.disabled = true;
            }
            if (acknowledgeInput) {
                acknowledgeInput.disabled = true;
            }

            progressElement.hidden = false;
            progressElement.classList.add('is-running', 'is-restore');
            progressElement.classList.remove('is-error');
            const updateRestoreTime = () => {
                const seconds = Math.floor((Date.now() - start) / 1000);
                const phase = currentPhase ? currentPhase + ' ' : '';
                progressElement.textContent = phase
                    + 'Wiederherstellung läuft seit ' + seconds + ' Sekunden. '
                    + 'Keine Updates, Sicherungen oder andere Aktionen starten. Bitte die Seite geöffnet lassen.';
            };
            updateRestoreTime();
            setBulkUiLocked(true);

            // The affected backup can be far down the history. Make the warning visible.
            progressElement.scrollIntoView({ block: 'center', behavior: 'smooth' });
            const timer = window.setInterval(updateRestoreTime, 5000);
            const statusTimer = window.setInterval(() => { void pollRestore(); }, 3000);

            let confirmedResult = false;
            let navigating = false;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: payload,
                    credentials: 'same-origin',
                    redirect: 'follow',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const resultUrl = new URL(response.url, window.location.href);
                const status = resultUrl.searchParams.get('dm_backup_restore');
                const resultId = resultUrl.searchParams.get('dm_backup_installation');

                if (resultUrl.origin !== window.location.origin
                    || !['success', 'success_sync_warning', 'error', 'confirmation_invalid'].includes(status)
                    || resultId !== installationId) {
                    throw new Error('Das Wiederherstellungsergebnis konnte nicht eindeutig zugeordnet werden.');
                }

                // These are *server* statuses. Never claim restore success based
                // on the fetch status or an elapsed-time estimate alone.
                confirmedResult = true;
                progressElement.textContent = 'Die Zielinstallation hat geantwortet. Ergebnis wird angezeigt …';

                // A restore can change installed versions and row metadata.
                // Navigate to the server-rendered result rather than keeping stale UI.
                restoreRunning = false;
                navigating = true;
                window.location.assign(resultUrl.href);
            } catch (error) {
                progressElement.hidden = false;
                progressElement.classList.remove('is-running');
                progressElement.classList.add('is-error');
                progressElement.textContent = confirmedResult
                    ? 'Die Wiederherstellung wurde vom Server beantwortet, aber die Ergebnisansicht konnte nicht geöffnet werden. Bitte den Zustand der Installation prüfen.'
                    : 'Der Wiederherstellungsstatus ist unklar. Die Zielinstallation könnte teilweise verändert sein. Bitte keine weitere Sicherung, Wiederherstellung oder Aktualisierung starten, bevor Website, Sicherungsverlauf und Serverprotokolle geprüft wurden.';
                statusUnknown = true;
                // Never let a lost response trigger an accidental second restore.
                button.disabled = true;
                button.textContent = 'Status prüfen';
            } finally {
                window.clearInterval(timer);
                window.clearInterval(statusTimer);
                restoreRunning = statusUnknown;
                backupRunning = statusUnknown;
                if (!statusUnknown) {
                    panel.removeAttribute('aria-busy');
                }

                if (!navigating && !statusUnknown) {
                    if (!progressElement.classList.contains('is-error')) {
                        button.disabled = false;
                        button.textContent = originalLabel;
                    }
                    setBulkUiLocked(false);
                    updateSelection();
                }
            }
        };

        const createManualBackup = async (form, row) => {
            const panel = form.closest('[data-dm-update-backups]');
            const progressElement = panel?.querySelector('[data-dm-backup-progress]');
            const button = form.querySelector('button[type="submit"]');
            const originalLabel = button?.textContent || 'Jetzt sichern';
            const installationId = String(row.dataset.installationId || '');

            if (!panel || !progressElement || !button || !/^\d+$/.test(installationId)) {
                // The normal POST form remains a fallback if the UI is incomplete.
                form.submit();
                return;
            }

            backupRunning = true;
            panel.open = true;
            panel.setAttribute('aria-busy', 'true');
            button.disabled = true;
            button.textContent = 'Sicherung läuft …';
            progressElement.hidden = false;
            progressElement.classList.add('is-running');
            progressElement.classList.remove('is-error');
            progressElement.textContent = 'Sicherung wird erstellt … Datenbank und Projektdateien werden gesichert. Bitte warten und die Seite nicht schließen.';
            setBulkUiLocked(true);

            // No remote percentage endpoint exists for backups. Indicate
            // elapsed time instead of suggesting an invented percentage.
            const start = Date.now();
            const timer = window.setInterval(() => {
                if (panel.isConnected) {
                    const seconds = Math.floor((Date.now() - start) / 1000);
                    progressElement.textContent = 'Sicherung läuft seit ' + seconds + ' Sekunden. Bitte warten und die Seite nicht schließen.';
                }
            }, 5000);

            let confirmedResult = false;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    redirect: 'follow',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const responseUrl = new URL(response.url, window.location.href);
                const status = responseUrl.searchParams.get('dm_backup_create');
                const resultId = responseUrl.searchParams.get('dm_backup_installation');

                if (responseUrl.origin !== window.location.origin
                    || !['success', 'error'].includes(status)
                    || resultId !== installationId) {
                    throw new Error('Das Sicherungsergebnis konnte nicht eindeutig bestätigt werden.');
                }

                confirmedResult = true;
                const html = await response.text();
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const updatedPanel = parsed.querySelector(
                    '[data-dm-update-row][data-installation-id="' + installationId + '"] [data-dm-update-backups]'
                );

                if (updatedPanel) {
                    const replacement = updatedPanel.cloneNode(true);
                    replacement.open = true;
                    panel.replaceWith(replacement);
                } else {
                    // Result confirmed but refreshed markup not available.
                    window.location.assign(response.url);
                }
            } catch (error) {
                progressElement.hidden = false;
                progressElement.classList.remove('is-running');
                progressElement.classList.add('is-error');
                progressElement.textContent = confirmedResult
                    ? 'Die Sicherungsantwort liegt vor, aber die Anzeige konnte nicht aktualisiert werden. Bitte die Seite neu laden und die Sicherungshistorie prüfen.'
                    : 'Der Sicherungsstatus ist unklar. Bitte die Seite neu laden und die Sicherungshistorie prüfen, bevor du eine weitere Sicherung startest.';
                // If the outcome is unknown, do not permit a duplicate submission.
                button.disabled = true;
                button.textContent = 'Status prüfen';
            } finally {
                window.clearInterval(timer);
                backupRunning = false;
                panel.removeAttribute('aria-busy');
                if (panel.isConnected && !progressElement.classList.contains('is-error')) {
                    button.disabled = false;
                    button.textContent = originalLabel;
                }
                setBulkUiLocked(false);
                updateSelection();
            }
        };

        root.addEventListener('submit', (event) => {
            const form = event.target;
            if (!form?.matches?.('.dm-backup-create-form')) {
                if (backupRunning) {
                    event.preventDefault();
                }
                return;
            }

            if (backupRunning || bulkRunning) {
                event.preventDefault();
                return;
            }

            const row = form.closest('[data-dm-update-row]');
            if (!row) {
                return; // Normal POST fallback.
            }

            event.preventDefault();
            void createManualBackup(form, row);
        });

        root.addEventListener('submit', (event) => {
            const form = event.target;
            if (!form?.matches?.('.dm-backup-restore-form')) {
                return;
            }

            if (backupRunning || bulkRunning) {
                event.preventDefault();
                return;
            }

            const row = form.closest('[data-dm-update-row]');
            if (!row) {
                return; // Native POST fallback.
            }

            event.preventDefault();
            void restoreBackup(form, row);
        });

        // The delete request uses a standard POST, but must still show immediate feedback.
        root.addEventListener('submit', (event) => {
            const form = event.target;
            if (!form?.matches?.('.dm-backup-delete-form')) {
                return;
            }

            if (backupRunning || bulkRunning) {
                event.preventDefault();
                return;
            }

            backupRunning = true;
            const button = form.querySelector('button[type="submit"]');
            const panel = form.closest('[data-dm-update-backups]');
            const progress = panel?.querySelector('[data-dm-backup-progress]');

            if (button) {
                button.disabled = true;
                button.textContent = 'Sicherung wird gelöscht …';
            }

            if (panel) {
                panel.open = true;
                panel.setAttribute('aria-busy', 'true');
            }

            if (progress) {
                progress.hidden = false;
                progress.classList.remove('is-error');
                progress.classList.add('is-running');
                progress.textContent = 'Sicherung wird auf der Zielinstallation gelöscht. Bitte warten …';
            }

            setBulkUiLocked(true);
        });

        root.addEventListener('submit', (event) => {
            const form = event.target.closest?.('.dm-updates-action-form');
            if (!form) {
                return;
            }

            if (bulkRunning || backupRunning) {
                event.preventDefault();
                return;
            }

            const actionName = String(form.dataset.dmAction || '').trim();

            if (!['update-check', 'update-install'].includes(actionName)) {
                return;
            }

            const button = form.querySelector('button[type="submit"]');
            const row = form.closest('[data-dm-update-row]');

            if (!button || !row) {
                return;
            }

            event.preventDefault();

            const confirmText = button.dataset.dmConfirm || '';
            if (confirmText && !window.confirm(confirmText)) {
                return;
            }

            void runSingleAction(form, row, actionName);
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
