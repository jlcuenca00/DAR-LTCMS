@php
    $mappedParcelCount = count($parcelGeoJson['features'] ?? []);
@endphp

<x-landowner-shell title="My Parcel Map" active="parcel-map">
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <style>
            .lo-map-layout{display:grid;grid-template-columns:310px minmax(0,1fr);gap:18px;align-items:stretch}.lo-map-sidebar{display:grid;gap:14px;align-content:start;min-width:0}.lo-map-card,.lo-map-panel{background:#fff;border:1px solid var(--lo-line);border-radius:14px;box-shadow:0 1px 3px rgba(15,23,42,.07)}.lo-map-card{padding:17px}.lo-map-panel{min-width:0;padding:11px}.lo-map-title{margin:0;color:var(--lo-ink);font-size:16px;font-weight:900}.lo-map-subtitle{margin:5px 0 0;color:var(--lo-muted);font-size:12px;line-height:1.45}.lo-map-count{margin-top:12px;display:inline-flex;align-items:center;gap:7px;min-height:28px;padding:0 9px;border:1px solid #bbf7d0;border-radius:999px;background:var(--lo-green-50);color:var(--lo-green-900);font-size:10px;font-weight:900}
            .lo-search-wrap{position:relative;margin-top:13px}.lo-search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#667085;font-size:12px;pointer-events:none}.lo-search-input{width:100%;min-height:40px;border:1px solid #cbd5d1;border-radius:9px;background:#fff;padding:8px 10px 8px 34px;color:#111827;font-size:12px}.lo-search-results{margin-top:9px;display:grid;gap:6px;max-height:310px;overflow-y:auto}.lo-search-result{width:100%;border:1px solid #e2e8f0;border-radius:9px;background:#f8faf9;padding:9px 10px;text-align:left;cursor:pointer}.lo-search-result:hover,.lo-search-result:focus{outline:none;border-color:#86efac;background:var(--lo-green-50)}.lo-search-code{display:block;color:var(--lo-green-900);font-size:11px;font-weight:900}.lo-search-meta{display:block;margin-top:3px;color:#667085;font-size:10px;line-height:1.35}.lo-search-empty{border:1px dashed #cbd5d1;border-radius:9px;padding:11px;color:#667085;font-size:11px;text-align:center}
            .lo-map-tools{margin-top:13px;display:grid;gap:8px}.lo-map-button{width:100%;min-height:39px;border:1px solid #d7ded9;border-radius:9px;background:#fff;color:#344054;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:8px 11px;font-size:11px;font-weight:900;text-decoration:none;cursor:pointer}.lo-map-button.primary{border-color:var(--lo-green-800);background:var(--lo-green-800);color:#fff}.lo-legend-list{margin-top:12px;display:grid;gap:9px}.lo-legend-row{display:flex;align-items:center;gap:9px;color:#475569;font-size:11px;font-weight:750}.lo-legend-dot{width:10px;height:10px;border-radius:999px;flex:0 0 auto}
            #parcel-map{width:100%;height:calc(100vh - 180px);min-height:620px;border:1px solid #d7ded9;border-radius:11px;overflow:hidden;background:#eef2f0}.parcel-tooltip{background:rgba(255,255,255,.98);color:#111827;border:1px solid #bbf7d0;border-radius:10px;padding:0;box-shadow:0 15px 30px rgba(15,23,42,.18)}.parcel-tooltip-card{min-width:230px;padding:12px}.parcel-tooltip-title{color:var(--lo-green-900);font-size:12px;font-weight:900;margin-bottom:6px}.parcel-tooltip-row{margin-top:4px;color:#344054;font-size:10px;line-height:1.4}.parcel-tooltip-label{color:#667085;font-weight:900}@media(max-width:1100px){.lo-map-layout{grid-template-columns:1fr}#parcel-map{height:580px;min-height:480px}}
        </style>
    @endpush

    <section class="lo-map-layout">
        <aside class="lo-map-sidebar">
            <article class="lo-map-card">
                <h2 class="lo-map-title">Find My Parcel</h2>
                <p class="lo-map-subtitle">Search the parcel code, title reference, tax declaration, or location.</p>
                <span class="lo-map-count"><i class="fa-solid fa-map-location-dot"></i>{{ $mappedParcelCount }} mapped</span>
                <div class="lo-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input id="parcel-search" type="search" class="lo-search-input" placeholder="Search linked parcels" autocomplete="off"></div>
                <div id="parcel-search-results" class="lo-search-results" aria-live="polite"></div>
            </article>

            <article class="lo-map-card">
                <h2 class="lo-map-title">Map Tools</h2>
                <p class="lo-map-subtitle">Return to the full linked-parcel view or open the records list.</p>
                <div class="lo-map-tools"><button type="button" id="reset-map-view" class="lo-map-button primary"><i class="fa-solid fa-expand"></i>Reset View</button><a href="{{ route('landowner.parcels.index') }}" class="lo-map-button"><i class="fa-solid fa-list"></i>Parcel List</a></div>
            </article>

            <article class="lo-map-card">
                <h2 class="lo-map-title">Legend</h2>
                <div class="lo-legend-list"><div class="lo-legend-row"><span class="lo-legend-dot" style="background:#15803d"></span>Your mapped parcel record</div></div>
            </article>
        </aside>
        <section class="lo-map-panel"><div id="parcel-map"></div></section>
    </section>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9coqIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded',function(){
                const container=document.getElementById('parcel-map');if(!container)return;if(typeof window.L==='undefined'){container.innerHTML='<div style="height:100%;display:grid;place-items:center;padding:24px;text-align:center">Map resources could not be loaded. Check the internet connection and refresh.</div>';return}
                const center=[9.3068,123.3054],data=@json($parcelGeoJson),layers={},search=document.getElementById('parcel-search'),results=document.getElementById('parcel-search-results');const map=L.map('parcel-map',{zoomControl:false,scrollWheelZoom:true}).setView(center,12);L.control.zoom({position:'topright'}).addTo(map);L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',{subdomains:'abcd',maxZoom:20,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'}).addTo(map);
                const esc=v=>String(v??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'),color='#15803d';
                const tip=p=>`<div class="parcel-tooltip-card"><div class="parcel-tooltip-title">${esc(p.parcel_code)}</div><div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Location:</span> ${esc(p.barangay)}, ${esc(p.municipality)}</div><div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Area:</span> ${esc(p.area_hectares)} hectares</div><div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Title:</span> ${esc(p.title_no)}</div><div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Tax declaration:</span> ${esc(p.tax_decl_no)}</div><div class="parcel-tooltip-row"><span class="parcel-tooltip-label">Select:</span> open parcel details</div></div>`;
                let parcelLayer=null;const onEach=(f,l)=>{layers[String(f.properties.id)]=l;l.bindTooltip(tip(f.properties),{sticky:true,direction:'top',opacity:1,className:'parcel-tooltip'});l.on({mouseover:e=>{e.target.setStyle({color,weight:5,fillColor:color,fillOpacity:.62});e.target.bringToFront();e.target.openTooltip()},mouseout:e=>{if(parcelLayer)parcelLayer.resetStyle(e.target);e.target.closeTooltip()},click:()=>{if(f.properties.details_url)window.location.href=f.properties.details_url}})};
                if(data.features&&data.features.length){parcelLayer=L.geoJSON(data,{style:{color,weight:2,opacity:.95,fillColor:color,fillOpacity:.34},pointToLayer:(f,ll)=>L.circleMarker(ll,{radius:7,color,weight:2,fillColor:color,fillOpacity:.56}),onEachFeature:onEach}).addTo(map);setTimeout(()=>{map.invalidateSize();map.fitBounds(parcelLayer.getBounds(),{padding:[40,40]})},120)}else L.popup().setLatLng(center).setContent('<strong>No mapped parcels are linked to your account.</strong>').openOn(map);
                const text=f=>{const p=f.properties||{};return[p.parcel_code,p.title_no,p.tax_decl_no,p.barangay,p.municipality].join(' ').toLowerCase()};const focus=f=>{const l=layers[String(f.properties.id)];if(!l)return;if(typeof l.getBounds==='function')map.fitBounds(l.getBounds(),{padding:[70,70],maxZoom:17});else if(typeof l.getLatLng==='function')map.setView(l.getLatLng(),17);l.openTooltip()};
                const render=(q='')=>{const n=q.trim().toLowerCase(),matches=(data.features||[]).filter(f=>!n||text(f).includes(n)).slice(0,8);results.innerHTML='';if(!matches.length){results.innerHTML='<div class="lo-search-empty">No linked parcel matches the search.</div>';return}matches.forEach(f=>{const p=f.properties,b=document.createElement('button');b.type='button';b.className='lo-search-result';b.innerHTML=`<span class="lo-search-code">${esc(p.parcel_code)}</span><span class="lo-search-meta">${esc(p.barangay)}, ${esc(p.municipality)} · ${esc(p.area_hectares)} ha</span>`;b.addEventListener('click',()=>focus(f));results.appendChild(b)})};
                search?.addEventListener('input',e=>render(e.target.value));document.getElementById('reset-map-view')?.addEventListener('click',()=>parcelLayer?map.fitBounds(parcelLayer.getBounds(),{padding:[40,40]}):map.setView(center,12));render();
            });
        </script>
    @endpush
</x-landowner-shell>
