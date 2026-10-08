import { test, expect } from '@playwright/test';

const username = process.env.E2E_STAFF_USERNAME;
const password = process.env.E2E_STAFF_PASSWORD;
const decisionDate = process.env.E2E_JOURNEY_DATE;

async function login(page, account, role) {
    await page.goto('/login');
    await page.locator('#username').fill(account);
    await page.locator('#password').fill(password);
    const tourPath = `/onboarding-tours/${role}_portal`;
    const tourStatus = page.waitForResponse(response =>
        new URL(response.url()).pathname === tourPath && response.request().method() === 'GET');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(new RegExp(`/${role}/dashboard`));
    const loaded = await tourStatus;
    expect(loaded.status()).toBe(200);
    if (!(await loaded.json()).seen) {
        const dismissal = page.waitForResponse(response =>
            new URL(response.url()).pathname === tourPath && response.request().method() === 'PATCH');
        await page.getByRole('button', { name: 'Skip Tour', exact: true }).click();
        expect((await dismissal).status()).toBe(200);
        await expect(page.locator('.onboarding-welcome-layer')).toHaveCount(0);
    }
}

async function state(page, id) {
    const response = await page.request.get(`/staff/applications/${id}/workflow-state`);
    expect(response.status()).toBe(200);
    return response.json();
}

async function review(page, id, expectedStatus) {
    const loaded = page.waitForResponse(response =>
        new URL(response.url()).pathname === `/staff/applications/${id}/workflow-state`
        && response.request().method() === 'GET');
    const response = await page.goto(`/staff/applications/${id}`);
    expect(response.status()).toBe(200);
    expect((await (await loaded).json()).status).toBe(expectedStatus);
    await page.locator('#workflow-overview [data-workflow-modal-open]').click();
    await expect(page.locator('#workflow-modal')).toBeVisible();
    const modal = await page.locator('#workflow-modal .workflow-modal-card').boundingBox();
    const viewport = page.viewportSize();
    expect(modal.x).toBeGreaterThanOrEqual(0);
    expect(modal.x + modal.width).toBeLessThanOrEqual(viewport.width + 1);
}

async function submit(page, form, button) {
    const action = new URL(await form.getAttribute('action'), page.url()).pathname;
    const posted = page.waitForResponse(response =>
        new URL(response.url()).pathname === action && response.request().method() === 'POST');
    await form.getByRole('button', { name: new RegExp(`${button}$`) }).click();
    expect((await posted).status(), `Real form POST ${action}`).toBe(302);
    await expect(page.locator('.review-alert-success')).toBeVisible();
    await expect(page.locator('.review-alert-error')).toHaveCount(0);
}

async function ownerCard(page, id) {
    const response = await page.goto('/landowner/applications');
    expect(response.status()).toBe(200);
    const card = page.locator(`#application-${id}`);
    await expect(card).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1)).toBe(true);
    return card;
}

async function notification(page, code, title) {
    const response = await page.goto('/notifications');
    expect(response.status()).toBe(200);
    await expect(page.locator('.notification-center-item').filter({ hasText: code })
        .filter({ hasText: title })).toHaveCount(1);
}

async function outputBlocked(page, id) {
    const response = await page.request.get(`/landowner/applications/${id}/decision-output`, { maxRedirects: 0 });
    expect([302, 403]).toContain(response.status());
    expect(response.headers()['content-type'] || '').not.toContain('application/pdf');
}

async function clearanceSnapshot(page, id) {
    const response = await page.request.get(`/staff/applications/${id}/clearance`);
    expect(response.status()).toBe(200);
    const content = (await response.text()).match(/<main class="ltc-page">([\s\S]*?)<\/main>/);
    expect(content, 'The real frozen Form No. 5 output must render').toBeTruthy();
    return content[1];
}

for (const mode of [
    { name: 'desktop', project: 'chromium-responsive', prefix: 'E2E_', suffix: '' },
    { name: 'phone', project: 'chromium-coarse-pointer', prefix: 'E2E_PHONE_', suffix: '-PHONE' },
]) {
test.describe(`real ${mode.name} browser-to-server journeys`, () => {
    const complianceId = process.env[mode.prefix + 'COMPLIANCE_APPLICATION_ID'];
    const approvalId = process.env[mode.prefix + 'APPROVAL_APPLICATION_ID'];
    const geometryId = process.env[mode.prefix + 'GEOMETRY_PARCEL_ID'];
    test.describe.configure({ retries: 0 });
    test.setTimeout(90_000);
    test.beforeEach(async ({ page }, testInfo) => {
        test.skip(testInfo.project.name !== mode.project, 'Each device uses its own mutable server fixtures.');
        expect(username, 'Staff credentials are required').toBeTruthy();
        expect(password, 'Staff credentials are required').toBeTruthy();
        for (const id of [complianceId, approvalId, geometryId]) {
            expect(id, 'Dedicated server journey fixture IDs are required').toMatch(/^\d+$/);
        }
        expect(decisionDate, 'Server decision date is required').toMatch(/^\d{4}-\d{2}-\d{2}$/);
        expect(await page.evaluate(() => matchMedia('(pointer: coarse)').matches)).toBe(mode.name === 'phone');
    });

    test('compliance request and resolution persist and reach the linked Landowner', async ({ page, browser }, testInfo) => {
        const ownerContext = await browser.newContext({ viewport: testInfo.project.use.viewport,
            hasTouch: testInfo.project.use.hasTouch, isMobile: testInfo.project.use.isMobile });
        const owner = await ownerContext.newPage();
        try {
            await login(page, username, 'staff');
            await login(owner, `${username}.owner${mode.suffix.toLowerCase()}`, 'landowner');
            await review(page, complianceId, 'pending_legal_review');
            const initial = await state(page, complianceId);
            expect(initial.can_request_compliance).toBe(true);
            const request = page.locator('[data-compliance-request-form]');
            await request.locator('[name="category"]').selectOption('other');
            await request.locator('[name="other_category"]').fill('Survey clarification');
            await request.locator('[name="details"]').fill('Please clarify the survey reference for this parcel.');
            await request.locator('[name="requested_items"]').fill('Corrected survey reference');
            await submit(page, request, 'Request Compliance');
            await page.reload();
            const requested = await state(page, complianceId);
            expect(requested.status).toBe('returned_for_compliance');
            expect(requested.workflow_revision).toBeGreaterThan(initial.workflow_revision);
            expect(requested.active_compliance_notice.details).toBe('Please clarify the survey reference for this parcel.');
            expect(requested.active_compliance_notice.resume_status).toBe('pending_legal_review');
            const card = await ownerCard(owner, complianceId);
            await expect(card.locator('.lo-denial-reason')).toContainText('Survey clarification');
            await expect(card).toContainText('Corrected survey reference');
            await notification(owner, `E2E-JOURNEY-COMPLIANCE${mode.suffix}`, 'Action required for your clearance application');

            await review(page, complianceId, 'returned_for_compliance');
            const resolve = page.locator('form[action$="/compliance/resolve"]');
            await resolve.locator('[name="resolution_note"]').fill('Reviewed the corrected survey reference.');
            await submit(page, resolve, 'Mark Compliance Resolved');
            await page.reload();
            const resumed = await state(page, complianceId);
            expect(resumed.status).toBe('pending_legal_review');
            expect(resumed.active_compliance_notice).toBeNull();
            expect(resumed.workflow_revision).toBeGreaterThan(requested.workflow_revision);
            await expect((await ownerCard(owner, complianceId)).locator('.lo-denial-reason')).toHaveCount(0);
            await notification(owner, `E2E-JOURNEY-COMPLIANCE${mode.suffix}`, 'Compliance issue resolved');
        } finally {
            await ownerContext.close();
        }
    });

    test('confirmed approval and release persist without exposing output early', async ({ page, browser }, testInfo) => {
        const ownerContext = await browser.newContext({ viewport: testInfo.project.use.viewport,
            hasTouch: testInfo.project.use.hasTouch, isMobile: testInfo.project.use.isMobile });
        const owner = await ownerContext.newPage();
        try {
            await login(page, username, 'staff');
            await login(owner, `${username}.owner${mode.suffix.toLowerCase()}`, 'landowner');
            await review(page, approvalId, 'for_releasing');
            const initial = await state(page, approvalId);
            expect(initial.can_finalize_decision).toBe(true);
            await outputBlocked(owner, approvalId);
            const approve = page.locator('form[action$="/approve"]');
            await approve.locator('[name="decision_officer_name"]').fill('Journey PARPO II');
            await approve.locator('[name="decision_date"]').fill(decisionDate);
            await approve.getByRole('button', { name: /Record Approved Decision$/ }).click();
            await expect(page.locator('#decision-confirm-modal')).toBeVisible();
            await expect(page.locator('#decision-confirm-warning')).toBeVisible();
            await expect(page.locator('#decision-confirm-warning')).toContainText('This finalizes and locks the application.');
            await page.locator('#decision-confirm-cancel').click();
            expect((await state(page, approvalId)).workflow_revision).toBe(initial.workflow_revision);
            expect((await state(page, approvalId)).status).toBe('for_releasing');

            await page.locator('#workflow-overview [data-workflow-modal-open]').click();
            await approve.getByRole('button', { name: /Record Approved Decision$/ }).click();
            const posted = page.waitForResponse(response =>
                new URL(response.url()).pathname === `/staff/applications/${approvalId}/approve`
                && response.request().method() === 'POST');
            await page.locator('#decision-confirm-submit').click();
            expect((await posted).status()).toBe(302);
            await expect(page.locator('.review-alert-success')).toBeVisible();
            await page.reload();
            const approved = await state(page, approvalId);
            expect(approved.status).toBe('approved');
            expect(approved.release_status).toBe('not_ready');
            expect(approved.decision_officer_name).toBe('Journey PARPO II');
            expect(approved.clearance_integrity_valid).toBe(true);
            const frozenOutput = await clearanceSnapshot(page, approvalId);
            let card = await ownerCard(owner, approvalId);
            await expect(card).toContainText('Decision recorded');
            await expect(card.locator('.lo-clearance-link')).toHaveCount(0);
            await outputBlocked(owner, approvalId);
            await notification(owner, `E2E-JOURNEY-APPROVAL${mode.suffix}`, 'Final clearance decision recorded');

            await review(page, approvalId, 'approved');
            await submit(page, page.locator('form[action$="/ready-for-release"]'), 'Mark Ready for Release');
            await page.reload();
            const ready = await state(page, approvalId);
            expect(ready.release_status).toBe('ready_for_release');
            expect(ready.clearance_integrity_valid).toBe(true);
            expect(await clearanceSnapshot(page, approvalId)).toBe(frozenOutput);
            card = await ownerCard(owner, approvalId);
            await expect(card).toContainText('Ready for Release');
            await expect(card.locator('.lo-clearance-link')).toHaveCount(0);
            await outputBlocked(owner, approvalId);
            await notification(owner, `E2E-JOURNEY-APPROVAL${mode.suffix}`, 'Decision output ready for release');

            await review(page, approvalId, 'approved');
            const release = page.locator('form[action$="/release"]');
            await release.locator('[name="release_recipient_name"]').fill('Journey Client');
            await release.locator('[name="release_logbook_reference"]').fill('LOG-E2E-JOURNEY');
            await release.locator('[name="csm_status"]').selectOption('received');
            await submit(page, release, 'Confirm Release to Client');
            await page.reload();
            const released = await state(page, approvalId);
            expect(released.status).toBe('approved');
            expect(released.release_status).toBe('released');
            expect(released.release_recipient_name).toBe('Journey Client');
            expect(released.clearance_integrity_valid).toBe(true);
            expect(await clearanceSnapshot(page, approvalId)).toBe(frozenOutput);
            expect(released.can_release_output).toBe(false);
            card = await ownerCard(owner, approvalId);
            await expect(card).toContainText('Released to Client');
            await card.getByRole('link', { name: 'View Decision Output', exact: true }).click();
            await expect(owner).toHaveURL(new RegExp(`/landowner/applications/${approvalId}/decision-output`));
            await expect(owner.locator('body')).toContainText('Journey PARPO II');
            await notification(owner, `E2E-JOURNEY-APPROVAL${mode.suffix}`, 'Decision output released');
        } finally {
            await ownerContext.close();
        }
    });

    test('real PRS92 editor rejects invalid input, saves, reloads and blocks a stale second editor', async ({ page, browser }, testInfo) => {
        const secondContext = await browser.newContext({ viewport: testInfo.project.use.viewport,
            hasTouch: testInfo.project.use.hasTouch, isMobile: testInfo.project.use.isMobile });
        const second = await secondContext.newPage();
        const url = `/geodetic/parcels/${geometryId}/geometry/edit`;
        const points = [[500000, 1000000], [500100, 1000000], [500100, 1000100], [500000, 1000100]];
        const fillPoints = async (target, offset = 0) => {
            for (let i = 0; i < points.length; i++) {
                await target.locator('[data-geojson-x]').nth(i).fill(String(points[i][0] + offset));
                await target.locator('[data-geojson-y]').nth(i).fill(String(points[i][1]));
            }
            await target.locator('[data-geojson-build]').click();
            const converted = JSON.parse(await target.locator('#geometry_geojson').inputValue());
            expect(converted.type).toBe('Polygon');
            expect(converted.coordinates[0]).toHaveLength(5);
            expect(converted.coordinates[0][0][0]).toBeGreaterThan(120);
            expect(converted.coordinates[0][0][0]).toBeLessThan(126);
        };
        try {
            await login(page, `${username}.geo`, 'geodetic');
            await page.goto(url);
            await expect(page.locator('[name="geometry_version"]')).toHaveValue('0');
            await page.locator('[data-geojson-x]').first().fill('500000');
            let mutationCount = 0;
            page.on('request', request => {
                if (new URL(request.url()).pathname === `/geodetic/parcels/${geometryId}/geometry`
                    && request.method() === 'POST') mutationCount++;
            });
            await page.locator('[data-save-geometry]').click();
            await expect(page.locator('[data-geojson-message]')).toContainText('Complete both coordinates');
            expect(mutationCount).toBe(0);
            await page.reload();
            await expect(page.locator('[name="geometry_version"]')).toHaveValue('0');
            await expect(page.locator('[data-geojson-x]').first()).toHaveValue('');
            await fillPoints(page);

            await login(second, `${username}.geo2`, 'geodetic');
            await second.goto(url);
            await expect(second.locator('[name="geometry_version"]')).toHaveValue('0');
            await fillPoints(second, 200);
            await page.locator('[data-save-geometry]').click();
            await expect(page).toHaveURL(new RegExp(`/geodetic/parcels/${geometryId}$`));
            await expect(page.getByRole('status')).toContainText('saved successfully as version 1');
            expect(mutationCount).toBe(1);

            // The second account retains its actual version-0 token and submits its real form.
            await second.locator('[data-save-geometry]').click();
            await expect(second.locator('[role="alert"]')).toContainText('Your save was blocked');
            await expect(second.locator('[name="geometry_version"]')).toHaveValue('1');
            await second.reload();
            await expect(second.locator('[data-geojson-x]').first()).toHaveValue('500000');
            await expect(second.locator('[data-geojson-x]').nth(1)).toHaveValue('500100');
            await expect(second.locator('.geo-map-editor-history-item').filter({ hasText: 'Version 1' })).toHaveCount(1);
        } finally {
            await secondContext.close();
        }
    });

    test('Audit Activity and Login History switch scope through the real UI and print report', async ({ page }) => {
        await login(page, username, 'staff');
        await page.goto('/staff/audit-logs');
        const views = page.getByRole('navigation', { name: 'Audit views' });
        await expect(views.getByRole('link', { name: 'Activity', exact: true })).toHaveAttribute('aria-current', 'page');
        await expect(page.locator('.audit-table tbody')).not.toContainText('User Login');
        await expect(page.locator('select[name="action"] option[value="user_login"]')).toHaveCount(0);
        await views.getByRole('link', { name: 'Login History', exact: true }).click();
        await expect(page).toHaveURL(/view=logins/);
        await expect(page.locator('.audit-table tbody')).toContainText('User Login');
        await page.locator('[name="actor"]').fill(username);
        await page.getByRole('button', { name: /Apply$/ }).click();
        await expect(page.locator('[name="view"]')).toHaveValue('logins');
        const printLink = page.getByRole('link', { name: 'Print / Save as PDF' });
        const report = await page.request.get(await printLink.getAttribute('href'));
        expect(report.status()).toBe(200);
        expect(await report.text()).toContain('Login History Report');
        await page.getByRole('navigation', { name: 'Audit views' }).getByRole('link', { name: 'Activity', exact: true }).click();
        await expect(page.locator('[name="actor"]')).toHaveValue(username);
        await expect(page.locator('.audit-table tbody')).not.toContainText('User Login');
    });
});
}
