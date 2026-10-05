import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const viewer = readFileSync('resources/js/parcel-map-viewer.js', 'utf8');
const leaflet = readFileSync('node_modules/leaflet/dist/leaflet.js', 'utf8');
const leafletCss = readFileSync('node_modules/leaflet/dist/leaflet.css', 'utf8');
const controlsCss = readFileSync('resources/css/app.css', 'utf8');

async function fixture(page, role, withLeaflet = true, nearby = false) {
    await page.route('https://*.basemaps.cartocdn.com/**', route => route.abort());
    await page.route('https://fixture.test/**', route => route.fulfill({ contentType:'text/html', body:'<h1>Parcel record</h1>' }));
    const record = {
        id:1, parcel_code:role.toUpperCase() + '-PARCEL', area_hectares:1,
        barangay:'Calindagan', municipality:'Dumaguete City',
        bounds:[123.3,9.3,123.31,9.31], details_url:'https://fixture.test/' + role + '/parcels/1',
    };
    await page.setContent('<div class="' + (role === 'landowner' ? 'lo' : role === 'geodetic' ? 'geo' : 'staff') + '-shell">' +
        '<div id="parcel-map" data-parcel-map-viewer style="width:300px;height:300px"></div>' +
        '<button id="reset-map-view">Reset View</button><div id="parcel-search-results"></div><div id="parcel-search-pages"></div>' +
        '<p id="parcel-search-status" role="status"></p><p id="parcel-map-status" role="status"></p>' +
        '<script type="application/json" data-parcel-map-config>' + JSON.stringify({
            role, bounds:record.bounds, features_url:'https://fixture.test/features',
            search_url:'https://fixture.test/search', focus_url:'https://fixture.test/feature/__ID__',
            initial_search:{ items:nearby ? [record, { ...record, id:2, parcel_code:role.toUpperCase() + '-NEARBY' }] : [record], total:nearby ? 2 : 1, page:1, last_page:1 },
        }) + '</script></div>');
    await page.addStyleTag({ content:controlsCss + '\n.staff-shell svg { max-width:100% !important; }' });
    await page.locator('#parcel-search-results').evaluate(node => { node.style.width = '280px'; });
    if (withLeaflet) {
        await page.addStyleTag({ content:leafletCss });
        await page.addScriptTag({ content:leaflet });
    }
    await page.evaluate(({record, nearby}) => {
        window.focusLoads = 0;
        window.viewportLoads = 0;
        const feature = id => ({ type:'Feature', properties:{ ...record, id, is_flagged:id === 2 }, geometry:{
            type:'Polygon', coordinates:[[[123.3 + id * .0001,9.3],[123.31 + id * .0001,9.3],[123.31 + id * .0001,9.31],[123.3 + id * .0001,9.31],[123.3 + id * .0001,9.3]]],
        } });
        window.fetch = async url => {
            if (String(url).includes('/feature/')) {
                window.focusLoads++;
                return { ok:true, json:async () => feature(String(url).endsWith('/2') ? 2 : 1) };
            }
            if (nearby) {
                window.viewportLoads++;
                return { ok:true, json:async () => ({ returned:2, total:2, features:[feature(1),feature(2)] }) };
            }
            // Partial map API failure must leave native record navigation usable.
            return { ok:false, status:503 };
        };
    }, {record, nearby});
    await page.addScriptTag({ content:viewer });
    await page.evaluate(() => initializeParcelMapViewer());
    return record;
}

for (const role of ['staff','geodetic','landowner']) {
    test(role + ' focused parcel remains distinct beside nearby geometry and resets cleanly', async ({ page }) => {
        const record = await fixture(page, role, true, true);
        await expect(page.locator('.leaflet-overlay-pane path')).toHaveCount(2);
        const first = page.getByRole('button', { name:'Show ' + record.parcel_code + ' on map', exact:true });
        const second = page.getByRole('button', { name:'Show ' + role.toUpperCase() + '-NEARBY on map', exact:true });
        const outline = page.locator('.parcel-map-focus-outline');
        await first.click();
        await expect(outline).toHaveCount(1);
        await expect(page.locator('.parcel-map-focus-halo')).toHaveCount(1);
        await expect(page.locator('.parcel-map-focus-label')).toHaveText(record.parcel_code);
        await expect(outline).toHaveAttribute('stroke', '#2563eb');
        await expect(first).toHaveAttribute('aria-pressed', 'true');
        await expect(page.locator('[data-map-selected]')).toHaveCount(1);
        expect(await outline.evaluate(node => getComputedStyle(node).pointerEvents)).toBe('none');
        expect(await outline.evaluate(node => getComputedStyle(node.ownerSVGElement).maxWidth)).toBe('none');
        expect(await outline.evaluate(node => getComputedStyle(node).filter)).toContain('drop-shadow');
        // A fresh viewport response must preserve the one selected boundary.
        const loads = await page.evaluate(() => window.viewportLoads);
        await first.click();
        await expect.poll(() => page.evaluate(() => window.viewportLoads)).toBeGreaterThan(loads);
        await expect(outline).toHaveCount(1);
        await expect(page.locator('.leaflet-overlay-pane path')).toHaveCount(2);
        await second.click();
        await expect(second).toHaveAttribute('aria-pressed', 'true');
        await expect(first).toHaveAttribute('aria-pressed', 'false');
        await expect(outline).toHaveCount(1);
        await expect(outline).toHaveAttribute('stroke', '#2563eb');
        await page.getByRole('button', { name:'Reset View', exact:true }).click();
        await expect(outline).toHaveCount(0);
        await expect(page.locator('.parcel-map-focus-label')).toHaveCount(0);
        await expect(page.locator('.parcel-map-focus-halo')).toHaveCount(0);
        await expect(page.locator('[data-map-selected]')).toHaveCount(0);
        await expect(second).toHaveAttribute('aria-pressed', 'false');
    });

    test(role + ' map offers independent focus and keyboard record navigation during API failure', async ({ page }) => {
        const record = await fixture(page, role);
        const show = page.getByRole('button', { name:'Show ' + record.parcel_code + ' on map', exact:true });
        const open = page.getByRole('link', { name:'Open ' + record.parcel_code + ' record', exact:true });
        await expect(show).toBeVisible();
        await expect(open).toHaveAttribute('href', record.details_url);
        await expect(open).toContainText(record.parcel_code);
        await expect(show).toHaveAttribute('title', 'Show on map');
        await expect(show.locator('svg')).toHaveCount(1);
        const result = page.locator('#parcel-search-results > div').first();
        await expect(result.locator('.parcel-search-area')).toHaveText('Parcel area1 ha');
        await expect(result.locator('.parcel-search-area strong')).toHaveText('1 ha');
        await expect(result.locator('[class$="-meta"]')).toHaveText('Calindagan, Dumaguete City');
        expect(await show.evaluate(node => node.getBoundingClientRect().height)).toBeGreaterThanOrEqual(48);
        expect(await show.evaluate(node => node.getBoundingClientRect().width)).toBe(48);
        expect(await result.evaluate(node => node.getBoundingClientRect().height)).toBeLessThan(130);
        expect(await show.evaluate(node => getComputedStyle(node).borderTopStyle)).toBe('solid');
        const pagination = page.locator('#parcel-search-pages');
        expect(await pagination.evaluate(node => parseFloat(getComputedStyle(node).gap))).toBeGreaterThan(0);
        await expect(pagination.getByRole('button', { name:'Previous parcel search page' })).toBeDisabled();
        await show.click();
        await expect.poll(() => page.evaluate(() => window.focusLoads)).toBe(1);
        await expect(show).toHaveAttribute('aria-pressed', 'true');
        await expect(page.locator('.parcel-map-focus-outline')).toHaveCount(1);
        await expect(page.locator('#parcel-map-status')).toContainText('The parcel list remains available.');
        expect(page.url()).toBe('about:blank');
        await open.focus();
        await page.keyboard.press('Enter');
        await expect(page).toHaveURL(record.details_url);
        await expect(page.getByRole('heading', { name:'Parcel record' })).toBeVisible();
    });
}

test('record navigation remains usable when the map library is unavailable', async ({ page }) => {
    const record = await fixture(page, 'landowner', false);
    await expect(page.locator('#parcel-map')).toContainText('The parcel list remains available.');
    await expect(page.getByRole('button', { name:'Show ' + record.parcel_code + ' on map' })).toBeHidden();
    await page.getByRole('link', { name:'Open ' + record.parcel_code + ' record' }).click();
    await expect(page).toHaveURL(record.details_url);
});
