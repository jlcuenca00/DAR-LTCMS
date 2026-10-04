import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(path, 'utf8');
const lookup = read('resources/js/remote-record-select.js');
const system = read('resources/js/ui-ux-system.js').replace(/^export .*;$/m, '');
const dialog = read('resources/js/dialog-focus.js').replace(/^export .*;$/m, '');
const review = read('resources/views/staff/applications/show.blade.php');
const reviewHandlers = review.slice(review.indexOf('            const workflowModal ='), review.lastIndexOf('        });'));
const decisionStart = review.indexOf('<div id="decision-confirm-modal"');
const decision = review.slice(decisionStart, review.indexOf('<script>', decisionStart));
const install = (page, source) => page.addScriptTag({ content: '(() => {' + source + '})();' });

async function lookupFixture(page) {
    await page.goto('/login');
    await page.setContent('<div data-remote-record-select data-lookup-url="/lookup">' +
        '<label for="record">Parcel</label><input type="search" data-remote-record-search>' +
        '<select id="record" data-remote-record-control><option value="">Choose</option>' +
        '<option value="7" selected data-area="2">Saved parcel</option></select>' +
        '<p data-remote-record-status></p></div>');
    await page.evaluate(() => {
        window.__requests = [];
        // Deliberately ignore abort to exercise responses already being decoded.
        window.fetch = url => new Promise(resolve => {
            window.__requests.push({ query: new URL(url).searchParams.get('q'),
                respond: payload => resolve({ ok:true, json:async () => payload }) });
        });
    });
    await install(page, lookup);
}

test('lookup ignores stale responses immediately during debounce and keeps selected metadata', async ({ page }) => {
    await lookupFixture(page);
    await page.locator('input').focus();
    await expect.poll(() => page.evaluate(() => window.__requests.length)).toBe(1);
    await page.locator('input').fill('New query');
    await page.evaluate(() => window.__requests[0].respond({ results:[{ id:8, text:'Old query result' }] }));
    await expect(page.locator('select option[value="8"]')).toHaveCount(0);
    await expect(page.locator('[data-remote-record-status]')).toHaveText('Searching records…');
    await expect.poll(() => page.evaluate(() => window.__requests.length)).toBe(2);
    await page.evaluate(() => window.__requests[1].respond({ results:[{ id:9, text:'New query result' }] }));
    await expect(page.locator('select option[value="9"]')).toHaveText('New query result');
    await expect(page.locator('select')).toHaveValue('7');
    await expect(page.locator('option:checked')).toHaveAttribute('data-area', '2');
});

test('lookup distinguishes invalid responses from valid empty results without losing selection', async ({ page }) => {
    await lookupFixture(page);
    await page.locator('input').focus();
    await expect.poll(() => page.evaluate(() => window.__requests.length)).toBe(1);
    await page.evaluate(() => window.__requests[0].respond({ error:'Service unavailable' }));
    await expect(page.locator('[data-remote-record-status]')).toHaveText('Unable to load records. Try searching again.');
    await expect(page.locator('select')).toHaveValue('7');
    await page.locator('input').fill('No match');
    await expect.poll(() => page.evaluate(() => window.__requests.length)).toBe(2);
    await page.evaluate(() => window.__requests[1].respond({ results:[] }));
    await expect(page.locator('[data-remote-record-status]')).toHaveText('No matching records found.');
    await expect(page.locator('select')).toHaveValue('7');
});

test('removal feedback respects cancellation and validity, prevents repeats and resets on back navigation', async ({ page }) => {
    await page.goto('/login');
    await page.setContent('<form data-submit-feedback><input name="revision" value="4" required>' +
        '<button type="submit" name="action" value="remove">Remove File</button></form>');
    await install(page, system);
    await page.evaluate(() => {
        const form = document.querySelector('form');
        window.__dispatch = () => form.dispatchEvent(new SubmitEvent('submit', {
            bubbles:true, cancelable:true, submitter:form.querySelector('button'),
        }));
        form.addEventListener('submit', event => event.preventDefault(), { once:true });
        window.__dispatch();
    });
    await expect(page.locator('button')).toHaveText('Remove File');
    await page.locator('input').fill('');
    await page.evaluate(() => window.__dispatch());
    await expect(page.locator('button')).not.toHaveAttribute('aria-busy', 'true');
    await page.locator('input').fill('4');
    await page.evaluate(() => window.__dispatch());
    await expect(page.locator('button')).toHaveText('⏳Removing…');
    expect(await page.evaluate(() => window.__dispatch())).toBe(false);
    expect(await page.evaluate(() => Array.from(new FormData(document.querySelector('form'), document.querySelector('button'))))).toEqual([['revision','4'],['action','remove']]);
    await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted:true })));
    await expect(page.locator('button')).toHaveText('Remove File');
    await expect(page.locator('button')).not.toHaveAttribute('aria-busy', 'true');
});

test('final approval cancellation stays editable and confirmation submits once with visible feedback', async ({ page }) => {
    await page.goto('/login');
    await page.setContent('<div class="application-review-page">' +
        '<p id="decision-submit-status" data-ui-submit-status role="status" hidden></p>' +
        '<button id="opener" data-workflow-modal-open>Manage workflow</button><a id="background" href="#">Background</a>' +
        '<div id="workflow-modal" aria-hidden="true"><div role="dialog"><button id="workflow-modal-close-top">Close</button>' +
        '<form id="approval" data-submit-feedback data-decision-confirm="approve">' +
        '<input name="expected_workflow_revision" value="4"><button type="submit" id="approve">Approve</button>' +
        '</form></div></div></div>' + decision);
    await page.addStyleTag({ content: '#workflow-modal, .decision-modal-backdrop { display:none } .is-open { display:block!important }' });
    await install(page, dialog + system + reviewHandlers);
    await page.evaluate(() => {
        window.__submissions = [];
        HTMLFormElement.prototype.submit = function () {
            window.__submissions.push({ payload:Array.from(new FormData(this)),
                inert:document.getElementById('background').inert });
        };
    });
    await page.locator('#opener').click();
    await page.locator('#approve').click();
    await page.locator('#decision-confirm-cancel').click();
    await expect(page.locator('#approval')).not.toHaveAttribute('data-ui-submitting');
    await page.locator('#opener').click();
    await page.locator('#approve').click();
    await page.locator('#decision-confirm-submit').click();
    await expect(page.locator('#decision-submit-status')).toBeVisible();
    await expect(page.locator('#decision-submit-status')).toContainText('Recording the Approved decision');
    await page.locator('#decision-confirm-submit').evaluate(button => { button.click(); button.click(); });
    expect(await page.evaluate(() => window.__submissions)).toEqual([{
        payload:[['expected_workflow_revision','4'],['final_decision_confirmation','1']], inert:false,
    }]);
    await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted:true })));
    await expect(page.locator('#decision-submit-status')).toBeHidden();
    await expect(page.locator('#decision-confirm-submit')).toHaveText('Record Approved Decision');
    await expect(page.locator('#approval')).not.toHaveAttribute('data-ui-submitting');
});
