<x-landowner-shell
    title="My Parcel Records"
    active="parcels"
>
    @push('styles')
        <style>
            .lo-page-stack {
                display: grid;
                gap: 18px;
            }

            .lo-page-hero {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                padding: 22px 24px;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 18px;
            }

            .lo-hero-label {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.16em;
                text-transform: uppercase;
                color: var(--lo-green-800);
            }

            .lo-hero-title {
                margin: 5px 0 0;
                font-size: 24px;
                line-height: 1.15;
                font-weight: 900;
                color: var(--lo-ink);
            }

            .lo-hero-copy {
                margin: 8px 0 0;
                color: var(--lo-muted);
                font-size: 13px;
                line-height: 1.55;
                max-width: 820px;
            }

            .lo-hero-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .lo-panel {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                overflow: hidden;
            }

            .lo-panel-header {
                padding: 20px 22px 0;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
            }

            .lo-panel-title {
                margin: 0;
                font-size: 18px;
                font-weight: 900;
                color: var(--lo-ink);
            }

            .lo-panel-subtitle {
                margin: 5px 0 0;
                font-size: 13px;
                color: var(--lo-muted);
                line-height: 1.45;
            }

            .lo-panel-body { padding: 18px 22px 22px; }

            .lo-table-wrap { overflow-x: auto; }

            .lo-table {
                width: 100%;
                border-collapse: collapse;
                min-width: 860px;
                font-size: 13px;
            }

            .lo-table thead {
                background: #f8faf9;
                border-bottom: 1px solid var(--lo-line);
            }

            .lo-table th {
                padding: 12px 14px;
                text-align: left;
                color: #667085;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .lo-table td {
                padding: 14px;
                border-bottom: 1px solid #edf0ee;
                color: #344054;
                vertical-align: top;
            }

            .lo-table tbody tr:last-child td { border-bottom: 0; }

            .lo-code-link {
                color: var(--lo-green-900);
                text-decoration: none;
                font-weight: 900;
            }

            .lo-code-link:hover { text-decoration: underline; }

            .lo-muted { color: var(--lo-muted); }

            .lo-status-pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 28px;
                border-radius: 999px;
                padding: 0 11px;
                font-size: 12px;
                font-weight: 900;
                white-space: nowrap;
                background: #dcfce7;
                border: 1px solid #bbf7d0;
                color: #166534;
            }

            .lo-status-pill.neutral {
                background: #f1f5f9;
                border-color: #e2e8f0;
                color: #334155;
            }

            .lo-map-state {
                font-weight: 900;
                color: var(--lo-green-900);
            }

            .lo-empty {
                border: 1px dashed #cbd5d1;
                border-radius: 10px;
                background: #fbfcfb;
                padding: 24px;
                color: var(--lo-muted);
                font-size: 13px;
                line-height: 1.55;
            }

            @media (max-width: 760px) {
                .lo-page-hero { flex-direction: column; }
                .lo-hero-actions { justify-content: flex-start; }
            }
        </style>
    @endpush

    <section class="lo-page-stack">
        <article class="lo-page-hero">
            <div>
                <p class="lo-hero-label">Landowner Parcel View</p>
                <h2 class="lo-hero-title">Linked Parcel and Landholding Records</h2>
                <p class="lo-hero-copy">
                    These records are limited to parcels and landholding references linked to your landowner account. They are displayed for reference and monitoring only.
                </p>
            </div>

            <div class="lo-hero-actions">
                <a href="{{ route('landowner.parcel-map.index') }}" class="lo-button lo-button-primary">
                    <i class="fa-solid fa-map-location-dot"></i>
                    Open Map
                </a>
            </div>
        </article>

        <article class="lo-panel">
            <div class="lo-panel-header">
                <div>
                    <h2 class="lo-panel-title">My Parcel Records</h2>
                    <p class="lo-panel-subtitle">Parcel references connected to your active or historical landholding records.</p>
                </div>

                <span class="lo-status-pill neutral">{{ $landholdings->count() }} linked</span>
            </div>

            <div class="lo-panel-body">
                @if ($landholdings->isEmpty())
                    <div class="lo-empty">
                        No parcel records are currently linked to your landowner account.
                    </div>
                @else
                    <div class="lo-table-wrap">
                        <table class="lo-table">
                            <thead>
                                <tr>
                                    <th>Parcel Code</th>
                                    <th>Title No.</th>
                                    <th>Tax Declaration</th>
                                    <th>Location</th>
                                    <th>Area</th>
                                    <th>Status</th>
                                    <th>Map</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($landholdings as $holding)
                                    @php($parcel = $holding->parcel)
                                    <tr>
                                        <td>
                                            @if ($parcel)
                                                <a href="{{ route('landowner.parcels.show', $parcel) }}" class="lo-code-link">
                                                    {{ $parcel->parcel_code }}
                                                </a>
                                            @else
                                                <span class="lo-muted">Unlinked parcel</span>
                                            @endif
                                        </td>
                                        <td>{{ $parcel?->title_no ?? 'N/A' }}</td>
                                        <td>{{ $parcel?->tax_decl_no ?? 'N/A' }}</td>
                                        <td>{{ $parcel?->barangay ?? 'N/A' }}, {{ $parcel?->municipality ?? 'N/A' }}</td>
                                        <td>{{ number_format((float) $holding->area_hectares, 4) }} ha</td>
                                        <td>
                                            <span class="lo-status-pill">
                                                {{ $holding->status ? ucwords(str_replace('_', ' ', $holding->status)) : 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="lo-map-state">
                                                {{ $parcel?->geometry_geojson ? 'Available' : 'Not mapped' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>
    </section>
</x-landowner-shell>
