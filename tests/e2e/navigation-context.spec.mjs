import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const navigation = readFileSync('resources/js/responsive-hardening.js', 'utf8');
const polish = readFileSync('resources/js/mobile-portal-polish.js', 'utf8')
    .replace(/^import ['"][^'"]+['"];\s*/m, '');
const polishCss = readFileSync('resources/css/mobile-portal-polish.css', 'utf8');

test('Staff compact navigation contains Dashboard and follows its destination', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.setContent(`<div class="staff-shell">
        <aside class="staff-sidebar"><div class="staff-brand"><span class="staff-brand-mark">DAR</span></div>
        ${[
            ['Dashboard', '/staff/dashboard'], ['Applications', '/staff/applications'],
            ['Landowner Records', '/staff/records/landowners'], ['Parcel Records', '/staff/records/parcels'],
            ['Parcel Map', '/staff/parcel-map'], ['Source Records', '/staff/legacy-records'],
            ['Monitoring Reports', '/staff/reports/monitoring'], ['Audit Logs', '/staff/audit-logs'],
        ].map(([label, url], i) => `<a class="staff-side-link ${i === 0 ? 'active' : ''}" href="https://fixture.test${url}">${label}</a>`).join('')}
        </aside><main><header class="staff-topbar"><div class="staff-topbar-actions"></div></header></main>
        </div>`);
    await page.addScriptTag({ content: navigation });
    const nav = page.getByRole('navigation', { name: 'Staff portal navigation', exact: true });
    await expect(nav.locator(':scope > a')).toHaveCount(4);
    const dashboard = nav.getByRole('link', { name: 'Dashboard', exact: true });
    await expect(dashboard).toHaveAttribute('href', 'https://fixture.test/staff/dashboard');
    await expect(dashboard).toHaveAttribute('aria-current', 'page');
    await page.route('https://fixture.test/staff/dashboard', route => route.fulfill({
        contentType: 'text/html', body: '<h1>Dashboard destination</h1>',
    }));
    await dashboard.click();
    await expect(page).toHaveURL('https://fixture.test/staff/dashboard');
    await expect(page.getByRole('heading', { name: 'Dashboard destination' })).toBeVisible();
});

test('Geodetic phone scope describes geometry access and survives desktop resize', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.setContent(`<div class="geo-shell">
        <aside class="geo-sidebar"><div class="geo-brand"><span class="geo-brand-mark">DAR</span></div>
        <a class="geo-nav-link" href="https://fixture.test/geodetic/dashboard">Dashboard</a>
        <a class="geo-nav-link" href="https://fixture.test/geodetic/parcel-map">Parcel Map</a>
        <a class="geo-nav-link active" href="https://fixture.test/geodetic/parcel-directory">Parcel References</a>
        </aside><main><header class="geo-topbar"><div class="geo-topbar-right">
        <span class="geo-access-chip">Limited Access</span></div></header></main></div>`);
    await page.addStyleTag({ content: polishCss });
    await page.addScriptTag({ content: navigation });
    await page.addScriptTag({ content: polish });
    const chip = page.locator('.geo-access-chip');
    await expect(page.locator('.dar-mobile-portal-actions .geo-access-chip')).toHaveCount(1);
    await expect(chip).toHaveAttribute('data-mobile-access-label', 'Geometry');
    expect(await chip.evaluate(node => getComputedStyle(node, '::after').content)).toBe('"Geometry"');
    await expect(page.getByRole('navigation', { name: 'Geodetic portal navigation', exact: true })
        .getByRole('link', { name: 'Parcels', exact: true })).toHaveAttribute('href', 'https://fixture.test/geodetic/parcel-directory');
    await page.setViewportSize({ width: 1280, height: 800 });
    await expect(page.locator('.geo-topbar-right .geo-access-chip')).toHaveCount(1);
    await expect(chip).toHaveText('Limited Access');
    await page.setViewportSize({ width: 390, height: 844 });
    await expect(page.locator('.dar-mobile-portal-actions .geo-access-chip')).toHaveCount(1);
});
