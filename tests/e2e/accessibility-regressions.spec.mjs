import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(path, 'utf8');
const dialog = read('resources/js/dialog-focus.js').replace(/export \{ open, close \};/, '');
const system = read('resources/js/ui-ux-system.js').replace(/export \{ initUiUxSystem \};/, '');
const lastMile = read('resources/js/ui-ux-last-mile.js');
const review = read('resources/views/staff/applications/show.blade.php');
const reviewHandlers = review.slice(review.indexOf("            const workflowModal ="), review.lastIndexOf('        });'));
const start = review.indexOf('<div id="decision-confirm-modal"');
const decision = review.slice(start, review.indexOf('<script>', start));
const app = read('resources/js/app.js');
const cropper = app.slice(app.indexOf('function initDarLtcmsProfileCropper()'), app.indexOf("if (document.readyState === 'loading')", app.indexOf('function initDarLtcmsProfileCropper()')));

async function install(page, source) {
    await page.addScriptTag({ content: '(() => {' + source + '})();' });
}

async function workflowFixture(page) {
    await page.setContent('<button id="opener" data-workflow-modal-open>Manage workflow</button><a id="background" href="#">Background</a>' +
        '<div id="already-inert" inert>Existing inert content</div>' +
        '<div id="workflow-modal" aria-hidden="true"><div role="dialog" aria-modal="true">' +
        '<button id="workflow-modal-close-top">Close</button><form id="approval" data-decision-confirm="approve">' +
        '<button id="approve">Approve</button></form></div></div>' + decision);
    await page.addStyleTag({ content: '.workflow-modal-backdrop, .decision-modal-backdrop, #workflow-modal { display:none } .is-open { display:block!important }' });
    await install(page, dialog);
    await install(page, reviewHandlers);
    await page.locator('#opener').click();
}

test('workflow and final confirmation contain focus and restore a visible opener on cancel', async ({ page }) => {
    await workflowFixture(page);
    await expect(page.locator('#workflow-modal-close-top')).toBeFocused();
    await page.keyboard.press('Shift+Tab');
    await expect(page.locator('#approve')).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.locator('#workflow-modal-close-top')).toBeFocused();
    await expect(page.locator('#background')).toHaveAttribute('inert', '');
    await page.locator('#background').evaluate(node => node.focus());
    await expect(page.locator('#workflow-modal-close-top')).toBeFocused();
    await page.locator('#approve').click();
    await expect(page.locator('#decision-confirm-submit')).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.locator('#decision-confirm-cancel')).toBeFocused();
    await page.keyboard.press('Shift+Tab');
    await expect(page.locator('#decision-confirm-submit')).toBeFocused();
    await page.keyboard.press('Escape');
    await expect(page.locator('#opener')).toBeFocused();
    await expect(page.locator('#background')).not.toHaveAttribute('inert');
    await expect(page.locator('#already-inert')).toHaveAttribute('inert', '');
    await page.locator('#opener').click();
    await page.keyboard.press('Escape');
    await expect(page.locator('#opener')).toBeFocused();
});

test('final approval keeps its confirmation payload and releases inert state before submission', async ({ page }) => {
    await workflowFixture(page);
    await page.evaluate(() => {
        window.__submissions = [];
        HTMLFormElement.prototype.submit = function () {
            window.__submissions.push({ confirmation: new FormData(this).get('final_decision_confirmation'), inert: document.getElementById('background').inert });
        };
    });
    await page.locator('#approve').click();
    await page.locator('#decision-confirm-submit').click();
    expect(await page.evaluate(() => window.__submissions)).toEqual([{ confirmation:'1', inert:false }]);
});

test('nested profile cropper traps focus and returns it to Choose Photo', async ({ page }) => {
    await page.setContent('<main><div data-profile-photo-editor><button data-profile-photo-choose>Choose Photo</button>' +
        '<input type="file" data-profile-photo-input><div data-profile-crop-modal hidden>' +
        '<div role="dialog" aria-modal="true"><button data-profile-crop-cancel>Close</button>' +
        '<div data-profile-crop-stage style="width:100px;height:100px"><img data-profile-crop-image></div>' +
        '<input type="range" data-profile-crop-zoom min="1" max="3" step=".01">' +
        '<button data-profile-crop-cancel>Cancel</button><button data-profile-crop-save>Save Crop</button>' +
        '</div></div></div><a id="outside" href="#">Outside</a></main>');
    await install(page, dialog + cropper + 'initDarLtcmsProfileCropper();');
    await page.locator('[data-profile-photo-input]').setInputFiles({ name:'photo.png', mimeType:'image/png',
        buffer:Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l9sAAAAASUVORK5CYII=', 'base64') });
    await expect(page.locator('[data-profile-crop-save]')).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.locator('[data-profile-crop-cancel]').first()).toBeFocused();
    await expect(page.locator('#outside')).toHaveAttribute('inert', '');
    await page.keyboard.press('Escape');
    await expect(page.locator('[data-profile-photo-choose]')).toBeFocused();
    await expect(page.locator('#outside')).not.toHaveAttribute('inert');
    expect(await page.locator('[data-profile-photo-input]').inputValue()).toBe('');
});

test('lookup labels name the select and dynamic search inputs have separate accessible names', async ({ page }) => {
    const field = '<div class="field-group"><label>Transferor Landowner Record</label><div data-remote-record-select>' +
        '<input type="search" data-remote-record-search><select name="transferors[0][landowner_id]" data-remote-record-control><option>None</option></select></div></div>';
    await page.setContent('<div class="application-create-page">' + field + '</div>');
    await install(page, system);
    await expect(page.getByRole('combobox', { name:'Transferor Landowner Record', exact:true })).toHaveCount(1);
    await expect(page.getByRole('searchbox', { name:'Search Transferor Landowner Record' })).toHaveCount(1);
    await page.locator('.application-create-page').evaluate((node, html) => node.insertAdjacentHTML('beforeend', html), field);
    await expect(page.getByRole('combobox', { name:'Transferor Landowner Record', exact:true })).toHaveCount(2);
    await expect(page.getByRole('searchbox', { name:'Search Transferor Landowner Record' })).toHaveCount(2);
    const ids = await page.locator('select').evaluateAll(nodes => nodes.map(node => node.id));
    expect(new Set(ids).size).toBe(2);
    await page.locator('.application-create-page').evaluate(node => node.insertAdjacentHTML('beforeend',
        '<div class="user-field"><label for="native-record">Linked Landowner Record</label>' +
        '<div data-remote-record-select data-lookup-url="/lookup"><input type="search" data-remote-record-search>' +
        '<select id="native-record" data-remote-record-control><option>None</option></select>' +
        '<p data-remote-record-status>Search records.</p></div></div>'));
    await install(page, read('resources/js/remote-record-select.js'));
    await expect(page.getByRole('searchbox', { name:'Search Linked Landowner Record' })).toHaveCount(1);
    await expect(page.locator('[data-remote-record-status]')).toHaveAttribute('role', 'status');
});

test('clearing client errors preserves server errors and existing help descriptions', async ({ page }) => {
    await page.setContent('<div class="staff-shell"><div><label for="code">Parcel Code</label>' +
        '<input id="code" required aria-invalid="true" aria-describedby="help server" data-ui-server-invalid>' +
        '<p id="help">Use the parcel code.</p><p id="server">Code already exists.</p></div></div>');
    await install(page, lastMile);
    await page.locator('#code').evaluate(node => node.reportValidity());
    await expect(page.locator('[data-ui-client-error]')).toHaveCount(1);
    await page.locator('#code').fill('ABC');
    await expect(page.locator('[data-ui-client-error]')).toHaveCount(0);
    await expect(page.locator('#code')).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#code')).toHaveAttribute('aria-describedby', 'help server');
});

test('keyboard outlines contrast on light surfaces and remain visible in body portals', async ({ page }) => {
    await page.setContent('<div class="staff-shell"><input aria-label="Field"><a href="#">Link</a></div>' +
        '<div class="decision-modal-backdrop"><button>Confirm</button></div>');
    for (const file of ['responsive-hardening.css', 'ui-ux-system.css', 'ui-ux-last-mile.css']) {
        await page.addStyleTag({ content:read('resources/css/' + file) });
    }
    const luminance = rgb => rgb.map(v => v / 255).map(v => v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4)
        .reduce((sum, v, i) => sum + v * [.2126,.7152,.0722][i], 0);
    for (const selector of ['input', 'a', 'button']) {
        await page.locator(selector).focus();
        await page.keyboard.press('Tab');
        await page.keyboard.press('Shift+Tab');
        const style = await page.locator(selector).evaluate(node => {
            const css = getComputedStyle(node); return { color:css.outlineColor, width:css.outlineWidth, shadow:css.boxShadow };
        });
        expect(parseFloat(style.width)).toBeGreaterThanOrEqual(3);
        const rgb = style.color.match(/[\d.]+/g).slice(0,3).map(Number);
        expect(1.05 / (luminance(rgb) + .05)).toBeGreaterThanOrEqual(3);
        expect(style.shadow).toContain('rgb(255, 255, 255)');
    }
});
