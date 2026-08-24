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
            filterArticle.insertBefore(layout, filter);
            layout.appendChild(filter);
            layout.appendChild(overview);
            filter.classList.add('domain-manager-layout-filter');
            overview.classList.add('domain-manager-layout-overview');
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
