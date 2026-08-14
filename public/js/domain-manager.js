(() => {
    const cleanOverviewQueryParameters = () => {
        const overview = document.querySelector('[data-domain-manager-overview]');
        if (!overview) {
            return;
        }

        const url = new URL(window.location.href);
        ['dm_sync', 'dm_domain', 'dm_synced', 'dm_skipped', 'dm_failed', 'dm_live', 'dm_live_domain']
            .forEach((parameter) => url.searchParams.delete(parameter));

        if (url.href !== window.location.href) {
            window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : '') + url.hash);
        }
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

        const matchesInstallation = (installation, selected, term, domainSearchMatches) => {
            if (!installation) return false;

            const contao = installation.dataset.contao || '';
            const php = installation.dataset.php || '';
            const environment = installation.dataset.environment || '';
            const trakked = installation.dataset.tracked || '0';

            if (selected.contao.length && !selected.contao.includes(contao)) return false;
            if (selected.php.length && !selected.php.includes(php)) return false;
            if (selected.environment.length && !selected.environment.includes(environment)) return false;
            if (selected.trakked.length && !selected.trakked.includes(trakked)) return false;

            if (term && !domainSearchMatches && !normalize(installation.dataset.search).includes(term)) return false;

            return true;
        };

        const apply = () => {
            const overview = document.querySelector('[data-domain-manager-overview]');
            if (!overview) return;

            const selected = {
                contao: getChecked('[data-dm-filter-contao]'),
                php: getChecked('[data-dm-filter-php]'),
                environment: getChecked('[data-dm-filter-environment]'),
                trakked: getChecked('[data-dm-filter-trakked]'),
            };
            const term = normalize(search ? search.value : '');
            const onlyCurrent = Boolean(currentOnly && currentOnly.checked);
            const hasTechnicalFilter = Boolean(
                selected.contao.length
                || selected.php.length
                || selected.environment.length
                || selected.trakked.length
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
        cleanOverviewQueryParameters();
        initializeFilter();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
