(() => {
    const findSiblingHosts = (filterHost, overviewHost) => {
        let filterNode = filterHost;

        while (filterNode && filterNode.parentElement) {
            let overviewNode = overviewHost;

            while (overviewNode && overviewNode.parentElement) {
                if (filterNode !== overviewNode && filterNode.parentElement === overviewNode.parentElement) {
                    return {
                        filterNode,
                        overviewNode,
                        parent: filterNode.parentElement,
                    };
                }

                overviewNode = overviewNode.parentElement;
            }

            filterNode = filterNode.parentElement;
        }

        return null;
    };

    const removeEmptyLegacyShell = (shell) => {
        if (!shell || shell.matches('.mod_article')) {
            return;
        }

        const inside = shell.querySelector(':scope > .inside');
        if (inside && inside.children.length === 0 && shell.children.length === 1) {
            shell.remove();
        }
    };

    const markContainer = () => {
        const container = document.getElementById('container');
        if (container) {
            container.classList.add('domain-manager-layout-container');
            // The legacy page layout reserves space for the former right sidebar
            // with an inline padding-right. The Domain Manager grid now owns that
            // sidebar itself, so keeping the legacy padding would reserve it twice.
            container.style.setProperty('padding-right', '0', 'important');
        }
    };

    const initializeLayout = () => {
        const filter = document.querySelector('[data-domain-manager-filter]');
        const overview = document.querySelector('[data-domain-manager-overview]');

        if (!filter || !overview || document.querySelector('[data-domain-manager-layout]')) {
            return;
        }

        const filterArticle = filter.closest('.mod_article') || filter.parentElement;
        const overviewArticle = overview.closest('.mod_article') || overview.parentElement;

        if (!filterArticle || !overviewArticle) {
            return;
        }

        const layout = document.createElement('div');
        layout.className = 'domain-manager-layout';
        layout.dataset.domainManagerLayout = '1';

        if (filterArticle === overviewArticle) {
            // Both content elements can live in the same Contao article. Their data
            // nodes are usually nested inside content-element wrappers and are not
            // direct children of .mod_article, so insertBefore(layout, filter) would
            // throw a DOMException. Resolve the closest sibling hosts instead and
            // move those complete wrappers into the Domain Manager grid.
            const hosts = findSiblingHosts(filter, overview);
            if (!hosts) {
                return;
            }

            const filterHost = hosts.filterNode;
            const overviewHost = hosts.overviewNode;
            const filterComesFirst = Boolean(filterHost.compareDocumentPosition(overviewHost) & Node.DOCUMENT_POSITION_FOLLOWING);

            hosts.parent.insertBefore(layout, filterComesFirst ? filterHost : overviewHost);
            layout.appendChild(filterHost);
            layout.appendChild(overviewHost);

            filterHost.classList.add('domain-manager-layout-filter');
            overviewHost.classList.add('domain-manager-layout-overview');
            markContainer();
            return;
        }

        const hosts = findSiblingHosts(filterArticle, overviewArticle);
        if (!hosts) {
            return;
        }

        const filterShell = hosts.filterNode;
        const overviewShell = hosts.overviewNode;
        const filterComesFirst = Boolean(filterShell.compareDocumentPosition(overviewShell) & Node.DOCUMENT_POSITION_FOLLOWING);

        hosts.parent.insertBefore(layout, filterComesFirst ? filterShell : overviewShell);

        layout.appendChild(filterArticle);
        layout.appendChild(overviewArticle);

        filterArticle.classList.add('domain-manager-layout-filter');
        overviewArticle.classList.add('domain-manager-layout-overview');

        removeEmptyLegacyShell(filterShell);
        removeEmptyLegacyShell(overviewShell);
        markContainer();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLayout, { once: true });
    } else {
        initializeLayout();
    }
})();
