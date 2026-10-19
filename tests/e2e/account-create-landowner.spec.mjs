import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

test('inline landowner creation preserves account edits and handles errors without navigation', async ({ page }) => {
    await page.route('**/inline-landowner-fixture', route => route.fulfill({
        contentType: 'text/html',
        body: `<meta name="viewport" content="width=device-width, initial-scale=1"><form data-account-edit-form><label>Account name<input name="name" value="Unsaved account name"></label>
            <button type="button" data-create-landowner-open>Create Landowner Record</button>
            <select id="field-default-landowner_id"><option value="">None</option></select></form>
            <dialog class="account-create-dialog" data-create-landowner-dialog aria-labelledby="title">
                <form action="/staff/records/landowners" method="post"><header><h2 id="title">Create Landowner Record</h2></header>
                    <div class="account-create-body"><div data-create-landowner-errors role="alert" hidden></div><div class="account-create-grid">
                    <label>First name<input name="first_name" required></label><label>Last name<input name="last_name" required></label>
                    <label for="fixture-owner-status">Status</label><select id="fixture-owner-status" name="registered_owner_status"><option value="">None</option><option value="married">Married</option></select>
                    <label data-inline-spouse hidden>Spouse<input name="spouse_name" disabled></label></div></div>
                    <footer><button type="button" data-create-landowner-close>Cancel</button><button type="submit">Create & Select</button></footer>
                </form></dialog><p data-create-landowner-status role="status"></p>`,
    }));
    await page.goto('/inline-landowner-fixture');
    await page.addStyleTag({ content: readFileSync('resources/css/account-details.css', 'utf8') });
    await page.addScriptTag({ content: readFileSync('resources/js/account-create-landowner.js', 'utf8') });
    const opener = page.getByRole('button', { name: 'Create Landowner Record', exact: true });
    const dialog = page.getByRole('dialog');
    await opener.click();
    await expect(dialog).toBeVisible();
    await page.getByLabel('First name').fill('Maria');
    await page.getByLabel('Last name').fill('Santos');
    await page.getByLabel('Status', { exact: true }).selectOption('married');
    await expect(page.getByLabel('Spouse', { exact: true })).toBeVisible();
    await expect(page.getByLabel('Spouse', { exact: true })).toHaveAttribute('required', '');
    await page.getByLabel('Status', { exact: true }).selectOption('');
    await page.keyboard.press('Escape');
    await expect(dialog).not.toBeVisible();
    await expect(opener).toBeFocused();
    await opener.click();
    let attempts = 0;
    await page.route('**/staff/records/landowners', route => {
        attempts += 1;
        return route.fulfill({ status: attempts === 1 ? 422 : 201, contentType: 'application/json', body: JSON.stringify(attempts === 1
            ? { errors: { first_name: ['Please check the first name.'] } }
            : { id: 123, text: 'Maria Santos — ID 123' }) });
    });
    await page.getByRole('button', { name: 'Create & Select' }).click();
    await expect(dialog.getByRole('alert')).toHaveText('Please check the first name.');
    await expect(page.getByLabel('First name')).toBeFocused();
    await page.getByRole('button', { name: 'Create & Select' }).click();
    await expect(dialog).not.toBeVisible();
    await expect(page.locator('#field-default-landowner_id')).toHaveValue('123');
    await expect(page.getByLabel('Account name')).toHaveValue('Unsaved account name');
    await expect(page.locator('[data-create-landowner-status]')).toContainText('Save Changes');
    await expect(page).toHaveURL(/\/inline-landowner-fixture$/);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
});
