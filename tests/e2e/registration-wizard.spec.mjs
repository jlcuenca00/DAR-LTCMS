import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

async function wizard(page, google = false) {
    const input = (name, extra = '') => `<label>${name}<input name="${name}" ${extra}></label>`;
    await page.route('**/wizard-fixture', route => route.fulfill({ contentType:'text/html', body: `
        <meta name="viewport" content="width=device-width, initial-scale=1"><style>[hidden]{display:none!important}</style>
        ${google ? '' : '<div data-signup-method><button type="button" data-signup-email hidden>Sign up with email</button></div>'}
        <div data-registration-wizard><div data-signup-progress hidden><p data-step-status role="status"></p><ol>${[1,2,3,4].map(x=>'<li data-step-marker><span>'+x+'</span></li>').join('')}</ol></div>
        <form data-signup-form>
        <section data-signup-step data-step-title="Your details"><h2 tabindex="-1">Your details</h2>${google ? '<p data-google-email>owner@example.com</p>' : input('password','type="password" required') + input('password_confirmation','type="password" required')}${input('name','required')}${input('email','type="email" required')}</section>
        <section data-signup-step data-step-title="Valid ID"><h2 tabindex="-1">Valid ID</h2><select name="id_type" required><option value="">Select ID</option><option value="passport">Passport</option></select><input type="file" name="id_front" required data-evidence-file="id_front"><img data-evidence-preview="id_front" hidden></section>
        <section data-signup-step data-step-title="Selfie"><h2 tabindex="-1">Selfie</h2><input type="file" name="selfie" required></section>
        <section data-signup-step data-step-title="Review"><h2 tabindex="-1">Review</h2><div data-signup-review></div>${input('identity_consent','type="checkbox" required')}${input('privacy_consent','type="checkbox" required')}</section>
        <button type="button" data-signup-back hidden>Back</button><button type="button" data-signup-next hidden>Continue</button><button type="submit" data-signup-submit>Submit for verification</button></form></div>` }));
    await page.goto('/wizard-fixture');
    await page.addScriptTag({content:readFileSync('resources/js/registration-wizard.js','utf8')});
}
const file = { name:'photo.png', mimeType:'image/png', buffer:Buffer.from('photo') };
test('manual wizard validates stages, preserves files, and reviews without exposing passwords', async ({page})=>{
    await wizard(page);
    await expect(page.locator('[data-signup-form]')).toBeHidden();
    await page.getByRole('button',{name:'Sign up with email'}).click();
    await page.getByRole('button',{name:'Continue',exact:true}).click();
    await expect(page.locator('[data-step-status]')).toContainText('Step 1');
    await page.locator('[name="name"]').fill('<img src=x onerror=alert(1)>');
    await page.locator('[name="email"]').fill('owner@example.com');
    await page.locator('[name="password"]').fill('StrongPassword321!');
    await page.locator('[name="password_confirmation"]').fill('WrongPassword');
    await page.getByRole('button',{name:'Continue',exact:true}).click();
    await expect(page.locator('[data-step-status]')).toContainText('Step 1');
    await page.locator('[name="password_confirmation"]').fill('StrongPassword321!');
    await page.getByRole('button',{name:'Continue',exact:true}).click();
    await page.locator('[name="id_type"]').selectOption('passport');
    await page.locator('[name="id_front"]').setInputFiles(file);
    await page.getByRole('button',{name:'Continue',exact:true}).click();
    await page.getByRole('button',{name:'Back',exact:true}).click();
    expect(await page.locator('[name="id_front"]').evaluate(n=>n.files[0].name)).toBe('photo.png');
    await page.getByRole('button',{name:'Continue',exact:true}).click();
    await page.locator('[name="selfie"]').setInputFiles(file);
    await page.getByRole('button',{name:'Continue',exact:true}).click();
    await expect(page.locator('[data-signup-review]')).toContainText('<img src=x onerror=alert(1)>');
    await expect(page.locator('[data-signup-review] img')).toHaveCount(0);
    await expect(page.locator('[data-signup-review]')).not.toContainText('StrongPassword321!');
    await page.getByRole('button',{name:'Edit your details'}).click();
    await expect(page.locator('[name="name"]')).toHaveValue('<img src=x onerror=alert(1)>');
    expect(await page.evaluate(()=>localStorage.length)).toBe(0);
});
test('Google enters the same four stages without password inputs', async ({page})=>{
    await wizard(page,true);
    await expect(page.locator('[data-step-status]')).toHaveText('Step 1 of 4 · Your details');
    await expect(page.locator('[data-google-email]')).toBeVisible();
    await expect(page.locator('[name="password"]')).toHaveCount(0);
    await expect(page.locator('[data-signup-back]')).toBeHidden();
});
