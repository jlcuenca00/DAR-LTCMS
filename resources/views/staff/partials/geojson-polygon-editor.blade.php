@php
    $geoFieldName = $fieldName ?? 'geometry_geojson';
    $geoFieldId = $fieldId ?? str_replace(['[', ']'], ['_', ''], $geoFieldName);
    $geoValue = old($geoFieldName, $value ?? '');

    if (is_array($geoValue)) {
        $geoValue = json_encode($geoValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    $geoInputClass = $inputClass ?? 'w-full rounded-lg border-gray-300 text-xs font-mono';
    $geoErrorClass = $errorClass ?? 'text-xs font-bold text-red-600';
    $geoRows = $rows ?? 8;
@endphp

<div class="geojson-helper" data-geojson-helper data-target="{{ $geoFieldId }}">
    <div class="geojson-toolbar">
        <div>
            <p class="geojson-title">Parcel boundary helper</p>
            <p class="geojson-copy">Enter longitude and latitude points. The helper creates the valid Polygon format used by the map field.</p>
        </div>
        <div class="geojson-actions">
            <button type="button" class="geojson-button" data-geojson-add-point>
                <i class="fa-solid fa-plus"></i> Add point
            </button>
            <button type="button" class="geojson-button" data-geojson-sample>
                <i class="fa-solid fa-map-location-dot"></i> Sample
            </button>
            <button type="button" class="geojson-button primary" data-geojson-build>
                <i class="fa-solid fa-wand-magic-sparkles"></i> Apply Coordinates
            </button>
        </div>
    </div>

    <div class="geojson-point-grid" data-geojson-points>
        @foreach ([1, 2, 3, 4] as $row)
            <div class="geojson-point-row">
                <span>Point {{ $row }}</span>
                <input type="number" step="0.000001" placeholder="Longitude / X" data-geojson-lng>
                <input type="number" step="0.000001" placeholder="Latitude / Y" data-geojson-lat>
            </div>
        @endforeach
    </div>

    <div class="geojson-textarea-wrap">
        <div class="geojson-textarea-header">
            <span>Map Geometry Output</span>
            <div class="geojson-actions compact">
                <button type="button" class="geojson-button" data-geojson-format>
                    <i class="fa-solid fa-code"></i> Format
                </button>
                <button type="button" class="geojson-button" data-geojson-clear>
                    <i class="fa-solid fa-eraser"></i> Clear
                </button>
            </div>
        </div>
        <textarea id="{{ $geoFieldId }}" name="{{ $geoFieldName }}" rows="{{ $geoRows }}" class="{{ $geoInputClass }}" placeholder='Use Sample or Apply Coordinates to fill this map geometry field.'>{{ $geoValue }}</textarea>
    </div>

    <p class="geojson-message" data-geojson-message></p>
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

        .geojson-copy {
            margin: 3px 0 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.45;
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
            min-height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid #bbf7d0;
            background: #ffffff;
            color: #166534;
            border-radius: 9px;
            padding: 0 10px;
            font-size: 11.5px;
            font-weight: 900;
            cursor: pointer;
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
            min-height: 36px;
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

            .geojson-button {
                width: 100%;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-geojson-helper]').forEach(function (editor) {
                const target = document.getElementById(editor.dataset.target);
                const pointsWrap = editor.querySelector('[data-geojson-points]');
                const message = editor.querySelector('[data-geojson-message]');

                if (! target || ! pointsWrap) return;

                const setMessage = function (text, isError = false) {
                    if (! message) return;
                    message.textContent = text || '';
                    message.classList.toggle('is-error', !!isError);
                };

                const addPointRow = function (lng = '', lat = '') {
                    const index = pointsWrap.querySelectorAll('.geojson-point-row').length + 1;
                    const row = document.createElement('div');
                    row.className = 'geojson-point-row';
                    row.innerHTML = '<span>Point ' + index + '</span>'
                        + '<input type="number" step="0.000001" placeholder="Longitude / X" data-geojson-lng>'
                        + '<input type="number" step="0.000001" placeholder="Latitude / Y" data-geojson-lat>';
                    row.querySelector('[data-geojson-lng]').value = lng;
                    row.querySelector('[data-geojson-lat]').value = lat;
                    pointsWrap.appendChild(row);
                };

                const renumberRows = function () {
                    pointsWrap.querySelectorAll('.geojson-point-row span').forEach(function (label, index) {
                        label.textContent = 'Point ' + (index + 1);
                    });
                };

                const buildFromRows = function () {
                    const coords = [];

                    pointsWrap.querySelectorAll('.geojson-point-row').forEach(function (row) {
                        const lng = row.querySelector('[data-geojson-lng]')?.value;
                        const lat = row.querySelector('[data-geojson-lat]')?.value;

                        if (lng !== '' && lat !== '') {
                            coords.push([Number(lng), Number(lat)]);
                        }
                    });

                    if (coords.length < 3) {
                        setMessage('Add at least 3 coordinate points before building a polygon.', true);
                        return;
                    }

                    const first = coords[0];
                    const last = coords[coords.length - 1];
                    if (first[0] !== last[0] || first[1] !== last[1]) {
                        coords.push(first);
                    }

                    target.value = JSON.stringify({ type: 'Polygon', coordinates: [coords] }, null, 2);
                    setMessage('Map geometry generated. You can save the form now.');
                };

                const loadSample = function () {
                    const sample = [
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
                    setMessage('Sample parcel polygon loaded. Adjust the coordinates if needed.');
                };

                editor.querySelector('[data-geojson-add-point]')?.addEventListener('click', function () {
                    addPointRow();
                    renumberRows();
                });

                editor.querySelector('[data-geojson-build]')?.addEventListener('click', buildFromRows);
                editor.querySelector('[data-geojson-sample]')?.addEventListener('click', loadSample);

                editor.querySelector('[data-geojson-format]')?.addEventListener('click', function () {
                    try {
                        const parsed = JSON.parse(target.value || '{}');
                        if (! parsed.type || ! parsed.coordinates) {
                            setMessage('GeoJSON must include type and coordinates.', true);
                            return;
                        }
                        target.value = JSON.stringify(parsed, null, 2);
                        setMessage('GeoJSON formatted successfully.');
                    } catch (error) {
                        setMessage('This is not valid JSON yet. Use Sample or Apply Coordinates if you do not want to type it manually.', true);
                    }
                });

                editor.querySelector('[data-geojson-clear]')?.addEventListener('click', function () {
                    target.value = '';
                    pointsWrap.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                    setMessage('GeoJSON field cleared.');
                });

                const form = editor.closest('form');
                form?.addEventListener('submit', function () {
                    if (! target.value.trim()) {
                        const completedRows = Array.from(pointsWrap.querySelectorAll('.geojson-point-row')).filter(function (row) {
                            return row.querySelector('[data-geojson-lng]')?.value !== '' && row.querySelector('[data-geojson-lat]')?.value !== '';
                        });

                        if (completedRows.length >= 3) {
                            buildFromRows();
                        }
                    }
                });
            });
        });
    </script>
@endonce
