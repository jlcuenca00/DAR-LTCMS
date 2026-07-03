<x-geodetic-shell
    title="Parcel References"
    active="parcels"
>
    <style>
        .geo-page-card {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .geo-card-header {
            padding: 22px 24px 16px;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            border-bottom: 1px solid #e5e7eb;
        }

        .geo-card-title {
            margin: 0;
            font-size: 19px;
            line-height: 1.2;
            font-weight: 900;
            color: var(--geo-ink);
        }

        .geo-card-subtitle {
            margin: 6px 0 0;
            max-width: 880px;
            color: var(--geo-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .geo-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .geo-header-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid #bbf7d0;
            background: var(--geo-green-50);
            color: var(--geo-green-800);
            border-radius: 999px;
            padding: 7px 11px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .geo-table-wrap {
            padding: 16px 24px 24px;
            overflow-x: auto;
        }

        .geo-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .geo-table th {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #d1d5db;
            font-size: 11px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #667085;
            white-space: nowrap;
        }

        .geo-table td {
            padding: 14px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            vertical-align: top;
        }

        .geo-code-link {
            color: var(--geo-green-900);
            font-weight: 900;
            text-decoration: none;
            white-space: nowrap;
        }

        .geo-code-link:hover { text-decoration: underline; }

        .geo-muted {
            color: var(--geo-muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .geo-owner {
            font-weight: 900;
            color: #111827;
        }

        .geo-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 11px;
            line-height: 1;
            font-weight: 900;
            border: 1px solid #d7ded9;
            background: #f8faf9;
            color: #344054;
            white-space: nowrap;
        }

        .geo-badge-active { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
        .geo-badge-muted { background: #f1f5f9; border-color: #e2e8f0; color: #475569; }

        .geo-empty {
            padding: 34px 24px;
            color: var(--geo-muted);
            font-size: 14px;
        }

        @media (max-width: 760px) {
            .geo-card-header { flex-direction: column; }
        }
    </style>

    <section class="geo-page-card">
        <div class="geo-card-header">
            <div>
                <h2 class="geo-card-title">Read-Only Parcel and Landholding References</h2>
                <p class="geo-card-subtitle">
                    These records support parcel checking, map review, hectare monitoring, and technical reference review only. DAR clearance processing applies to agricultural land records; this view does not verify ownership, encode ownership changes, or finalize legal land transactions.
                </p>
            </div>

            <div class="geo-header-actions">
                <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-button geo-button-primary">
                    <i class="fa-solid fa-map-location-dot"></i>
                    Open Map
                </a>

                <span class="geo-header-pill">
                    <i class="fa-solid fa-lock"></i>
                    Read Only
                </span>
            </div>
        </div>

        @if($landholdings->isEmpty())
            <div class="geo-empty">No parcel or landholding records found.</div>
        @else
            <div class="geo-table-wrap">
                <table class="geo-table">
                    <thead>
                        <tr>
                            <th>Parcel</th>
                            <th>Title No.</th>
                            <th>Tax Declaration</th>
                            <th>Landowner</th>
                            <th>Location</th>
                            <th>Area</th>
                            <th>Clearance Scope</th>
                            <th>Status</th>
                            <th>Geometry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($landholdings as $holding)
                            <tr>
                                <td>
                                    @if ($holding->parcel)
                                        <a class="geo-code-link" href="{{ route('geodetic.parcels.show', $holding->parcel) }}">
                                            {{ $holding->parcel->parcel_code }}
                                        </a>
                                    @else
                                        <span class="geo-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $holding->parcel?->title_no ?? 'N/A' }}</td>
                                <td>{{ $holding->parcel?->tax_decl_no ?? 'N/A' }}</td>
                                <td>
                                    <span class="geo-owner">{{ $holding->landowner?->full_name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    {{ $holding->parcel?->barangay ?? 'N/A' }},
                                    {{ $holding->parcel?->municipality ?? 'N/A' }}
                                </td>
                                <td>
                                    <strong>{{ number_format((float) $holding->area_hectares, 4) }} ha</strong>
                                </td>
                                <td>
                                    <span class="geo-badge geo-badge-muted">
                                        Agricultural land record
                                    </span>
                                </td>
                                <td>
                                    <span class="geo-badge {{ $holding->status === 'active' ? 'geo-badge-active' : 'geo-badge-muted' }}">
                                        {{ $holding->status ? ucwords(str_replace('_', ' ', $holding->status)) : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="geo-badge {{ $holding->parcel?->geometry_geojson ? 'geo-badge-active' : 'geo-badge-muted' }}">
                                        {{ $holding->parcel?->geometry_geojson ? 'Mapped' : 'No Geometry' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-geodetic-shell>
