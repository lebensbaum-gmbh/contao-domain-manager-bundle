(() => {
    const renderDebugInfo = (layout) => {
        const existing = document.querySelector('[data-domain-manager-debug]');
        if (existing) {
            existing.remove();
        }

        const box = document.createElement('div');
        box.dataset.domainManagerDebug = '1';
        box.style.position = 'fixed';
        box.style.top = '8px';
        box.style.right = '8px';
        box.style.zIndex = '2147483647';
        box.style.maxWidth = '360px';
        box.style.padding = '10px 12px';
        box.style.background = '#111';
        box.style.color = '#fff';
        box.style.font = '12px/1.45 monospace';
        box.style.whiteSpace = 'pre-line';
        box.style.borderRadius = '6px';
        box.style.boxShadow = '0 2px 10px rgba(0,0,0,.35)';

        const update = () => {
            const rect = layout.getBoundingClientRect();
            const mqWidth = window.matchMedia('(max-width: 1180px)').matches;
            const mqTouch = window.matchMedia('(hover: none) and (pointer: coarse)').matches;
            const hoverNone = window.matchMedia('(hover: none)').matches;
            const pointerCoarse = window.matchMedia('(pointer: coarse)').matches;

            box.textContent = [
                'DM layout debug',
                `innerWidth: ${window.innerWidth}`,
                `outerWidth: ${window.outerWidth}`,
                `screen.width: ${window.screen.width}`,
                `visualViewport: ${window.visualViewport ? Math.round(window.visualViewport.width) : 'n/a'}`,
                `layout width: ${Math.round(rect.width)}`,
                `max-width 1180: ${mqWidth}`,
                `hover none: ${hoverNone}`,
                `pointer coarse: ${pointerCoarse}`,
                `touch query: ${mqTouch}`,
                `maxTouchPoints: ${navigator.maxTouchPoints || 0}`,
                `DPR: ${window.devicePixelRatio}`,
            ].join('\n');
        };

        document.body.appendChild(box);
        update();
        window.addEventListener('resize', update);
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', update);
        }
    };

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
            renderDebugInfo(layout);
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

        renderDebugInfo(layout);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLayout, { once: true });
    } else {
        initializeLayout();
    }
})();
