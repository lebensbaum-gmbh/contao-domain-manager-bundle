(() => {
    const globalSyncParameters = [
        'dm_sync_all',
        'dm_all_domains',
        'dm_all_success',
        'dm_all_partial',
        'dm_all_domain_failed',
        'dm_all_synced',
        'dm_all_skipped',
        'dm_all_failed',
        'dm_all_live_errors',
    ];

    const cleanOverviewQueryParameters = () => {
        const overview = document.querySelector('[data-domain-manager-overview]');
        if (!overview) {
            return;
        }

        const url = new URL(window.location.href);
        [
            'dm_sync',
            'dm_domain',
            'dm_synced',
            'dm_skipped',
            'dm_failed',
            'dm_live',
            'dm_live_domain',
            ...globalSyncParameters,
        ].forEach((parameter) => url.searchParams.delete(parameter));

        if (url.href !== window.location.href) {
            window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : '') + url.hash);
        }
    };

    const initializeGlobalSync = () => {
        const overview = document.querySelector('[data-domain-manager-overview]');
        if (!overview || overview.dataset.dmGlobalSyncInitialized === '1') {
            return;
        }

        const firstDomainForm = overview.querySelector('.domain-sync-form');
        if (!firstDomainForm) {
            return;
        }

        overview.dataset.dmGlobalSyncInitialized = '1';

        let actions = overview.querySelector('.domain-manager-global-actions');
        if (!actions) {
            actions = document.createElement('div');
            actions.className = 'domain-manager-global-actions';
            overview.prepend(actions);
        }

        const tokenInput = firstDomainForm.querySelector('input[name="REQUEST_TOKEN"]');
        const form = document.createElement('form');
        form.className = 'domain-sync-all-form';
        form.method = 'post';

        const action = new URL(firstDomainForm.action, window.location.href);
        action.pathname = action.pathname.replace(/\/\d+\/?$/, '');
        form.action = action.toString();

        if (tokenInput) {
            const tokenClone = tokenInput.cloneNode(true);
            form.appendChild(tokenClone);
        }

        const button = document.createElement('button');
        button.className = 'domain-sync-button domain-sync-all-button';
        button.type = 'submit';
        button.title = 'Systeminformationen aller Hauptdomains aktualisieren';
        button.innerHTML = '<span aria-hidden="true">↻</span> Alle Systemdaten aktualisieren';
        form.appendChild(button);

        form.addEventListener('submit', (event) => {
            if (!window.confirm('Systemdaten aller Hauptdomains aktualisieren?')) {
                event.preventDefault();
                return;
            }

            button.disabled = true;
            button.textContent = 'Aktualisierung läuft …';
        });

        actions.prepend(form);

        const url = new URL(window.location.href);
        const status = url.searchParams.get('dm_sync_all');
        if (!status) {
            return;
        }

        const message = document.createElement('div');
        message.className = 'domain-sync-all-message ' + (
            status === 'success' ? 'is-success' : (status === 'partial' ? 'is-warning' : 'is-error')
        );
        message.setAttribute('role', 'status');

        if (status === 'error') {
            message.innerHTML = '<strong>Die Sammelsynchronisation ist fehlgeschlagen.</strong>';
        } else {
            const domains = Number(url.searchParams.get('dm_all_domains') || 0);
            const success = Number(url.searchParams.get('dm_all_success') || 0);
            const partial = Number(url.searchParams.get('dm_all_partial') || 0);
            const domainFailed = Number(url.searchParams.get('dm_all_domain_failed') || 0);
            const synced = Number(url.searchParams.get('dm_all_synced') || 0);
            const skipped = Number(url.searchParams.get('dm_all_skipped') || 0);
            const failed = Number(url.searchParams.get('dm_all_failed') || 0);
            const liveErrors = Number(url.searchParams.get('dm_all_live_errors') || 0);

            const heading = status === 'success'
                ? 'Sammelsynchronisation abgeschlossen.'
                : 'Sammelsynchronisation teilweise abgeschlossen.';

            const lines = [
                `${domains} ${domains === 1 ? 'Hauptdomain' : 'Hauptdomains'} verarbeitet: ${success} erfolgreich, ${partial} teilweise, ${domainFailed} fehlgeschlagen.`,
                `${synced} ${synced === 1 ? 'Installation' : 'Installationen'} synchronisiert, ${skipped} übersprungen, ${failed} fehlgeschlagen.`,
            ];

            if (liveErrors > 0) {
                lines.push(
                    liveErrors === 1
                        ? '1 Zielermittlung konnte nicht abgeschlossen werden.'
                        : `${liveErrors} Zielermittlungen konnten nicht abgeschlossen werden.`
                );
            }

            const strong = document.createElement('strong');
            strong.textContent = heading;
            message.appendChild(strong);

            lines.forEach((line) => {
                message.appendChild(document.createElement('br'));
                message.appendChild(document.createTextNode(line));
            });
        }

        actions.insertAdjacentElement('afterend', message);
    };

    const initializeFilter = () => {
        const filter = document.querySelector('[data-domain-manager-filter]');
        if (!filter || filter.dataset.dmInitialized === '1') {
            return;
        }

        filter.dataset.dmInitialized = '1';

        const search = filter.querySelector('[data-dm-filter-search]');
        const currentOnly = filter.querySelector('[data-dm-filter-current]');
        const reset = filter.querySelector('[data-dm-filter-reset]');
        const rememberedOpenState = new WeakMap();
        let filterWasActive = false;

        const getChecked = (selector) => Array.from(filter.querySelectorAll(selector + ':checked')).map((input) => input.value);
        const normalize = (value) => String(value || '').toLocaleLowerCase('de-DE').trim();

        const getHealthStatus = (installation) => {
            const badge = installation.querySelector('.health-badge');
            if (!badge) return '';
            if (badge.classList.contains('is-error')) return 'error';
            if (badge.classList.contains('is-warning')) return 'warning';
            if (badge.classList.contains('is-ok')) return 'ok';
            return '';
        };

        const matchesInstallation = (installation, selected, term, domainSearchMatches) => {
            if (!installation) return false;

            const contao = installation.dataset.contao || '';
            const php = installation.dataset.php || '';
            const environment = installation.dataset.environment || '';
            const services = (installation.dataset.services || '').split(/\s+/).filter(Boolean);
            const health = getHealthStatus(installation);

            if (selected.health.length && !selected.health.includes(health)) return false;
            if (selected.contao.length && !selected.contao.includes(contao)) return false;
            if (selected.php.length && !selected.php.includes(php)) return false;
            if (selected.environment.length && !selected.environment.includes(environment)) return false;

            if (selected.services.length) {
                const matchNone = selected.services.includes('none') && services.length === 0;
                const matchService = selected.services
                    .filter((serviceId) => serviceId !== 'none')
                    .some((serviceId) => services.includes(serviceId));

                if (!matchNone && !matchService) return false;
            }

            if (term && !domainSearchMatches && !normalize(installation.dataset.search).includes(term)) return false;

            return true;
        };

        const apply = () => {
            const overview = document.querySelector('[data-domain-manager-overview]');
            if (!overview) return;

            const selected = {
                health: getChecked('[data-dm-filter-health]'),
                contao: getChecked('[data-dm-filter-contao]'),
                php: getChecked('[data-dm-filter-php]'),
                environment: getChecked('[data-dm-filter-environment]'),
                services: getChecked('[data-dm-filter-service]'),
            };
            const term = normalize(search ? search.value : '');
            const onlyCurrent = Boolean(currentOnly && currentOnly.checked);
            const hasTechnicalFilter = Boolean(
                selected.health.length
                || selected.contao.length
                || selected.php.length
                || selected.environment.length
                || selected.services.length
            );
            const filterIsActive = Boolean(term || onlyCurrent || hasTechnicalFilter);

            const domains = Array.from(overview.querySelectorAll('[data-dm-domain]'));

            if (filterIsActive && !filterWasActive) {
                domains.forEach((domain) => rememberedOpenState.set(domain, domain.open));
            }

            let visibleDomains = 0;

            domains.forEach((domain) => {
                const domainName = normalize(domain.querySelector('.domain-name')?.textContent || '');
                const domainSearchMatches = Boolean(term && domainName.includes(term));
                const target = domain.querySelector('.installation-featured[data-dm-installation]');
                const others = Array.from(domain.querySelectorAll('.installation-item[data-dm-installation]'));
                const allInstallations = [target, ...others].filter(Boolean);

                if (!filterIsActive) {
                    domain.hidden = false;
                    if (rememberedOpenState.has(domain)) {
                        domain.open = rememberedOpenState.get(domain);
                    }

                    if (target) target.hidden = false;
                    others.forEach((installation) => { installation.hidden = false; });

                    const othersSection = domain.querySelector('.other-installations');
                    if (othersSection) othersSection.hidden = false;

                    visibleDomains += 1;
                    return;
                }

                const candidates = onlyCurrent ? (target ? [target] : []) : allInstallations;
                const matchingCandidates = candidates.filter((installation) =>
                    matchesInstallation(installation, selected, term, domainSearchMatches)
                );

                const visible = matchingCandidates.length > 0;
                domain.hidden = !visible;

                if (!visible) {
                    if (target) target.hidden = true;
                    others.forEach((installation) => { installation.hidden = true; });
                    const othersSection = domain.querySelector('.other-installations');
                    if (othersSection) othersSection.hidden = true;
                    return;
                }

                visibleDomains += 1;
                domain.open = true;

                const targetMatches = Boolean(
                    target
                    && candidates.includes(target)
                    && matchesInstallation(target, selected, term, domainSearchMatches)
                );
                if (target) target.hidden = !targetMatches;

                others.forEach((installation) => {
                    const matches = !onlyCurrent
                        && matchesInstallation(installation, selected, term, domainSearchMatches);
                    installation.hidden = !matches;
                });

                const othersSection = domain.querySelector('.other-installations');
                if (othersSection) {
                    const visibleOthers = others.some((installation) => !installation.hidden);
                    othersSection.hidden = !visibleOthers;
                }
            });

            filterWasActive = filterIsActive;

            let empty = overview.querySelector('[data-dm-filter-empty]');
            if (!empty) {
                empty = document.createElement('div');
                empty.dataset.dmFilterEmpty = '1';
                empty.className = 'dm-filter-empty';
                empty.textContent = 'Keine Installationen entsprechen den gewählten Filtern.';
                overview.appendChild(empty);
            }
            empty.hidden = visibleDomains > 0;
        };

        filter.addEventListener('input', apply);
        filter.addEventListener('change', apply);

        if (reset) {
            reset.addEventListener('click', () => {
                if (search) search.value = '';
                filter.querySelectorAll('input[type="checkbox"]').forEach((input) => { input.checked = false; });
                apply();
                search?.focus();
            });
        }

        apply();
    };

    const initialize = () => {
        initializeGlobalSync();
        cleanOverviewQueryParameters();
        initializeFilter();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
