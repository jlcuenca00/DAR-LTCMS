import { test, expect } from '@playwright/test';

test('Geodetic directory discovers unlinked parcels and preserves search filters across pages', async ({ page }) => {
    const username = process.env.E2E_STAFF_USERNAME || '';
    const password = process.env.E2E_STAFF_PASSWORD || '';
    test.skip(!username || !password, 'Isolated Geodetic browser fixtures are required.');

    await page.goto('/login');
    await page.locator('#username').fill(username + '.geo');
    await page.locator('#password').fill(password);
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/geodetic\/dashboard/);
    await page.goto('/geodetic/parcel-directory');

    await page.getByLabel('Search references, landowner or location').fill('E2E-DIR-');
    await page.getByLabel('Geometry', { exact: true }).selectOption('unmapped');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('25 matching parcel records');
    await expect(page.locator('.geo-directory-table tbody tr')).toHaveCount(20);
    await expect(page.locator('.geo-directory-table')).toContainText('0 reference records');
    await page.locator('a[rel="next"]').click();
    await expect(page.locator('.geo-directory-table tbody tr')).toHaveCount(5);
    await expect(page.locator('.geo-directory-table')).toContainText('E2E-DIR-025');
    await expect(page.getByLabel('Geometry', { exact: true })).toHaveValue('unmapped');
    await expect(page.getByLabel('Search references, landowner or location')).toHaveValue('E2E-DIR-');

    await page.getByLabel('Search references, landowner or location').fill('E2E-DIR-MAPPED');
    await page.getByLabel('Geometry', { exact: true }).selectOption('mapped');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    await expect(page.locator('.geo-directory-table tbody tr')).toHaveCount(1);
    await expect(page.locator('.geo-directory-table')).toContainText('Display bounds available');

    await page.getByLabel('Search references, landowner or location').fill('E2E-DIR-ARCHIVED');
    await page.getByLabel('Geometry', { exact: true }).selectOption('all');
    await page.getByLabel('Record status').selectOption('inactive');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    await expect(page.locator('.geo-directory-table')).toContainText('Archived');
    await expect(page.locator('.geo-directory-table a[href$="/geometry/edit"]')).toHaveCount(0);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);

    await page.getByRole('link', { name: 'Awaiting Geometry', exact: true }).click();
    await expect(page).toHaveURL(/geometry=unmapped/);
    await expect(page.getByLabel('Record status')).toHaveValue('active');
    await expect(page.getByRole('link', { name: 'Landholding References', exact: true })).toHaveAttribute('href', /\/geodetic\/parcels$/);
});
