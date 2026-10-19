import { test, expect } from '@playwright/test';

test.describe('public registration and legal pages', () => {
    for (const width of [390, 1280]) {
        test(`DAR registration and policies fit at ${width}px`, async ({ page }) => {
            await page.setViewportSize({ width, height: 900 });
            for (const path of ['/register', '/privacy-policy', '/terms-of-service']) {
                await page.goto(path);
                await expect(page.locator('h1')).toBeVisible();
                await expect(page.locator('.logo-image')).toBeVisible();
                await expect(page.locator('.legal-links a')).toHaveCount(2);
                expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
                await page.locator('.legal-links').scrollIntoViewIfNeeded();
                await expect(page.locator('.legal-links')).toBeInViewport();
            }
            await page.goto('/register');
            await expect(page.locator('#email')).toHaveJSProperty('required', true);
            await expect(page.locator('#contact_number')).toHaveJSProperty('required', true);
            await page.locator('#registered_owner_status').selectOption('married');
            await expect(page.locator('#spouse_name')).toBeVisible();
            await expect(page.locator('#spouse_name')).toHaveJSProperty('required', true);
            await page.locator('#registered_owner_status').selectOption('single');
            await expect(page.locator('#spouse_name')).toBeHidden();
            await expect(page.locator('button[type="submit"]')).toHaveText('Create Landowner Account');
            await page.locator('.legal-links a').first().click();
            await expect(page).toHaveURL(/\/privacy-policy$/);
        });
    }
});
