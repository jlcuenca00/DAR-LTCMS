import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const notificationScript = readFileSync('resources/js/notification-dropdown.js', 'utf8');
const dashboardScript = readFileSync('resources/js/dashboard-work-queue.js', 'utf8');

async function dropdown(page) {
    await page.setContent(`<details class="notification-dropdown" data-notification-dropdown
        data-read-visible-url="/notifications/read-visible" data-csrf-token="test">
        <summary>Notifications <span class="notification-badge">6</span></summary>
        <span class="notification-dropdown-count">6 unread</span>
        <p data-notification-read-status hidden></p>
        ${[1, 2, 3, 4, 5].map(id => `<a class="notification-dropdown-item is-unread" data-notification-id="${id}">Notice ${id}</a>`).join('')}
        </details>`);
    await page.addScriptTag({ content: notificationScript });
}

async function openClose(page) {
    await page.locator('summary').click();
    await expect(page.locator('details')).toHaveAttribute('open', '');
    // Wait for the native toggle listener before closing.
    await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    await page.locator('summary').click();
    await expect(page.locator('details')).not.toHaveAttribute('open');
}

test('dropdown reads only rendered IDs after server confirmation and retains unseen count', async ({ page }) => {
    await dropdown(page);
    await page.evaluate(() => {
        window.calls = [];
        window.fetch = (url, options) => {
            window.calls.push({ url, ...options });
            return new Promise(resolve => { window.finishRead = resolve; });
        };
    });
    await openClose(page);
    await expect.poll(() => page.evaluate(() => window.calls.length)).toBe(1);
    await expect(page.locator('.notification-badge')).toHaveText('6');
    await expect(page.locator('.is-unread')).toHaveCount(5);
    expect(await page.evaluate(() => JSON.parse(window.calls[0].body))).toEqual({ notification_ids: [1, 2, 3, 4, 5] });
    expect(await page.evaluate(() => window.calls[0].url)).toBe('/notifications/read-visible');
    await page.evaluate(() => window.finishRead({
        ok: true, json: async () => ({ ok: true, read_ids: [1, 2, 3, 4, 5], unread_count: 1 }),
    }));
    await expect(page.locator('.notification-badge')).toHaveText('1');
    await expect(page.locator('.notification-dropdown-count')).toHaveText('1 unread');
    await expect(page.locator('.is-unread')).toHaveCount(0);
});

for (const failure of ['http', 'network']) {
    test(`dropdown preserves unread state after ${failure} failure and retries`, async ({ page }) => {
        await dropdown(page);
        await page.evaluate(failure => {
            window.calls = 0;
            window.fetch = async () => {
                window.calls += 1;
                if (window.calls === 1) {
                    if (failure === 'network') throw new Error('Offline');
                    return { ok: false };
                }
                return { ok: true, json: async () => ({
                    ok: true, read_ids: [1, 2, 3, 4, 5], unread_count: 0,
                }) };
            };
        }, failure);
        await openClose(page);
        await expect.poll(() => page.evaluate(() => document.querySelector('[data-notification-read-status]').textContent)).toContain('Could not mark');
        await expect(page.locator('.notification-badge')).toHaveText('6');
        await expect(page.locator('.is-unread')).toHaveCount(5);
        await openClose(page);
        await expect(page.locator('.notification-badge')).toHaveCount(0);
        await expect(page.locator('.notification-dropdown-count')).toHaveText('All caught up');
        expect(await page.evaluate(() => window.calls)).toBe(2);
    });
}

for (const attention of ['', 'missing_requirements', 'requirements_complete', 'stale']) {
    test(`dashboard initial filter respects attention: ${attention || 'normal'}`, async ({ page }) => {
        await page.setContent(`<div class="staff-dashboard" data-dashboard-attention="${attention}">
            <button data-dashboard-filter="all">All</button>
            <button data-dashboard-filter="active_workflow">Workflow</button>
            <button data-dashboard-filter="intake_compliance">Intake</button>
            <table><tbody>
            ${Array.from({ length: 12 }, (_, i) => `<tr data-dashboard-status="${i % 3 === 0 ? 'pending_legal_review' : i % 3 === 1 ? 'endorsed_lti' : 'for_releasing'}"><td>Application ${i}</td></tr>`).join('')}
            <tr data-dashboard-filter-empty hidden><td>No matches</td></tr>
            </tbody></table></div>`);
        await page.addScriptTag({ content: dashboardScript });
        await expect(page.locator('[data-dashboard-status]:visible')).toHaveCount(attention ? 12 : 4);
        await expect(page.locator('[data-dashboard-filter-empty]')).toBeHidden();
        await expect(page.locator(`[data-dashboard-filter="${attention ? 'all' : 'active_workflow'}"]`)).toHaveAttribute('aria-pressed', 'true');
        await page.getByRole('button', { name: 'Intake', exact: true }).click();
        await expect(page.locator('[data-dashboard-status]:visible')).toHaveCount(4);
    });
}
