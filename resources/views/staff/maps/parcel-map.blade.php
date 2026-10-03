@php
    $mappedParcelCount = $mapConfig['total'];
@endphp

<x-staff-shell title="Parcel Map Viewer" active="parcel-map" maxWidth="">
    <x-slot name="styles">
        <style>
            .map-workspace { display: grid; grid-template-columns: 300px minmax(0, 1fr); gap: 18px; align-items: stretch; min-width: 0; }
            .map-workspace > * { min-width: 0; }
            .map-sidebar { display: grid; gap: 14px; align-content: start; min-width: 0; }
            .map-card { min-width: 0; overflow: hidden; border: 1px solid var(--border); border-radius: 14px; background: #fff; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); }
            .panel-pad { padding: 18px 20px; }
            .panel-title { margin: 0; color: #111827; font-size: 16px; font-weight: 900; }
            .panel-copy { margin: 5px 0 0; color: #6b7280; font-size: 12.5px; line-height: 1.55; }
            .parcel-search-input-wrap { position: relative; margin-top: 14px; }
            .parcel-search-input-wrap i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 13px; pointer-events: none; }
            .parcel-search-input { width: 100%; min-height: 44px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; padding: 9px 11px 9px 36px; color: #0f172a; font-size: 13px; }
            .parcel-search-input:focus { outline: none; border-color: #15803d; box-shadow: 0 0 0 3px rgba(21, 128, 61, .12); }
            .parcel-search-results { display: grid; gap: 7px; margin-top: 10px; max-height: min(350px, 42dvh); overflow-y: auto; overscroll-behavior: contain; }
            .parcel-search-result { width: 100%; min-height: 44px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; padding: 10px 11px; text-align: left; cursor: pointer; touch-action: manipulation; }
            .parcel-search-result:hover,
            .parcel-search-result:focus-visible { outline: 3px solid rgba(21, 128, 61, .16); outline-offset: 1px; border-color: #86efac; background: #f0fdf4; }
            .parcel-search-result-code { display: block; color: #065f46; font-size: 12px; font-weight: 900; overflow-wrap: anywhere; }
            .parcel-search-result-meta { display: block; margin-top: 3px; color: #64748b; font-size: 11px; line-height: 1.35; overflow-wrap: anywhere; }
            .parcel-search-empty { border: 1px dashed #cbd5e1; border-radius: 10px; padding: 12px; color: #64748b; font-size: 12px; text-align: center; }
            .legend-list { display: grid; gap: 11px; margin-top: 14px; }
            .legend-item { display: flex; align-items: center; gap: 10px; color: #4b5563; font-size: 12.5px; font-weight: 800; }
            .legend-dot { width: 11px; height: 11px; flex: 0 0 auto; border-radius: 999px; box-shadow: 0 0 0 3px rgba(15, 23, 42, .06); }
            .map-panel { min-width: 0; }
            .map-panel-header { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 18px 20px; border-bottom: 1px solid #e5e7eb; }
            .map-panel-title { margin: 0; color: #111827; font-size: 16px; font-weight: 900; }
            .map-panel-subtitle { margin: 4px 0 0; color: #6b7280; font-size: 12.5px; font-weight: 600; line-height: 1.45; }
            .map-header-actions { display: flex; align-items: center; justify-content: flex-end; gap: 9px; flex-wrap: wrap; }
            .map-count { display: inline-flex; align-items: center; gap: 8px; min-height: 36px; padding: 7px 12px; border: 1px solid #bbf7d0; border-radius: 999px; background: #f0fdf4; color: #14532d; font-size: 12px; font-weight: 900; white-space: nowrap; }
            .map-frame { padding: 12px; min-width: 0; }
            #parcel-map { width: 100%; height: clamp(520px, calc(100dvh - 212px), 820px); min-height: 520px; overflow: hidden; border: 1px solid #d1d5db; border-radius: 12px; background: #eef2f0; }
            .map-fallback { height: 100%; min-height: 360px; display: grid; place-items: center; padding: 24px; text-align: center; color: #475569; }
            .map-fallback strong { display: block; margin-bottom: 6px; color: #0f172a; }
            .leaflet-control-zoom a { background: #fff !important; color: #14532d !important; }
            .leaflet-control-attribution { background: rgba(255, 255, 255, .92) !important; }
            .parcel-tooltip { padding: 0; border: 1px solid #bbf7d0; border-radius: 12px; background: rgba(255, 255, 255, .98); color: #111827; box-shadow: 0 15px 30px rgba(15, 23, 42, .18); }
            .parcel-tooltip-card { min-width: 0; width: min(230px, calc(100vw - 64px)); padding: 13px; }
            .parcel-tooltip-title { margin-bottom: 6px; color: #14532d; font-size: 13px; font-weight: 900; }
            .parcel-tooltip-row { margin-top: 4px; color: #374151; font-size: 11px; line-height: 1.4; overflow-wrap: anywhere; }
            .parcel-tooltip-label { color: #6b7280; font-weight: 800; }
            .parcel-tooltip-row.is-flagged { color: #b91c1c; font-weight: 800; }

            @media (pointer: coarse) {
                .parcel-search-input,
                .parcel-search-result { min-height: 48px; }
            }

            @media (max-width: 1180px) {
                .map-workspace { grid-template-columns: 1fr; }
                .map-sidebar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                #parcel-map { height: min(62dvh, 680px); min-height: 500px; }
            }

            @media (max-width: 900px) {
                .map-sidebar { grid-template-columns: 1fr; }
                .map-panel-header { flex-direction: column; align-items: flex-start; }
                .map-header-actions { width: 100%; justify-content: flex-start; }
                #parcel-map { height: min(58dvh, 560px); min-height: 420px; }
            }

            @media (max-width: 560px) {
                .panel-pad,
                .map-panel-header { padding-left: 16px; padding-right: 16px; }
                .map-frame { padding: 8px; }
                .map-header-actions { display: grid; grid-template-columns: 1fr; }
                .map-header-actions > * { width: 100%; justify-content: center; }
                .map-count { white-space: normal; }
                #parcel-map { height: min(56dvh, 500px); min-height: 340px; }
            }
        </style>
    </x-slot>

    <section class="map-workspace">
        <aside class="map-sidebar">
            <div class="map-card">
                <div class="panel-pad">
                    <h3 class="panel-title">Find a Parcel</h3>
                    <p class="panel-copy">Search mapped parcels by parcel code, title number, landowner, or location.</p>
                    <div class="parcel-search-input-wrap">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input id="parcel-map-search" type="search" aria-label="Search mapped parcel records" maxlength="100" class="parcel-search-input" placeholder="Search mapped parcels" autocomplete="off">
                    </div>
                    <div id="parcel-search-results" class="parcel-search-results" aria-live="polite"></div>
                    <p id="parcel-search-status" role="status" aria-live="polite"></p>
                    <div id="parcel-search-pages"></div>
                </div>
            </div>

            <div class="map-card">
                <div class="panel-pad">
                    <h3 class="panel-title">Map Legend</h3>
                    <p class="panel-copy">Review flags identify records that require additional administrative or technical verification.</p>
                    <div class="legend-list">
                        <div class="legend-item"><span class="legend-dot" style="background:#22c55e"></span>Mapped parcel record</div>
                        <div class="legend-item"><span class="legend-dot" style="background:#dc2626"></span>Flagged for review</div>
                    </div>
                </div>
            </div>
        </aside>

        <section class="map-card map-panel">
            <div class="map-panel-header">
                <div>
                    <h3 class="map-panel-title">Mapped Parcel Records</h3>
                    <p class="map-panel-subtitle">Select a search result to focus the map, or click a parcel boundary to open its record.</p>
                </div>
                <div class="map-header-actions">
                    <div class="map-count"><i class="fa-solid fa-draw-polygon" aria-hidden="true"></i>{{ number_format($mappedParcelCount) }} mapped parcel{{ $mappedParcelCount === 1 ? '' : 's' }}</div>
                    <button type="button" id="reset-map-view" class="staff-button staff-button-light"><i class="fa-solid fa-expand" aria-hidden="true"></i>Reset View</button>
                </div>
            </div>
            <p id="parcel-map-status" role="status" aria-live="polite"></p><div class="map-frame"><div id="parcel-map" data-parcel-map-viewer aria-label="Parcel map"><div class="map-fallback">Loading parcel map…</div></div></div>
        </section>
    </section>

    <x-slot name="scripts">
        <script type="application/json" data-parcel-map-config>@json($mapConfig)</script>
    </x-slot>
</x-staff-shell>
