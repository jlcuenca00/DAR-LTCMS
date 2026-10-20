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
            await page.getByRole('button', { name: 'Sign up with email' }).click();
            await expect(page.locator('[data-step-status]')).toHaveText('Step 1 of 4 · Your details');
            await expect(page.locator('#email')).toHaveJSProperty('required', true);
            await expect(page.locator('#contact_number')).toHaveJSProperty('required', true);
            await page.locator('#registered_owner_status').selectOption('married');
            await expect(page.locator('#spouse_name')).toBeVisible();
            await expect(page.locator('#spouse_name')).toHaveJSProperty('required', true);
            await page.locator('#registered_owner_status').selectOption('single');
            await expect(page.locator('#spouse_name')).toBeHidden();
            await expect(page.locator('[data-signup-submit]')).toHaveText('Submit for verification');
            await expect(page.locator('[data-signup-submit]')).toBeHidden();
            await page.locator('#username').fill('wizard_owner');
            await page.locator('#email').fill('owner@example.com');
            await page.locator('#password').fill('StrongPassword321!');
            await page.locator('#password_confirmation').fill('StrongPassword321!');
            await page.locator('#name').fill('Maria Santos');
            await page.locator('#contact_number').fill('09171234567');
            await page.locator('#address').fill('Dumaguete City, Negros Oriental');
            await page.getByRole('button', { name: 'Continue', exact: true }).click();
            await expect(page.locator('[data-step-status]')).toHaveText('Step 2 of 4 · Valid ID');
            await page.locator('#id_type').selectOption('passport');
            const image = { name: 'photo.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII=', 'base64') };
            await page.locator('#id_front').setInputFiles(image);
            await page.getByRole('button', { name: 'Continue', exact: true }).click();
            await page.locator('#selfie').setInputFiles(image);
            await page.getByRole('button', { name: 'Continue', exact: true }).click();
            await expect(page.locator('[data-signup-submit]')).toBeVisible();
            await expect(page.locator('[data-signup-review]')).toContainText('Maria Santos');
            await expect(page.locator('[data-signup-review] img')).toHaveCount(2);
            await expect(page.locator('[data-signup-review]')).not.toContainText('StrongPassword321!');
            await page.getByRole('button', { name: 'Edit valid id' }).click();
            expect(await page.locator('#id_front').evaluate(input => input.files[0].name)).toBe('photo.png');
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);

            await page.locator('.legal-links a').first().click();
            await expect(page).toHaveURL(/\/privacy-policy$/);
        });
    }
});
