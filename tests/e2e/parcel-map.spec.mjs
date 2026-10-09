import { test, expect } from '@playwright/test';

test('map search pages independently of viewport limits and keeps record links on map failure', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'chromium-responsive', 'Run once in desktop Chromium.');
    const username = process.env.E2E_STAFF_USERNAME || '';
    const password = process.env.E2E_STAFF_PASSWORD || '';
    test.skip(!username || !password, 'Staff E2E credentials are required.');

    await page.goto('/login');
    await page.locator('#username').fill(username);
    await page.locator('#password').fill(password);
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/staff\/dashboard/);

    const record = (id, code) => ({
        id, parcel_code: code, title_no: 'TITLE-REF', tax_decl_no: 'TAX-REF',
        municipality: 'Dumaguete City', barangay: 'Calindagan', area_hectares: '10.0000',
        active_linked_area_hectares: '2.0000', historical_holding_count: 1,
        landowner: 'Map Owner', reference_scope: 'Active landholding reference',
        details_url: '/staff/records/parcels/' + id, bounds: [123.3, 9.3, 123.31, 9.31],
    });
    await page.route('**/staff/parcel-map/features?*', route => route.fulfill({
        json: { type: 'FeatureCollection', features: [], total: 75, returned: 0, limited: true, skipped: 0, limit: 50 },
    }));
    await page.route('**/staff/parcel-map/search?*', route => {
        const url = new URL(route.request().url());
        const pageNumber = Number(url.searchParams.get('page'));
        return route.fulfill({ json: {
            items: [record(pageNumber === 1 ? 9001 : 9002, pageNumber === 1 ? 'FOUND-OUTSIDE-LIMIT' : 'SECOND-SEARCH-PAGE')],
            total: 16, page: pageNumber, last_page: 2,
        } });
    });
    await page.route('**/staff/parcel-map/feature/9002', route => route.fulfill({
        json: { type: 'Feature', properties: record(9002, 'SECOND-SEARCH-PAGE'), geometry: {
            type: 'Polygon', coordinates: [[[123.3, 9.3], [123.31, 9.3], [123.31, 9.31], [123.3, 9.31], [123.3, 9.3]]],
        } },
    }));
    await page.goto('/staff/parcel-map');
    await expect(page.locator('#parcel-map-status')).toContainText('Display is limited');
    await page.locator('#parcel-map-search').fill('FOUND');
    await expect(page.locator('#parcel-search-results')).toContainText('FOUND-OUTSIDE-LIMIT');
    await page.getByRole('button', { name: 'Next parcel search page' }).click();
    await expect(page.locator('#parcel-search-results')).toContainText('SECOND-SEARCH-PAGE');
    await expect(page.locator('#parcel-search-status')).toContainText('Page 2 of 2');
    await page.getByRole('button', { name:'Show SECOND-SEARCH-PAGE on map', exact:true }).click();
    await expect(page.locator('#parcel-map .leaflet-overlay-pane path')).toHaveCount(1);
    await page.locator('#parcel-map .leaflet-overlay-pane path').hover({ force: true });
    await expect(page.locator('.parcel-tooltip')).toHaveCount(1);
    await expect(page.locator('.parcel-tooltip')).toContainText('Parcel area');
    await expect(page.locator('.parcel-tooltip')).toContainText('Active holding area');

    await page.route('**/staff/parcel-map/features?*', route => route.fulfill({ status: 503, json: {} }));
    await page.locator('#reset-map-view').click();
    await expect(page.locator('#parcel-map-status')).toContainText('The parcel list remains available.');
    await expect(page.locator('#parcel-search-results a')).toHaveAttribute('href', '/staff/records/parcels/9002');
});

