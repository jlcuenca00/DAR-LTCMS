@php
    $mappedParcelCount = count($parcelGeoJson['features'] ?? []);
@endphp

<x-staff-shell title="Parcel Map Viewer" active="parcel-map" maxWidth="">
    <x-slot name="head">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    </x-slot>

    <x-slot name="styles">
        <style>
            .map-workspace{display:grid;grid-template-columns:300px minmax(0,1fr);gap:18px;align-items:stretch}
            .map-sidebar{display:grid;gap:14px;align-content:start;min-width:0}
            .map-card{min-width:0;overflow:hidden;border:1px solid var(--border);border-radius:14px;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.08)}
            .panel-pad{padding:18px 20px}.panel-title{margin:0;color:#111827;font-size:16px;font-weight:900}.panel-copy{margin:5px 0 0;color:#6b7280;font-size:12.5px;line-height:1.55}
            .parcel-search-input-wrap{position:relative;margin-top:14px}.parcel-search-input-wrap i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#64748b;font-size:13px;pointer-events:none}
            .parcel-search-input{width:100%;min-height:42px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;padding:9px 11px 9px 36px;color:#0f172a;font-size:13px}.parcel-search-input:focus{outline:none;border-color:#15803d;box-shadow:0 0 0 3px rgba(21,128,61,.12)}
            .parcel-search-results{display:grid;gap:7px;margin-top:10px;max-height:350px;overflow-y:auto}.parcel-search-result{width:100%;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;padding:10px 11px;text-align:left;cursor:pointer}.parcel-search-result:hover,.parcel-search-result:focus{outline:none;border-color:#86efac;background:#f0fdf4}.parcel-search-result-code{display:block;color:#065f46;font-size:12px;font-weight:900}.parcel-search-result-meta{display:block;margin-top:3px;color:#64748b;font-size:11px;line-height:1.35}.parcel-search-empty{border:1px dashed #cbd5e1;border-radius:10px;padding:12px;color:#64748b;font-size:12px;text-align:center}
            .legend-list{display:grid;gap:11px;margin-top:14px}.legend-item{display:flex;align-items:center;gap:10px;color:#4b5563;font-size:12.5px;font-weight:800}.legend-dot{width:11px;height:11px;flex:0 0 auto;border-radius:999px;box-shadow:0 0 0 3px rgba(15,23,42,.06)}
            .map-panel{min-width:0}.map-panel-header{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 20px;border-bottom:1px solid #e5e7eb}.map-panel-title{margin:0;color:#111827;font-size:16px;font-weight:900}.map-panel-subtitle{margin:4px 0 0;color:#6b7280;font-size:12.5px;font-weight:600}.map-header-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap}.map-count{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid #bbf7d0;border-radius:999px;background:#f0fdf4;color:#14532d;font-size:12px;font-weight:900;white-space:nowrap}
            .map-frame{padding:12px}#parcel-map{width:100%;height:calc(100vh - 212px);min-height:590px;overflow:hidden;border:1px solid #d1d5db;border-radius:12px;background:#eef2f0}.leaflet-control-zoom a{background:#fff!important;color:#14532d!important}.leaflet-control-attribution{background:rgba(255,255,255,.92)!important}.parcel-tooltip{padding:0;border:1px solid #bbf7d0;border-radius:12px;background:rgba(255,255,255,.98);color:#111827;box-shadow:0 15px 30px rgba(15,23,42,.18)}.parcel-tooltip-card{min-width:230px;padding:13px}.parcel-tooltip-title{margin-bottom:6px;color:#14532d;font-size:13px;font-weight:900}.parcel-tooltip-row{margin-top:4px;color:#374151;font-size:11px;line-height:1.4}.parcel-tooltip-label{color:#6b7280;font-weight:800}.parcel-tooltip-row.is-flagged{color:#b91c1c;font-weight:800}
            @media(max-width:1180px){.map-workspace{grid-template-columns:1fr}.map-sidebar{grid-template-columns:repeat(2,minmax(0,1fr))}#parcel-map{height:620px;min-height:520px}}@media(max-width:900px){.map-sidebar{grid-template-columns:1fr}.map-panel-header{flex-direction:column;align-items:flex-start}#parcel-map{height:520px;min-height:460px}}@media(max-width:560px){#parcel-map{height:440px;min-height:400px}}
        </style>
    </x-slot>

    <section class="map-workspace">
        <aside class="map-sidebar">
            <div class="map-card"><div class="panel-pad">
                <h3 class="panel-title">Find a Parcel</h3>
                <p class="panel-copy">Search mapped parcels by parcel code, title number, landowner, or location.</p>
                <div class="parcel-search-input-wrap"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="parcel-map-search" type="search" class="parcel-search-input" placeholder="Search mapped parcels" autocomplete="off"></div>
                <div id="parcel-search-results" class="parcel-search-results" aria-live="polite"></div>
            </div></div>

            <div class="map-card"><div class="panel-pad">
                <h3 class="panel-title">Map Legend</h3>
                <p class="panel-copy">Review flags identify records that require additional administrative or technical verification.</p>
                <div class="legend-list">
                    <div class="legend-item"><span class="legend-dot" style="background:#22c55e"></span>Mapped parcel record</div>
                    <div class="legend-item"><span class="legend-dot" style="background:#dc2626"></span>Flagged for review</div>
                </div>
            </div></div>
        </aside>

        <section class="map-card map-panel">
            <div class="map-panel-header">
                <div><h3 class="map-panel-title">Mapped Parcel Records</h3><p class="map-panel-subtitle">Select a search result to focus the map, or click a parcel boundary to open its record.</p></div>
                <div class="map-header-actions"><div class="map-count"><i class="fa-solid fa-draw-polygon"></i>{{ number_format($mappedParcelCount) }} mapped parcel{{ $mappedParcelCount === 1 ? '' : 's' }}</div><button type="button" id="reset-map-view" class="staff-button staff-button-light"><i class="fa-solid fa-expand"></i>Reset View</button></div>
            </div>
            <div class="map-frame"><div id="parcel-map"></div></div>
        </section>
    </section>

    <x-slot name="scripts">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9coqIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded',function(){
                const container=document.getElementById('parcel-map');if(!container)return;
                if(typeof window.L==='undefined'){container.innerHTML='<div style="height:100%;display:grid;place-items:center;padding:24px;text-align:center">Map resources could not be loaded. Check the internet connection and refresh.</div>';return}
                const center=[9.3068,123.3054],data=@json($parcelGeoJson),search=document.getElementById('parcel-map-search'),results=document.getElementById('parcel-search-results'),layers=new Map();
                const map=L.map('parcel-map',{zoomControl:false,scrollWheelZoom:true,minZoom:7,maxZoom:20}).setView(center,12);L.control.zoom({position:'topright'}).addTo(map);L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',{subdomains:'abcd',maxZoom:20,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'}).addTo(map);
                const esc=v=>String(v??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
                const color=s=>s==='flagged'?'#dc2626':'#22c55e';
                const style=f=>({color:color(f.properties.status),weight:2.5,opacity:.98,fillColor:color(f.properties.status),fillOpacity:.38});
                const hover=f=>({color:color(f.properties.status),weight:5,opacity:1,fillColor:color(f.properties.status),fillOpacity:.68});
                const tip=p=>{const flag=p.is_flagged?`<div class="parcel-tooltip-row is-flagged"><span class="parcel-tooltip-label">Review flag:</span> ${esc(p.flag_reason||'Requires verification')}</div>`:'';return `<div class="parcel-tooltip-card"><div class="parcel-tooltip-title">${esc(p.parcel_code)}</div><div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Landowner:</span> ${esc(p.landowner)}</div><div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Location:</span> ${esc(p.barangay)}, ${esc(p.municipality)}</div><div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Area:</span> ${esc(p.area_hectares)} hectares</div><div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Title No.:</span> ${esc(p.title_no)}</div><div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Tax Declaration:</span> ${esc(p.tax_decl_no)}</div>${flag}<div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Click:</span> open parcel record</div></div>`};
                let parcelLayer=null;const onEach=(f,l)=>{layers.set(String(f.properties.id),l);l.bindTooltip(tip(f.properties),{sticky:true,direction:'top',opacity:1,className:'parcel-tooltip'});l.on({mouseover:e=>{e.target.setStyle(hover(f));e.target.bringToFront();e.target.openTooltip()},mouseout:e=>{if(parcelLayer)parcelLayer.resetStyle(e.target);e.target.closeTooltip()},click:()=>{if(f.properties.details_url)window.location.href=f.properties.details_url}})};
                if(data.features&&data.features.length){parcelLayer=L.geoJSON(data,{style,pointToLayer:(f,ll)=>L.circleMarker(ll,{radius:7,color:color(f.properties.status),weight:2.5,fillColor:color(f.properties.status),fillOpacity:.62}),onEachFeature:onEach}).addTo(map);setTimeout(()=>{map.invalidateSize();map.fitBounds(parcelLayer.getBounds(),{padding:[40,40]})},120)}else{L.popup().setLatLng(center).setContent('<strong>No mapped parcels yet.</strong>').openOn(map)}
                const searchText=f=>{const p=f.properties||{};return[p.parcel_code,p.title_no,p.tax_decl_no,p.landowner,p.municipality,p.barangay].join(' ').toLowerCase()};
                const focus=f=>{const l=layers.get(String(f.properties.id));if(!l)return;if(typeof l.getBounds==='function')map.fitBounds(l.getBounds(),{padding:[70,70],maxZoom:17});else if(typeof l.getLatLng==='function')map.setView(l.getLatLng(),17);setTimeout(()=>l.openTooltip(),300)};
                const render=(q='')=>{if(!results)return;const n=q.trim().toLowerCase(),matches=(data.features||[]).filter(f=>!n||searchText(f).includes(n)).slice(0,8);results.innerHTML='';if(!matches.length){results.innerHTML='<div class="parcel-search-empty">No mapped parcels match this search.</div>';return}matches.forEach(f=>{const p=f.properties||{},b=document.createElement('button');b.type='button';b.className='parcel-search-result';b.innerHTML=`<span class="parcel-search-result-code">${esc(p.parcel_code||'Parcel record')}</span><span class="parcel-search-result-meta">${esc(p.barangay||'N/A')}, ${esc(p.municipality||'N/A')} · ${esc(p.area_hectares||'N/A')} ha</span>`;b.addEventListener('click',()=>focus(f));results.appendChild(b)})};
                document.getElementById('reset-map-view')?.addEventListener('click',()=>parcelLayer?map.fitBounds(parcelLayer.getBounds(),{padding:[40,40]}):map.setView(center,12));search?.addEventListener('input',e=>render(e.target.value));render();
            });
        </script>
    </x-slot>
</x-staff-shell>
