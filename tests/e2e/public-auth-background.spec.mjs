import { test, expect } from '@playwright/test';

test('public page backgrounds stay fixed while content scrolls', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'chromium-responsive', 'Check desktop and mobile once.');
    for (const width of [390, 1440]) {
        await page.setViewportSize({ width, height: 700 });
        for (const path of ['/register', '/privacy-policy', '/terms-of-service']) {
            await page.goto(path);
            const bg = page.locator('.public-auth .login-bg');
            await expect(bg).toHaveCSS('position', 'fixed');
            await expect(bg).toHaveCSS('background-size', 'cover');
            const before = await bg.boundingBox();
            const contentBefore = await page.locator('.login-content').boundingBox();
            await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
            await expect.poll(() => page.evaluate(() => window.scrollY)).toBeGreaterThan(0);
            const after = await bg.boundingBox();
            const contentAfter = await page.locator('.login-content').boundingBox();
            expect(after).toEqual(before);
            expect(after.height).toBe(700);
            expect(contentAfter.y).toBeLessThan(contentBefore.y);
            expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
        }
    }
});
