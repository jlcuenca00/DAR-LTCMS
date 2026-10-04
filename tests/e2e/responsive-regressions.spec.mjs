import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(path, 'utf8');
const review = read('resources/views/staff/applications/show.blade.php');
const reviewCss = review.match(/<style>([\s\S]*?)<\/style>/)[1];
const modalStart = review.indexOf('<div id="decision-confirm-modal"');
const decisionMarkup = review.slice(modalStart, review.indexOf('<script>', modalStart));
const lastMile = read('resources/js/ui-ux-last-mile.js');
const styles = [
    'responsive.css', 'responsive-no-scroll.css', 'responsive-table-cards.css',
    'responsive-hardening.css', 'ui-ux-system.css', 'ui-ux-last-mile.css',
    'application-modal-viewport.css',
].map(file => read('resources/css/' + file)).join('\n');

async function fixture(page, html) {
    await page.setContent('<meta name="viewport" content="width=device-width, initial-scale=1">' + html);
    await page.addStyleTag({ content: '* { box-sizing:border-box } body { margin:0 } ' + styles });
}

test('map status text stays compact across role shells and viewport sizes', async ({ page }) => {
    for (const width of [320, 390, 640, 760, 768, 1440]) {
        await page.setViewportSize({ width, height: 844 });
        for (const role of ['staff', 'lo', 'geo']) {
            await fixture(page, '<div class="' + role + '-shell"><div class="staff-content-inner">' +
                '<p id="parcel-map-status" role="status">Map display is limited.</p>' +
                '<div id="parcel-map" data-parcel-map-viewer></div></div></div>');
            const status = page.locator('#parcel-map-status');
            expect(await status.evaluate(node => node.getBoundingClientRect().height)).toBeLessThan(100);
            if (width <= 760) {
                expect(await page.locator('#parcel-map').evaluate(node => node.getBoundingClientRect().height)).toBeGreaterThanOrEqual(340);
            }
            await status.evaluate(node => { node.textContent = ''; });
            expect(await status.evaluate(node => node.getBoundingClientRect().height)).toBe(0);
        }
    }
});

async function modalFixture(page) {
    await fixture(page, '<div class="staff-shell"><div class="application-review-page">' +
        decisionMarkup + '<div class="workflow-modal-backdrop is-open"><div class="workflow-modal-card">' +
        '<div class="workflow-modal-body"><div class="workflow-decision-card">' +
        '<input type="text" class="review-input" aria-label="Decision officer">' +
        '<select class="review-input" aria-label="Category"><option>Other</option></select>' +
        '<textarea class="review-input" aria-label="Details"></textarea>' +
        '<button type="button" class="staff-button">Close workflow</button></div></div></div></div></div></div>');
    await page.addStyleTag({ content: reviewCss });
    await page.addScriptTag({ content: '(() => {' + lastMile + '})();' });
    await expect(page.locator('body > .decision-modal-backdrop')).toHaveCount(1);
    await expect(page.locator('body > .workflow-modal-backdrop')).toHaveCount(1);
    await page.locator('.workflow-modal-backdrop').evaluate(node => { node.style.display = 'none'; });
    await page.locator('#decision-confirm-modal').evaluate(node => {
        node.classList.add('is-open');
        node.setAttribute('aria-hidden', 'false');
    });
}

test('short-screen final confirmation remains scrollable and both actions can be reached', async ({ page }) => {
    for (const viewport of [{ width:320, height:568 }, { width:568, height:320 }, { width:844, height:390 }]) {
        await page.setViewportSize(viewport);
        await modalFixture(page);
        const card = page.locator('.decision-modal-card');
        const warning = page.locator('#decision-confirm-warning');
        // Enlarged text/content exercises clipping even when the default copy fits.
        await warning.evaluate(node => { node.textContent = node.textContent.repeat(12); });
        expect(await card.evaluate(node => getComputedStyle(node).overflowY)).toBe('auto');
        expect(await card.evaluate(node => node.scrollHeight)).toBeGreaterThan(await card.evaluate(node => node.clientHeight));
        const bounds = await card.boundingBox();
        expect(bounds.y).toBeGreaterThanOrEqual(0);
        expect(bounds.y + bounds.height).toBeLessThanOrEqual(viewport.height + 1);
        await page.evaluate(() => {
            window.__confirmationClicks = [];
            ['decision-confirm-cancel', 'decision-confirm-submit'].forEach(id => {
                document.getElementById(id).addEventListener('click', () => window.__confirmationClicks.push(id));
            });
        });
        await page.locator('#decision-confirm-cancel').click();
        await page.locator('#decision-confirm-submit').click();
        expect(await page.evaluate(() => window.__confirmationClicks)).toEqual(['decision-confirm-cancel', 'decision-confirm-submit']);
    }
});

test('portaled workflow fields retain phone typography and pointer-specific target sizes', async ({ page }) => {
    await page.setViewportSize({ width:390, height:844 });
    await modalFixture(page);
    await page.locator('#decision-confirm-modal').evaluate(node => { node.style.display = 'none'; });
    await page.locator('.workflow-modal-backdrop').evaluate(node => { node.style.display = 'flex'; });
    const target = await page.evaluate(() => matchMedia('(pointer: coarse)').matches ? 48 : 44);
    for (const control of await page.locator('body > .workflow-modal-backdrop input, body > .workflow-modal-backdrop select, body > .workflow-modal-backdrop textarea, body > .workflow-modal-backdrop button').all()) {
        expect(await control.evaluate(node => node.getBoundingClientRect().height)).toBeGreaterThanOrEqual(target);
        if (await control.evaluate(node => node.tagName !== 'BUTTON')) {
            expect(await control.evaluate(node => parseFloat(getComputedStyle(node).fontSize))).toBeGreaterThanOrEqual(16);
        }
    }
});
