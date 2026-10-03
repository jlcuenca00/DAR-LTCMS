import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const helperView = readFileSync('resources/views/staff/partials/geojson-polygon-editor.blade.php', 'utf8');
const helperScript = helperView.match(/<script>([\s\S]*?)<\/script>/)[1];
const original = { type: 'Polygon', coordinates: [[[123, 9], [124, 9], [124, 10], [123, 9]]], reference: { notes: 'Keep metadata' } };

async function initialize(page, geometry, mode = 'geographic', prefill = null) {
    await page.setContent('<form><div data-geojson-helper data-target="geometry" data-coordinate-mode="' + mode + '" data-require-geometry="true">' +
        '<div data-geojson-points>' + Array.from({ length: 4 }, (_, i) => '<div class="geojson-point-row"><span>Point ' + (i + 1) + '</span><input data-geojson-x type="number"><input data-geojson-y type="number"></div>').join('') + '</div>' +
        '<button type="button" data-geojson-build>Apply</button><button type="button" data-geojson-add-point>Add</button>' +
        '<button type="button" data-geojson-undo>Undo</button><button type="button" data-geojson-redo>Redo</button>' +
        '<textarea id="geometry"></textarea><p data-geojson-message></p></div></form>');
    await page.evaluate(({ geometry, prefill }) => {
        document.getElementById('geometry').value = JSON.stringify(geometry);
        window.DarLtcmsProjection = { toWgs84: (x, y) => [123 + x / 1000000, 9 + y / 1000000] };
        if (prefill) document.querySelectorAll('.geojson-point-row').forEach((row, i) => {
            row.querySelector('[data-geojson-x]').value = prefill[i]?.[0] ?? '';
            row.querySelector('[data-geojson-y]').value = prefill[i]?.[1] ?? '';
        });
    }, { geometry, prefill });
    await page.addScriptTag({ content: helperScript });
    await page.evaluate(() => document.dispatchEvent(new Event('DOMContentLoaded')));
}

async function submit(page) {
    return page.evaluate(() => {
        const event = new Event('submit', { bubbles: true, cancelable: true });
        document.querySelector('form').dispatchEvent(event);
        return { blocked: event.defaultPrevented, geometry: JSON.parse(document.getElementById('geometry').value) };
    });
}

test.beforeEach(({}, info) => { test.skip(info.project.name !== 'chromium-responsive', 'Run helper behavior once in Chromium.'); });

test('unchanged legacy projected references keep the exact geometry and metadata', async ({ page }) => {
    await initialize(page, original, 'prs92-zone4', [[100, 100], [200, 100], [200, 200]]);
    const result = await submit(page);
    expect(result.blocked).toBe(false);
    expect(result.geometry).toEqual(original);
});

test('adding an unused blank row does not convert unchanged legacy geometry', async ({ page }) => {
    await initialize(page, original, 'prs92-zone4', [[100, 100], [200, 100], [200, 200]]);
    await page.locator('[data-geojson-add-point]').click();
    const result = await submit(page);
    expect(result.blocked).toBe(false);
    expect(result.geometry).toEqual(original);
});

test('partial coordinate edits block submit without dropping a row or changing saved geometry', async ({ page }) => {
    await initialize(page, original);
    await page.locator('[data-geojson-add-point]').click();
    await page.locator('[data-geojson-x]').nth(3).fill('123.5');
    const result = await submit(page);
    expect(result.blocked).toBe(true);
    expect(result.geometry).toEqual(original);
    await expect(page.locator('[data-geojson-message]')).toContainText('Complete both coordinates');
});

test('single-ring edits retain metadata and undo restores the original geometry', async ({ page }) => {
    await initialize(page, original);
    await page.locator('[data-geojson-x]').nth(1).fill('123.8');
    const result = await submit(page);
    expect(result.blocked).toBe(false);
    expect(result.geometry.reference).toEqual(original.reference);
    expect(result.geometry.coordinates[0][1][0]).toBe(123.8);
    await page.locator('[data-geojson-undo]').click();
    const restored = await submit(page);
    expect(restored.geometry).toEqual(original);
});

test('multiple rings remain unchanged and cannot be flattened by the point helper', async ({ page }) => {
    const withHole = { ...original, coordinates: [...original.coordinates, [[123.7, 9.2], [123.8, 9.2], [123.8, 9.3], [123.7, 9.2]]] };
    await initialize(page, withHole);
    await expect(page.locator('[data-geojson-build]')).toBeDisabled();
    await expect(page.locator('[data-geojson-message]')).toContainText('read-only');
    const result = await submit(page);
    expect(result.blocked).toBe(false);
    expect(result.geometry).toEqual(withHole);
});
