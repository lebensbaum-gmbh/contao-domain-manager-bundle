const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { runInNewContext } = require('node:vm');

const script = readFileSync(join(__dirname, '../../public/js/domain-manager-updates.js'), 'utf8');
const requestId = 'a'.repeat(32);

async function check(initial, replies, expected) {
    const base = new URL('https://maintend.example.test/updates');
    const classes = { add() {}, remove() {}, toggle() {}, contains() { return false; } };
    const progress = { classList: classes, scrollIntoView() {} };
    const button = {};
    const panel = {
        dataset: { dmRestoreProgressUrl: '/restore-status/2' },
        querySelector: () => progress,
        setAttribute() {},
        removeAttribute() {},
    };
    const row = { classList: classes, dataset: { installationId: '2' }, querySelector: () => null };
    const form = {
        action: new URL('/restore/2', base).href,
        matches: selector => selector === '.dm-backup-restore-form',
        closest: selector => selector === '[data-dm-update-backups]' ? panel : selector === '[data-dm-update-row]' ? row : null,
        querySelector: selector => selector === 'button[type="submit"]' ? button : null,
    };
    const submit = [];
    const root = {
        dataset: {}, classList: classes, querySelector: () => null,
        querySelectorAll: selector => selector === '[data-dm-update-row]' ? [row] : [],
        addEventListener: (event, listener) => { if (event === 'submit') submit.push(listener); },
    };

    let posts = 0;
    let polls = 0;
    let navigate;
    const navigated = new Promise(resolve => { navigate = resolve; });
    runInNewContext(script, {
        URL, AbortSignal,
        document: { querySelector: () => root },
        window: {
            addEventListener() {}, setInterval() {}, clearInterval() {},
            setTimeout: callback => queueMicrotask(callback),
            location: { href: base.href, origin: base.origin, assign: navigate },
        },
        FormData: class {},
        fetch: async (input, options) => {
            const url = new URL(input);
            if (url.pathname === '/restore/2') {
                assert.equal(options.method, 'POST');
                posts++;
                const result = new URL(base);
                result.searchParams.set('dm_backup_installation', '2');
                result.searchParams.set('dm_backup_restore', initial);
                if (initial === 'restore_running') result.searchParams.set('dm_restore_request', requestId);
                return { ok: true, url: result.href };
            }
            assert.equal(url.pathname, '/restore-status/2');
            assert.equal(url.searchParams.get('request_id'), requestId);
            assert.match(progress.textContent, /Keine zweite Wiederherstellung gestartet/);
            return { ok: true, json: async () => replies[polls++] };
        },
    });

    for (const listener of submit) listener({ target: form, preventDefault() {} });
    let timer;
    const result = new URL(await Promise.race([
        navigated,
        new Promise((_, reject) => { timer = setTimeout(() => reject(new Error('No navigation')), 1000); }),
    ]).finally(() => clearTimeout(timer)));
    assert.equal(result.searchParams.get('dm_backup_restore'), expected);
    assert.equal(result.searchParams.has('dm_restore_request'), false);
    assert.equal(posts, 1);
    assert.equal(polls, replies.length);
}

(async () => {
    await check('restore_running', [
        { request_id: 'b'.repeat(32), status: 'success', recorded: true },
        { request_id: requestId, status: 'success' },
        { request_id: requestId, status: 'success', recorded: true },
    ], 'tracked_success');
    await check('restore_running', [
        { request_id: requestId, status: 'error', recorded: true },
    ], 'error');
    await check('maintenance_busy', [], 'maintenance_busy');
    console.log('Restore feedback: tracked success, tracked error and unassigned maintenance lock passed.');
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
