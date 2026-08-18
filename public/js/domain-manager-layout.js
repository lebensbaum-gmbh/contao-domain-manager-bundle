(() => {
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
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLayout, { once: true });
    } else {
        initializeLayout();
    }
})();
