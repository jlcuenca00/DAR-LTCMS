import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const system = readFileSync('resources/js/ui-ux-system.js', 'utf8').replace(/export \{ initUiUxSystem \};/, '');
const lastMile = readFileSync('resources/js/ui-ux-last-mile.js', 'utf8');
const review = readFileSync('resources/views/staff/applications/show.blade.php', 'utf8').match(/<style>([\s\S]*?)<\/style>/)[1];
const form4 = readFileSync('resources/views/staff/applications/partials/form4-attestation-recommendation.blade.php', 'utf8').match(/<style>([\s\S]*?)<\/style>/)[1];
const styles = ['resources/css/app.css', 'resources/css/ui-ux-last-mile.css'].map(path => readFileSync(path, 'utf8')).join('\n');

for (const width of [390, 1280]) {
    test(`review disclosures retain one control and reveal invalid collapsed fields at ${width}px`, async ({ page }) => {
        await page.setViewportSize({ width, height: 900 });
        await page.route('**/review-controls-fixture', route => route.fulfill({ contentType: 'text/html', body: '<html><body></body></html>' }));
        await page.goto('/review-controls-fixture');
        await page.setContent(`<main class="staff-shell application-review-page">
            <details id="ltc-form-no-4-review" class="review-panel ltc-form-accordion">
                <summary class="review-panel-header"><div><h2 class="review-panel-title">LTC Form No. 4 — Certification, Attestation and Recommendation</h2></div><div class="ltc-form-accordion-meta"><span class="ltc-form-accordion-status">Requirements pending</span><i class="ltc-form-accordion-chevron">⌄</i></div></summary>
                <div class="review-panel-body">Review fields</div>
            </details>
            <section class="review-panel requirement-group-panel is-collapsed">
                <div class="review-panel-header"><div class="party-heading-block"><div class="party-heading-copy"><h2 class="review-panel-title">Transferor Requirements</h2></div><div class="requirement-group-actions"><span class="party-group-badge">Transferor side</span><button data-requirement-group-toggle>Expand</button></div></div></div>
                <div class="review-panel-body">Documents</div>
            </section>
            <details id="landowner-links" class="review-panel landowner-links-disclosure">
                <summary class="review-panel-header">Landowner Record Links</summary>
                <form><details class="landowner-link-card"><summary class="landowner-link-heading">Transferor 1</summary><div class="landowner-link-card-body"><label for="share">Share</label><input id="share" type="number" min="0" value="-1"></div></details><button type="submit">Save Link Changes</button></form>
            </details>
        </main>`);
        await page.addStyleTag({ content: styles + review + form4 });
        await page.addScriptTag({ content: system });
        await page.addScriptTag({ content: lastMile });

        const panel = page.locator('#ltc-form-no-4-review');
        await expect(panel.locator('.ui-review-disclosure-toggle')).toHaveCount(0);
        await expect(panel.locator('.review-panel-body')).toBeHidden();
        await panel.locator('summary').press('Enter');
        await expect(panel.locator('.review-panel-body')).toBeVisible();
        await panel.locator('summary').press('Space');
        await expect(panel.locator('.review-panel-body')).toBeHidden();

        const header = page.locator('.requirement-group-panel > .review-panel-header');
        await expect(header.locator('[data-requirement-group-toggle]')).toHaveCount(0);
        const positions = await header.evaluate(node => ({
            badge: node.querySelector('.party-group-badge').getBoundingClientRect().right,
            arrow: node.querySelector('.ui-requirement-group-chevron').getBoundingClientRect().left,
        }));
        expect(positions.arrow).toBeGreaterThanOrEqual(positions.badge);
        await header.press('Enter');
        await expect(page.locator('.requirement-group-panel > .review-panel-body')).toBeVisible();

        // Native validation must reveal both levels before focusing an invalid share.
        await page.locator('#landowner-links form').evaluate(form => form.requestSubmit());
        await expect(page.locator('#landowner-links')).toHaveAttribute('open', '');
        await expect(page.locator('.landowner-link-card')).toHaveAttribute('open', '');
        await expect(page.locator('#share')).toBeFocused();
        await expect(page.locator('#share')).toHaveAttribute('aria-invalid', 'true');
    });
}
