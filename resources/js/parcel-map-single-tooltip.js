// Keep the staff parcel map to one visible Leaflet parcel tooltip at a time.
function initSingleParcelTooltipGuard() {
    if (!document.getElementById('parcel-map')) {
        return;
    }

    let attempts = 0;
    const maxAttempts = 40;

    const installGuard = () => {
        const leaflet = window.L;
        const layerPrototype = leaflet?.Layer?.prototype;

        if (!layerPrototype?.openTooltip || !layerPrototype?.closeTooltip) {
            attempts += 1;

            if (attempts < maxAttempts) {
                window.setTimeout(installGuard, 100);
            }

            return;
        }

        if (layerPrototype.__darSingleParcelTooltipGuardInstalled) {
            return;
        }

        const originalOpenTooltip = layerPrototype.openTooltip;
        const originalCloseTooltip = layerPrototype.closeTooltip;
        const activeLayerByMap = new WeakMap();

        layerPrototype.openTooltip = function (...args) {
            const map = this._map;
            const isParcelMap = map?.getContainer?.()?.id === 'parcel-map';

            if (isParcelMap) {
                const activeLayer = activeLayerByMap.get(map);

                if (activeLayer && activeLayer !== this) {
                    activeLayer.closeTooltip();
                }

                activeLayerByMap.set(map, this);
            }

            return originalOpenTooltip.apply(this, args);
        };

        layerPrototype.closeTooltip = function (...args) {
            const map = this._map;
            const isParcelMap = map?.getContainer?.()?.id === 'parcel-map';

            if (isParcelMap && activeLayerByMap.get(map) === this) {
                activeLayerByMap.delete(map);
            }

            return originalCloseTooltip.apply(this, args);
        };

        layerPrototype.__darSingleParcelTooltipGuardInstalled = true;
    };

    // The page loads Leaflet separately from the Vite bundle, so wait until
    // Leaflet is available before patching the parcel-map tooltip behavior.
    window.setTimeout(installGuard, 0);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSingleParcelTooltipGuard, { once: true });
} else {
    initSingleParcelTooltipGuard();
}
