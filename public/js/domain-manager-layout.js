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

    const initializeLayout = () => {
        const filter = document.querySelector('[data-domain-manager-filter]');
        const overview = document.querySelector('[data-domain-manager-overview]');

        if (!filter || !overview || document.querySelector('[data-domain-manager-layout]')) {
            return;
        }

        const filterHost = filter.parentElement;
        const overviewHost = overview.parentElement;

        if (!filterHost || !overviewHost) {
            return;
        }

        const layout = document.createElement('div');
        layout.className = 'domain-manager-layout';
        layout.dataset.domainManagerLayout = '1';

        if (filterHost === overviewHost) {
            filterHost.insertBefore(layout, filter);
            layout.appendChild(filter);
            layout.appendChild(overview);
            filter.classList.add('domain-manager-layout-filter');
            overview.classList.add('domain-manager-layout-overview');
            renderDebugInfo(layout);
            return;
        }

        if (!filterHost.parentElement || filterHost.parentElement !== overviewHost.parentElement) {
            return;
        }

        const parent = filterHost.parentElement;
        const filterComesFirst = Boolean(filterHost.compareDocumentPosition(overviewHost) & Node.DOCUMENT_POSITION_FOLLOWING);
        parent.insertBefore(layout, filterComesFirst ? filterHost : overviewHost);

        layout.appendChild(filterHost);
        layout.appendChild(overviewHost);

        filterHost.classList.add('domain-manager-layout-filter');
        overviewHost.classList.add('domain-manager-layout-overview');
        renderDebugInfo(layout);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLayout, { once: true });
    } else {
        initializeLayout();
    }
})();
