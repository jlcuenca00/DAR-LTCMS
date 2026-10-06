import { test, expect } from '@playwright/test';

const username = process.env.E2E_STAFF_USERNAME;
const password = process.env.E2E_STAFF_PASSWORD;
const ids = {
    A: process.env.E2E_MAP_PARCEL_A_ID,
    B: process.env.E2E_MAP_PARCEL_B_ID,
    PRIVATE: process.env.E2E_MAP_PRIVATE_PARCEL_ID,
};

async function login(page, role) {
    const account = role === 'staff' ? username : role === 'geodetic' ? `${username}.geo` : `${username}.mapowner`;
    await page.goto('/login');
    await page.locator('#username').fill(account);
    await page.locator('#password').fill(password);
    const path = `/onboarding-tours/${role}_portal`;
    const loaded = page.waitForResponse(response => new URL(response.url()).pathname === path && response.request().method() === 'GET');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(new RegExp(`/${role}/dashboard`));
    const response = await loaded;
    expect(response.status()).toBe(200);
    if (!(await response.json()).seen) {
        const dismissed = page.waitForResponse(response => new URL(response.url()).pathname === path && response.request().method() === 'PATCH');
        await page.getByRole('button', { name: 'Skip Tour', exact: true }).click();
        expect((await dismissed).status()).toBe(200);
        await expect(page.locator('.onboarding-welcome-layer')).toHaveCount(0);
    }
}

async function noOverflow(page, context) {
    const sizes = await page.evaluate(() => ({
        viewport: document.documentElement.clientWidth,
        document: document.documentElement.scrollWidth,
        body: document.body.scrollWidth,
    }));
    expect(sizes.document, context).toBeLessThanOrEqual(sizes.viewport + 1);
    expect(sizes.body, context).toBeLessThanOrEqual(sizes.viewport + 1);
}

test.beforeEach(async ({ page }) => {
    expect(username, 'Isolated Staff credentials are required').toBeTruthy();
    expect(password, 'Isolated browser credentials are required').toBeTruthy();
    for (const id of Object.values(ids)) expect(id, 'Real map fixture IDs are required').toMatch(/^\d+$/);
    // Only external basemap tiles are blocked. App HTML, assets and map APIs remain real.
    await page.route('https://*.basemaps.cartocdn.com/**', route => route.abort());
});

for (const role of ['staff', 'geodetic', 'landowner']) {
    test(`${role} real map searches, highlights nearby parcels, resets and opens the correct record`, async ({ page }) => {
        await login(page, role);
        const base = `/${role}/parcel-map`;
        const initial = page.waitForResponse(response => new URL(response.url()).pathname === `${base}/features`);
        expect((await page.goto(base)).status()).toBe(200);
        expect((await initial).status()).toBe(200);
        const search = page.locator('#parcel-map-search, #parcel-search');
        const searched = page.waitForResponse(response => {
            const url = new URL(response.url());
            return url.pathname === `${base}/search` && url.searchParams.get('q') === 'E2E-MAP-ROLE-';
        });
        await search.fill('E2E-MAP-ROLE-');
        const result = await searched;
        expect(result.status()).toBe(200);
        const data = await result.json();
        expect(data.items.map(item => String(item.id)).sort()).toEqual(
            (role === 'landowner' ? [ids.A, ids.B] : Object.values(ids)).sort());
        await expect(page.locator('[data-parcel-search-row]')).toHaveCount(role === 'landowner' ? 2 : 3);
        await noOverflow(page, `${role} real map`);

        const first = page.getByRole('button', { name: 'Show E2E-MAP-ROLE-A on map', exact: true });
        const second = page.getByRole('button', { name: 'Show E2E-MAP-ROLE-B on map', exact: true });
        const outline = page.locator('.parcel-map-focus-outline');
        for (const [label, button] of [['A', first], ['B', second]]) {
            const focused = page.waitForResponse(response => new URL(response.url()).pathname === `${base}/feature/${ids[label]}`);
            const refreshed = page.waitForResponse(response => new URL(response.url()).pathname === `${base}/features`);
            await button.click();
            const featureResponse = await focused;
            expect(featureResponse.status()).toBe(200);
            expect((await featureResponse.json()).properties.id).toBe(Number(ids[label]));
            const viewportResponse = await refreshed;
            expect(viewportResponse.status()).toBe(200);
            const viewportData = await viewportResponse.json();
            expect(viewportData.features.map(feature => String(feature.properties.id))).toEqual(expect.arrayContaining([ids.A, ids.B]));
            if (role === 'landowner') expect(viewportData.features.map(feature => String(feature.properties.id))).not.toContain(ids.PRIVATE);
            await expect(outline).toHaveCount(1);
            await expect(outline).toBeVisible();
            await expect(page.locator('.parcel-map-focus-halo')).toHaveCount(1);
            await expect(page.locator('.parcel-map-focus-label')).toHaveText(`E2E-MAP-ROLE-${label}`);
            await expect(button).toHaveAttribute('aria-pressed', 'true');
            await expect(page.locator('[data-map-selected]')).toHaveCount(1);
            await expect(outline).toHaveAttribute('stroke', '#2563eb');
            expect(await outline.evaluate(node => getComputedStyle(node).filter)).toContain('drop-shadow');
            expect(await outline.evaluate(node => getComputedStyle(node.ownerSVGElement).maxWidth)).toBe('none');
            const bounds = await outline.boundingBox();
            const mapBounds = await page.locator('#parcel-map').boundingBox();
            expect(bounds.width).toBeGreaterThan(10);
            expect(bounds.height).toBeGreaterThan(10);
            expect(bounds.x + bounds.width / 2).toBeGreaterThanOrEqual(mapBounds.x);
            expect(bounds.x + bounds.width / 2).toBeLessThanOrEqual(mapBounds.x + mapBounds.width);
            await expect(outline).toHaveAttribute('d', /M.+/);
        }
        await expect(first).toHaveAttribute('aria-pressed', 'false');
        if (role === 'landowner') {
            expect((await page.request.get(`${base}/feature/${ids.PRIVATE}`)).status()).toBe(404);
            await expect(page.locator('#parcel-search-results')).not.toContainText('E2E-MAP-ROLE-PRIVATE');
        }
        const reset = page.waitForResponse(response => new URL(response.url()).pathname === `${base}/features`);
        await page.locator('#reset-map-view').click();
        expect((await reset).status()).toBe(200);
        await expect(outline).toHaveCount(0);
        await expect(page.locator('.parcel-map-focus-halo, .parcel-map-focus-label, [data-map-selected]')).toHaveCount(0);
        await expect(second).toHaveAttribute('aria-pressed', 'false');

        const record = data.items.find(item => String(item.id) === ids.B);
        await page.getByRole('link', { name: 'Open E2E-MAP-ROLE-B record', exact: true }).click();
        await expect(page).toHaveURL(record.details_url);
        await expect(page.locator('body')).toContainText('E2E-MAP-ROLE-B');
    });
}

test('populated Landowner pages retain their content and mobile navigation across screen sizes', async ({ page }, testInfo) => {
    await login(page, 'landowner');
    const widths = testInfo.project.name === 'chromium-coarse-pointer' ? [390] : [320, 390, 768, 1100, 1101, 1440];
    for (const width of widths) {
        await page.setViewportSize({ width, height: 900 });
        for (const path of ['/landowner/dashboard', '/landowner/parcels', `/landowner/parcels/${ids.A}`]) {
            expect((await page.goto(path)).status()).toBe(200);
            expect(new URL(page.url()).pathname).toBe(path);
            await expect(page.locator('.lo-shell')).toBeVisible();
            await expect(page.locator('body')).toContainText('E2E-MAP-ROLE-A');
            await noOverflow(page, `${path} at ${width}px`);
            if (width <= 1100) {
                const nav = page.getByRole('navigation', { name: 'Landowner portal navigation', exact: true });
                await expect(nav).toBeVisible();
                await expect(nav.getByRole('link', { name: 'Parcels', exact: true })).toHaveAttribute('href', /\/landowner\/parcels$/);
            }
        }
    }
});
