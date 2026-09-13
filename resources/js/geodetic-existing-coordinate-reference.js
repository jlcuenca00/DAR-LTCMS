import proj4 from 'proj4';

proj4.defs(
    'EPSG:3124',
    '+proj=tmerc +lat_0=0 +lon_0=123 +k=0.99995 +x_0=500000 +y_0=0 +ellps=clrk66 +towgs84=-127.62,-67.24,-47.04,3.068,-4.903,-1.578,-1.06 +units=m +no_defs +type=crs'
);
proj4.defs('EPSG:4326', '+proj=longlat +datum=WGS84 +no_defs +type=crs');

function normalizePolygonRing(coordinates) {
    if (!Array.isArray(coordinates)) return [];

    const points = coordinates
        .filter((point) => Array.isArray(point) && point.length >= 2)
        .map((point) => [Number(point[0]), Number(point[1])])
        .filter((point) => Number.isFinite(point[0]) && Number.isFinite(point[1]));

    if (points.length > 1) {
        const first = points[0];
        const last = points[points.length - 1];

        if (first[0] === last[0] && first[1] === last[1]) {
            points.pop();
        }
    }

    return points;
}

function createCoordinateRow(index, easting, northing) {
    const row = document.createElement('div');
    row.className = 'geojson-point-row';
    row.innerHTML = `
        <span>Point ${index}</span>
        <input
            type="number"
            step="0.001"
            inputmode="decimal"
            placeholder="Easting (m)"
            aria-label="Point ${index} easting"
            data-geojson-x
        >
        <input
            type="number"
            step="0.001"
            inputmode="decimal"
            placeholder="Northing (m)"
            aria-label="Point ${index} northing"
            data-geojson-y
        >
    `;

    row.querySelector('[data-geojson-x]').value = Number(easting).toFixed(3);
    row.querySelector('[data-geojson-y]').value = Number(northing).toFixed(3);

    return row;
}

function prefillLegacyGeodeticCoordinates() {
    const editor = document.querySelector('[data-geojson-helper][data-coordinate-mode="prs92-zone4"]');
    if (!editor) return;

    const target = document.getElementById(editor.dataset.target || '');
    const pointsWrap = editor.querySelector('[data-geojson-points]');
    if (!target || !pointsWrap || !target.value.trim()) return;

    let geometry;

    try {
        geometry = JSON.parse(target.value);
    } catch (error) {
        return;
    }

    // Newer geometry already carries the exact original PRS92 source values.
    // The shared coordinate editor loads those directly, so do not replace them.
    if (geometry?.dar_source?.crs === 'EPSG:3124') return;

    if (geometry?.type !== 'Polygon') return;

    const geographicPoints = normalizePolygonRing(geometry?.coordinates?.[0]);
    if (geographicPoints.length < 3) return;

    try {
        const projectedPoints = geographicPoints.map(([longitude, latitude]) => {
            const [easting, northing] = proj4('EPSG:4326', 'EPSG:3124', [longitude, latitude]);

            if (!Number.isFinite(Number(easting)) || !Number.isFinite(Number(northing))) {
                throw new Error('Invalid projected coordinate');
            }

            return [easting, northing];
        });

        pointsWrap.innerHTML = '';
        projectedPoints.forEach(([easting, northing], index) => {
            pointsWrap.appendChild(createCoordinateRow(index + 1, easting, northing));
        });

        editor.dataset.legacyReferencePrefilled = 'true';
    } catch (error) {
        // Keep the editor usable with blank PRS92 inputs if an old geometry
        // cannot be projected safely.
    }
}

// Vite modules execute after the document has been parsed and before
// DOMContentLoaded. Populate legacy reference values now so the shared editor's
// history snapshot starts from the visible current coordinates rather than from
// blank inputs.
prefillLegacyGeodeticCoordinates();

document.addEventListener('DOMContentLoaded', () => {
    const editor = document.querySelector('[data-geojson-helper][data-legacy-reference-prefilled="true"]');
    const message = editor?.querySelector('[data-geojson-message]');

    if (message) {
        message.textContent = 'Current parcel boundary values loaded for reference. They were derived from the existing mapped geometry and will be stored as PRS92 source coordinates when you save.';
        message.classList.remove('is-error');
    }
}, { once: true });
