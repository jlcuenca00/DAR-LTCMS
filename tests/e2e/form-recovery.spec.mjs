import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const drafts = readFileSync('resources/js/form-drafts.js', 'utf8').replace(/^export .*;$/m, '');
const ui = readFileSync('resources/js/ui-ux-system.js', 'utf8').replace(/^export .*;$/m, '');
const username = process.env.E2E_STAFF_USERNAME || '';
const password = process.env.E2E_STAFF_PASSWORD || '';

async function login(page, testInfo) {
    test.skip(testInfo.project.name !== 'chromium-responsive', 'Authenticated recovery checks run once.');
    test.skip(!username || !password, 'Staff credentials required.');
    await page.goto('/login');
    await page.locator('#username').fill(username);
    await page.locator('#password').fill(password);
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/staff\/dashboard/);
}

test('application draft reload preserves extra parties, instruments and selected record context', async ({ page }, testInfo) => {
    await login(page, testInfo);
    await page.goto('/staff/applications/create');
    await page.evaluate(() => localStorage.removeItem('dar_ltcms_form_draft:clearance-application-create'));
    await page.reload();
    await page.locator('[data-add-party="transferors"]').click();
    await page.locator('[data-add-party="transferees"]').click();
    await page.locator('[data-add-instrument]').click();
    await page.locator('[name="transferors[0][name]"]').fill('First transferor');
    await page.locator('[name="transferors[1][name]"]').fill('Second transferor');
    await page.locator('[name="transferees[0][name]"]').fill('First transferee');
    await page.locator('[name="transferees[1][name]"]').fill('Second transferee');
    await page.locator('[name="transfer_instruments[0][name]"]').fill('First instrument');
    await page.locator('[name="transfer_instruments[1][name]"]').fill('Second instrument');
    await page.locator('#parcel_id').evaluate(select => {
        const option = new Option('<Saved parcel>', '987654', true, true);
        option.dataset.area = '2.5';
        select.appendChild(option);
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await expect.poll(() => page.evaluate(() => JSON.parse(localStorage.getItem('dar_ltcms_form_draft:clearance-application-create') || '{}').data?.['transferors[1][name]'])).toBe('Second transferor');
    await page.reload();
    await expect(page.locator('[data-party-list="transferors"] [data-party-item]')).toHaveCount(2);
    await expect(page.locator('[data-party-list="transferees"] [data-party-item]')).toHaveCount(2);
    await expect(page.locator('[data-instrument-item]')).toHaveCount(2);
    await expect(page.locator('[name="transferors[1][name]"]')).toHaveValue('Second transferor');
    await expect(page.locator('[name="transferees[1][name]"]')).toHaveValue('Second transferee');
    await expect(page.locator('[name="transfer_instruments[1][name]"]')).toHaveValue('Second instrument');
    await expect(page.locator('#parcel_id')).toHaveValue('987654');
    await expect(page.locator('#parcel_id option:checked')).toHaveText('<Saved parcel>');
    expect(await page.locator('#parcel_id option:checked').getAttribute('data-area')).toBe('2.5');
    await expect(page.locator('#parcel_id img')).toHaveCount(0);
});

async function draftFixture(page, hasOldInput = false) {
    await page.goto('/login');
    await page.setContent('<form data-autosave-key="recovery-fixture"><input name="name" value="Server value"><button type="submit">Save</button></form>');
    await page.evaluate(hasOldInput => {
        localStorage.setItem('dar_ltcms_form_draft:recovery-fixture', JSON.stringify({
            path: location.pathname, data: { name: 'Browser draft' },
        }));
        window.darFormDraftContext = { hasOldInput };
    }, hasOldInput);
    await page.addScriptTag({ content: '(() => {' + drafts + '})();' });
}

test('server-returned input takes precedence over browser drafts', async ({ page }) => {
    await draftFixture(page, true);
    await expect(page.locator('[name="name"]')).toHaveValue('Server value');
});

test('canceled submission preserves draft, accepted submission cancels pending saves', async ({ page }) => {
    await draftFixture(page);
    await expect(page.locator('[name="name"]')).toHaveValue('Browser draft');
    await page.evaluate(() => {
        const form = document.querySelector('form');
        form.addEventListener('submit', event => event.preventDefault(), { once: true });
        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });
    expect(await page.evaluate(() => localStorage.getItem('dar_ltcms_form_draft:recovery-fixture'))).not.toBeNull();
    await page.locator('[name="name"]').fill('Pending draft write');
    await page.evaluate(() => document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })));
    await page.waitForTimeout(650);
    expect(await page.evaluate(() => localStorage.getItem('dar_ltcms_form_draft:recovery-fixture'))).toBeNull();
});

test('unavailable draft storage does not break submit handlers', async ({ page }) => {
    await page.goto('/login');
    await page.setContent('<form data-autosave-key="storage-fixture"><input name="name"><button>Save</button></form>');
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.evaluate(() => {
        Storage.prototype.getItem = () => { throw new Error('Storage blocked'); };
        Storage.prototype.setItem = () => { throw new Error('Storage blocked'); };
        Storage.prototype.removeItem = () => { throw new Error('Storage blocked'); };
    });
    await page.addScriptTag({ content: '(() => {' + drafts + '})();' });
    await page.locator('input').fill('Test');
    await page.evaluate(() => document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })));
    await page.waitForTimeout(650);
    expect(errors).toEqual([]);
});

test('busy state uses submitter form ownership and recovers on persisted pageshow', async ({ page }) => {
    await page.goto('/login');
    await page.setContent('<div class="user-editor-wrap"><form id="edit"><input name="name" value="Valid"><button type="submit" form="reset" id="reset-button">Reset Password</button><button type="submit" id="save">Save Changes</button></form><form id="reset"></form></div>');
    await page.addScriptTag({ content: '(() => {' + ui + '})();' });
    await page.evaluate(() => document.querySelector('#edit').dispatchEvent(new SubmitEvent('submit', {
        bubbles: true, cancelable: true, submitter: document.querySelector('#save'),
    })));
    await expect(page.locator('#save')).toHaveAttribute('aria-busy', 'true');
    await expect(page.locator('#reset-button')).toHaveText('Reset Password');
    expect(await page.evaluate(() => document.querySelector('#edit').dispatchEvent(new SubmitEvent('submit', { cancelable: true })))).toBe(false);
    await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true })));
    await expect(page.locator('#save')).toHaveText('Save Changes');
    await expect(page.locator('#save')).not.toHaveAttribute('aria-busy', 'true');
    await page.evaluate(() => {
        const form = document.querySelector('#edit');
        form.addEventListener('submit', event => event.preventDefault(), { once: true });
        form.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: document.querySelector('#save') }));
    });
    await expect(page.locator('#save')).toHaveText('Save Changes');
    await expect(page.locator('#save')).not.toHaveAttribute('aria-busy', 'true');
});

test('source package reload preserves mode, included sections and scope', async ({ page }, testInfo) => {
    await login(page, testInfo);
    await page.goto('/staff/source-record-packages/create');
    await page.evaluate(() => localStorage.removeItem('dar_ltcms_form_draft:source-package-create'));
    await page.reload();
    await page.locator('[data-source-choice-card][data-mode="combined"]').click();
    await page.locator('[data-source-continue]').click();
    await page.locator('[data-combined-toggle="title"]').check();
    await page.locator('[data-combined-toggle="landholding"]').uncheck();
    await page.locator('[data-combined-toggle="parcel_source"]').uncheck();
    await page.locator('[data-combined-toggle="historical_clearance"]').check();
    const scope = await page.locator('[data-scope-select]').evaluate(select => select.options[select.options.length - 1].value);
    await page.locator('[data-scope-select]').selectOption(scope);
    await expect.poll(() => page.evaluate(() => JSON.parse(localStorage.getItem('dar_ltcms_form_draft:source-package-create') || '{}').package?.sections)).toEqual(['title', 'historical_clearance']);
    await page.reload();
    await expect(page.locator('[data-source-package-mode]')).toHaveValue('combined');
    await expect(page.locator('[data-combined-toggle="title"]')).toBeChecked();
    await expect(page.locator('[data-combined-toggle="landholding"]')).not.toBeChecked();
    await expect(page.locator('[data-combined-toggle="parcel_source"]')).not.toBeChecked();
    await expect(page.locator('[data-combined-toggle="historical_clearance"]')).toBeChecked();
    await expect(page.locator('[data-scope-value]')).toHaveValue(scope);
    await expect(page.locator('[data-source-workspace-body]')).toHaveClass(/is-visible/);
    // A single-section package must not be restored as a combined package.
    await page.locator('[data-source-change]').click();
    await page.locator('[data-source-choice-card][data-mode="title"]').click();
    await page.locator('[data-source-continue]').click();
    await page.locator('[name="title_number"]').fill('Draft title');
    await expect.poll(() => page.evaluate(() => JSON.parse(localStorage.getItem('dar_ltcms_form_draft:source-package-create') || '{}').package?.mode)).toBe('title');
    await page.reload();
    await expect(page.locator('[data-source-package-mode]')).toHaveValue('title');
    await expect(page.locator('[name="title_number"]')).toHaveValue('Draft title');
});
