import { test, expect } from '@playwright/test';

const viewportMatrix = [
    { width: 320, height: 568, label: '320x568' },
    { width: 360, height: 800, label: '360x800' },
    { width: 390, height: 844, label: '390x844' },
    { width: 430, height: 932, label: '430x932' },
    { width: 600, height: 960, label: '600x960' },
    { width: 768, height: 1024, label: '768x1024' },
    { width: 834, height: 1194, label: '834x1194' },
    { width: 1024, height: 1366, label: '1024x1366' },
    { width: 1100, height: 900, label: '1100-boundary' },
    { width: 1101, height: 900, label: '1101-boundary' },
    { width: 1280, height: 800, label: '1280x800' },
    { width: 1440, height: 900, label: '1440x900' },
];

const staffRouteMatrix = [
    '/staff/dashboard',
    '/staff/applications',
    '/staff/applications/create',
    '/staff/records/landowners',
    '/staff/records/landowners/create',
    '/staff/records/parcels',
    '/staff/records/parcels/create',
    '/staff/legacy-records',
    '/staff/legacy-records/create',
    '/staff/source-record-packages/create',
    '/staff/source-record-package-imports/create',
    '/staff/parcel-map',
    '/staff/reports/monitoring',
    '/staff/audit-logs',
    '/staff/users',
    '/staff/users/create',
    '/profile',
    '/notifications',
];

const staffCredentials = {
    username: process.env.E2E_STAFF_USERNAME || '',
    password: process.env.E2E_STAFF_PASSWORD || '',
};

async function waitForResponsiveController(page) {
    await page.waitForFunction(() => document.documentElement.dataset.responsiveHardening === 'true');
}

async function assertNoPageLevelHorizontalOverflow(page, context) {
    const dimensions = await page.evaluate(() => ({
        htmlClient: document.documentElement.clientWidth,
        htmlScroll: document.documentElement.scrollWidth,
        bodyClient: document.body?.clientWidth || 0,
        bodyScroll: document.body?.scrollWidth || 0,
    }));

    expect(
        dimensions.htmlScroll,
        `${context}: documentElement should not horizontally overflow`,
    ).toBeLessThanOrEqual(dimensions.htmlClient + 1);

    expect(
        dimensions.bodyScroll,
        `${context}: body should not horizontally overflow`,
    ).toBeLessThanOrEqual(Math.max(dimensions.bodyClient, dimensions.htmlClient) + 1);
}

async function assertCompactFormControlsAreTouchSafe(page, context) {
    const undersized = await page.locator(
        'button, summary, input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select, textarea',
    ).evaluateAll((nodes) => nodes
        .filter((node) => {
            const style = getComputedStyle(node);
            const rect = node.getBoundingClientRect();
            return style.display !== 'none'
                && style.visibility !== 'hidden'
                && Number(style.opacity) !== 0
                && rect.width > 0
                && rect.height > 0;
        })
        .map((node) => {
            const rect = node.getBoundingClientRect();
            return {
                tag: node.tagName,
                id: node.id,
                className: String(node.className || ''),
                width: Math.round(rect.width),
                height: Math.round(rect.height),
            };
        })
        .filter((item) => item.height < 44));

    expect(undersized, `${context}: visible form controls should be at least 44px tall`).toEqual([]);
}

async function loginAsStaff(page) {
    await page.goto('/login');
    await page.locator('#username').fill(staffCredentials.username);
    await page.locator('#password').fill(staffCredentials.password);
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/staff\/dashboard/);
    await waitForResponsiveController(page);
}

test.describe('public responsive reflow matrix', () => {
    for (const viewport of viewportMatrix) {
        test(`login reflows at ${viewport.label}`, async ({ page }, testInfo) => {
            test.skip(testInfo.project.name !== 'chromium-responsive', 'Full matrix runs once in desktop Chromium.');

            await page.setViewportSize({ width: viewport.width, height: viewport.height });
            await page.goto('/login');
            await waitForResponsiveController(page);
            await assertNoPageLevelHorizontalOverflow(page, `login ${viewport.label}`);

            if (viewport.width <= 760) {
                await assertCompactFormControlsAreTouchSafe(page, `login ${viewport.label}`);
            }
        });
    }

    test('login remains usable in phone landscape', async ({ page }, testInfo) => {
        test.skip(testInfo.project.name !== 'chromium-responsive', 'Orientation case runs once.');
        await page.setViewportSize({ width: 844, height: 390 });
        await page.goto('/login');
        await waitForResponsiveController(page);
        await assertNoPageLevelHorizontalOverflow(page, 'login phone landscape');
        await expect(page.locator('#username')).toBeVisible();
        await expect(page.locator('#password')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('coarse pointer receives the stronger target contract', async ({ page }, testInfo) => {
        test.skip(testInfo.project.name !== 'chromium-coarse-pointer', 'Coarse-pointer assertion runs only in touch project.');
        await page.goto('/login');
        await waitForResponsiveController(page);
        await assertNoPageLevelHorizontalOverflow(page, 'coarse pointer login');
        await assertCompactFormControlsAreTouchSafe(page, 'coarse pointer login');
    });
});

test.describe('authenticated Staff responsive route matrix', () => {
    test.beforeEach(async ({ page }, testInfo) => {
        test.skip(
            testInfo.project.name !== 'chromium-responsive',
            'Authenticated route matrix runs once in desktop Chromium.',
        );
        test.skip(
            !staffCredentials.username || !staffCredentials.password,
            'Set E2E_STAFF_USERNAME and E2E_STAFF_PASSWORD to exercise authenticated Staff routes.',
        );
        await loginAsStaff(page);
    });

    const authenticatedViewports = [
        { width: 320, height: 568, label: '320x568' },
        { width: 390, height: 844, label: '390x844' },
        { width: 768, height: 1024, label: '768x1024' },
        { width: 1100, height: 900, label: '1100-boundary' },
        { width: 1101, height: 900, label: '1101-boundary' },
        { width: 1440, height: 900, label: '1440x900' },
    ];

    for (const viewport of authenticatedViewports) {
        test(`Staff view families do not cause page overflow at ${viewport.label}`, async ({ page }) => {
            await page.setViewportSize({ width: viewport.width, height: viewport.height });

            for (const route of staffRouteMatrix) {
                const response = await page.goto(route);
                expect(response?.status(), `${route} should render successfully`).toBeLessThan(500);
                await waitForResponsiveController(page);
                await assertNoPageLevelHorizontalOverflow(page, `${route} ${viewport.label}`);
            }
        });
    }

    test('1100 and 1101 preserve the intended portal boundary', async ({ page }) => {
        await page.setViewportSize({ width: 1100, height: 900 });
        await page.goto('/staff/dashboard');
        await waitForResponsiveController(page);
        await expect(page.locator('.dar-mobile-portal-header')).toBeVisible();
        await expect(page.locator('.staff-sidebar')).toBeHidden();

        await page.setViewportSize({ width: 1101, height: 900 });
        await expect(page.locator('.dar-mobile-portal-header')).toBeHidden();
        await expect(page.locator('.staff-sidebar')).toBeVisible();
    });

    test('WCAG text spacing override does not introduce page overflow', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/staff/dashboard');
        await waitForResponsiveController(page);
        await page.addStyleTag({
            content: `
                p, li, label, input, select, textarea, button, a, td, th {
                    line-height: 1.5 !important;
                    letter-spacing: .12em !important;
                    word-spacing: .16em !important;
                }
                p { margin-bottom: 2em !important; }
            `,
        });
        await assertNoPageLevelHorizontalOverflow(page, 'Staff dashboard with WCAG text spacing');
    });

    test('keyboard focus remains inside the visual viewport with sticky mobile navigation', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/staff/dashboard');
        await waitForResponsiveController(page);

        for (let index = 0; index < 10; index += 1) {
            await page.keyboard.press('Tab');
            const focused = await page.evaluate(() => {
                const element = document.activeElement;
                if (!element || element === document.body) return null;
                const rect = element.getBoundingClientRect();
                return {
                    top: rect.top,
                    bottom: rect.bottom,
                    height: rect.height,
                    viewportHeight: window.innerHeight,
                };
            });

            if (!focused || focused.height === 0) continue;
            expect(focused.bottom, `Tab stop ${index + 1} should not be completely above the viewport`).toBeGreaterThan(0);
            expect(focused.top, `Tab stop ${index + 1} should not be completely below the viewport`).toBeLessThan(focused.viewportHeight);
        }
    });

    test('dense Audit Logs table keeps overflow inside its own region', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/staff/audit-logs');
        await waitForResponsiveController(page);
        await assertNoPageLevelHorizontalOverflow(page, 'Audit Logs');

        const denseTable = page.locator('.no-responsive-table').first();
        if (await denseTable.count()) {
            const region = denseTable.locator('xpath=ancestor::*[contains(@class,"responsive-local-scroll")][1]');
            await expect(region).toHaveAttribute('role', 'region');
            await expect(region).toHaveAttribute('tabindex', '0');
        }
    });

    test('reduced-motion preference disables meaningful UI transitions', async ({ page }) => {
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/staff/dashboard');
        await waitForResponsiveController(page);

        const duration = await page.locator('.dar-mobile-portal-nav-item').first().evaluate((node) => getComputedStyle(node).transitionDuration);
        expect(['0s', '0.001s']).toContain(duration);
    });
});
