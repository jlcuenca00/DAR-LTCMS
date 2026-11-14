<section class="section" id="parcels-for-sale" aria-labelledby="parcels-for-sale-title">
    <div class="container">
        <div class="section-grid">
            <h2 class="section-title" id="parcels-for-sale-title">Public Parcel Listings</h2>
            <div>
                <p class="section-intro">Browse parcels offered for sale by their owners. Select a parcel on the map or in the list to view its location, approximate area, and contact details.</p>
                <p class="section-intro">Clearance and legal transfer follow separate procedures.</p>
            </div>
        </div>
        <p id="listing-demo-note" hidden class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            This map includes simulated listings with fictional owners, boundaries, and contact details.
        </p>
        <div class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div id="public-parcel-map" class="isolate h-[min(52vh,32rem)] min-h-72 rounded-xl border bg-slate-100"
                data-features-url="{{ route('parcel-listings.features', absolute: false) }}" aria-label="Map of listed parcels"></div>
            <section aria-labelledby="list-title" class="rounded-xl border bg-white p-4">
                <h3 id="list-title" class="text-lg font-semibold">Listed parcels</h3>
                <p id="listing-map-status" class="mt-2 text-sm text-slate-700" role="status">Loading listings…</p>
                <ul id="listing-map-list" class="mt-3 max-h-[42vh] space-y-3 overflow-y-auto"></ul>
                <button id="listing-map-more" type="button" hidden class="mt-3 rounded border px-3 py-2">Load more parcels</button>
            </section>
        </div>
        <noscript><p class="mt-4">Enable JavaScript to use the parcel map.</p></noscript>
    </div>
</section>
