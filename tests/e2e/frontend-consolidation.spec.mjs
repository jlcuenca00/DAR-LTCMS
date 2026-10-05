import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(path, 'utf8');
const clean = source => source.replace(/^import .*;$/gm, '').replace(/^export .*;$/gm, '');
const wrap = source => '(() => {' + clean(source) + '})();';
const dialog = read('resources/js/dialog-focus.js');
const replay = read('resources/js/onboarding-replay-confirmation.js');
const tourCss = read('resources/css/onboarding-tour.css');
const install = (page, source) => page.addScriptTag({ content:wrap(source) });
const roles = [
    { role:'landowner', shell:'lo-shell', mount:'lo-topbar-right', content:'lo-content', prefix:'onboarding', file:'onboarding-tour.js' },
    { role:'staff', shell:'staff-shell', mount:'staff-topbar-actions', content:'staff-content-inner', prefix:'role-onboarding', file:'role-onboarding-tours.js' },
    { role:'geodetic', shell:'geo-shell', mount:'geo-topbar-right', content:'geo-content', prefix:'role-onboarding', file:'role-onboarding-tours.js' },
];

async function blankPage(page) {
    await page.route('**/consolidation-fixture', route => route.fulfill({
        contentType:'text/html', body:'<!doctype html><html><head></head><body></body></html>',
    }));
    await page.goto('/consolidation-fixture');
}

function tourFixture(config) {
    return '<main id="background" class="' + config.shell + '">' +
        '<header class="' + config.mount + '"></header><a id="outside" href="#outside">Background link</a>' +
        '<div class="' + config.content + '" style="height:400px">Portal content</div>' +
        '</main><aside id="existing-inert" inert>Preserved inert content</aside>';
}

async function setupTour(page, config) {
    await blankPage(page);
    await page.evaluate(path => history.replaceState({}, '', path), '/' + config.role + '/dashboard');
    await page.setContent(tourFixture(config));
    await page.addStyleTag({ content:tourCss });
    await page.evaluate(() => {
        window.__tourWrites = [];
        window.fetch = async (url, options = {}) => {
            if (options.method === 'PATCH') window.__tourWrites.push(JSON.parse(options.body));
            return { ok:true, json:async () => ({ seen:false, version:3 }) };
        };
    });
    await install(page, dialog);
    await install(page, read('resources/js/' + config.file));
    await install(page, replay);
}

for (const config of roles) {
    test(config.role + ' welcome, replay and active tour contain focus and restore the help button', async ({ page }) => {
        await setupTour(page, config);
        const help = page.locator('[data-onboarding-help="' + config.role + '_portal"]');
        const welcomeStart = page.locator('[data-' + config.prefix + '-welcome-start]');
        const welcomeSkip = page.locator('[data-' + config.prefix + '-welcome-skip]');
        await expect(welcomeStart).toBeFocused();
        await expect(page.locator('#background')).toHaveAttribute('inert', '');
        await page.keyboard.press('Tab');
        await expect(welcomeSkip).toBeFocused();
        await page.keyboard.press('Shift+Tab');
        await expect(welcomeStart).toBeFocused();
        await page.keyboard.press('Escape');
        await expect(page.locator('.onboarding-welcome-layer')).toHaveCount(0);
        await expect(help).toBeFocused();
        await expect(page.locator('#background')).not.toHaveAttribute('inert');
        await expect(page.locator('#existing-inert')).toHaveAttribute('inert', '');

        await help.click();
        await expect(page.locator('[data-onboarding-replay-start]')).toBeFocused();
        await page.keyboard.press('Tab');
        await expect(page.locator('[data-onboarding-replay-cancel]')).toBeFocused();
        await page.keyboard.press('Escape');
        await expect(page.locator('[data-onboarding-replay-confirmation]')).toHaveCount(0);
        await expect(help).toBeFocused();

        await help.click();
        await page.locator('[data-onboarding-replay-start]').click();
        const next = page.locator('[data-' + config.prefix + '-next]');
        const skip = page.locator('[data-' + config.prefix + '-skip]');
        await expect(page.locator('.onboarding-tour-layer')).toHaveClass(/is-ready/);
        await expect(next).toBeFocused();
        await expect(page.locator('#background')).toHaveAttribute('inert', '');
        await page.keyboard.press('Tab');
        await expect(skip).toBeFocused();
        await page.keyboard.press('Shift+Tab');
        await expect(next).toBeFocused();
        await page.locator('#outside').evaluate(node => node.focus());
        await expect(page.locator('.onboarding-tour-layer')).toContainText('Step 1');
        expect(await page.evaluate(() => document.querySelector('.onboarding-tour-layer').contains(document.activeElement))).toBe(true);
        await page.keyboard.press('Escape');
        await expect(page.locator('.onboarding-tour-layer')).toHaveCount(0);
        await expect(help).toBeFocused();
        await expect(page.locator('#background')).not.toHaveAttribute('inert');
        await expect(page.locator('#existing-inert')).toHaveAttribute('inert', '');

        await help.click();
        await page.locator('[data-onboarding-replay-start]').click();
        await expect(page.locator('.onboarding-tour-layer')).toHaveClass(/is-ready/);
        await skip.click();
        await expect(page.locator('.onboarding-tour-layer')).toHaveCount(0);
        await expect(help).toBeFocused();
        expect(await page.evaluate(() => window.__tourWrites.at(-1).status)).toBe('skipped');
    });
}

for (const config of [roles[0], roles[2]]) {
    test(config.role + ' Finish releases background controls and records completion', async ({ page }) => {
        await blankPage(page);
        const path = config.role === 'landowner' ? '/landowner/applications' : '/geodetic/parcel-map';
        await page.evaluate(({ path, role }) => {
            history.replaceState({}, '', path);
            sessionStorage.setItem('darltcms:onboarding:' + role + '_portal', JSON.stringify({
                active:true, version:3, index:9999,
            }));
        }, { path, role:config.role });
        await page.setContent(tourFixture(config));
        await page.addStyleTag({ content:tourCss });
        await page.evaluate(() => {
            window.__tourWrites = [];
            window.fetch = async (url, options = {}) => {
                if (options.method === 'PATCH') window.__tourWrites.push(JSON.parse(options.body));
                return { ok:true, json:async () => ({ seen:false, version:3 }) };
            };
        });
        await install(page, dialog);
        await install(page, read('resources/js/' + config.file));
        const finish = page.locator('[data-' + config.prefix + '-next]');
        await expect(finish).toHaveText('Finish');
        await expect(page.locator('.onboarding-tour-layer')).toHaveClass(/is-ready/);
        await finish.click();
        await expect(page.locator('.onboarding-tour-layer')).toHaveCount(0);
        await expect(page.locator('[data-onboarding-help]')).toBeFocused();
        await expect(page.locator('#background')).not.toHaveAttribute('inert');
        await expect(page.locator('#existing-inert')).toHaveAttribute('inert', '');
        expect(await page.evaluate(() => window.__tourWrites.at(-1).status)).toBe('completed');
    });

    test(config.role + ' tour navigation releases the old layer and resumes on the destination', async ({ page }) => {
        const scripts = wrap(dialog) + wrap(read('resources/js/' + config.file));
        const html = tourFixture(config) + '<style>' + tourCss + '</style><script>' +
            'window.fetch=async()=>({ok:true,json:async()=>({seen:false,version:3})});' +
            'window.addEventListener("pagehide",()=>sessionStorage.setItem("last-tour-pagehide",JSON.stringify({' +
            'inert:document.getElementById("background").inert,layers:document.querySelectorAll(".onboarding-tour-layer").length})));' +
            scripts + '</script>';
        await page.route('**/' + config.role + '/**', route => route.fulfill({ contentType:'text/html', body:html }));
        await page.goto('/' + config.role + '/dashboard');
        await page.locator('[data-' + config.prefix + '-welcome-start]').click();
        const next = page.locator('[data-' + config.prefix + '-next]');
        await expect(page.locator('.onboarding-tour-layer')).toHaveClass(/is-ready/);
        await next.click();
        await expect(page.locator('.onboarding-step-count')).toHaveText(/Step 2/);
        await expect(page.locator('.onboarding-tour-layer')).not.toHaveClass(/is-moving/);
        await next.click();
        await expect(page).toHaveURL(new RegExp('/' + config.role + '/parcels$'));
        await expect(page.locator('.onboarding-step-count')).toHaveText(/Step 3/);
        await expect(page.locator('.onboarding-tour-layer')).toHaveClass(/is-ready/);
        expect(await page.evaluate(() => JSON.parse(sessionStorage.getItem('last-tour-pagehide')))).toEqual({ inert:false, layers:0 });
        await page.keyboard.press('Escape');
        await expect(page.locator('.onboarding-tour-layer')).toHaveCount(0);
        await expect(page.locator('[data-onboarding-help]')).toBeFocused();
        await expect(page.locator('#background')).not.toHaveAttribute('inert');
    });
}

const linkCases = [
    { name:'user management', path:'/staff/users', file:'user-management-linked-records.js', href:'/staff/users/23/edit',
        html:'<table class="user-management-table"><thead><tr><th>User</th><th>Role</th><th>Record</th><th>Activity</th><th>Sign-in</th><th class="staff-table-action">Action</th></tr></thead><tbody><tr><td><span class="user-management-primary">Test User</span></td><td>Staff</td><td>None</td><td>Active</td><td>Ready</td><td><a href="/staff/users/23/edit">Manage</a></td></tr></tbody></table>' },
    { name:'source package cards', path:'/staff/legacy-records', file:'staff-record-row-navigation.js', href:'/staff/source-record-packages/23',
        html:'<article class="source-package-row"><p class="source-package-code">SOURCE-23</p><a href="/staff/source-record-packages/23">Open Package</a></article>' },
    { name:'matched source rows', path:'/staff/applications/23', file:'staff-record-row-navigation.js', href:'/staff/legacy-records/23',
        html:'<div class="application-review-page"><div class="source-table-wrap"><table class="staff-table"><thead><tr><th>Source</th><th>Action</th></tr></thead><tbody><tr><td><strong>Source reference</strong></td><td class="staff-table-action"><a href="/staff/legacy-records/23">View Record</a></td></tr></tbody></table></div></div>' },
];

for (const fixture of linkCases) {
    test(fixture.name + ' keeps a native destination link beside row navigation', async ({ page }) => {
        await blankPage(page);
        await page.evaluate(path => history.replaceState({}, '', path), fixture.path);
        await page.setContent(fixture.html);
        await install(page, read('resources/js/' + fixture.file));
        const link = page.locator('a[href="' + fixture.href + '"]');
        await expect(link).toHaveCount(1);
        await link.focus();
        await expect(link).toBeFocused();
        await page.context().route('**' + fixture.href, route => route.fulfill({ contentType:'text/html', body:'<p>Opened record</p>' }));
        const popupPromise = page.waitForEvent('popup');
        await link.click({ button:'middle' });
        const popup = await popupPromise;
        await expect(popup).toHaveURL(new RegExp(fixture.href + '$'));
        await popup.close();
        await link.press('Enter');
        await expect(page).toHaveURL(new RegExp(fixture.href + '$'));
    });
}

test('real-click cancellation after draft initialization preserves the draft and accepted submission clears it', async ({ page }) => {
    await blankPage(page);
    await page.setContent('<form action="/draft-accepted" method="POST" data-autosave-key="native-cancellation">' +
        '<input name="name" value="Draft value"><button type="submit">Save</button></form>');
    await page.evaluate(() => localStorage.setItem('dar_ltcms_form_draft:native-cancellation', JSON.stringify({
        path:location.pathname, data:{ name:'Saved draft' },
    })));
    await install(page, read('resources/js/form-drafts.js'));
    await page.evaluate(() => document.querySelector('form').addEventListener('submit', event => event.preventDefault(), { once:true }));
    await page.locator('button').click();
    await page.waitForTimeout(50);
    expect(await page.evaluate(() => localStorage.getItem('dar_ltcms_form_draft:native-cancellation'))).not.toBeNull();
    await page.locator('input').fill('Pending draft write');
    await page.route('**/draft-accepted', async route => {
        await new Promise(resolve => setTimeout(resolve, 750));
        await route.fulfill({ contentType:'text/html', body:'<p>Saved</p>' });
    });
    await page.locator('button').click();
    await expect(page).toHaveURL(/draft-accepted$/);
    expect(await page.evaluate(() => localStorage.getItem('dar_ltcms_form_draft:native-cancellation'))).toBeNull();
});
