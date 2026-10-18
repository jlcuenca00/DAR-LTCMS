import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const script = readFileSync('resources/js/form-drafts.js', 'utf8').replace(/^export .*;$/m, '');
const key = 'dar_ltcms_form_draft:requirement-1-10-20';

async function open(page, context = {}, version = 'new') {
    await page.route('http://draft.test/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<form data-autosave-key="requirement-1-10-20" data-draft-confirmed-save data-draft-server-version="${version}">
            <input name="document_metadata[document_number]" value="server"><input type="file" name="file">
            <p data-draft-status></p><button>Save</button></form>
            <form data-autosave-key="requirement-1-10-21" data-draft-confirmed-save data-draft-server-version="new"><input name="other" value="untouched"></form>`,
    }));
    await page.goto('http://draft.test/requirements');
    await page.evaluate(context => { window.darFormDraftContext = context; }, context);
    await page.addScriptTag({ content: script });
}

test('requirement text survives reload and failed submission without restoring files', async ({ page, context }) => {
    await open(page);
    const number = '0000123456789012345678901234567890';
    await page.locator('[name="document_metadata[document_number]"]').fill(number);
    await expect.poll(() => page.evaluate(key => JSON.parse(localStorage.getItem(key) || '{}').data?.['document_metadata[document_number]'], key)).toBe(number);
    await context.setOffline(true);
    await page.evaluate(() => {
        const form = document.querySelector('form');
        form.addEventListener('submit', event => event.preventDefault());
        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });
    await expect.poll(() => page.evaluate(key => localStorage.getItem(key), key)).not.toBeNull();
    await context.setOffline(false);
    await open(page);
    await expect(page.locator('[name="document_metadata[document_number]"]')).toHaveValue(number);
    await expect(page.locator('[name="file"]')).toHaveValue('');
    await expect(page.locator('[name="other"]')).toHaveValue('untouched');
});

test('accepted submission keeps the latest draft until explicit server confirmation', async ({ page }) => {
    await open(page);
    await page.locator('[name="document_metadata[document_number]"]').fill('latest-before-debounce');
    await page.evaluate(() => document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })));
    await expect.poll(() => page.evaluate(key => JSON.parse(localStorage.getItem(key) || '{}').data?.['document_metadata[document_number]'], key)).toBe('latest-before-debounce');
    await open(page, { savedKey: 'requirement-1-10-20' });
    expect(await page.evaluate(key => localStorage.getItem(key), key)).toBeNull();
    await expect(page.locator('[name="document_metadata[document_number]"]')).toHaveValue('server');
});

test('a newer saved record wins over an older browser draft', async ({ page }) => {
    await open(page);
    await page.locator('[name="document_metadata[document_number]"]').fill('stale');
    await expect.poll(() => page.evaluate(key => localStorage.getItem(key), key)).not.toBeNull();
    await open(page, {}, 'newer-record');
    await expect(page.locator('[name="document_metadata[document_number]"]')).toHaveValue('server');
    expect(await page.evaluate(key => localStorage.getItem(key), key)).toBeNull();
});
