@php
    $geoFieldName = $fieldName ?? 'geometry_geojson';
    $geoFieldId = $fieldId ?? str_replace(['[', ']'], ['_', ''], $geoFieldName);
    $geoValue = old($geoFieldName, $value ?? '');
    $isPrs92Zone4 = $geoFieldName === 'geometry_geojson';
    $requireGeometry = (bool) ($requireGeometry ?? false);

    if (is_array($geoValue)) {
        $geoValue = json_encode($geoValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    $geoInputClass = $inputClass ?? 'w-full rounded-lg border-gray-300 text-xs font-mono';
    $geoErrorClass = $errorClass ?? 'text-xs font-bold text-red-600';
    $geoRows = $rows ?? 8;
@endphp

<div
    class="geojson-helper"
    data-geojson-helper
    data-target="{{ $geoFieldId }}"
    data-coordinate-mode="{{ $isPrs92Zone4 ? 'prs92-zone4' : 'geographic' }}"
    data-require-geometry="{{ $requireGeometry ? 'true' : 'false' }}"
>
    <div class="geojson-toolbar">
        <div>
            <p class="geojson-title">{{ $isPrs92Zone4 ? 'PRS92 / PTM Zone IV parcel boundary' : 'Parcel boundary helper' }}</p>
            <p class="geojson-copy">
                @if ($isPrs92Zone4)
                    Enter the parcel's Easting and Northing survey points. DAR-LTCMS retains those PRS92 Zone IV values and converts them to WGS84 for the online map.
                @else
                    Enter longitude and latitude points. The helper creates the Polygon geometry used by the map field.
                @endif
            </p>
            @if ($isPrs92Zone4)
                <span class="geojson-crs-badge">PRS92 / Philippines Zone 4 · EPSG:3124 · metres</span>
            @endif
        </div>

        <div class="geojson-actions" aria-label="Coordinate editor actions">
            <button type="button" class="geojson-button" data-geojson-undo disabled title="Undo (Ctrl/Cmd+Z)">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Undo
            </button>
            <button type="button" class="geojson-button" data-geojson-redo disabled title="Redo (Ctrl+Y or Ctrl/Cmd+Shift+Z)">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Redo
            </button>
            <button type="button" class="geojson-button" data-geojson-add-point>
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Add point
            </button>
            <button type="button" class="geojson-button primary" data-geojson-build>
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                {{ $isPrs92Zone4 ? 'Convert & Apply' : 'Apply Coordinates' }}
            </button>
        </div>
    </div>

    <div class="geojson-point-grid" data-geojson-points>
        @foreach ([1, 2, 3, 4] as $row)
            <div class="geojson-point-row">
                <span>Point {{ $row }}</span>
                <input
                    type="number"
                    step="{{ $isPrs92Zone4 ? '0.001' : '0.000001' }}"
                    inputmode="decimal"
                    placeholder="{{ $isPrs92Zone4 ? 'Easting (m)' : 'Longitude / X' }}"
                    aria-label="Point {{ $row }} {{ $isPrs92Zone4 ? 'easting' : 'longitude' }}"
                    data-geojson-x
                >
                <input
                    type="number"
                    step="{{ $isPrs92Zone4 ? '0.001' : '0.000001' }}"
                    inputmode="decimal"
                    placeholder="{{ $isPrs92Zone4 ? 'Northing (m)' : 'Latitude / Y' }}"
                    aria-label="Point {{ $row }} {{ $isPrs92Zone4 ? 'northing' : 'latitude' }}"
                    data-geojson-y
                >
            </div>
        @endforeach
    </div>

    @if ($isPrs92Zone4)
        <textarea id="{{ $geoFieldId }}" name="{{ $geoFieldName }}" hidden>{{ $geoValue }}</textarea>
        <p class="geojson-output-note">The online-map geometry is generated automatically from these survey points. The original Easting/Northing values remain stored with the parcel geometry.</p>
    @else
        <div class="geojson-textarea-wrap">
            <div class="geojson-textarea-header">
                <span>Map Geometry Output</span>
            </div>
            <textarea id="{{ $geoFieldId }}" name="{{ $geoFieldName }}" rows="{{ $geoRows }}" class="{{ $geoInputClass }}" placeholder="Use Apply Coordinates to generate the map geometry, or paste valid Polygon GeoJSON.">{{ $geoValue }}</textarea>
        </div>
    @endif

    <p class="geojson-message" data-geojson-message aria-live="polite"></p>
    @error($geoFieldName)<p class="{{ $geoErrorClass }}">{{ $message }}</p>@enderror
</div>

@once
    <style>
        .geojson-helper {
            display: grid;
            gap: 14px;
            border: 1px solid #bbf7d0;
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
            border-radius: 14px;
            padding: 14px;
        }

        .geojson-toolbar,
        .geojson-textarea-header {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: flex-start;
        }

        .geojson-title {
            margin: 0;
            color: #14532d;
            font-size: 13px;
            font-weight: 950;
        }

        .geojson-copy,
        .geojson-output-note {
            margin: 4px 0 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
        }

        .geojson-output-note {
            margin: 0;
            color: #64748b;
            font-size: 11px;
        }

        .geojson-crs-badge {
            display: inline-flex;
            align-items: center;
            margin-top: 8px;
            min-height: 26px;
            padding: 0 9px;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            background: #ecfdf5;
            color: #166534;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .02em;
        }

        .geojson-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 7px;
        }

        .geojson-button {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid #bbf7d0;
            background: #ffffff;
            color: #166534;
            border-radius: 9px;
            padding: 0 12px;
            font-size: 11.5px;
            font-weight: 900;
            cursor: pointer;
            touch-action: manipulation;
        }

        .geojson-button:hover:not(:disabled) {
            background: #ecfdf5;
            border-color: #86efac;
        }

        .geojson-button:disabled {
            cursor: not-allowed;
            opacity: .45;
            background: #f8fafc;
            color: #64748b;
            border-color: #e2e8f0;
        }

        .geojson-button.primary {
            border-color: #166534;
            background: #166534;
            color: #ffffff;
        }

        .geojson-point-grid {
            display: grid;
            gap: 8px;
        }

        .geojson-point-row {
            display: grid;
            grid-template-columns: 72px repeat(2, minmax(0, 1fr));
            gap: 8px;
            align-items: center;
        }

        .geojson-point-row span {
            color: #334155;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .geojson-point-row input,
        .geojson-textarea-wrap textarea {
            border-color: #cbd5e1;
            background: #ffffff;
        }

        .geojson-point-row input {
            width: 100%;
            min-height: 44px;
            border-radius: 9px;
            font-size: 13px;
        }

        .geojson-textarea-wrap {
            display: grid;
            gap: 7px;
        }

        .geojson-textarea-header span {
            color: #334155;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .geojson-message {
            min-height: 16px;
            margin: 0;
            color: #166534;
            font-size: 12px;
            font-weight: 800;
        }

        .geojson-message.is-error {
            color: #b91c1c;
        }

        @media (pointer: coarse) {
            .geojson-button,
            .geojson-point-row input {
                min-height: 48px;
            }
        }

        @media (max-width: 760px) {
            .geojson-toolbar,
            .geojson-textarea-header,
            .geojson-point-row {
                grid-template-columns: 1fr;
                display: grid;
            }

            .geojson-actions {
                justify-content: stretch;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .geojson-button {
                width: 100%;
            }

            .geojson-point-row input,
            .geojson-textarea-wrap textarea {
                font-size: 16px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-geojson-helper]').forEach(function (editor) {
                const target = document.getElementById(editor.dataset.target);
                const pointsWrap = editor.querySelector('[data-geojson-points]');
                const message = editor.querySelector('[data-geojson-message]');
                const undoButton = editor.querySelector('[data-geojson-undo]');
                const redoButton = editor.querySelector('[data-geojson-redo]');
                const isPrs92Zone4 = editor.dataset.coordinateMode === 'prs92-zone4';
                const requireGeometry = editor.dataset.requireGeometry === 'true';

                if (! target || ! pointsWrap) return;

                const axisLabels = isPrs92Zone4
                    ? { x: 'easting', y: 'northing', xPlaceholder: 'Easting (m)', yPlaceholder: 'Northing (m)', step: '0.001' }
                    : { x: 'longitude', y: 'latitude', xPlaceholder: 'Longitude / X', yPlaceholder: 'Latitude / Y', step: '0.000001' };

                const undoStack = [];
                const redoStack = [];
                const historyLimit = 60;
                let lastSnapshot = null;
                let pendingCapture = null;
                let restoring = false;
                let appliedRows = null;
                let helperReadOnly = false;

                const setMessage = function (text, isError = false) {
                    if (! message) return;
                    message.textContent = text || '';
                    message.classList.toggle('is-error', !!isError);
                };

                const addPointRow = function (x = '', y = '') {
                    const index = pointsWrap.querySelectorAll('.geojson-point-row').length + 1;
                    const row = document.createElement('div');
                    row.className = 'geojson-point-row';
                    row.innerHTML = '<span>Point ' + index + '</span>'
                        + '<input type="number" step="' + axisLabels.step + '" inputmode="decimal" placeholder="' + axisLabels.xPlaceholder + '" aria-label="Point ' + index + ' ' + axisLabels.x + '" data-geojson-x>'
                        + '<input type="number" step="' + axisLabels.step + '" inputmode="decimal" placeholder="' + axisLabels.yPlaceholder + '" aria-label="Point ' + index + ' ' + axisLabels.y + '" data-geojson-y>';
                    row.querySelector('[data-geojson-x]').value = x;
                    row.querySelector('[data-geojson-y]').value = y;
                    pointsWrap.appendChild(row);
                };

                const renumberRows = function () {
                    pointsWrap.querySelectorAll('.geojson-point-row').forEach(function (row, index) {
                        const number = index + 1;
                        const label = row.querySelector('span');
                        const x = row.querySelector('[data-geojson-x]');
                        const y = row.querySelector('[data-geojson-y]');
                        if (label) label.textContent = 'Point ' + number;
                        if (x) x.setAttribute('aria-label', 'Point ' + number + ' ' + axisLabels.x);
                        if (y) y.setAttribute('aria-label', 'Point ' + number + ' ' + axisLabels.y);
                    });
                };

                const snapshot = function () {
                    return {
                        rows: Array.from(pointsWrap.querySelectorAll('.geojson-point-row')).map(function (row) {
                            return [
                                row.querySelector('[data-geojson-x]')?.value ?? '',
                                row.querySelector('[data-geojson-y]')?.value ?? ''
                            ];
                        }),
                        target: target.value,
                        appliedRows
                    };
                };

                const sameSnapshot = function (left, right) {
                    return JSON.stringify(left) === JSON.stringify(right);
                };

                const updateHistoryButtons = function () {
                    if (undoButton) undoButton.disabled = undoStack.length === 0;
                    if (redoButton) redoButton.disabled = redoStack.length === 0;
                };

                const captureChange = function () {
                    if (restoring) return;
                    const current = snapshot();

                    if (lastSnapshot === null) {
                        lastSnapshot = current;
                        updateHistoryButtons();
                        return;
                    }

                    if (sameSnapshot(current, lastSnapshot)) return;

                    undoStack.push(lastSnapshot);
                    if (undoStack.length > historyLimit) undoStack.shift();
                    redoStack.length = 0;
                    lastSnapshot = current;
                    updateHistoryButtons();
                };

                const scheduleCapture = function () {
                    if (restoring) return;
                    window.clearTimeout(pendingCapture);
                    pendingCapture = window.setTimeout(function () {
                        pendingCapture = null;
                        captureChange();
                    }, 300);
                };

                const flushCapture = function () {
                    if (pendingCapture) {
                        window.clearTimeout(pendingCapture);
                        pendingCapture = null;
                        captureChange();
                    }
                };

                const restoreSnapshot = function (state) {
                    restoring = true;
                    pointsWrap.innerHTML = '';
                    (state.rows || []).forEach(function (row) {
                        addPointRow(row[0], row[1]);
                    });
                    if ((state.rows || []).length === 0) {
                        [1, 2, 3, 4].forEach(function () { addPointRow(); });
                    }
                    renumberRows();
                    target.value = state.target || '';
                    appliedRows = state.appliedRows;
                    lastSnapshot = snapshot();
                    restoring = false;
                    updateHistoryButtons();
                };

                const undo = function () {
                    flushCapture();
                    if (undoStack.length === 0) return;
                    const current = snapshot();
                    const previous = undoStack.pop();
                    redoStack.push(current);
                    restoreSnapshot(previous);
                    setMessage('Last coordinate edit undone.');
                };

                const redo = function () {
                    flushCapture();
                    if (redoStack.length === 0) return;
                    const current = snapshot();
                    const next = redoStack.pop();
                    undoStack.push(current);
                    restoreSnapshot(next);
                    setMessage('Coordinate edit restored.');
                };

                const recordImmediateMutation = function (callback) {
                    flushCapture();
                    const before = snapshot();
                    callback();
                    const after = snapshot();

                    if (!sameSnapshot(before, after)) {
                        undoStack.push(before);
                        if (undoStack.length > historyLimit) undoStack.shift();
                        redoStack.length = 0;
                        lastSnapshot = after;
                        updateHistoryButtons();
                    }
                };

                const readRows = function () {
                    const coordinates = [];

                    let incomplete = false;
                    pointsWrap.querySelectorAll('.geojson-point-row').forEach(function (row, index) {
                        const x = row.querySelector('[data-geojson-x]')?.value ?? '';
                        const y = row.querySelector('[data-geojson-y]')?.value ?? '';
                        if ((x === '') !== (y === '')) {
                            incomplete = true;
                            setMessage('Complete both coordinates for Point ' + (index + 1) + ' before applying or saving.', true);
                        } else if (x !== '' && y !== '') {
                            coordinates.push([Number(x), Number(y)]);
                        }
                    });
                    if (incomplete) return null;

                    return coordinates;
                };

                const closeRing = function (coordinates) {
                    const ring = coordinates.map(function (point) { return [point[0], point[1]]; });
                    const first = ring[0];
                    const last = ring[ring.length - 1];

                    if (first && last && (first[0] !== last[0] || first[1] !== last[1])) {
                        ring.push([first[0], first[1]]);
                    }

                    return ring;
                };

                const buildFromRows = function () {
                    flushCapture();
                    const currentRows = snapshot().rows;
                    if (helperReadOnly) {
                        setMessage('This geometry has multiple rings, extra dimensions, or an unsupported shape. The point helper is read-only to preserve it.', true);
                        return false;
                    }
                    if (target.value.trim() && JSON.stringify(currentRows) === JSON.stringify(appliedRows)) return true;
                    const sourceCoordinates = readRows();
                    if (sourceCoordinates === null) return false;

                    if (sourceCoordinates.length < 3) {
                        setMessage('Add at least 3 complete coordinate points before building a polygon.', true);
                        return false;
                    }

                    if (sourceCoordinates.some(function (point) {
                        return !Number.isFinite(point[0]) || !Number.isFinite(point[1]);
                    })) {
                        setMessage('Every coordinate point must contain valid numbers.', true);
                        return false;
                    }

                    let mapCoordinates = sourceCoordinates;
                    let geometry = { type: 'Polygon', coordinates: [] };
                    if (target.value.trim()) {
                        try { geometry = JSON.parse(target.value); }
                        catch {
                            setMessage('The existing geometry must be reviewed before applying coordinate changes.', true);
                            return false;
                        }
                    }

                    if (isPrs92Zone4) {
                        const projection = window.DarLtcmsProjection;

                        if (! projection || typeof projection.toWgs84 !== 'function') {
                            setMessage('The PRS92 coordinate converter is unavailable. Refresh the page and try again.', true);
                            return false;
                        }

                        try {
                            mapCoordinates = sourceCoordinates.map(function (point) {
                                const converted = projection.toWgs84(point[0], point[1]);
                                const longitude = Number(converted[0]);
                                const latitude = Number(converted[1]);

                                if (!Number.isFinite(longitude) || !Number.isFinite(latitude)) {
                                    throw new Error('Invalid transformed coordinate');
                                }

                                if (longitude < 116 || longitude > 130 || latitude < 3 || latitude > 23) {
                                    throw new Error('Coordinate is outside the expected Philippines extent');
                                }

                                return [Number(longitude.toFixed(8)), Number(latitude.toFixed(8))];
                            });
                        } catch (error) {
                            setMessage('Could not convert these PRS92 Zone IV coordinates. Check the Easting/Northing values and their order.', true);
                            return false;
                        }

                        geometry.dar_source = {
                            ...(geometry.dar_source || {}),
                            crs: 'EPSG:3124',
                            name: 'PRS92 / Philippines zone 4',
                            projection: 'PTM Zone IV',
                            coordinate_order: ['easting', 'northing'],
                            unit: 'metre',
                            coordinates: sourceCoordinates
                        };
                    }

                    const ring = closeRing(mapCoordinates);
                    const distinct = new Set(mapCoordinates.map(point => JSON.stringify(point)));
                    const origin = ring[0];
                    let twiceArea = 0;
                    for (let i = 0; i < ring.length - 1; i++) {
                        twiceArea += (ring[i][0] - origin[0]) * (ring[i + 1][1] - origin[1])
                            - (ring[i + 1][0] - origin[0]) * (ring[i][1] - origin[1]);
                    }
                    if (distinct.size < 3 || Math.abs(twiceArea) <= 1e-14) {
                        setMessage('Use at least three distinct points forming a non-zero area.', true);
                        return false;
                    }
                    geometry.coordinates = [ring];
                    if (Object.hasOwn(geometry, 'bbox')) {
                        geometry.bbox = [Math.min(...mapCoordinates.map(p => p[0])), Math.min(...mapCoordinates.map(p => p[1])),
                            Math.max(...mapCoordinates.map(p => p[0])), Math.max(...mapCoordinates.map(p => p[1]))];
                    }
                    appliedRows = currentRows;
                    target.value = JSON.stringify(geometry, null, 2);
                    lastSnapshot = snapshot();
                    updateHistoryButtons();
                    setMessage(isPrs92Zone4
                        ? 'Coordinates converted and ready to save. Original Easting/Northing values are retained.'
                        : 'Map geometry generated. You can save the form now.');
                    return true;
                };

                const normalizeStoredRing = function (coordinates) {
                    if (!Array.isArray(coordinates)) return [];

                    const points = coordinates
                        .filter(function (point) {
                            return Array.isArray(point) && point.length >= 2;
                        })
                        .map(function (point) {
                            return [point[0], point[1]];
                        });

                    if (points.length > 1) {
                        const first = points[0];
                        const last = points[points.length - 1];
                        if (first[0] === last[0] && first[1] === last[1]) {
                            points.pop();
                        }
                    }

                    return points;
                };

                const loadExistingCoordinates = function () {
                    if (!target.value.trim()) return;

                    try {
                        const parsed = JSON.parse(target.value);
                        helperReadOnly = parsed?.type !== 'Polygon' || !Array.isArray(parsed.coordinates)
                            || parsed.coordinates.length !== 1 || !Array.isArray(parsed.coordinates[0])
                            || parsed.coordinates[0].some(point => !Array.isArray(point) || point.length !== 2);
                        if (helperReadOnly) {
                            pointsWrap.querySelectorAll('input').forEach(input => { input.readOnly = true; });
                            editor.querySelector('[data-geojson-build]').disabled = true;
                            editor.querySelector('[data-geojson-add-point]').disabled = true;
                            setMessage('This geometry has multiple rings, extra dimensions, or an unsupported shape. The point helper is read-only; stored geometry stays unchanged.', true);
                            return;
                        }
                        let coordinates = [];

                        if (isPrs92Zone4) {
                            if (parsed?.dar_source?.crs === 'EPSG:3124') {
                                coordinates = normalizeStoredRing(parsed.dar_source.coordinates);
                            }
                        } else {
                            coordinates = normalizeStoredRing(parsed?.coordinates?.[0]);
                        }

                        if (coordinates.length >= 3) {
                            pointsWrap.innerHTML = '';
                            coordinates.forEach(function (point) {
                                addPointRow(point[0], point[1]);
                            });
                            renumberRows();

                            if (isPrs92Zone4) {
                                setMessage('Stored PRS92 Zone IV source coordinates loaded.');
                            }
                        } else if (isPrs92Zone4 && parsed?.type === 'Polygon' && Array.isArray(parsed?.coordinates?.[0])) {
                            setMessage('This parcel has legacy geographic-only geometry. It stays unchanged until PRS92 Zone IV points are entered and converted.');
                        }
                    } catch (error) {
                        setMessage('The stored geometry could not be loaded into the coordinate helper.', true);
                    }
                };

                pointsWrap.addEventListener('input', function (event) {
                    if (event.target.matches('[data-geojson-x], [data-geojson-y]')) {
                        scheduleCapture();
                    }
                });

                editor.querySelector('[data-geojson-add-point]')?.addEventListener('click', function () {
                    recordImmediateMutation(function () {
                        addPointRow();
                        renumberRows();
                    });
                });

                editor.querySelector('[data-geojson-build]')?.addEventListener('click', buildFromRows);
                undoButton?.addEventListener('click', undo);
                redoButton?.addEventListener('click', redo);

                editor.addEventListener('keydown', function (event) {
                    if (!(event.ctrlKey || event.metaKey)) return;

                    const key = String(event.key || '').toLowerCase();
                    if (key === 'z') {
                        event.preventDefault();
                        if (event.shiftKey) {
                            redo();
                        } else {
                            undo();
                        }
                        return;
                    }

                    if (key === 'y') {
                        event.preventDefault();
                        redo();
                    }
                });

                const form = editor.closest('form');
                form?.addEventListener('submit', function (event) {
                    flushCapture();

                    const rowsChanged = JSON.stringify(snapshot().rows) !== JSON.stringify(appliedRows);
                    if (rowsChanged) {
                        if (!buildFromRows()) event.preventDefault();
                        return;
                    }

                    if (requireGeometry && !target.value.trim()) {
                        event.preventDefault();
                        setMessage('Enter at least 3 complete coordinate points before saving this parcel geometry.', true);
                    }
                });

                loadExistingCoordinates();
                appliedRows = snapshot().rows;
                lastSnapshot = snapshot();
                updateHistoryButtons();
            });
        });
    </script>
@endonce
