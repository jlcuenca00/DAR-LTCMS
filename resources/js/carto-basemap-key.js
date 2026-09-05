// CARTO began requiring API keys for public basemap tile requests in 2026.
// Basemap keys are browser-side credentials by design: every tile request sends
// the key to CARTO. Keep this key scoped to the DAR-LTCMS domain in CARTO.
const CARTO_BASEMAP_KEY = 'cb1_2xth_1_41aed4ca342ae1da9426c873';

function patchCartoTileRequests() {
    const leaflet = window.L;
    const prototype = leaflet?.TileLayer?.prototype;

    if (!prototype || prototype.__darLtcmsCartoKeyPatched) {
        return Boolean(prototype?.__darLtcmsCartoKeyPatched);
    }

    const originalGetTileUrl = prototype.getTileUrl;

    if (typeof originalGetTileUrl !== 'function') {
        return false;
    }

    prototype.getTileUrl = function getDarLtcmsCartoTileUrl(coords) {
        const url = originalGetTileUrl.call(this, coords);

        if (
            typeof url !== 'string'
            || !url.includes('basemaps.cartocdn.com')
            || /[?&]key=/.test(url)
        ) {
            return url;
        }

        const separator = url.includes('?') ? '&' : '?';

        return `${url}${separator}key=${encodeURIComponent(CARTO_BASEMAP_KEY)}`;
    };

    Object.defineProperty(prototype, '__darLtcmsCartoKeyPatched', {
        configurable: false,
        enumerable: false,
        value: true,
        writable: false,
    });

    return true;
}

function ensureCartoTilePatch() {
    if (patchCartoTileRequests()) {
        return;
    }

    let attempts = 0;
    const timer = window.setInterval(() => {
        attempts += 1;

        if (patchCartoTileRequests() || attempts >= 100) {
            window.clearInterval(timer);
        }
    }, 50);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ensureCartoTilePatch, { once: true });
} else {
    ensureCartoTilePatch();
}
