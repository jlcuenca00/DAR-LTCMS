<x-app-layout>
    @push('styles')
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
            crossorigin=""
        />

        <style>
            .map-shell {
                display: grid;
                grid-template-columns: 340px 1fr;
                gap: 1rem;
            }

            #parcel-map {
                height: 680px;
                width: 100%;
                border-radius: 1rem;
                border: 1px solid #d1d5db;
                overflow: hidden;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            }

            .leaflet-popup-content-wrapper {
                border-radius: 0.9rem;
            }

            .leaflet-popup-content {
                margin: 14px 16px;
                font-family: inherit;
            }
            .leaflet-control-zoom a {
    background: #111827 !important;
    color: #f9fafb !important;
    border-color: #374151 !important;
}

.leaflet-control-zoom a:hover {
    background: #1f2937 !important;
}

.leaflet-control-attribution {
    background: rgba(17, 24, 39, 0.85) !important;
    color: #d1d5db !important;
    border-radius: 0.5rem 0 0 0;
}

.leaflet-control-attribution a {
    color: #86efac !important;
}

.leaflet-popup-content-wrapper,
.leaflet-popup-tip {
    background: #111827;
    color: #f9fafb;
}

.leaflet-popup-content p {
    color: #d1d5db !important;
}

.parcel-tooltip {
    background: rgba(17, 24, 39, 0.96);
    color: #f9fafb;
    border: 1px solid rgba(134, 239, 172, 0.7);
    border-radius: 0.75rem;
    padding: 0;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.35);
}

.parcel-tooltip::before {
    border-top-color: rgba(17, 24, 39, 0.96);
}

.parcel-tooltip-card {
    min-width: 230px;
    padding: 0.85rem;
}

.parcel-tooltip-title {
    font-size: 0.85rem;
    font-weight: 800;
    color: #bbf7d0;
    margin-bottom: 0.35rem;
}

.parcel-tooltip-row {
    font-size: 0.75rem;
    color: #d1d5db;
    margin-top: 0.2rem;
}

.parcel-tooltip-label {
    color: #9ca3af;
}

            .custom-parcel-marker {
                width: 24px;
                height: 24px;
                border-radius: 9999px;
                background: #166534;
                border: 4px solid #dcfce7;
                box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.25);
            }

            .map-legend-dot {
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 9999px;
                margin-right: 8px;
            }

            @media (max-width: 1024px) {
                .map-shell {
                    grid-template-columns: 1fr;
                }

                #parcel-map {
                    height: 540px;
                }
            }
        </style>
    @endpush

    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800">
                My Parcel Map
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Read-only map of parcel records linked to your landowner account.
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">

            <div class="bg-white border border-gray-200 shadow-sm sm:rounded-xl p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-green-700 uppercase">
                            DAR Negros Oriental Provincial Office
                        </p>

                        <h3 class="text-2xl font-bold text-gray-900 mt-1">
                            Parcel Reference Map
                        </h3>

                        <p class="text-sm text-gray-600 mt-2 max-w-3xl">
                            This map supports parcel viewing, reference checking, clearance processing,
                            and monitoring only. It does not automatically transfer land ownership,
                            mutate registry records, or finalize legal land transactions.
                        </p>
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                        <p class="text-xs text-green-700 font-semibold uppercase">
                            Map Mode
                        </p>
                        <p class="text-sm text-green-900 font-bold">
                            Read-only viewer
                        </p>
                    </div>
                </div>
            </div>

            <div class="map-shell">

                <aside class="space-y-4">

                    <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5">
                        <h4 class="font-semibold text-gray-900">
                            Map Tools
                        </h4>

                        <p class="text-sm text-gray-600 mt-1">
                            These tools are for viewing and reference only.
                        </p>

                        <div class="mt-4 space-y-3">
                            <button
                                type="button"
                                id="reset-map-view"
                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-700 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                            >
                                Reset to Negros Oriental
                            </button>

                            <button
                                type="button"
                                id="focus-dar-office"
                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                            >
                                Focus Reference Point
                            </button>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5">
                        <h4 class="font-semibold text-gray-900">
                            Legend
                        </h4>

                        <div class="mt-4 space-y-3 text-sm text-gray-700">
                            <p>
                                <span class="map-legend-dot" style="background:#166534;"></span>
                                Reference parcel/location point
                            </p>

                            <p>
                                <span class="map-legend-dot" style="background:#f59e0b;"></span>
                                Pending / for review parcel
                            </p>

                            <p>
                                <span class="map-legend-dot" style="background:#2563eb;"></span>
                                Linked clearance application
                            </p>

                            <p>
                                <span class="map-legend-dot" style="background:#dc2626;"></span>
                                Flagged reference issue
                            </p>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                        <h4 class="font-semibold text-amber-900">
                            Scope Notice
                        </h4>

                        <p class="text-sm text-amber-800 mt-2">
                            Approval of a clearance application does not mean ownership has already
                            been legally transferred. This system only records, generates, monitors,
                            and audits clearance-related actions.
                        </p>
                    </div>

                    <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5">
                        <h4 class="font-semibold text-gray-900">
                            Current Viewer Access
                        </h4>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Role</dt>
                                <dd class="font-semibold text-gray-900">Landowner</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Access Level</dt>
                                <dd class="font-semibold text-gray-900">Own linked parcel viewing only</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Editing</dt>
                                <dd class="font-semibold text-red-700">Not allowed on map</dd>
                            </div>
                        </dl>
                    </div>

                </aside>

                <section class="bg-white border border-gray-200 shadow-sm rounded-xl p-4">
                    <div id="parcel-map"></div>
                </section>

            </div>
        </div>
    </div>

    @push('scripts')
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin="">
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const negrosOrientalCenter = [9.3068, 123.3054];
            const parcelGeoJson = @json($parcelGeoJson);

            const map = L.map('parcel-map', {
                zoomControl: false,
                scrollWheelZoom: true
            }).setView(negrosOrientalCenter, 12);

            L.control.zoom({
                position: 'topright'
            }).addTo(map);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                subdomains: 'abcd',
                maxZoom: 20,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
            }).addTo(map);

            

            function getParcelColor(status) {
                if (status === 'pending_review') {
                    return '#f59e0b';
                }

                if (status === 'linked_application') {
                    return '#2563eb';
                }

                if (status === 'flagged') {
                    return '#dc2626';
                }

                return '#22c55e';
            }

            function getParcelStyle(feature) {
                const color = getParcelColor(feature.properties.status);

                return {
                    color: color,
                    weight: 2,
                    opacity: 0.9,
                    fillColor: color,
                    fillOpacity: 0.28
                };
            }

            function getParcelHoverStyle(feature) {
                const color = getParcelColor(feature.properties.status);

                return {
                    color: '#ffffff',
                    weight: 6,
                    opacity: 1,
                    fillColor: color,
                    fillOpacity: 0.65
                };
            }

            function buildTooltipContent(properties) {
                return `
                    <div class="parcel-tooltip-card">
                        <div class="parcel-tooltip-title">
                            ${properties.parcel_code}
                        </div>

                        <div class="parcel-tooltip-row">
                            <span class="parcel-tooltip-label">Landowner:</span>
                            ${properties.landowner}
                        </div>

                        <div class="parcel-tooltip-row">
                            <span class="parcel-tooltip-label">Location:</span>
                            ${properties.barangay}, ${properties.municipality}
                        </div>

                        <div class="parcel-tooltip-row">
                            <span class="parcel-tooltip-label">Area:</span>
                            ${properties.area_hectares} hectares
                        </div>

                        <div class="parcel-tooltip-row">
                            <span class="parcel-tooltip-label">Title No.:</span>
                            ${properties.title_no}
                        </div>

                        <div class="parcel-tooltip-row">
                            <span class="parcel-tooltip-label">Tax Declaration:</span>
                            ${properties.tax_decl_no}
                        </div>

                        <div class="parcel-tooltip-row">
                            <span class="parcel-tooltip-label">Click:</span>
                            open parcel record
                        </div>
                    </div>
                `;
            }

            let parcelLayer;

            function onEachParcel(feature, layer) {
                layer.bindTooltip(buildTooltipContent(feature.properties), {
                    sticky: true,
                    direction: 'top',
                    opacity: 1,
                    className: 'parcel-tooltip'
                });

                layer.on({
                    mouseover: function (event) {
                        const hoveredLayer = event.target;

                        hoveredLayer.setStyle(getParcelHoverStyle(feature));
                        hoveredLayer.bringToFront();
                        hoveredLayer.openTooltip();
                    },

                    mouseout: function (event) {
                        parcelLayer.resetStyle(event.target);
                        event.target.closeTooltip();
                    },

                    click: function () {
    if (feature.properties.details_url) {
        window.location.href = feature.properties.details_url;
    }
}
                });
            }

            if (parcelGeoJson.features.length > 0) {
    parcelLayer = L.geoJSON(parcelGeoJson, {
        style: getParcelStyle,
        onEachFeature: onEachParcel
    }).addTo(map);

    map.fitBounds(parcelLayer.getBounds(), {
        padding: [40, 40]
    });
} else {
    L.popup()
        .setLatLng(negrosOrientalCenter)
        .setContent(`
            <strong>No mapped parcels linked to your account yet.</strong><br>
            Please contact DAR staff if your parcel record is missing or not yet mapped.
        `)
        .openOn(map);
}

            document.getElementById('reset-map-view').addEventListener('click', function () {
    if (parcelLayer) {
        map.fitBounds(parcelLayer.getBounds(), {
            padding: [40, 40]
        });
    } else {
        map.setView(negrosOrientalCenter, 12);
    }
});

            document.getElementById('focus-dar-office').addEventListener('click', function () {
                map.setView(negrosOrientalCenter, 14);
            });
        });
    </script>
@endpush
</x-app-layout>