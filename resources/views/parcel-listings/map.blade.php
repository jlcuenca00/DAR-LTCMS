<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Parcels for Sale | DAR-LTCMS</title>
    @vite(['resources/css/app.css', 'resources/js/public-parcel-listings.js'])
    <style>
        .parcel-list-item { border: 1px solid #e2e8f0; border-radius: .5rem; padding: .75rem; font-size: .875rem; }
        .parcel-call-link { color: #065f46; font-weight: 600; text-decoration: underline; }
        .parcel-locate { margin-top: .5rem; text-decoration: underline; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-4 sm:px-8">
            <img src="{{ asset('images/dar-logo.svg') }}" alt="" class="h-10 w-10">
            <a href="{{ route('home') }}" class="font-semibold text-emerald-900 underline-offset-4 hover:underline">DAR-LTCMS</a>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-8">
        <h1 class="text-3xl font-bold tracking-tight">Parcels for Sale</h1>
        <p class="mt-2 text-slate-700">Zoom in and select a parcel on the map, or choose one from the list.</p>
        <p class="mt-1 text-sm text-slate-600">Listings express the owner's interest in selling. Clearance and legal transfer follow separate procedures.</p>
        <div class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div id="public-parcel-map" class="isolate h-[52vh] min-h-80 rounded-xl border bg-slate-100"
                data-features-url="{{ route('parcel-listings.features', absolute: false) }}" aria-label="Map of listed parcels"></div>
            <section aria-labelledby="list-title" class="rounded-xl border bg-white p-4">
                <h2 id="list-title" class="text-lg font-semibold">Available listings</h2>
                <p id="listing-map-status" class="mt-2 text-sm text-slate-700" role="status">Loading listings…</p>
                <ul id="listing-map-list" class="mt-3 max-h-[42vh] space-y-3 overflow-y-auto"></ul>
                <button id="listing-map-more" type="button" hidden class="mt-3 rounded border px-3 py-2">Load more parcels</button>
            </section>
        </div>
        <noscript><p class="mt-4">Enable JavaScript to use the parcel map.</p></noscript>
    </main>
</body>
</html>
