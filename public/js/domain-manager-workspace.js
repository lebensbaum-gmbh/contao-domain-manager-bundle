(() => {
    const instances = new WeakMap();
    const text = (tag, className, value) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (value !== undefined) node.textContent = value;
        return node;
    };
    const priority = (row) => {
        const state = row.dataset.updateState || row.dataset.dmHealth || 'ok';
        if (state === 'error') return 0;
        if (Number(row.dataset.releaseGap) >= 5) return 1;
        if (['check_failed', 'warning'].includes(state)) return 2;
        if (state === 'available') return 3;
        if (state === 'unknown') return 4;
        if (state === 'running') return 5;
        if (Number(row.dataset.dmErrors) > 0) return 0;
        if (Number(row.dataset.dmAttention) > 0) return 3;
        return 9;
    };
    const create = (root) => {
        const updates = root.dataset.dmWorkspace === 'updates';
        const list = root.querySelector(updates ? '[data-dm-update-list]' : '.domain-manager-list');
        if (!list) return { apply() {}, begin() {} };
        const rows = Array.from(list.querySelectorAll(updates ? '[data-dm-update-row]' : '[data-dm-domain]'));
        const groups = new Map();
        let scope = root.dataset.dmWorkspaceScope || root.dataset.dmWorkspaceDefault || 'all';
        let view = 'list';
        let page = 0;
        let pageSize = 10;
        let wasBusy = false;
        let forceFlat = false;
        let resultRow = rows.find(row => row.classList.contains('is-result') || row.querySelector('[data-dm-update-backups][open], .domain-sync-message')) || null;
        if (resultRow) { scope = 'all'; root.dataset.dmWorkspaceScope = 'all'; }
        try { view = localStorage.getItem('maintend.workspace.view.' + root.dataset.dmWorkspace) === 'tiles' ? 'tiles' : 'list'; } catch (_) { /* Storage is optional. */ }

        const pager = text('nav', 'dm-workspace-pages');
        pager.setAttribute('aria-label', 'Seiten der ' + (updates ? 'Updates' : 'Domainübersicht'));
        const range = text('span'); range.setAttribute('aria-live', 'polite');
        const controls = text('div', 'dm-workspace-page-controls');
        const label = text('label', '', 'Einträge ');
        const size = text('select'); size.setAttribute('aria-label', 'Einträge pro Seite');
        [10, 20, 40].forEach(value => { const option = text('option', '', String(value)); option.value = String(value); size.append(option); });
        label.append(size);
        const previous = text('button', '', 'Zurück'); previous.type = 'button';
        const counter = text('span');
        const next = text('button', '', 'Weiter'); next.type = 'button';
        controls.append(label, previous, counter, next); pager.append(range, controls); list.after(pager);

        const wrapDetails = () => rows.forEach(row => {
            const extra = row.querySelector('[data-dm-update-extra]');
            if (!extra || extra.parentElement?.classList.contains('dm-workspace-detail')) return;
            const wrapper = text('details', 'dm-workspace-detail');
            const summary = text('summary', '', 'Paketplan und Sicherungen anzeigen');
            wrapper.open = Boolean(extra.querySelector('details[open]'));
            extra.before(wrapper); wrapper.append(summary, extra);
        });
        const updateGroup = (group, members) => {
            const best = [...members].sort((a, b) => priority(a) - priority(b))[0];
            const source = best.querySelector('.dm-update-state');
            group.badge.replaceChildren();
            if (source) group.badge.append(source.cloneNode(true));
            group.count.textContent = members.length + (members.length === 1 ? ' Installation' : ' Installationen');
        };
        const groupFor = (row) => {
            const key = row.dataset.parentId && row.dataset.parentId !== '0' ? 'domain-' + row.dataset.parentId : 'installation-' + row.dataset.installationId;
            if (groups.has(key)) return groups.get(key);
            const element = text('details', 'dm-workspace-group');
            const summary = text('summary');
            const thumbnail = row.querySelector('.dm-update-thumbnail')?.cloneNode(true) || text('span');
            const name = text('span', 'dm-workspace-group-name', row.dataset.parentDomain || row.querySelector('.dm-update-domain strong')?.textContent || 'Installation');
            const count = text('span', 'dm-workspace-group-count');
            const badge = text('span');
            const chevron = text('span', 'dm-workspace-group-chevron'); chevron.setAttribute('aria-hidden', 'true');
            summary.append(thumbnail, name, count, badge, chevron);
            const children = text('div', 'dm-workspace-group-children');
            element.append(summary, children);
            const group = { element, children, count, badge }; groups.set(key, group); return group;
        };
        const matchesScope = (row) => {
            if (updates) return row.dataset.dmExternalMatch !== '0';
            if (row.dataset.dmExternalMatch === '0') return false;
            if (scope === 'attention') return Number(row.dataset.dmAttention) > 0;
            if (scope === 'available') return Number(row.dataset.dmAvailable) > 0;
            if (scope === 'error') return Number(row.dataset.dmErrors) > 0;
            if (scope === 'running') return Number(row.dataset.dmRunning) > 0;
            return true;
        };
        const refreshMetrics = () => {
            if (!updates) return;
            const running = rows.filter(row => row.dataset.updateState === 'running' || row.dataset.dmMaintenanceRunning === '1').length;
            const jobBox = root.querySelector('.dm-workspace-running');
            if (jobBox && !jobBox.querySelector('[data-dm-pro-hint]')) {
                jobBox.hidden = running === 0;
                const title = jobBox.querySelector('strong');
                if (title) title.textContent = running + (running === 1 ? ' Installation in Bearbeitung' : ' Installationen in Bearbeitung');
            }
            const values = { available: rows.filter(row => row.dataset.updateState === 'available').length, error: rows.filter(row => ['error', 'check_failed', 'warning'].includes(row.dataset.updateState)).length };
            Object.entries(values).forEach(([name, value]) => {
                const target = root.querySelector('.dm-workspace-metrics [data-dm-workspace-scope="' + name + '"] strong');
                if (target) target.textContent = String(value);
            });
        };
        const apply = () => {
            refreshMetrics();
            if (root.dataset.dmWorkspaceBusy === '1') { wasBusy = true; return; }
            if (wasBusy) { forceFlat = false; wasBusy = false; }
            scope = root.dataset.dmWorkspaceScope || scope;
            root.dataset.dmWorkspaceView = view;
            root.querySelectorAll('[data-dm-workspace-view]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.dmWorkspaceView === view)));
            root.querySelectorAll('[data-dm-workspace-scope]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.dmWorkspaceScope === scope)));
            wrapDetails();
            const matching = rows.filter(matchesScope);
            const grouped = updates && scope === 'all' && !forceFlat;
            let entries;
            if (grouped) {
                const matchingGroups = new Map();
                matching.forEach(row => { const group = groupFor(row); if (!matchingGroups.has(group)) matchingGroups.set(group, []); matchingGroups.get(group).push(row); });
                entries = Array.from(matchingGroups.keys());
                matchingGroups.forEach((members, group) => { updateGroup(group, members); group.children.replaceChildren(...members); });
            } else {
                entries = [...matching];
                if (scope !== 'all') entries.sort((a, b) => priority(a) - priority(b));
            }
            if (resultRow) {
                const index = entries.findIndex(entry => grouped ? entry.children.contains(resultRow) : entry === resultRow);
                if (index >= 0) { page = Math.floor(index / pageSize); if (grouped) entries[index].element.open = true; }
                resultRow = null;
            }
            const pages = Math.max(1, Math.ceil(entries.length / pageSize)); page = Math.min(page, pages - 1);
            const onPage = new Set(entries.slice(page * pageSize, (page + 1) * pageSize));
            rows.forEach(row => { row.hidden = true; });
            if (updates) list.replaceChildren(...entries.map(entry => grouped ? entry.element : entry));
            else entries.forEach(entry => list.append(entry));
            entries.forEach(entry => {
                if (grouped) {
                    entry.element.hidden = !onPage.has(entry);
                    Array.from(entry.children.children).forEach(row => { row.hidden = !onPage.has(entry); });
                    if (root.querySelector('[data-dm-update-search]')?.value.trim()) entry.element.open = true;
                } else entry.hidden = !onPage.has(entry);
            });
            const start = entries.length ? page * pageSize + 1 : 0;
            range.textContent = start + '–' + Math.min((page + 1) * pageSize, entries.length) + ' von ' + entries.length + ((grouped || !updates) ? ' Hauptdomains' : ' Installationen');
            counter.textContent = (page + 1) + ' / ' + pages; previous.disabled = page === 0; next.disabled = page >= pages - 1;
            const empty = root.querySelector(updates ? '[data-dm-update-empty]' : '[data-dm-filter-empty]');
            if (empty) empty.hidden = entries.length > 0;
            root.dispatchEvent(new CustomEvent('dm:workspace-visible'));
        };
        const setScope = value => {
            if (root.dataset.dmWorkspaceBusy === '1') return;
            scope = value; root.dataset.dmWorkspaceScope = value; page = 0;
            if (updates) root.dispatchEvent(new CustomEvent('dm:workspace-scope', { detail: value }));
            else apply();
        };
        root.querySelectorAll('[data-dm-workspace-scope]').forEach(button => button.addEventListener('click', () => setScope(button.dataset.dmWorkspaceScope)));
        root.querySelectorAll('[data-dm-workspace-view]').forEach(button => button.addEventListener('click', () => {
            if (root.dataset.dmWorkspaceBusy === '1') return;
            view = button.dataset.dmWorkspaceView; try { localStorage.setItem('maintend.workspace.view.' + root.dataset.dmWorkspace, view); } catch (_) { /* Optional. */ } apply();
        }));
        previous.addEventListener('click', () => { if (page > 0 && root.dataset.dmWorkspaceBusy !== '1') { page--; apply(); } });
        next.addEventListener('click', () => { if (root.dataset.dmWorkspaceBusy !== '1') { page++; apply(); } });
        size.addEventListener('change', () => { if (root.dataset.dmWorkspaceBusy !== '1') { pageSize = Number(size.value); page = 0; apply(); } });
        root.addEventListener('dm:workspace-filtered', () => { page = 0; apply(); });
        root.addEventListener('click', event => {
            const target = event.target instanceof Element ? event.target.closest('[data-dm-pro-hint]') : null;
            if (!target) return;
            event.preventDefault(); event.stopImmediatePropagation();
            const hint = root.querySelector('[data-dm-pro-hint-panel]'); if (hint) { hint.hidden = false; hint.scrollIntoView({ block: 'nearest' }); }
        }, true);
        root.querySelector('[data-dm-pro-hint-close]')?.addEventListener('click', () => { root.querySelector('[data-dm-pro-hint-panel]').hidden = true; });
        apply();
        return { apply, begin() { forceFlat = true; list.replaceChildren(...rows); groups.forEach(group => { group.element.hidden = true; }); } };
    };
    const get = root => { if (!instances.has(root)) instances.set(root, create(root)); return instances.get(root); };
    window.MaintendWorkspace = { priority, apply: root => get(root).apply(), begin: root => get(root).begin() };
    const initialize = () => document.querySelectorAll('[data-dm-workspace]').forEach(root => {
        // The update engine must collect all installation/form references first.
        if (root.dataset.dmWorkspace === 'updates' && root.dataset.dmUpdatesInitialized !== '1') return;
        get(root);
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
