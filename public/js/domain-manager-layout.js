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

    const directChildHost = (node, parent) => {
        let current = node;

        while (current && current.parentElement && current.parentElement !== parent) {
            current = current.parentElement;
        }

        return current && current.parentElement === parent ? current : null;
    };

    const sortByDocumentOrder = (nodes) => Array.from(new Set(nodes.filter(Boolean))).sort((left, right) => {
        if (left === right) {
            return 0;
        }

        return left.compareDocumentPosition(right) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
    });

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

    const initializeUpdatesLayout = () => {
        const updates = document.querySelector('[data-dm-updates]');

        if (!updates || document.querySelector('[data-domain-manager-updates-layout]')) {
            return false;
        }

        const mainInside = document.querySelector('#main > .inside');
        const updateArticle = updates.closest('.mod_article') || updates.parentElement;

        if (!mainInside || !updateArticle) {
            return false;
        }

        const previousParent = updateArticle.parentElement;
        const layout = document.createElement('div');
        layout.className = 'domain-manager-layout domain-manager-layout-updates';
        layout.dataset.domainManagerUpdatesLayout = '1';

        mainInside.appendChild(layout);
        layout.appendChild(updateArticle);
        updateArticle.classList.add('domain-manager-layout-updates-host');

        if (previousParent && previousParent !== mainInside && previousParent.children.length === 0) {
            removeEmptyLegacyShell(previousParent);
        }

        markContainer();

        return true;
    };

    const initializeLayout = () => {
        if (initializeUpdatesLayout()) {
            return;
        }

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
            // The managed workspace keeps its heading/login and overview in the
            // left column while the filter occupies the right column. Existing
            // installations may still have these elements as ordinary siblings
            // in the article, so collect and move their complete host wrappers.
            const hosts = findSiblingHosts(filter, overview);
            if (!hosts) {
                return;
            }

            const filterHost = hosts.filterNode;
            const overviewHost = hosts.overviewNode;
            const headlineHost = directChildHost(
                hosts.parent.querySelector('.content-headline'),
                hosts.parent
            );
            const loginHost = directChildHost(
                hosts.parent.querySelector('.domainverwaltung-login'),
                hosts.parent
            );
            const mainHosts = sortByDocumentOrder([headlineHost, loginHost, overviewHost]);
            const allHosts = sortByDocumentOrder([...mainHosts, filterHost]);
            const firstHost = allHosts[0];

            if (!firstHost) {
                return;
            }

            const main = document.createElement('div');
            main.className = 'domain-manager-layout-main';

            hosts.parent.insertBefore(layout, firstHost);
            layout.appendChild(main);
            layout.appendChild(filterHost);

            mainHosts.forEach((host) => main.appendChild(host));

            filterHost.classList.add('domain-manager-layout-filter');
            overviewHost.classList.add('domain-manager-layout-overview');
            overviewArticle.classList.add('domain-manager-overview-article');
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
