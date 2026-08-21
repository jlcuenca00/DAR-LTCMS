import { test, expect } from '@playwright/test';

const staffCredentials = {
    username: process.env.E2E_STAFF_USERNAME || '',
    password: process.env.E2E_STAFF_PASSWORD || '',
};

async function waitForUiUx(page) {
    await page.waitForFunction(() => document.documentElement.classList.contains('dar-ui-ux-ready'));
}

async function loginAsStaff(page) {
    await page.goto('/login');
    await waitForUiUx(page);
    await page.locator('#username').fill(staffCredentials.username);
    await page.locator('#password').fill(staffCredentials.password);
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/staff\/dashboard/);
    await waitForUiUx(page);
}

test.describe('public UI UX baseline', () => {
    test('login uses conventional sign-in hierarchy and checkbox treatment', async ({ page }, testInfo) => {
        test.skip(testInfo.project.name !== 'chromium-responsive', 'Run once in desktop Chromium.');
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/login');
        await waitForUiUx(page);

        await expect(page.locator('.login-button')).toHaveText('Sign in');
        await expect(page.locator('.forgot-link')).toHaveText('Need help signing in?');

        const radius = await page.locator('.remember-control').evaluate((node) => getComputedStyle(node).borderRadius);
        expect(parseFloat(radius)).toBeLessThanOrEqual(5);
    });
});

test.describe('authenticated UI UX behavior', () => {
    test.beforeEach(async ({ page }, testInfo) => {
        test.skip(testInfo.project.name !== 'chromium-responsive', 'Authenticated UI UX checks run once.');
        test.skip(!staffCredentials.username || !staffCredentials.password, 'Staff E2E credentials are required.');
        await loginAsStaff(page);
    });

    test('active Staff navigation is announced as current', async ({ page }) => {
        await page.goto('/staff/dashboard');
        await waitForUiUx(page);
        await expect(page.locator('.staff-side-link.active')).toHaveAttribute('aria-current', 'page');
    });

    test('application intake exposes section navigation and conditional fields without changing submission names', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/staff/applications/create');
        await waitForUiUx(page);

        await expect(page.locator('.ui-section-nav')).toBeVisible();
        await expect(page.locator('#authorized_representative_name').locator('xpath=ancestor::*[contains(@class,"field-group")][1]')).toBeHidden();

        await page.locator('#applicant_type').selectOption('authorized_representative');
        await expect(page.locator('#authorized_representative_name')).toBeVisible();

        const radioGroups = page.locator('.ui-radio-group');
        expect(await radioGroups.count()).toBeGreaterThanOrEqual(3);
        await expect(page.locator('#retention_certificate_required')).toBeHidden();
        await expect(page.locator('#retention_certificate_required')).toHaveAttribute('name', 'retention_certificate_required');
    });

    test('user role disclosure shows landowner linking only when relevant', async ({ page }) => {
        await page.goto('/staff/users/create');
        await waitForUiUx(page);

        const role = page.locator('select[name="role"]');
        const landowner = page.locator('select[name="landowner_id"]');
        const disclosure = landowner.locator('xpath=ancestor::details[contains(@class,"user-disclosure")][1]');

        await expect(disclosure).toBeHidden();
        await role.selectOption('landowner');
        await expect(disclosure).toBeVisible();
        await expect(disclosure).toHaveAttribute('open', '');
    });

    test('application review exposes final-decision scope and collapsible LTC detail panels when records exist', async ({ page }) => {
        await page.goto('/staff/applications');
        await waitForUiUx(page);

        const firstApplication = page.locator('.application-desktop-table tbody a.staff-link').first();
        if (!(await firstApplication.count())) test.skip(true, 'No application record is available for review.');

        await firstApplication.click();
        await waitForUiUx(page);
        await expect(page.locator('.ui-decision-scope-note')).toBeVisible();

        const disclosure = page.locator('.ui-review-disclosure').first();
        if (await disclosure.count()) {
            const toggle = disclosure.locator('.ui-review-disclosure-toggle');
            await expect(toggle).toHaveAttribute('aria-expanded', 'false');
            await toggle.click();
            await expect(toggle).toHaveAttribute('aria-expanded', 'true');
        }
    });

    test('requirement group cards toggle from the full header without an Expand button', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/staff/applications');
        await waitForUiUx(page);

        const firstApplication = page.locator('.application-desktop-table tbody a.staff-link').first();
        if (!(await firstApplication.count())) test.skip(true, 'No application record is available for review.');

        await firstApplication.click();
        await waitForUiUx(page);

        const panel = page.locator('.requirement-group-panel').first();
        if (!(await panel.count())) test.skip(true, 'No requirement group is available for review.');

        const header = panel.locator(':scope > .review-panel-header');
        const body = panel.locator(':scope > .review-panel-body');

        // Wait past every DOMContentLoaded callback so a late legacy button cannot slip past the assertion.
        await page.waitForTimeout(100);
        await expect(panel.locator('[data-requirement-group-toggle]')).toHaveCount(0);
        await expect(panel.locator('.ui-requirement-group-chevron')).toHaveCount(1);
        await expect(header).toHaveAttribute('role', 'button');
        await expect(header).toHaveAttribute('tabindex', '0');
        await expect(header).toHaveAttribute('aria-expanded', 'false');
        await expect(body).toBeHidden();

        const chevronBeforeBadge = await header.evaluate((node) => {
            const chevron = node.querySelector('.ui-requirement-group-chevron');
            const actions = node.querySelector('.requirement-group-actions');
            const badge = node.querySelector('.party-group-badge');
            return Boolean(chevron && actions && badge && chevron.nextElementSibling === actions && badge.parentElement === actions);
        });
        expect(chevronBeforeBadge).toBe(true);

        await header.click();
        await expect(header).toHaveAttribute('aria-expanded', 'true');
        await expect(body).toBeVisible();

        await header.press('Space');
        await expect(header).toHaveAttribute('aria-expanded', 'false');
        await expect(body).toBeHidden();
    });

    test('Application Actions backdrop covers the complete browser viewport', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto('/staff/applications');
        await waitForUiUx(page);

        const firstApplication = page.locator('.application-desktop-table tbody a.staff-link').first();
        if (!(await firstApplication.count())) test.skip(true, 'No application record is available for review.');

        await firstApplication.click();
        await waitForUiUx(page);

        const trigger = page.locator('#workflow-modal-open');
        if (!(await trigger.count())) test.skip(true, 'Application Actions is not available for this record.');

        const backdrop = page.locator('body > .workflow-modal-backdrop');
        await expect(backdrop).toHaveAttribute('data-ui-viewport-portal', 'true');
        await trigger.click();
        await expect(backdrop).toBeVisible();

        const bounds = await backdrop.evaluate((node) => {
            const rect = node.getBoundingClientRect();
            return { top: rect.top, left: rect.left, width: rect.width, height: rect.height };
        });
        const viewport = page.viewportSize();

        expect(Math.abs(bounds.top)).toBeLessThanOrEqual(1);
        expect(Math.abs(bounds.left)).toBeLessThanOrEqual(1);
        expect(bounds.width).toBeGreaterThanOrEqual(viewport.width - 1);
        expect(bounds.height).toBeGreaterThanOrEqual(viewport.height - 1);
    });

    test('Audit Logs identifies Philippine Time explicitly', async ({ page }) => {
        await page.goto('/staff/audit-logs');
        await waitForUiUx(page);
        await expect(page.locator('.ui-timezone-note')).toContainText('Philippine Time (PHT)');
    });
});
