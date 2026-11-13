const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { runInNewContext } = require('node:vm');

const script = readFileSync(join(__dirname, '../../public/js/domain-manager-updates.js'), 'utf8');
const base = new URL('https://install1.example.test/updates');

async function run(scenario, action = 'update-install', bulk = false) {
    const classes = { add() {}, remove() {}, toggle() {}, contains: () => false };
    const elements = new Map(['title', 'current', 'summary'].map(name => ['[data-dm-progress-' + name + ']', {}]));
    const controls = { handlers: {}, addEventListener(name, handler) { this.handlers[name] = handler; } };
    if (bulk) elements.set('[data-dm-install-selected]', controls);
    const requests = [];
    const confirmations = [];
    const fixtures = [2, 3].map(id => {
        let installPresent = true;
        const badge = {};
        const row = {
            dataset: { installationId: String(id), updateState: 'available' }, classList: classes,
            querySelector(selector) {
                if (selector === '.dm-update-domain strong') return { textContent: id === 2 ? base.hostname : 'remote.example.test' };
                if (selector === '.dm-update-state') return badge;
                if (selector === '[data-dm-update-select]') return checkbox;
                if (selector.includes('data-dm-action="update-install"')) return installPresent ? install : null;
                if (selector.includes('data-dm-action="update-check"')) return check;
                return null;
            },
        };
        const makeForm = name => ({
            dataset: { dmAction: name },
            action: new URL('/' + name + '/' + id, base).href,
            matches: () => false,
            closest: selector => selector === '.dm-updates-action-form' ? makeForm(name)
                : selector === '[data-dm-update-row]' ? row : null,
            querySelector: () => ({ dataset: { dmConfirm: 'Jetzt installieren?' } }),
            remove: () => { if (name === 'update-install') installPresent = false; },
        });
        const install = makeForm('update-install');
        const check = makeForm('update-check');
        const checkbox = { dataset: {}, checked: true, closest: () => row, addEventListener() {} };
        return { row, install, check, checkbox, badge, installPresent: () => installPresent };
    });
    const handlers = [];
    const root = {
        dataset: {}, classList: classes,
        querySelector: selector => elements.get(selector) || null,
        querySelectorAll: selector => selector === '[data-dm-update-row]' ? fixtures.map(f => f.row)
            : selector === '[data-dm-update-select]' ? fixtures.map(f => f.checkbox) : [],
        addEventListener: (name, handler) => { if (name === 'submit') handlers.push(handler); },
    };

    runInNewContext(script, {
        URL, FormData: class {}, DOMParser: class {
            parseFromString() { return { querySelector: () => null }; }
        },
        window: {
            location: base, addEventListener() {},
            confirm(message) { confirmations.push(message); return true; },
        },
        document: { querySelector: () => root },
        async fetch(url, options) {
            requests.push([url, options.method]);
            if (scenario === 'network') throw new TypeError('Failed to fetch');
            if (scenario === 'unavailable') return { ok: false, status: 503 };
            if (scenario === 'login') return { ok: true, status: 200, redirected: true, url: new URL('/login', base).href, text: async () => '' };
            const id = scenario === 'wrong-installation' ? '3' : '2';
            const status = scenario === 'confirmed-error' ? 'error' : 'success';
            return {
                ok: true, status: 200, url: base.href + '?dm_update_install=' + status + '&dm_installation=' + id,
                text: async () => { if (scenario === 'lost-html') throw new TypeError('Disconnected'); return ''; },
            };
        },
    });

    if (bulk) await controls.handlers.click();
    else {
        const first = fixtures[0];
        const form = action === 'update-install' ? first.install : first.check;
        for (const handler of handlers) handler({ target: form, preventDefault() {} });
        for (let tick = 0; root.dataset.dmWorkspaceBusy === '1' && tick < 10; tick++) {
            await new Promise(resolve => setImmediate(resolve));
        }
    }
    assert.equal(root.dataset.dmWorkspaceBusy, '0');
    assert.equal(requests.length, 1);
    assert.equal(requests[0][1], 'POST');
    return { fixtures, confirmations, summary: elements.get('[data-dm-progress-summary]').textContent };
}

(async () => {
    for (const scenario of ['network', 'unavailable', 'login', 'wrong-installation']) {
        const { fixtures: [row], confirmations, summary } = await run(scenario);
        assert.equal(row.row.dataset.updateState, 'warning');
        assert.equal(row.badge.textContent, 'Update-Ergebnis nicht bestätigt');
        assert.equal(row.installPresent(), false);
        assert.match(confirmations[0], /Domainübersicht selbst wird aktualisiert/);
        assert.match(summary, /erneut anmelden/);
    }
    for (const [scenario, expected] of [['confirmed-error', 'error'], ['success', 'current'], ['lost-html', 'current']]) {
        const { fixtures: [row] } = await run(scenario);
        assert.equal(row.row.dataset.updateState, expected);
    }
    const { fixtures: [checking] } = await run('login', 'update-check');
    assert.equal(checking.badge.textContent, 'Anmeldung oder Zugriff prüfen');
    const { fixtures: [first, second], summary } = await run('unavailable', 'update-install', true);
    assert.equal(first.badge.textContent, 'Update-Ergebnis nicht bestätigt');
    assert.equal(second.row.dataset.updateState, 'available');
    assert.match(summary, /1 nicht gestartet/);
    console.log('Update feedback: confirmed results, interrupted responses, login and bulk stop passed.');
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
