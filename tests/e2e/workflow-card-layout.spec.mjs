import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const reviewCss = readFileSync('resources/views/staff/applications/show.blade.php', 'utf8').match(/<style>([\s\S]*?)<\/style>/)[1];
const flow = readFileSync('resources/js/application-citizens-charter-flow.js', 'utf8');

for (const width of [390, 1280]) {
    test(`workflow stage fields keep a compact submit footer at ${width}px`, async ({ page }) => {
        await page.setViewportSize({ width, height: 900 });
        await page.route('**/workflow-layout-fixture', route => route.fulfill({ contentType: 'text/html', body: '<html><body></body></html>' }));
        await page.goto('/workflow-layout-fixture');
        await page.setContent(`<div class="application-review-page"><div class="workflow-decision-grid">
            <form action="/staff/applications/1/submit" class="workflow-decision-card approve-card">
                <div class="workflow-decision-heading"><div><p class="workflow-action-title"></p><p class="workflow-action-copy"></p></div></div>
                <div class="workflow-decision-actions"><div class="workflow-decision-note"></div></div>
                <button type="submit" class="staff-button staff-button-primary"><i></i> Record Workflow Update</button>
            </form>
            <form class="workflow-decision-card compliance-card"><div class="workflow-decision-heading">Request Compliance / Action Required</div><label>Details<textarea rows="8"></textarea></label><button type="submit" class="staff-button">Request Compliance</button></form>
        </div></div>`);
        await page.addStyleTag({ content: reviewCss + '\n.staff-button { display:inline-flex; align-items:center; min-height:44px; padding:10px 14px; } textarea { display:block; width:100%; }' });
        await page.addScriptTag({ content: flow });
        await page.evaluate(() => configureAdvanceForm({
            status: 'pending_legal_review', next_status: 'awaiting_payment',
            workflow_action_label: 'Record Completeness Review and Payment Order',
        }));
        const advance = page.locator('form[action$="/submit"]');
        const button = advance.getByRole('button', { name: 'Record Workflow Update' });
        await expect(advance.getByRole('checkbox', { name: 'Applicant is a juridical entity' })).toBeVisible();
        const reference = advance.locator('input[name="payment_order_reference"]');
        await expect(reference).toBeVisible();
        const positions = await button.evaluate(node => ({
            height: node.getBoundingClientRect().height,
            top: node.getBoundingClientRect().top,
            fieldBottom: node.form.querySelector('input[name="payment_order_reference"]').getBoundingClientRect().bottom,
        }));
        expect(positions.height).toBeGreaterThanOrEqual(44);
        expect(positions.height).toBeLessThan(70);
        expect(positions.top).toBeGreaterThan(positions.fieldBottom);
        await reference.fill('PO-TEST');
        expect(await advance.evaluate(form => new FormData(form).get('payment_order_reference'))).toBe('PO-TEST');
    });
}
