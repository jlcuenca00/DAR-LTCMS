<x-landowner-shell title="My Parcel Details" active="parcels">
    @push('styles')
        <style>
            .lo-detail-page { display: grid; gap: 18px; }

            .lo-detail-hero {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                padding: 22px 24px;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 22px;
                align-items: center;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
            }

            .lo-detail-kicker { margin: 0; color: var(--lo-green-800); font-size: 10px; font-weight: 900; letter-spacing: .16em; text-transform: uppercase; }
            .lo-detail-code { margin: 7px 0 0; color: var(--lo-ink); font-size: clamp(28px, 4vw, 42px); line-height: 1; font-weight: 900; letter-spacing: .03em; }
            .lo-detail-location { margin: 9px 0 0; color: var(--lo-muted); font-size: 13px; line-height: 1.45; }
            .lo-detail-badges { margin-top: 15px; display: flex; flex-wrap: wrap; gap: 7px; }

            .lo-detail-badge {
                display: inline-flex;
                align-items: center;
                min-height: 26px;
                padding: 0 9px;
                border: 1px solid #e2e8f0;
                border-radius: 999px;
                background: #f8fafc;
                color: #475569;
                font-size: 10px;
                font-weight: 900;
            }
            .lo-detail-badge.is-green { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
            .lo-detail-badge.is-blue { background: #dbeafe; border-color: #bfdbfe; color: #1d4ed8; }

            .lo-detail-summary {
                display: grid;
                grid-template-columns: repeat(3, minmax(115px, 1fr));
                border: 1px solid #d7ded9;
                border-radius: 12px;
                overflow: hidden;
                min-width: 390px;
            }
            .lo-detail-summary-item { padding: 14px; border-right: 1px solid #e5e7eb; background: #fbfcfb; }
            .lo-detail-summary-item:last-child { border-right: 0; }
            .lo-detail-summary-value { display: block; color: var(--lo-ink); font-size: 18px; font-weight: 900; line-height: 1.15; }
            .lo-detail-summary-label { display: block; margin-top: 5px; color: #667085; font-size: 9px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }

            .lo-detail-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.55fr) minmax(320px, .75fr);
                gap: 18px;
                align-items: start;
            }

            .lo-detail-panel {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
                overflow: hidden;
            }
            .lo-detail-panel-header { padding: 18px 20px 15px; border-bottom: 1px solid #e8eeea; }
            .lo-detail-panel-title { margin: 0; color: var(--lo-ink); font-size: 17px; font-weight: 900; }
            .lo-detail-panel-copy { margin: 4px 0 0; color: var(--lo-muted); font-size: 12px; line-height: 1.45; }
            .lo-detail-panel-body { padding: 18px 20px 20px; }

            .lo-info-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 24px; }
            .lo-info-row { display: grid; grid-template-columns: minmax(120px, .7fr) minmax(0, 1fr); gap: 12px; padding: 11px 0; border-bottom: 1px solid #edf1ee; }
            .lo-info-row.full { grid-column: 1 / -1; }
            .lo-info-label { color: #667085; font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
            .lo-info-value { color: #1f2937; font-size: 13px; line-height: 1.45; font-weight: 750; overflow-wrap: anywhere; }

            .lo-map-callout {
                display: grid;
                gap: 12px;
                padding: 18px 20px 20px;
            }
            .lo-map-state {
                border: 1px solid #bbf7d0;
                border-radius: 11px;
                background: var(--lo-green-50);
                padding: 14px;
            }
            .lo-map-state-label { margin: 0; color: #166534; font-size: 10px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
            .lo-map-state-value { margin: 6px 0 0; color: var(--lo-green-900); font-size: 16px; font-weight: 900; }
            .lo-map-state-copy { margin: 5px 0 0; color: #166534; font-size: 11px; line-height: 1.45; }

            .lo-detail-table-wrap { overflow-x: auto; }
            .lo-detail-table { width: 100%; min-width: 840px; border-collapse: collapse; font-size: 13px; }
            .lo-detail-table th {
                padding: 12px 15px;
                background: #f8faf9;
                border-bottom: 1px solid var(--lo-line);
                color: #667085;
                text-align: left;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: .13em;
                text-transform: uppercase;
                white-space: nowrap;
            }
            .lo-detail-table td { padding: 15px; border-bottom: 1px solid #edf1ee; color: #344054; vertical-align: top; }
            .lo-detail-table tbody tr:last-child td { border-bottom: 0; }
            .lo-table-strong { color: #0f172a; font-weight: 900; }
            .lo-table-sub { margin-top: 3px; color: #667085; font-size: 11px; line-height: 1.4; }

            @media (max-width: 1120px) {
                .lo-detail-hero,
                .lo-detail-grid { grid-template-columns: 1fr; }
                .lo-detail-summary { min-width: 0; }
            }

            @media (max-width: 760px) {
                .lo-detail-summary,
                .lo-info-list { grid-template-columns: 1fr; }
                .lo-detail-summary-item { border-right: 0; border-bottom: 1px solid #e5e7eb; }
                .lo-detail-summary-item:last-child { border-bottom: 0; }
                .lo-info-row { grid-template-columns: 1fr; gap: 5px; }
            }
        </style>
    @endpush

    <section class="lo-detail-page">
        <article class="lo-detail-hero">
            <div>
                <p class="lo-detail-kicker">Linked Parcel Reference</p>
                <h2 class="lo-detail-code">{{ $parcel->parcel_code }}</h2>
                <p class="lo-detail-location">{{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }}, {{ $parcel->province ?? 'Negros Oriental' }}</p>
                <div class="lo-detail-badges">
                    <span class="lo-detail-badge {{ $parcel->status === 'active' ? 'is-green' : '' }}">{{ $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'Reference Record' }}</span>
                    <span class="lo-detail-badge {{ $parcel->geometry_geojson ? 'is-blue' : '' }}">{{ $parcel->geometry_geojson ? 'Mapped Geometry' : 'No Geometry' }}</span>
                    <span class="lo-detail-badge">Viewing Only</span>
                </div>
            </div>

            <div class="lo-detail-summary" aria-label="Parcel summary">
                <div class="lo-detail-summary-item">
                    <span class="lo-detail-summary-value">{{ number_format((float) $landholdings->sum('area_hectares'), 4) }} ha</span>
                    <span class="lo-detail-summary-label">Linked area</span>
                </div>
                <div class="lo-detail-summary-item">
                    <span class="lo-detail-summary-value">{{ $landholdings->count() }}</span>
                    <span class="lo-detail-summary-label">Landholdings</span>
                </div>
                <div class="lo-detail-summary-item">
                    <span class="lo-detail-summary-value">{{ $parcel->geometry_geojson ? 'Yes' : 'No' }}</span>
                    <span class="lo-detail-summary-label">Map available</span>
                </div>
            </div>
        </article>

        <section class="lo-detail-grid">
            <article class="lo-detail-panel">
                <header class="lo-detail-panel-header">
                    <h2 class="lo-detail-panel-title">Parcel Reference Information</h2>
                    <p class="lo-detail-panel-copy">Encoded parcel details used for administrative reference and clearance monitoring.</p>
                </header>
                <div class="lo-detail-panel-body">
                    <dl class="lo-info-list">
                        <div class="lo-info-row"><dt class="lo-info-label">Title Type</dt><dd class="lo-info-value">{{ $parcel->title_type ?? 'N/A' }}</dd></div>
                        <div class="lo-info-row"><dt class="lo-info-label">Title Number</dt><dd class="lo-info-value">{{ $parcel->title_no ?? 'N/A' }}</dd></div>
                        <div class="lo-info-row"><dt class="lo-info-label">Tax Declaration</dt><dd class="lo-info-value">{{ $parcel->tax_decl_no ?? 'N/A' }}</dd></div>
                        <div class="lo-info-row"><dt class="lo-info-label">Lot Number</dt><dd class="lo-info-value">{{ $parcel->lot_number ?? 'N/A' }}</dd></div>
                        <div class="lo-info-row"><dt class="lo-info-label">Survey Plan</dt><dd class="lo-info-value">{{ $parcel->survey_plan_number ?? 'N/A' }}</dd></div>
                        <div class="lo-info-row"><dt class="lo-info-label">ROD Office</dt><dd class="lo-info-value">{{ $parcel->rod_office ?? 'N/A' }}</dd></div>
                        <div class="lo-info-row"><dt class="lo-info-label">Parcel Area</dt><dd class="lo-info-value">{{ $parcel->area_hectares ? number_format((float) $parcel->area_hectares, 4).' ha' : 'N/A' }}</dd></div>
                        <div class="lo-info-row"><dt class="lo-info-label">Agricultural Status</dt><dd class="lo-info-value">{{ $parcel->agricultural_status ? ucwords(str_replace('_', ' ', $parcel->agricultural_status)) : 'N/A' }}</dd></div>
                        <div class="lo-info-row full"><dt class="lo-info-label">Remarks</dt><dd class="lo-info-value">{{ $parcel->remarks ?? 'No remarks recorded.' }}</dd></div>
                    </dl>
                </div>
            </article>

            <aside class="lo-detail-panel">
                <header class="lo-detail-panel-header">
                    <h2 class="lo-detail-panel-title">Map Reference</h2>
                    <p class="lo-detail-panel-copy">Geometry is displayed for parcel reference only.</p>
                </header>
                <div class="lo-map-callout">
                    <div class="lo-map-state">
                        <p class="lo-map-state-label">Geometry Status</p>
                        <p class="lo-map-state-value">{{ $parcel->geometry_geojson ? 'Available' : 'Not yet encoded' }}</p>
                        <p class="lo-map-state-copy">Opening the map shows only parcels linked to your landowner account.</p>
                    </div>
                    <a href="{{ route('landowner.parcel-map.index') }}" class="lo-button lo-button-primary">
                        <i class="fa-solid fa-map-location-dot"></i>
                        Open Parcel Map
                    </a>
                </div>
            </aside>
        </section>

        <article class="lo-detail-panel">
            <header class="lo-detail-panel-header">
                <h2 class="lo-detail-panel-title">My Linked Landholding Records</h2>
                <p class="lo-detail-panel-copy">Administrative landholding references connected to this parcel and your account.</p>
            </header>

            <div class="lo-detail-table-wrap">
                <table class="lo-detail-table">
                    <thead>
                        <tr>
                            <th>Landowner</th>
                            <th>Linked Area</th>
                            <th>Status</th>
                            <th>Record Dates</th>
                            <th>Source Reference</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($landholdings as $landholding)
                            <tr>
                                <td><div class="lo-table-strong">{{ $landowner->full_name }}</div></td>
                                <td><div class="lo-table-strong">{{ number_format((float) $landholding->area_hectares, 4) }} ha</div></td>
                                <td><span class="lo-detail-badge {{ $landholding->status === 'active' ? 'is-green' : '' }}">{{ $landholding->status ? ucwords(str_replace('_', ' ', $landholding->status)) : 'N/A' }}</span></td>
                                <td>
                                    <div class="lo-table-strong">{{ $landholding->date_acquired?->format('M d, Y') ?? 'No acquisition date' }}</div>
                                    <div class="lo-table-sub">Transferred: {{ $landholding->date_transferred?->format('M d, Y') ?? 'N/A' }}</div>
                                </td>
                                <td>{{ $landholding->source_reference_number ?? 'N/A' }}</td>
                                <td>{{ $landholding->remarks ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</x-landowner-shell>
