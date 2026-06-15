<x-landowner-shell
    title="My Parcel Details"
    active="parcels"
>
    @push('styles')
        <style>
            .lo-detail-stack {
                display: grid;
                gap: 18px;
            }

            .lo-hero-card {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                padding: 24px;
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(320px, 0.8fr);
                gap: 22px;
                align-items: stretch;
            }

            .lo-eyebrow-small {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.16em;
                text-transform: uppercase;
                color: #667085;
            }

            .lo-parcel-code {
                margin: 8px 0 0;
                font-size: clamp(2rem, 4vw, 3rem);
                line-height: 1;
                font-weight: 900;
                color: var(--lo-ink);
                letter-spacing: 0.06em;
            }

            .lo-parcel-meta {
                margin: 10px 0 0;
                color: var(--lo-muted);
                font-size: 13px;
            }

            .lo-badge-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 18px;
            }

            .lo-pill {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                border: 1px solid #bbf7d0;
                background: #dcfce7;
                color: var(--lo-green-800);
                min-height: 28px;
                padding: 0 11px;
                font-size: 12px;
                font-weight: 900;
            }

            .lo-pill.neutral {
                background: #f1f5f9;
                border-color: #e2e8f0;
                color: #334155;
            }

            .lo-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 20px;
            }

            .lo-review-grid {
                display: grid;
                gap: 10px;
                align-content: start;
            }

            .lo-review-card {
                border: 1px solid #edf0ee;
                background: #fbfcfb;
                border-radius: 10px;
                padding: 14px;
            }

            .lo-review-card.highlight {
                border-color: #bbf7d0;
                background: #effaf2;
            }

            .lo-review-label {
                margin: 0;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #667085;
            }

            .lo-review-value {
                margin: 6px 0 0;
                font-size: 18px;
                font-weight: 900;
                color: var(--lo-ink);
            }

            .lo-review-help {
                margin: 4px 0 0;
                color: var(--lo-muted);
                font-size: 12px;
                line-height: 1.45;
            }

            .lo-grid-main {
                display: grid;
                grid-template-columns: minmax(0, 2fr) minmax(330px, 0.78fr);
                gap: 18px;
                align-items: start;
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

            .lo-info-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .lo-info-box {
                border: 1px solid #edf0ee;
                background: #fbfcfb;
                border-radius: 10px;
                padding: 14px;
                min-height: 76px;
            }

            .lo-info-label {
                margin: 0;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #667085;
            }

            .lo-info-value {
                margin: 7px 0 0;
                color: #111827;
                font-size: 14px;
                line-height: 1.45;
                font-weight: 900;
            }

            .lo-info-box.full { grid-column: 1 / -1; }

            .lo-geometry-action {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                border: 1px solid #bbf7d0;
                background: #effaf2;
                color: var(--lo-green-900);
                text-decoration: none;
                border-radius: 10px;
                padding: 14px;
                font-size: 14px;
                font-weight: 900;
            }

            .lo-table-wrap { overflow-x: auto; }

            .lo-table {
                width: 100%;
                border-collapse: collapse;
                min-width: 760px;
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

            .lo-remarks {
                max-width: 420px;
                white-space: normal;
                line-height: 1.45;
            }

            @media (max-width: 1100px) {
                .lo-hero-card,
                .lo-grid-main { grid-template-columns: 1fr; }
            }

            @media (max-width: 640px) {
                .lo-info-grid { grid-template-columns: 1fr; }
                .lo-parcel-code { font-size: 2rem; }
            }
        </style>
    @endpush

    <section class="lo-detail-stack">
        <article class="lo-hero-card">
            <div>
                <p class="lo-eyebrow-small">Linked Parcel Reference</p>
                <h2 class="lo-parcel-code">{{ $parcel->parcel_code }}</h2>
                <p class="lo-parcel-meta">
                    Record ID: {{ $parcel->id }} · {{ $parcel->municipality ?? 'N/A' }}, {{ $parcel->barangay ?? 'N/A' }}
                </p>

                <div class="lo-badge-row">
                    <span class="lo-pill">{{ $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'Reference Record' }}</span>
                    <span class="lo-pill neutral">{{ $parcel->geometry_geojson ? 'Mapped Geometry' : 'No Geometry' }}</span>
                    <span class="lo-pill neutral">DAR Clearance Scope: Agricultural land record</span>
                    <span class="lo-pill neutral">Viewing Only</span>
                </div>

                <div class="lo-actions">
                    <a href="{{ route('landowner.parcel-map.index') }}" class="lo-button lo-button-primary">
                        <i class="fa-solid fa-map-location-dot"></i>
                        Back to Map
                    </a>

                    <a href="{{ route('landowner.parcels.index') }}" class="lo-button">
                        <i class="fa-solid fa-arrow-left"></i>
                        Back to My Parcels
                    </a>
                </div>
            </div>

            <div class="lo-review-grid">
                <div class="lo-review-card highlight">
                    <p class="lo-review-label">Linked Area</p>
                    <p class="lo-review-value">
                        {{ number_format((float) $landholdings->sum('area_hectares'), 4) }} ha
                    </p>
                    <p class="lo-review-help">Computed from your linked landholding record(s) for this parcel.</p>
                </div>

                <div class="lo-review-card">
                    <p class="lo-review-label">Landholding Records</p>
                    <p class="lo-review-value">{{ $landholdings->count() }} linked</p>
                    <p class="lo-review-help">Only records connected to your landowner account are shown.</p>
                </div>
            </div>
        </article>

        <section class="lo-grid-main">
            <article class="lo-panel">
                <div class="lo-panel-header">
                    <div>
                        <h2 class="lo-panel-title">Parcel Reference Information</h2>
                        <p class="lo-panel-subtitle">Core encoded parcel reference values used for viewing, clearance context, monitoring, source matching, and map display. This view does not verify ownership or perform registry changes.</p>
                    </div>
                </div>

                <div class="lo-panel-body">
                    <div class="lo-info-grid">
                        <div class="lo-info-box">
                            <p class="lo-info-label">Title Number</p>
                            <p class="lo-info-value">{{ $parcel->title_no ?? 'N/A' }}</p>
                        </div>

                        <div class="lo-info-box">
                            <p class="lo-info-label">Tax Declaration Number</p>
                            <p class="lo-info-value">{{ $parcel->tax_decl_no ?? 'N/A' }}</p>
                        </div>

                        <div class="lo-info-box">
                            <p class="lo-info-label">Parcel Area</p>
                            <p class="lo-info-value">
                                {{ $parcel->area_hectares ? number_format((float) $parcel->area_hectares, 4) . ' hectares' : 'N/A' }}
                            </p>
                        </div>

                        <div class="lo-info-box">
                            <p class="lo-info-label">Province</p>
                            <p class="lo-info-value">{{ $parcel->province ?? 'N/A' }}</p>
                        </div>

                        <div class="lo-info-box">
                            <p class="lo-info-label">Municipality</p>
                            <p class="lo-info-value">{{ $parcel->municipality ?? 'N/A' }}</p>
                        </div>

                        <div class="lo-info-box">
                            <p class="lo-info-label">Barangay</p>
                            <p class="lo-info-value">{{ $parcel->barangay ?? 'N/A' }}</p>
                        </div>

                        <div class="lo-info-box full">
                            <p class="lo-info-label">Remarks</p>
                            <p class="lo-info-value">{{ $parcel->remarks ?? 'No remarks recorded.' }}</p>
                        </div>
                    </div>
                </div>
            </article>

            <article class="lo-panel">
                <div class="lo-panel-header">
                    <div>
                        <h2 class="lo-panel-title">Geometry Reference</h2>
                        <p class="lo-panel-subtitle">Map geometry is for visualization and parcel reference checking only.</p>
                    </div>

                    <span class="lo-pill">{{ $parcel->geometry_geojson ? 'Available' : 'Not mapped' }}</span>
                </div>

                <div class="lo-panel-body">
                    <div class="lo-info-grid" style="grid-template-columns: 1fr;">
                        <div class="lo-info-box">
                            <p class="lo-info-label">Map Display</p>
                            <p class="lo-info-value">{{ $parcel->geometry_geojson ? 'Available' : 'Not yet encoded' }}</p>
                        </div>

                        <a href="{{ route('landowner.parcel-map.index') }}" class="lo-geometry-action">
                            <span>
                                <i class="fa-solid fa-map-location-dot"></i>
                                Open Parcel Map
                            </span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>
        </section>

        <article class="lo-panel">
            <div class="lo-panel-header">
                <div>
                    <h2 class="lo-panel-title">My Linked Landholding Records</h2>
                    <p class="lo-panel-subtitle">Administrative landholding references connected to this parcel and your account.</p>
                </div>

                <span class="lo-pill neutral">{{ $landholdings->count() }} linked</span>
            </div>

            <div class="lo-panel-body">
                <div class="lo-table-wrap">
                    <table class="lo-table">
                        <thead>
                            <tr>
                                <th>Landowner</th>
                                <th>Area</th>
                                <th>Status</th>
                                <th>Dates</th>
                                <th>Reference</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($landholdings as $landholding)
                                <tr>
                                    <td><strong>{{ $landowner->full_name }}</strong></td>
                                    <td><strong>{{ number_format((float) $landholding->area_hectares, 4) }} ha</strong></td>
                                    <td>
                                        <span class="lo-pill">
                                            {{ $landholding->status ? ucwords(str_replace('_', ' ', $landholding->status)) : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        Acquired: {{ $landholding->date_acquired?->format('M d, Y') ?? 'N/A' }}<br>
                                        Transferred: {{ $landholding->date_transferred?->format('M d, Y') ?? 'N/A' }}
                                    </td>
                                    <td>{{ $landholding->source_reference_number ?? 'N/A' }}</td>
                                    <td class="lo-remarks">{{ $landholding->remarks ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </article>
    </section>
</x-landowner-shell>
