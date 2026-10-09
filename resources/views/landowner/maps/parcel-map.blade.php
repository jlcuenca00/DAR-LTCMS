@php
    $mappedParcelCount = $mapConfig['total'];
@endphp

<x-landowner-shell title="My Parcel Map" active="parcel-map">
    @push('styles')
        <style>
            .lo-map-layout { display: grid; grid-template-columns: 310px minmax(0, 1fr); gap: 18px; align-items: stretch; min-width: 0; }
            .lo-map-layout > * { min-width: 0; }
            .lo-map-sidebar { display: grid; gap: 14px; align-content: start; min-width: 0; }
            .lo-map-card,
            .lo-map-panel { background: #fff; border: 1px solid var(--lo-line); border-radius: 14px; box-shadow: 0 1px 3px rgba(15, 23, 42, .07); }
            .lo-map-card { padding: 17px; }
            .lo-map-panel { min-width: 0; padding: 11px; overflow: hidden; }
            .lo-map-title { margin: 0; color: var(--lo-ink); font-size: 16px; font-weight: 900; }
            .lo-map-subtitle { margin: 5px 0 0; color: var(--lo-muted); font-size: 12px; line-height: 1.45; }
            .lo-map-count { margin-top: 12px; display: inline-flex; align-items: center; gap: 7px; min-height: 30px; padding: 4px 9px; border: 1px solid #bbf7d0; border-radius: 999px; background: var(--lo-green-50); color: var(--lo-green-900); font-size: 10px; font-weight: 900; }
            .lo-search-wrap { position: relative; margin-top: 13px; }
            .lo-search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #667085; font-size: 12px; pointer-events: none; }
            .lo-search-input { width: 100%; min-height: 44px; border: 1px solid #cbd5d1; border-radius: 9px; background: #fff; padding: 8px 10px 8px 34px; color: #111827; font-size: 13px; }
            .lo-search-results { margin-top: 9px; display: grid; gap: 6px; max-height: min(310px, 38dvh); overflow-y: auto; overscroll-behavior: contain; }
            .lo-search-result { width: 100%; min-height: 44px; border: 1px solid #e2e8f0; border-radius: 9px; background: #f8faf9; padding: 9px 10px; text-align: left; cursor: pointer; touch-action: manipulation; }
            .lo-search-result:hover,
            .lo-search-result:focus-visible { outline: 3px solid rgba(21, 128, 61, .16); outline-offset: 1px; border-color: #86efac; background: var(--lo-green-50); }
            .lo-search-code { display: block; color: var(--lo-green-900); font-size: 11px; font-weight: 900; overflow-wrap: anywhere; }
            .lo-search-meta { display: block; margin-top: 3px; color: #667085; font-size: 10px; line-height: 1.35; overflow-wrap: anywhere; }
            .lo-search-empty { border: 1px dashed #cbd5d1; border-radius: 9px; padding: 11px; color: #667085; font-size: 11px; text-align: center; }
            .lo-map-tools { margin-top: 13px; display: grid; gap: 8px; }
            .lo-map-button { width: 100%; min-height: 44px; border: 1px solid #d7ded9; border-radius: 9px; background: #fff; color: #344054; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 11px; font-size: 11px; font-weight: 900; text-decoration: none; cursor: pointer; touch-action: manipulation; }
            .lo-map-button.primary { border-color: var(--lo-green-800); background: var(--lo-green-800); color: #fff; }
            #parcel-map { width: 100%; height: clamp(520px, calc(100dvh - 180px), 820px); min-height: 520px; border: 1px solid #d7ded9; border-radius: 11px; overflow: hidden; background: #eef2f0; }
            .lo-map-fallback { height: 100%; min-height: 340px; display: grid; place-items: center; padding: 24px; text-align: center; color: #475569; }
            .lo-map-fallback strong { display: block; margin-bottom: 6px; color: #0f172a; }
            .parcel-tooltip { white-space: normal; background: rgba(255, 255, 255, .98); color: #111827; border: 1px solid #bbf7d0; border-radius: 10px; padding: 0; box-shadow: 0 15px 30px rgba(15, 23, 42, .18); }
            .parcel-tooltip-card { box-sizing: border-box; overflow-wrap: anywhere; min-width: 0; width: min(290px, calc(100vw - 64px)); padding: 12px; }
            .parcel-tooltip-title { color: var(--lo-green-900); font-size: 12px; font-weight: 900; margin-bottom: 6px; }
            .parcel-tooltip-row { margin-top: 4px; color: #344054; font-size: 10px; line-height: 1.4; overflow-wrap: anywhere; }
            .parcel-tooltip-label { color: #667085; font-weight: 900; }

            @media (pointer: coarse) {
                .lo-search-input,
                .lo-search-result,
                .lo-map-button { min-height: 48px; }
            }

            @media (max-width: 1100px) {
                .lo-map-layout { grid-template-columns: 1fr; }
                #parcel-map { height: min(60dvh, 620px); min-height: 440px; }
            }

            @media (max-width: 640px) {
                .lo-map-card { padding: 16px; }
                .lo-map-panel { padding: 8px; }
                .lo-search-input { font-size: 16px; }
                #parcel-map { height: min(56dvh, 520px); min-height: 340px; }
            }
        </style>
    @endpush

    <section class="lo-map-layout">
        <aside class="lo-map-sidebar">
            <article class="lo-map-card">
                <h2 class="lo-map-title">Find My Parcel</h2>
                <p class="lo-map-subtitle">Search the parcel code, title reference, tax declaration, or location.</p>
                <span class="lo-map-count"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>{{ $mappedParcelCount }} mapped</span>
                <div class="lo-search-wrap">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="parcel-search" type="search" aria-label="Search mapped parcel records" maxlength="100" class="lo-search-input" placeholder="Search linked parcels" autocomplete="off">
                </div>
                <div id="parcel-search-results" class="lo-search-results" aria-live="polite"></div>
                    <p id="parcel-search-status" role="status" aria-live="polite"></p>
                    <div id="parcel-search-pages"></div>
            </article>

            <article class="lo-map-card">
                <h2 class="lo-map-title">Map Tools</h2>
                <p class="lo-map-subtitle">Return to the full linked-parcel view or open the records list.</p>
                <div class="lo-map-tools">
                    <button type="button" id="reset-map-view" class="lo-map-button primary"><i class="fa-solid fa-expand" aria-hidden="true"></i>Reset View</button>
                    <a href="{{ route('landowner.parcels.index') }}" class="lo-map-button"><i class="fa-solid fa-list" aria-hidden="true"></i>Parcel List</a>
                </div>
            </article>
        </aside>
        <section class="lo-map-panel"><p id="parcel-map-status" role="status" aria-live="polite"></p><div id="parcel-map" data-parcel-map-viewer aria-label="Parcel map"><div class="lo-map-fallback">Loading parcel map…</div></div></section>
    </section>

    @push('scripts')
        <script type="application/json" data-parcel-map-config>@json($mapConfig)</script>
    @endpush
</x-landowner-shell>
