<x-geodetic-shell title="Parcel Geometry Mapping" active="parcels">
    <style>
        .geo-map-editor-page { display: grid; gap: 18px; }
        .geo-map-editor-hero,
        .geo-map-editor-panel {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
        }
        .geo-map-editor-hero {
            padding: 22px 24px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
        }
        .geo-map-editor-kicker { margin: 0; color: var(--geo-green-800); font-size: 10px; font-weight: 900; letter-spacing: .15em; text-transform: uppercase; }
        .geo-map-editor-title { margin: 7px 0 0; color: var(--geo-ink); font-size: 28px; line-height: 1.1; font-weight: 900; overflow-wrap: anywhere; }
        .geo-map-editor-copy { margin: 8px 0 0; color: var(--geo-muted); font-size: 12px; line-height: 1.55; overflow-wrap: anywhere; }
        .geo-map-editor-status {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 11px;
            border-radius: 999px;
            border: 1px solid {{ $parcel->geometry_geojson ? '#bbf7d0' : '#fed7aa' }};
            background: {{ $parcel->geometry_geojson ? '#dcfce7' : '#fff7ed' }};
            color: {{ $parcel->geometry_geojson ? '#166534' : '#c2410c' }};
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }
        .geo-map-editor-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(320px, .65fr); gap: 18px; align-items: start; }
        .geo-map-editor-grid > * { min-width: 0; }
        .geo-map-editor-panel-header { padding: 17px 20px 14px; border-bottom: 1px solid #e8eeea; }
        .geo-map-editor-panel-title { margin: 0; color: var(--geo-ink); font-size: 16px; font-weight: 900; }
        .geo-map-editor-panel-copy { margin: 4px 0 0; color: var(--geo-muted); font-size: 11px; line-height: 1.5; }
        .geo-map-editor-panel-body { padding: 18px 20px 20px; min-width: 0; }

        .geo-map-editor-alert,
        .geo-map-editor-presence {
            margin-bottom: 14px;
            padding: 12px 13px;
            border-radius: 10px;
            font-size: 11px;
            line-height: 1.5;
        }
        .geo-map-editor-alert { border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; }
        .geo-map-editor-presence { display: grid; gap: 3px; border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; }
        .geo-map-editor-presence.is-warning { border-color: #fde68a; background: #fffbeb; color: #92400e; }
        .geo-map-editor-presence.is-expired { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
        .geo-map-editor-presence strong { font-weight: 900; }

        .geo-map-editor-note {
            margin-top: 12px;
            padding: 12px 13px;
            border: 1px solid #dbe7df;
            border-radius: 9px;
            background: #f7fbf8;
            color: #3f5d4a;
            font-size: 11px;
            line-height: 1.5;
        }
        .geo-map-editor-actions { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 9px; }
        .geo-map-editor-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 14px;
            border-radius: 9px;
            border: 1px solid var(--geo-green-800);
            background: var(--geo-green-800);
            color: #ffffff;
            text-decoration: none;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
            touch-action: manipulation;
        }
        .geo-map-editor-button:disabled { opacity: .5; cursor: not-allowed; }
        .geo-map-editor-button.secondary { background: #ffffff; color: var(--geo-green-900); border-color: #cfd8d2; }
        .geo-map-editor-info { display: grid; }
        .geo-map-editor-info-row { padding: 11px 0; border-bottom: 1px solid #edf1ee; min-width: 0; }
        .geo-map-editor-info-row:last-child { border-bottom: 0; }
        .geo-map-editor-info-label { color: #667085; font-size: 9px; font-weight: 900; letter-spacing: .09em; text-transform: uppercase; }
        .geo-map-editor-info-value { margin-top: 4px; color: #1f2937; font-size: 12px; font-weight: 800; line-height: 1.45; overflow-wrap: anywhere; }
        .geo-map-editor-scope {
            margin-top: 14px;
            padding: 13px;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 11px;
            line-height: 1.5;
        }
        .geo-map-editor-history { margin-top: 16px; padding-top: 14px; border-top: 1px solid #edf1ee; }
        .geo-map-editor-history-title { margin: 0 0 8px; color: #334155; font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .geo-map-editor-history-list { display: grid; gap: 7px; }
        .geo-map-editor-history-item { padding: 9px 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
        .geo-map-editor-history-item strong { display: block; color: #1f2937; font-size: 11px; }
        .geo-map-editor-history-item span { display: block; margin-top: 2px; color: #64748b; font-size: 10px; line-height: 1.4; }
        .geo-map-editor-history-empty { color: #64748b; font-size: 11px; }

        /* Keep the shared Staff/Geodetic coordinate editor compact on this focused mapping page. */
        .geo-map-editor-panel .geojson-helper { border: 0; padding: 0; background: transparent; }

        @media (pointer: coarse) {
            .geo-map-editor-button { min-height: 48px; }
        }

        @media (max-width: 1100px) {
            .geo-map-editor-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .geo-map-editor-hero { flex-direction: column; padding: 18px 16px; }
            .geo-map-editor-title { font-size: clamp(22px, 8vw, 28px); }
            .geo-map-editor-status { white-space: normal; }
            .geo-map-editor-panel-header,
            .geo-map-editor-panel-body { padding-left: 16px; padding-right: 16px; }
            .geo-map-editor-actions { display: grid; grid-template-columns: 1fr; }
            .geo-map-editor-button { width: 100%; }
        }
    </style>

    <section class="geo-map-editor-page">
        <article class="geo-map-editor-hero">
            <div>
                <p class="geo-map-editor-kicker">Geodetic Mapping Task</p>
                <h2 class="geo-map-editor-title">{{ $parcel->parcel_code }}</h2>
                <p class="geo-map-editor-copy">{{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }}, {{ $parcel->province ?? 'Negros Oriental' }}</p>
            </div>
            <span class="geo-map-editor-status">{{ $parcel->geometry_geojson ? 'Geometry encoded' : 'Awaiting geometry' }}</span>
        </article>

        <section class="geo-map-editor-grid">
            <article class="geo-map-editor-panel">
                <header class="geo-map-editor-panel-header">
                    <h2 class="geo-map-editor-panel-title">Parcel Boundary Coordinates</h2>
                    <p class="geo-map-editor-panel-copy">Enter PRS92 / PTM Zone IV Easting and Northing points. DAR-LTCMS converts them to WGS84 for the online map while retaining the original survey coordinates.</p>
                </header>
                <div class="geo-map-editor-panel-body">
                    @if (session('error'))
                        <div class="geo-map-editor-alert" role="alert">{{ session('error') }}</div>
                    @endif

                    <div
                        class="geo-map-editor-presence {{ $activeEditors->isEmpty() ? '' : 'is-warning' }}"
                        data-geometry-presence
                        data-heartbeat-url="{{ route('geodetic.parcels.geometry.session.heartbeat', $parcel) }}"
                        data-release-url="{{ route('geodetic.parcels.geometry.session.release', $parcel) }}"
                        data-session-token="{{ $editSession->session_token }}"
                        data-csrf-token="{{ csrf_token() }}"
                    >
                        <strong data-presence-title>
                            {{ $activeEditors->isEmpty() ? 'No other active Geodetic editors' : 'Another Geodetic user is editing this parcel' }}
                        </strong>
                        <span data-presence-copy>
                            @if ($activeEditors->isEmpty())
                                Your save is still protected by a geometry version check in case another user edits the parcel after you open it.
                            @else
                                {{ $activeEditors->pluck('name')->join(', ') }} {{ $activeEditors->count() === 1 ? 'is' : 'are' }} currently active. You may continue reviewing, but DAR-LTCMS will block your save if a newer geometry is saved first.
                            @endif
                        </span>
                    </div>

                    <form method="POST" action="{{ route('geodetic.parcels.geometry.update', $parcel) }}" data-geometry-edit-form>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="geometry_version" value="{{ (int) $parcel->geometry_version }}">
                        <input type="hidden" name="edit_session_token" value="{{ $editSession->session_token }}">

                        @include('staff.partials.geojson-polygon-editor', [
                            'fieldName' => 'geometry_geojson',
                            'fieldId' => 'geometry_geojson',
                            'value' => $parcel->geometry_geojson ? json_encode($parcel->geometry_geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
                            'errorClass' => 'geo-map-editor-error',
                            'rows' => 5,
                            'requireGeometry' => true,
                        ])

                        <div class="geo-map-editor-note">
                            Use at least <strong>3 coordinate points</strong>. The first point is automatically repeated to close the polygon. Undo/Redo works with the buttons or <strong>Ctrl/Cmd+Z</strong>, <strong>Ctrl+Y</strong>, and <strong>Ctrl/Cmd+Shift+Z</strong>. Only the parcel's map geometry is editable here; ownership, landholding, application, title, registry, and clearance records are not changed.
                        </div>

                        <div class="geo-map-editor-actions">
                            <button type="submit" class="geo-map-editor-button" data-save-geometry>
                                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                                Save Geometry
                            </button>
                            <a href="{{ route('geodetic.parcels.show', $parcel) }}" class="geo-map-editor-button secondary">Back to Parcel</a>
                            <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-map-editor-button secondary">Open Parcel Map</a>
                        </div>
                    </form>
                </div>
            </article>

            <aside class="geo-map-editor-panel">
                <header class="geo-map-editor-panel-header">
                    <h2 class="geo-map-editor-panel-title">Read-Only Parcel Reference</h2>
                    <p class="geo-map-editor-panel-copy">Use these values to confirm you are mapping the correct parcel.</p>
                </header>
                <div class="geo-map-editor-panel-body">
                    <div class="geo-map-editor-info">
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Geometry Version</div><div class="geo-map-editor-info-value">{{ (int) $parcel->geometry_version }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Title Number</div><div class="geo-map-editor-info-value">{{ $parcel->title_no ?? 'N/A' }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Tax Declaration</div><div class="geo-map-editor-info-value">{{ $parcel->tax_decl_no ?? 'N/A' }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Lot Number</div><div class="geo-map-editor-info-value">{{ $parcel->lot_number ?? 'N/A' }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Survey Plan</div><div class="geo-map-editor-info-value">{{ $parcel->survey_plan_number ?? 'N/A' }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Area</div><div class="geo-map-editor-info-value">{{ $parcel->area_hectares ? number_format((float) $parcel->area_hectares, 4).' ha' : 'N/A' }}</div></div>
                        <div class="geo-map-editor-info-row"><div class="geo-map-editor-info-label">Parcel Status</div><div class="geo-map-editor-info-value">{{ $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'N/A' }}</div></div>
                    </div>

                    <div class="geo-map-editor-scope">
                        <strong>Geodetic permission scope:</strong> map geometry only. All administrative, ownership, application, and clearance information remains protected from Geodetic edits. Saving geometry does not establish or transfer legal ownership.
                    </div>

                    <div class="geo-map-editor-history">
                        <h3 class="geo-map-editor-history-title">Recent Geometry Revisions</h3>
                        @if ($recentRevisions->isEmpty())
                            <div class="geo-map-editor-history-empty">No tracked geometry revisions yet.</div>
                        @else
                            <div class="geo-map-editor-history-list">
                                @foreach ($recentRevisions as $revision)
                                    <div class="geo-map-editor-history-item">
                                        <strong>Version {{ $revision->geometry_version }}</strong>
                                        <span>
                                            {{ $revision->actor?->name ?? ($revision->source === 'baseline_snapshot' ? 'Baseline snapshot' : 'System') }}
                                            · {{ $revision->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </aside>
        </section>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const presence = document.querySelector('[data-geometry-presence]');
            const form = document.querySelector('[data-geometry-edit-form]');
            const saveButton = document.querySelector('[data-save-geometry]');

            if (!presence) return;

            const heartbeatUrl = presence.dataset.heartbeatUrl;
            const releaseUrl = presence.dataset.releaseUrl;
            const token = presence.dataset.sessionToken;
            const csrf = presence.dataset.csrfToken;
            const title = presence.querySelector('[data-presence-title]');
            const copy = presence.querySelector('[data-presence-copy]');
            let sessionExpired = false;

            const renderEditors = function (editors) {
                presence.classList.remove('is-expired');
                presence.classList.toggle('is-warning', editors.length > 0);

                if (editors.length === 0) {
                    title.textContent = 'No other active Geodetic editors';
                    copy.textContent = 'Your save is still protected by a geometry version check in case another user edits the parcel after you open it.';
                    return;
                }

                const names = editors.map(function (editor) { return editor.name; }).join(', ');
                title.textContent = editors.length === 1
                    ? 'Another Geodetic user is editing this parcel'
                    : 'Other Geodetic users are editing this parcel';
                copy.textContent = names + (editors.length === 1 ? ' is' : ' are') + ' currently active. You may continue reviewing, but DAR-LTCMS will block your save if a newer geometry is saved first.';
            };

            const expireSession = function (message) {
                sessionExpired = true;
                presence.classList.remove('is-warning');
                presence.classList.add('is-expired');
                title.textContent = 'Editing session expired';
                copy.textContent = message || 'Reload this parcel before saving so the latest geometry can be checked.';
                if (saveButton) saveButton.disabled = true;
            };

            const heartbeat = async function () {
                if (sessionExpired || !heartbeatUrl) return;

                try {
                    const response = await fetch(heartbeatUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({ edit_session_token: token })
                    });

                    if (response.status === 409) {
                        const payload = await response.json().catch(function () { return {}; });
                        expireSession(payload.message);
                        return;
                    }

                    if (!response.ok) return;
                    const payload = await response.json();
                    renderEditors(Array.isArray(payload.editors) ? payload.editors : []);
                } catch (error) {
                    // A temporary network problem should not destroy local work.
                    // The server-side version check still prevents stale overwrites.
                }
            };

            const interval = window.setInterval(heartbeat, 25000);
            heartbeat();

            window.addEventListener('pagehide', function () {
                window.clearInterval(interval);
                if (!releaseUrl || !token) return;

                fetch(releaseUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ edit_session_token: token })
                }).catch(function () {});
            });

            form?.addEventListener('submit', function (event) {
                if (!sessionExpired) return;
                event.preventDefault();
                expireSession('Reload this parcel before saving so the latest geometry can be checked.');
            });
        });
    </script>
</x-geodetic-shell>
