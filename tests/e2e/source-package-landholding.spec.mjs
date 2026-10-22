import { test, expect } from '@playwright/test';

const username = process.env.E2E_STAFF_USERNAME;
const password = process.env.E2E_STAFF_PASSWORD;

function csvCell(value) {
    const text = String(value ?? '');
    return /[,"\n\r]/.test(text) ? '"' + text.replaceAll('"', '""') + '"' : text;
}

test('source package CSV import becomes a reviewed master landholding and affects current hectares', async ({ page }, testInfo) => {
    test.skip(!username || !password, 'Requires isolated CI staff account and database.');
    test.setTimeout(90_000);

    const suffix = testInfo.project.name === 'chromium-coarse-pointer' ? 'PHONE' : 'DESKTOP';
    const parcelCode = 'E2E-MASTER-' + suffix;
    const landholdingRef = 'E2E-LH-' + suffix;
    const owner = 'Browser ' + suffix + ' Owner';

    await page.goto('/login');
    await page.locator('#username').fill(username);
    await page.locator('#password').fill(password);
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/staff\/dashboard/);

    const skipTour = page.getByRole('button', { name: 'Skip Tour', exact: true });
    if (await skipTour.isVisible().catch(() => false)) {
        await skipTour.click();
    }

    // Download the server's canonical CSV template rather than duplicating its headers.
    const template = await page.request.get('/staff/source-record-package-imports/template');
    expect(template.status()).toBe(200);
    const headers = (await template.text()).trimStart().split(/\r?\n/, 1)[0]
        .replace(/^\uFEFF/, '').split(',').map(h => h.replaceAll('"', '').trim());
    const row = {
        include_title: 'no',
        include_landholding: 'yes',
        include_parcel_source: 'yes',
        include_historical_clearance: 'no',
        source_record_scope: 'current_active',
        landowner_name: owner,
        parcel_code: parcelCode,
        landholding_reference_number: landholdingRef,
        title_number: 'TCT-' + suffix,
        lot_number: 'LOT-' + suffix,
        survey_number: 'SUR-' + suffix,
        area_hectares: '2.5000',
        crop_or_land_use: 'Agricultural',
        barangay: 'Bantayan',
        municipality: 'Dumaguete City',
        province: 'Negros Oriental',
        source_book: 'E2E Source Book ' + suffix,
        page_number: '1',
        transcribed_by: 'E2E Reviewer',
        transcription_date: new Date().toISOString().slice(0, 10),
    };
    const csv = headers.map(csvCell).join(',') + '\n'
        + headers.map(key => csvCell(row[key] ?? '')).join(',') + '\n';

    await page.goto('/staff/source-record-package-imports/create');
    await page.locator('input[name="import_file"]').setInputFiles({
        name: 'e2e-source-master-' + suffix + '.csv',
        mimeType: 'text/csv',
        buffer: Buffer.from(csv),
    });
    await page.getByRole('button', { name: 'Upload and Preview' }).click();
    await expect(page).toHaveURL(/\/staff\/source-record-package-imports\/\d+\/preview/);
    await expect(page.locator('table tbody')).toContainText('Valid');
    await expect(page.locator('input[name="selected_rows[]"]')).toBeChecked();

    await page.getByRole('button', { name: 'Commit Selected Valid Rows' }).first().click();
    await expect(page).toHaveURL(/\/staff\/legacy-records/);

    await page.goto('/staff/legacy-records?view=packages&search=' + encodeURIComponent(parcelCode));
    await expect(page.locator('.source-package-row')).toHaveCount(1);
    await page.getByRole('link', { name: 'Open Package' }).click();
    const packagePath = new URL(page.url()).pathname;
    await expect(page).toHaveURL(/\/staff\/source-record-packages\/\d+$/);
    await expect(page.locator('.source-detail-code')).toContainText('SRC-PKG-');

    // Source evidence alone must never be treated as an operational landholding.
    await expect(page.getByText('Link a Landowner and Parcel above before reviewing Landholding creation.')).toBeVisible();

    const createOwner = page.locator('form[action$="/create-landowner"]');
    await createOwner.locator('xpath=ancestor::details[1]').locator('summary').click();
    await createOwner.locator('input[name="first_name"]').fill('Browser');
    await createOwner.locator('input[name="last_name"]').fill(suffix + ' Owner');
    await createOwner.getByRole('button', { name: 'Create and Link Landowner' }).click();
    await expect(page).toHaveURL(new RegExp(packagePath + '$'));
    await expect(page.locator('.source-detail-page')).toContainText('Landowner record created from source package');

    const createParcel = page.locator('form[action$="/create-parcel"]');
    await createParcel.locator('xpath=ancestor::details[1]').locator('summary').click();
    await createParcel.locator('input[name="area_hectares"]').fill('3.0000');
    await createParcel.locator('select[name="status"]').selectOption('active');
    await createParcel.getByRole('button', { name: /Create and Link Parcel/ }).click();
    await expect(page).toHaveURL(new RegExp(packagePath + '$'));
    await expect(page.locator('.source-detail-page')).toContainText('Parcel record created and linked');

    const reviewLink = page.getByRole('link', { name: 'Review & Create Landholding' });
    await expect(reviewLink).toBeVisible();
    await reviewLink.click();
    await expect(page.getByRole('heading', { name: 'Review Landholding' })).toBeVisible();
    await expect(page.locator('dl')).toContainText(landholdingRef);
    await expect(page.locator('dl')).toContainText('0.0000 ha');

    // Verify a share smaller than the overall parcel area.
    await page.locator('input[name="area_hectares"]').fill('2.5000');
    await page.locator('input[name="verified_source"]').check();
    await page.getByRole('button', { name: 'Confirm & Create Landholding' }).click();
    await expect(page).toHaveURL(new RegExp(packagePath + '$'));
    await expect(page.locator('.source-detail-page')).toContainText('Verified Landholding created and linked');

    await page.getByRole('link', { name: 'View Landholding' }).click();
    await expect(page.locator('.hectare-focus-card')).toContainText('2.5000 ha');
    await expect(page.locator('#landholdings')).toContainText(landholdingRef);
    await expect(page.locator('#landholdings')).toContainText(parcelCode);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
});
