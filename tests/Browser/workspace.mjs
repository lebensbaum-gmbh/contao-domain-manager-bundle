import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import { resolve } from 'node:path';
import assert from 'node:assert/strict';

const browser = await chromium.launch({ headless: true });
const failures = [];
try {
    const page = await browser.newPage({ viewport: { width: 1280, height: 1000 } });
    page.on('pageerror', error => failures.push(error.message));
    await page.goto(pathToFileURL(resolve('build/browser/free.html')).href);
    assert.equal(await page.locator('[data-dm-update-row]').count(), 80);
    assert.equal(await page.locator('.dm-workspace-group:visible').count(), 10);
    assert.equal(await page.locator('.dm-workspace-group[open]').count(), 0);
    assert.equal(await page.locator('form[action^="/pro/"]').count(), 0);
    // Pro actions are aria-disabled; their pointer interaction still opens help.
    await page.locator('[data-dm-pro-hint]').first().click({ force: true });
    assert.equal(await page.locator('[data-dm-pro-hint-panel]').isVisible(), true);
    assert.equal(await page.locator('[data-dm-pro-hint-panel] a').getAttribute('href'), 'https://maintend.de/');
    await page.locator('[data-dm-pro-hint-close]').click();
    await page.locator('[data-dm-update-search]').fill('kunde-55');
    assert.equal(await page.locator('.dm-workspace-group:visible').count(), 1);
    assert.equal(await page.locator('.dm-workspace-group:visible').getAttribute('open'), '');
    await page.screenshot({ path: 'build/browser/free-search.png', fullPage: true });

    await page.goto(pathToFileURL(resolve('build/browser/pro-readonly.html')).href);
    assert.equal(await page.locator('form[action^="/pro/"]').count(), 0);
    await page.locator('[data-dm-pro-hint]').first().focus();
    await page.keyboard.press('Enter');
    assert.match(await page.locator('[data-dm-pro-hint-panel]').innerText(), /Bedienrechte/);
    assert.equal(await page.locator('[data-dm-pro-hint-panel] a').count(), 0);

    await page.goto(pathToFileURL(resolve('build/browser/pro.html')).href);
    assert.equal(await page.locator('[data-dm-update-row]:visible').count(), 10);
    const olderCheck = page.locator('[data-dm-update-row][data-installation-id="55"]');
    assert.equal(await olderCheck.isVisible(), true);
    assert.match(await olderCheck.locator('.dm-update-state').innerText(), /Wartungsrückstand/);
    assert.equal(await olderCheck.locator('[data-dm-action="update-install"]').count(), 0);
    assert.equal(await page.locator('[data-dm-check-failure-count]').innerText(), '1');
    assert.equal(await page.locator('[data-dm-install-failure-count]').innerText(), '1');
    await page.locator('[data-dm-select-visible]').check();
    const selected = await page.locator('[data-dm-selected-count]').innerText();
    assert.equal(selected, '10');
    await page.locator('[data-dm-update-filter="all"]').click();
    assert.equal(await page.locator('.dm-workspace-group:visible').count(), 10);
    assert.equal(await page.locator('[data-dm-selected-count]').innerText(), selected);
    await page.locator('button[data-dm-workspace-view="tiles"]').click();
    assert.equal(await page.locator('[data-dm-updates]').getAttribute('data-dm-workspace-view'), 'tiles');
    assert.equal(await page.locator('[data-dm-selected-count]').innerText(), selected);
    await page.locator('[data-dm-update-search]').fill('mirror.kunde-15');
    assert.equal(await page.locator('[data-dm-update-row]:visible').count(), 1);
    assert.match(await page.locator('[data-dm-update-row]:visible').innerText(), /mirror.kunde-15/);
    assert.equal(await page.locator('[data-dm-selected-count]').innerText(), selected);
    await page.locator('[data-dm-update-search]').fill('');
    for (const width of [1280, 736, 375]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.screenshot({ path: 'build/browser/pro-' + width + '.png', fullPage: true });
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
        if (overflow) console.log('Updates overflow:', await page.evaluate(() => ({
            viewport: window.innerWidth, scrollWidth: document.documentElement.scrollWidth,
            elements: Array.from(document.querySelectorAll('body *')).filter(element => {
                const rect = element.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0 && rect.right > window.innerWidth + 1;
            }).slice(-15).map(element => ({ tag: element.tagName, class: element.className, right: element.getBoundingClientRect().right, width: element.getBoundingClientRect().width })),
        })));
        assert.equal(overflow, false, 'horizontal overflow at ' + width);
    }

    await page.setViewportSize({ width: 1280, height: 1000 });
    for (const variant of ['free', 'pro']) {
        await page.goto(pathToFileURL(resolve('build/browser/overview-' + variant + '.html')).href);
        assert.equal(await page.locator('[data-dm-domain]').count(), 60);
        assert.equal(await page.locator('[data-dm-installation]').count(), 80);
        assert.equal(await page.locator('[data-dm-domain]:visible').count(), 10);
        assert.equal(await page.locator('[data-dm-domain][open]').count(), 0);
        assert.equal(await page.locator('.domain-thumbnail img').count(), 60);
        await page.locator('[data-dm-filter-search]').fill('mirror.kunde-15');
        assert.equal(await page.locator('[data-dm-domain]:visible').count(), 1);
        assert.equal(await page.locator('[data-dm-domain]:visible').getAttribute('open'), '');
        assert.match(await page.locator('[data-dm-installation]:visible').innerText(), /mirror.kunde-15/);
        if (variant === 'free') {
            assert.equal(await page.locator('form[action^="/pro/"]').count(), 0);
            await page.locator('[data-dm-pro-hint]').first().click({ force: true });
            assert.match(await page.locator('[data-dm-pro-hint-panel]').innerText(), /Nur in der Pro-Version möglich/);
            await page.locator('[data-dm-pro-hint-close]').click();
        }
        await page.locator('[data-dm-filter-search]').fill('');
        await page.locator('button[data-dm-workspace-view="tiles"]').click();
        for (const width of [1280, 736, 375]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.screenshot({ path: 'build/browser/overview-' + variant + '-' + width + '.png', fullPage: true });
            const beyondViewport = await page.evaluate(() => Array.from(document.querySelectorAll('body *')).filter(element => {
                const rect = element.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0 && rect.right > window.innerWidth + 1;
            }).slice(-8).map(element => ({ tag: element.tagName, class: element.className, right: element.getBoundingClientRect().right })));
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
            if (overflow) console.log('Overflow candidates:', beyondViewport);
            assert.equal(overflow, false, 'overview overflow at ' + width);
        }
        await page.setViewportSize({ width: 1280, height: 1000 });
    }
    assert.deepEqual(failures, []);
} finally {
    await browser.close();
}
