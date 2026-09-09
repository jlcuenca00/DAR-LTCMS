@php
    $geoFieldName = $fieldName ?? 'geometry_geojson';
    $geoFieldId = $fieldId ?? str_replace(['[', ']'], ['_', ''], $geoFieldName);
    $geoValue = old($geoFieldName, $value ?? '');
    $isPrs92Zone4 = $geoFieldName === 'geometry_geojson';

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
>
    <div class="geojson-toolbar">
        <div>
            <p class="geojson-title">{{ $isPrs92Zone4 ? 'PRS92 / PTM Zone IV parcel boundary' : 'Parcel boundary helper' }}</p>
            <p class="geojson-copy">
                @if ($isPrs92Zone4)
                    Enter the parcel's Easting and Northing survey points. DAR-LTCMS retains those PRS92 Zone IV values and converts them to WGS84 coordinates for the online map.
                @else
                    Enter longitude and latitude points. The helper creates the valid Polygon format used by the map field.
                @endif
            </p>
            @if ($isPrs92Zone4)
                <span class="geojson-crs-badge">PRS92 / Philippines Zone 4 · EPSG:3124 · metres</span>
            @endif
        </div>
        <div class="geojson-actions">
            <button type="button" class="geojson-button" data-geojson-add-point>
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Add point
            </button>
            <button type="button" class="geojson-button" data-geojson-sample>
                <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Sample
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

    <div class="geojson-textarea-wrap">
        <div class="geojson-textarea-header">
            <span>{{ $isPrs92Zone4 ? 'Generated Web Map Geometry (WGS84)' : 'Map Geometry Output' }}</span>
            <div class="geojson-actions compact">
                <button type="button" class="geojson-button" data-geojson-format>
                    <i class="fa-solid fa-code" aria-hidden="true"></i> Format
                </button>
                <button type="button" class="geojson-button" data-geojson-clear>
                    <i class="fa-solid fa-eraser" aria-hidden="true"></i> Clear
                </button>
            </div>
        </div>
        <textarea id="{{ $geoFieldId }}" name="{{ $geoFieldName }}" rows="{{ $geoRows }}" class="{{ $geoInputClass }}" placeholder='{{ $isPrs92Zone4 ? 'Use Convert & Apply to generate the web-map geometry from PRS92 Zone IV points.' : 'Use Sample or Apply Coordinates to fill this map geometry field.' }}'>{{ $geoValue }}</textarea>
        @if ($isPrs92Zone4)
            <p class="geojson-output-note">The original Easting/Northing points are stored inside this geometry record as DAR source metadata. The converted longitude/latitude polygon is used only for web-map display.</p>
        @endif
    </div>

    <p class="geojson-message" data-geojson-message aria-live="polite"></p>
    @error($geoFieldName)<p class="{{ $geoErrorClass }}">{{ $message }}</p>@enderror
</div>

@once
    <style>
        .geojson-helper {
            display: grid;
            gap: 12px;
            border: 1px solid #bbf7d0;
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
            border-radius: 14px;
            padding: 14px;
        }

        .geojson-toolbar,
        .geojson-textarea-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
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
            margin: 3px 0 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.45;
        }

        .geojson-output-note {
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

        .geojson-actions.compact {
            flex-wrap: nowrap;
        }

        .geojson-button {
            min-height: 44px;
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

        .geojson-button:hover {
            background: #ecfdf5;
            border-color: #86efac;
        }

        .geojson-button.primary {
            border-color: #166534;
            background: #166534;
            color: #ffffff;
        }

        .geojson-point-grid {
            display: grid;
            gap: 7px;
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
            }

            .geojson-actions.compact {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                display: grid;
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
                const isPrs92Zone4 = editor.dataset.coordinateMode === 'prs92-zone4';

                if (! target || ! pointsWrap) return;

                const axisLabels = isPrs92Zone4
                    ? { x: 'easting', y: 'northing', xPlaceholder: 'Easting (m)', yPlaceholder: 'Northing (m)', step: '0.001' }
                    : { x: 'longitude', y: 'latitude', xPlaceholder: 'Longitude / X', yPlaceholder: 'Latitude / Y', step: '0.000001' };

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

                const readRows = function () {
                    const coordinates = [];

                    pointsWrap.querySelectorAll('.geojson-point-row').forEach(function (row) {
                        const x = row.querySelector('[data-geojson-x]')?.value;
                        const y = row.querySelector('[data-geojson-y]')?.value;

                        if (x !== '' && y !== '') {
                            coordinates.push([Number(x), Number(y)]);
                        }
                    });

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
                    const sourceCoordinates = readRows();

                    if (sourceCoordinates.length < 3) {
                        setMessage('Add at least 3 coordinate points before building a polygon.', true);
                        return false;
                    }

                    if (sourceCoordinates.some(function (point) {
                        return !Number.isFinite(point[0]) || !Number.isFinite(point[1]);
                    })) {
                        setMessage('Every coordinate point must contain valid numbers.', true);
                        return false;
                    }

                    let mapCoordinates = sourceCoordinates;
                    const geometry = { type: 'Polygon', coordinates: [] };

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
                            crs: 'EPSG:3124',
                            name: 'PRS92 / Philippines zone 4',
                            projection: 'PTM Zone IV',
                            coordinate_order: ['easting', 'northing'],
                            unit: 'metre',
                            coordinates: sourceCoordinates
                        };
                    }

                    geometry.coordinates = [closeRing(mapCoordinates)];
                    target.value = JSON.stringify(geometry, null, 2);
                    setMessage(isPrs92Zone4
                        ? 'PRS92 points converted to WGS84 for the web map. Original Easting/Northing values were retained.'
                        : 'Map geometry generated. You can save the form now.');
                    return true;
                };

                const loadSample = function () {
                    const sample = isPrs92Zone4
                        ? [
                            [477318.941, 1034526.171],
                            [478911.914, 1034912.331],
                            [478659.955, 1036095.895],
                            [477451.427, 1035632.091]
                        ]
                        : [
                            [122.795000, 9.355000],
                            [122.809500, 9.358500],
                            [122.807200, 9.369200],
                            [122.796200, 9.365000]
                        ];

                    pointsWrap.innerHTML = '';
                    sample.forEach(function (point) {
                        addPointRow(point[0], point[1]);
                    });

                    buildFromRows();
                    setMessage(isPrs92Zone4
                        ? 'Sample PRS92 Zone IV parcel loaded and converted. Replace it with the actual survey coordinates before saving.'
                        : 'Sample parcel polygon loaded. Adjust the coordinates if needed.');
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
                            setMessage('This is a legacy geographic-only parcel geometry. It remains unchanged unless you enter PRS92 Zone IV Easting/Northing points and convert it.');
                        }
                    } catch (error) {
                        // Keep the existing raw value visible so the normal validation path can report it.
                    }
                };

                if (isPrs92Zone4) {
                    const fieldLabel = editor.closest('.parcel-create-field, .parcel-edit-field')?.querySelector('label[for="' + target.id + '"]');
                    if (fieldLabel && /geojson geometry/i.test(fieldLabel.textContent || '')) {
                        fieldLabel.textContent = 'PRS92 / PTM Zone IV Parcel Boundary';
                    }

                    const panelCopy = editor.closest('.geo-map-editor-panel')?.querySelector('.geo-map-editor-panel-copy');
                    if (panelCopy && /longitude and latitude/i.test(panelCopy.textContent || '')) {
                        panelCopy.textContent = 'Enter PRS92 / PTM Zone IV Easting and Northing points. The system converts them to WGS84 for the online map while retaining the original survey coordinates.';
                    }
                }

                editor.querySelector('[data-geojson-add-point]')?.addEventListener('click', function () {
                    addPointRow();
                    renumberRows();
                });

                editor.querySelector('[data-geojson-build]')?.addEventListener('click', buildFromRows);
                editor.querySelector('[data-geojson-sample]')?.addEventListener('click', loadSample);

                editor.querySelector('[data-geojson-format]')?.addEventListener('click', function () {
                    try {
                        const parsed = JSON.parse(target.value || '{}');
                        if (!parsed.type || !parsed.coordinates) {
                            setMessage('GeoJSON must include type and coordinates.', true);
                            return;
                        }
                        target.value = JSON.stringify(parsed, null, 2);
                        setMessage('GeoJSON formatted successfully.');
                    } catch (error) {
                        setMessage('This is not valid JSON yet. Use the coordinate builder if you do not want to type it manually.', true);
                    }
                });

                editor.querySelector('[data-geojson-clear]')?.addEventListener('click', function () {
                    target.value = '';
                    pointsWrap.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                    setMessage('Map geometry field cleared.');
                });

                const form = editor.closest('form');
                form?.addEventListener('submit', function (event) {
                    const completedRows = Array.from(pointsWrap.querySelectorAll('.geojson-point-row')).filter(function (row) {
                        return row.querySelector('[data-geojson-x]')?.value !== '' && row.querySelector('[data-geojson-y]')?.value !== '';
                    });

                    if (completedRows.length >= 3 && !buildFromRows()) {
                        event.preventDefault();
                    }
                });

                loadExistingCoordinates();
            });
        });
    </script>
@endonce
