import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const leaflet = readFileSync('node_modules/leaflet/dist/leaflet.js', 'utf8');
const viewer = readFileSync('resources/js/parcel-map-viewer.js', 'utf8');

test('parcel viewer keeps one tooltip and cleans overlays before redraws', async ({ page }) => {
    await page.route('https://*.basemaps.cartocdn.com/**', route => route.abort());
    await page.setContent(`<div id="parcel-map" data-parcel-map-viewer style="width:300px;height:300px"></div>
        <div id="parcel-search-results"></div><div id="parcel-search-pages"></div>
        <p id="parcel-search-status"></p><p id="parcel-map-status"></p>
        <script type="application/json" data-parcel-map-config>${JSON.stringify({
            role: 'staff', features_url: 'https://fixture.test/features',
            search_url: 'https://fixture.test/search', focus_url: 'https://fixture.test/feature/__ID__',
            initial_search: { items: [], total: 0, page: 1, last_page: 1 },
        })}</script>`);
    await page.addScriptTag({ content: leaflet });
    await page.evaluate(() => {
        const createMap = window.L.map;
        window.L.map = (...args) => {
            window.fixtureMap = createMap(...args);
            return window.fixtureMap;
        };
        window.viewportLoads = 0;
        window.fetch = async () => {
            window.viewportLoads++;
            return { ok: true, json: async () => ({
                total: 2, returned: 2, limited: false, skipped: 0,
                features: [1, 2].map(id => ({
                    type: 'Feature',
                    properties: {
                        id, parcel_code: 'TOOLTIP-' + id,
                        area_hectares: 10, active_linked_area_hectares: 2,
                        historical_holding_count: 0, details_url: '/parcel/' + id,
                    },
                    geometry: {
                        type: 'Polygon',
                        coordinates: [[[123.30, 9.30], [123.31, 9.30], [123.31, 9.31], [123.30, 9.30]]],
                    },
                })),
            }) };
        };
    });
    await page.addScriptTag({ content: viewer });
    await page.evaluate(() => initializeParcelMapViewer());
    await expect(page.locator('#parcel-map .leaflet-overlay-pane path')).toHaveCount(2);
    await page.evaluate(() => {
        const parcelLayers = [];
        window.fixtureMap.eachLayer(layer => { if (layer.getTooltip?.()) parcelLayers.push(layer); });
        parcelLayers[0].openTooltip();
        parcelLayers[1].openTooltip();
    });
    await expect(page.locator('.parcel-tooltip')).toHaveCount(1);
    await expect(page.locator('.parcel-tooltip')).toContainText('TOOLTIP-2');

    const previousLoads = await page.evaluate(() => window.viewportLoads);
    await page.evaluate(() => window.fixtureMap.fire('moveend'));
    await expect.poll(() => page.evaluate(() => window.viewportLoads)).toBeGreaterThan(previousLoads);
    await expect(page.locator('.parcel-tooltip')).toHaveCount(0);
    await page.evaluate(() => {
        window.fixtureMap.eachLayer(layer => { if (layer.getTooltip?.()) layer.openTooltip(); });
    });
    await expect(page.locator('.parcel-tooltip')).toHaveCount(1);
    await expect(page.locator('.parcel-tooltip')).toContainText('Current active linked area');
});
