<x-staff-shell
    title="Parcel Details"
    active="parcel-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.parcel-map.index') }}" class="staff-button staff-button-primary">
            <i class="fa-solid fa-map-location-dot"></i>
            Back to Map
        </a>

        <a href="{{ route('staff.records.parcels.edit', $parcel) }}" class="staff-button staff-button-primary">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Record
        </a>

        <button type="button" class="staff-button staff-button-danger" id="parcel-archive-modal-open">
            <i class="fa-solid fa-box-archive"></i>
            Archive Record
        </button>

        <a href="{{ route('staff.records.parcels.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Parcel Records
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .parcel-page-stack {
                display: grid;
                gap: 18px;
            }

            .parcel-hero-card {
                border: 1px solid #d8dee8;
                background: #ffffff;
                border-radius: 16px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
                overflow: hidden;
            }

            .parcel-hero-main {
                display: grid;
                grid-template-columns: 1fr;
                gap: 16px;
                padding: 18px 22px;
            }

            .parcel-eyebrow,
            .parcel-meta-label,
            .parcel-section-label {
                margin: 0 0 5px;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #64748b;
            }

            .parcel-code-title {
                margin: 0;
                font-size: clamp(24px, 2.4vw, 34px);
                line-height: 1.05;
                font-weight: 950;
                letter-spacing: 0.065em;
                color: #0f172a;
                word-break: break-word;
            }

            .parcel-hero-subtitle {
                margin: 8px 0 0;
                font-size: 13px;
                color: #64748b;
                line-height: 1.45;
            }

            .parcel-chip-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 12px;
            }

            .parcel-summary-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 10px;
            }

            .parcel-summary-card {
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                border-radius: 12px;
                padding: 11px 13px;
                min-height: 82px;
            }

            .parcel-summary-card.is-highlight {
                border-color: #bbf7d0;
                background: #f0fdf4;
            }

            .parcel-summary-value {
                margin: 0;
                font-size: 16px;
                line-height: 1.2;
                font-weight: 900;
                color: #0f172a;
            }

            .parcel-summary-card.is-highlight .parcel-summary-value {
                color: #052e16;
            }

            .parcel-summary-help {
                margin: 3px 0 0;
                font-size: 12px;
                line-height: 1.4;
                color: #64748b;
            }

            .parcel-grid-two {
                display: grid;
                grid-template-columns: minmax(0, 1.55fr) minmax(340px, 0.55fr);
                gap: 18px;
                align-items: start;
            }

            .parcel-meta-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .parcel-meta-card {
                border: 1px solid #e5e7eb;
                background: #f9fafb;
                border-radius: 12px;
                padding: 13px 15px;
                min-height: 72px;
            }

            .parcel-meta-value {
                margin: 0;
                font-size: 14px;
                font-weight: 850;
                color: #111827;
                line-height: 1.35;
                word-break: break-word;
            }

            .parcel-meta-subvalue {
                margin-top: 3px;
                font-size: 12.5px;
                color: #6b7280;
                line-height: 1.4;
            }


            .parcel-remarks-box {
                margin-top: 12px;
                border: 1px solid #e5e7eb;
                background: #ffffff;
                border-radius: 12px;
                padding: 14px 15px;
            }

            .parcel-remarks-box p:last-child {
                margin-bottom: 0;
            }

            .parcel-side-panel {
                display: grid;
                gap: 12px;
            }

            .parcel-geometry-card {
                display: grid;
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .parcel-geometry-summary-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .parcel-geometry-mini-card {
                border: 1px solid #e5e7eb;
                background: #f9fafb;
                border-radius: 12px;
                padding: 12px 13px;
                min-height: 76px;
            }

            .parcel-geometry-mini-card.is-wide {
                grid-column: 1 / -1;
                min-height: auto;
            }

            .parcel-geometry-note {
                margin: 4px 0 0;
                font-size: 12px;
                line-height: 1.45;
                color: #64748b;
            }

            .parcel-map-action {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                align-items: center;
                gap: 12px;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                border-radius: 12px;
                padding: 12px 13px;
                color: #14532d;
                text-decoration: none;
                transition: border-color .15s ease, background .15s ease, transform .15s ease;
            }

            .parcel-map-action:hover {
                background: #dcfce7;
                border-color: #86efac;
                transform: translateY(-1px);
            }

            .parcel-map-action-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 34px;
                height: 34px;
                border-radius: 10px;
                border: 1px solid #bbf7d0;
                background: #ffffff;
                color: #15803d;
                flex: none;
            }

            .parcel-map-action-title {
                display: block;
                font-size: 13.5px;
                font-weight: 950;
                line-height: 1.2;
            }

            .parcel-map-action-help {
                display: block;
                margin-top: 2px;
                font-size: 11.5px;
                font-weight: 700;
                color: #4b5563;
                line-height: 1.3;
            }

            .parcel-code-box {
                margin-top: 12px;
                border: 1px solid #d1d5db;
                border-radius: 12px;
                overflow: hidden;
                background: #ffffff;
            }

            .parcel-code-box summary {
                cursor: pointer;
                padding: 12px 13px;
                font-size: 13px;
                color: #14532d;
                list-style: none;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }

            .parcel-code-summary-main {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                min-width: 0;
            }

            .parcel-code-summary-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 30px;
                height: 30px;
                border-radius: 9px;
                border: 1px solid #e5e7eb;
                background: #f9fafb;
                color: #15803d;
                flex: none;
            }

            .parcel-code-summary-title {
                display: block;
                font-size: 13px;
                font-weight: 950;
                line-height: 1.2;
            }

            .parcel-code-summary-help {
                display: block;
                margin-top: 2px;
                font-size: 11.5px;
                font-weight: 700;
                color: #64748b;
                line-height: 1.25;
            }

            .parcel-code-box summary::-webkit-details-marker { display: none; }

            .parcel-code-box pre {
                margin: 0;
                max-height: 300px;
                overflow: auto;
                border-top: 1px solid #e5e7eb;
                background: #0f172a;
                color: #dbeafe;
                padding: 16px;
                font-size: 12px;
                line-height: 1.65;
                white-space: pre-wrap;
                word-break: break-word;
            }

            .parcel-empty-state {
                border: 1px dashed #cbd5e1;
                border-radius: 12px;
                padding: 16px;
                color: #64748b;
                background: #f8fafc;
                font-size: 13px;
                line-height: 1.6;
            }

            .parcel-source-summary {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
                margin-top: 14px;
            }

            .parcel-source-count-card {
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 14px;
                background: #ffffff;
            }

            .parcel-source-count-card strong {
                display: block;
                margin-top: 4px;
                font-size: 24px;
                line-height: 1;
                color: #111827;
            }

            .parcel-table-scroll {
                overflow-x: auto;
                border-radius: 12px;
            }

            .parcel-landholding-table {
                min-width: 980px;
                table-layout: fixed;
            }

            .parcel-landholding-table th:nth-child(1),
            .parcel-landholding-table td:nth-child(1) {
                width: 14%;
            }

            .parcel-landholding-table th:nth-child(2),
            .parcel-landholding-table td:nth-child(2) {
                width: 9%;
            }

            .parcel-landholding-table th:nth-child(3),
            .parcel-landholding-table td:nth-child(3) {
                width: 10%;
            }

            .parcel-landholding-table th:nth-child(4),
            .parcel-landholding-table td:nth-child(4) {
                width: 16%;
            }

            .parcel-landholding-table th:nth-child(5),
            .parcel-landholding-table td:nth-child(5) {
                width: 13%;
            }

            .parcel-landholding-table th:nth-child(6),
            .parcel-landholding-table td:nth-child(6) {
                width: 38%;
            }

            .parcel-landholding-table td {
                vertical-align: top;
            }

            .parcel-remarks-text {
                display: block;
                max-width: 48rem;
                color: #334155;
                font-size: 12.5px;
                line-height: 1.55;
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: normal;
            }

            .parcel-record-chip {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border-radius: 999px;
                padding: 5px 9px;
                font-size: 11px;
                font-weight: 900;
                border: 1px solid #cbd5e1;
                background: #f8fafc;
                color: #475569;
            }

            .parcel-landholding-card-list {
                display: grid;
                gap: 12px;
            }

            .parcel-landholding-card {
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                background: #ffffff;
                padding: 14px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }

            .parcel-landholding-card-head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
                padding-bottom: 12px;
                border-bottom: 1px solid #edf2f7;
            }

            .parcel-landholding-owner {
                margin: 0;
                color: #0f172a;
                font-size: 15px;
                line-height: 1.25;
                font-weight: 950;
            }

            .parcel-landholding-owner-meta {
                margin: 3px 0 0;
                color: #64748b;
                font-size: 12.5px;
                line-height: 1.4;
            }

            .parcel-landholding-detail-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 10px;
                margin-top: 12px;
            }

            .parcel-landholding-detail-card {
                border: 1px solid #edf2f7;
                border-radius: 12px;
                background: #f8fafc;
                padding: 10px 11px;
                min-width: 0;
            }

            .parcel-landholding-detail-card.is-wide {
                grid-column: span 2;
            }

            .parcel-landholding-detail-label {
                margin: 0;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.11em;
                text-transform: uppercase;
                color: #64748b;
            }

            .parcel-landholding-detail-value {
                margin: 4px 0 0;
                font-size: 13px;
                font-weight: 850;
                line-height: 1.45;
                color: #111827;
                overflow-wrap: anywhere;
            }

            .parcel-source-card-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
                margin-top: 14px;
            }

            .parcel-source-card {
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                background: #ffffff;
                padding: 14px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }

            .parcel-source-card-title {
                margin: 0;
                color: #0f172a;
                font-size: 14px;
                font-weight: 950;
            }

            .parcel-source-card-meta {
                margin: 5px 0 0;
                color: #64748b;
                font-size: 12px;
                line-height: 1.45;
            }

            .parcel-source-card-row {
                display: grid;
                grid-template-columns: 90px minmax(0, 1fr);
                gap: 8px;
                margin-top: 10px;
                font-size: 12.5px;
                line-height: 1.45;
            }

            .parcel-source-card-row span:first-child {
                color: #64748b;
                font-weight: 800;
            }

            .parcel-source-card-actions {
                margin-top: 12px;
                display: flex;
                justify-content: flex-end;
            }

            @media (max-width: 1180px) {
                .parcel-grid-two {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 760px) {
                .parcel-hero-main {
                    padding: 18px;
                }

                .parcel-meta-grid,
                .parcel-source-summary,
                .parcel-summary-grid,
                .parcel-geometry-summary-grid,
                .parcel-landholding-detail-grid,
                .parcel-source-card-grid {
                    grid-template-columns: 1fr;
                }

                .parcel-landholding-detail-card.is-wide {
                    grid-column: 1 / -1;
                }
            }


            .archive-modal-backdrop {
                position: fixed;
                inset: 0;
                z-index: 90;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 24px;
                background: rgba(15, 23, 42, 0.64);
                backdrop-filter: blur(3px);
            }

            .archive-modal-backdrop.is-open {
                display: flex;
            }

            .archive-modal-card {
                width: min(520px, 100%);
                border: 1px solid #fed7aa;
                border-radius: 16px;
                background: #ffffff;
                box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
                overflow: hidden;
            }

            .archive-modal-header {
                display: flex;
                gap: 14px;
                align-items: flex-start;
                padding: 20px 22px 16px;
                border-bottom: 1px solid #e5e7eb;
                background: #fffbeb;
            }

            .archive-modal-icon {
                width: 42px;
                height: 42px;
                border-radius: 12px;
                background: #fef3c7;
                color: #92400e;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
            }

            .archive-modal-title {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 18px;
                font-weight: 950;
                color: #111827;
            }

            .archive-modal-copy {
                margin: 6px 0 0;
                color: #64748b;
                font-size: 13px;
                line-height: 1.55;
            }

            .archive-modal-body {
                padding: 18px 22px;
            }

            .archive-modal-warning {
                border: 1px solid #fed7aa;
                background: #fffbeb;
                color: #92400e;
                border-radius: 12px;
                padding: 12px 14px;
                font-size: 12.5px;
                line-height: 1.55;
                font-weight: 750;
            }

            .archive-modal-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                padding: 16px 22px 20px;
                border-top: 1px solid #e5e7eb;
                background: #f8fafc;
            }

            .archive-modal-actions .staff-button {
                min-width: 130px;
                justify-content: center;
            }

        </style>
    </x-slot>

    @php
        $geometryData = $parcel->geometry_geojson;

        if (is_string($geometryData)) {
            $decodedGeometry = json_decode($geometryData, true);
            $geometryData = json_last_error() === JSON_ERROR_NONE ? $decodedGeometry : $geometryData;
        }

        $geometryType = is_array($geometryData) ? ($geometryData['type'] ?? 'Encoded geometry') : 'Encoded geometry';
        $geometryText = $parcel->geometry_geojson
            ? json_encode($geometryData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : null;

        $landholdings = $parcel->landholdings ?? collect();
        $activeLandholdings = $landholdings->where('status', 'active');
        $activeArea = $activeLandholdings->sum(fn ($item) => (float) $item->area_hectares);
        $sourcePackages = $parcel->sourceRecordPackages ?? collect();
        $legacyRecords = $parcel->legacyRecords ?? collect();
        $attachedSourceCount = $sourcePackages->count() + $legacyRecords->count();
    @endphp

    <div class="parcel-page-stack">

        <section class="parcel-hero-card">
            <div class="parcel-hero-main">
                <div>
                    <p class="parcel-eyebrow">Main Parcel Record</p>
                    <h2 class="parcel-code-title">{{ $parcel->parcel_code }}</h2>
                    <p class="parcel-hero-subtitle">
                        Record ID: {{ $parcel->id }} ·
                        {{ $parcel->municipality ?? 'No municipality' }}{{ $parcel->barangay ? ', '.$parcel->barangay : '' }}
                    </p>

                    <div class="parcel-chip-row">
                        <span class="staff-badge
                            @if ($parcel->status === 'active') staff-badge-green
                            @elseif ($parcel->status === 'linked_application') staff-badge-blue
                            @elseif ($parcel->status === 'flagged') staff-badge-red
                            @else staff-badge-slate
                            @endif">
                            {{ $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'Status N/A' }}
                        </span>

                        <span class="staff-badge {{ $parcel->geometry_geojson ? 'staff-badge-green' : 'staff-badge-slate' }}">
                            {{ $parcel->geometry_geojson ? 'Mapped Geometry' : 'No Geometry' }}
                        </span>

                    </div>
                </div>

                <div class="parcel-summary-grid">
                    <div class="parcel-summary-card is-highlight">
                        <p class="parcel-meta-label">Linked Active Area</p>
                        <p class="parcel-summary-value">{{ number_format($activeArea, 4) }} ha</p>
                        <p class="parcel-summary-help">Computed from active landholding records linked to this parcel.</p>
                    </div>

                    <div class="parcel-summary-card">
                        <p class="parcel-meta-label">Landholding Records</p>
                        <p class="parcel-summary-value">{{ $landholdings->count() }} linked</p>
                        <p class="parcel-summary-help">{{ $activeLandholdings->count() }} active record(s)</p>
                    </div>

                    <div class="parcel-summary-card">
                        <p class="parcel-meta-label">Attached Source Records</p>
                        <p class="parcel-summary-value">{{ $attachedSourceCount }} attached</p>
                        <p class="parcel-summary-help">Packages and individual source records.</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="parcel-grid-two">
            <section class="staff-panel staff-panel-pad">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-5">
                    <div>
                        <h3 class="staff-panel-title">Parcel Reference Information</h3>
                        <p class="staff-panel-subtitle">Core encoded reference values used for DAR clearance review, source matching, and map display. This page does not execute ownership transfer or registry mutation.</p>
                    </div>
                </div>

                <div class="parcel-meta-grid">
                    <div class="parcel-meta-card">
                        <p class="parcel-meta-label">Title Number</p>
                        <p class="parcel-meta-value">{{ $parcel->title_no ?? 'N/A' }}</p>
                        <p class="parcel-meta-subvalue">{{ $parcel->title_type_label }}</p>
                    </div>

                    <div class="parcel-meta-card">
                        <p class="parcel-meta-label">Lot Number</p>
                        <p class="parcel-meta-value">{{ $parcel->lot_number ?? 'N/A' }}</p>
                    </div>

                    <div class="parcel-meta-card">
                        <p class="parcel-meta-label">Survey Plan Number</p>
                        <p class="parcel-meta-value">{{ $parcel->survey_plan_number ?? 'N/A' }}</p>
                    </div>

                    <div class="parcel-meta-card">
                        <p class="parcel-meta-label">Register of Deeds Office</p>
                        <p class="parcel-meta-value">{{ $parcel->rod_office ?? 'N/A' }}</p>
                    </div>

                    <div class="parcel-meta-card">
                        <p class="parcel-meta-label">Tax Declaration Number</p>
                        <p class="parcel-meta-value">{{ $parcel->tax_decl_no ?? 'N/A' }}</p>
                    </div>

                    <div class="parcel-meta-card">
                        <p class="parcel-meta-label">Total Area</p>
                        <p class="parcel-meta-value">{{ $parcel->area_square_meters ? number_format((float) $parcel->area_square_meters, 2).' sq. m.' : 'N/A' }}</p>
                        <p class="parcel-meta-subvalue">{{ $parcel->area_hectares ? number_format((float) $parcel->area_hectares, 4).' hectares' : 'No hectare conversion recorded' }}</p>
                    </div>

                    <div class="parcel-meta-card">
                        <p class="parcel-meta-label">DAR Clearance Scope</p>
                        <p class="parcel-meta-value">Agricultural land record</p>
                        <p class="parcel-meta-subvalue">For clearance review, monitoring, source matching, and map reference only.</p>
                    </div>

                    <div class="parcel-meta-card">
                        <p class="parcel-meta-label">Province</p>
                        <p class="parcel-meta-value">{{ $parcel->province ?? 'N/A' }}</p>
                    </div>

                    <div class="parcel-meta-card">
                        <p class="parcel-meta-label">Municipality</p>
                        <p class="parcel-meta-value">{{ $parcel->municipality ?? 'N/A' }}</p>
                    </div>

                    <div class="parcel-meta-card">
                        <p class="parcel-meta-label">Barangay</p>
                        <p class="parcel-meta-value">{{ $parcel->barangay ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="parcel-remarks-box">
                    <p class="parcel-meta-label">Remarks</p>
                    <p class="text-sm text-slate-700 leading-relaxed">
                        {{ $parcel->remarks ?? 'No remarks recorded.' }}
                    </p>
                </div>
            </section>

            <aside class="staff-panel staff-panel-pad">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="staff-panel-title">Geometry Reference</h3>
                        <p class="staff-panel-subtitle">Stored reference geometry for map visualization and parcel checking.</p>
                    </div>

                    <span class="staff-badge {{ $parcel->geometry_geojson ? 'staff-badge-green' : 'staff-badge-slate' }}">
                        {{ $parcel->geometry_geojson ? $geometryType : 'Not encoded' }}
                    </span>
                </div>

                @if ($parcel->geometry_geojson)
                    <div class="parcel-geometry-card">
                        <div class="parcel-geometry-summary-grid">
                            <div class="parcel-geometry-mini-card">
                                <p class="parcel-meta-label">Geometry Type</p>
                                <p class="parcel-meta-value">{{ $geometryType }}</p>
                            </div>

                            <div class="parcel-geometry-mini-card">
                                <p class="parcel-meta-label">Map Display</p>
                                <p class="parcel-meta-value">Available</p>
                            </div>

                            <div class="parcel-geometry-mini-card is-wide">
                                <p class="parcel-meta-label">Reference Note</p>
                                <p class="parcel-geometry-note">Stored for visualization and parcel checking.</p>
                            </div>
                        </div>

                        <a href="{{ route('staff.parcel-map.index') }}" class="parcel-map-action">
                            <span class="parcel-map-action-icon">
                                <i class="fa-solid fa-map-location-dot"></i>
                            </span>
                            <span>
                                <span class="parcel-map-action-title">Open Parcel Map</span>
                                <span class="parcel-map-action-help">View this parcel in the map viewer</span>
                            </span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <details class="parcel-code-box">
                        <summary>
                            <span class="parcel-code-summary-main">
                                <span class="parcel-code-summary-icon">
                                    <i class="fa-solid fa-code"></i>
                                </span>
                                <span>
                                    <span class="parcel-code-summary-title">Raw GeoJSON</span>
                                    <span class="parcel-code-summary-help">Developer/reference data</span>
                                </span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                        </summary>
                        <pre>{{ $geometryText }}</pre>
                    </details>
                @else
                    <div class="parcel-empty-state">
                        No geometry data has been encoded for this parcel.
                    </div>
                @endif
            </aside>
        </div>

        <section class="staff-panel staff-panel-pad">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
                <div>
                    <h3 class="staff-panel-title">Linked Landholding Records</h3>
                    <p class="staff-panel-subtitle">
                        Administrative landholding references linked to this parcel for hectare monitoring and record traceability.
                    </p>
                </div>

                <span class="staff-badge {{ $activeLandholdings->count() > 0 ? 'staff-badge-green' : 'staff-badge-slate' }}">
                    {{ $activeLandholdings->count() }} active
                </span>
            </div>

            @if ($landholdings->isEmpty())
                <div class="parcel-empty-state">
                    No landholding records are currently linked to this parcel.
                </div>
            @else
                <div class="parcel-landholding-card-list">
                    @foreach ($landholdings as $landholding)
                        <article class="parcel-landholding-card">
                            <div class="parcel-landholding-card-head">
                                <div>
                                    <p class="parcel-landholding-owner">
                                        @if ($landholding->landowner)
                                            @if (\Illuminate\Support\Facades\Route::has('staff.records.landowners.show'))
                                                <a href="{{ route('staff.records.landowners.show', $landholding->landowner) }}" class="staff-link">
                                                    {{ $landholding->landowner->full_name }}
                                                </a>
                                            @else
                                                {{ $landholding->landowner->full_name }}
                                            @endif
                                        @else
                                            No landowner linked
                                        @endif
                                    </p>
                                    <p class="parcel-landholding-owner-meta">Landholding reference linked to {{ $parcel->parcel_code }}</p>
                                </div>
                                <span class="staff-badge
                                    @if ($landholding->status === 'active') staff-badge-green
                                    @elseif ($landholding->status === 'historical') staff-badge-amber
                                    @else staff-badge-slate
                                    @endif">
                                    {{ $landholding->status ? ucwords(str_replace('_', ' ', $landholding->status)) : 'N/A' }}
                                </span>
                            </div>

                            <div class="parcel-landholding-detail-grid">
                                <div class="parcel-landholding-detail-card">
                                    <p class="parcel-landholding-detail-label">Area</p>
                                    <p class="parcel-landholding-detail-value">{{ number_format((float) $landholding->area_hectares, 4) }} ha</p>
                                </div>
                                <div class="parcel-landholding-detail-card">
                                    <p class="parcel-landholding-detail-label">Date Acquired</p>
                                    <p class="parcel-landholding-detail-value">{{ $landholding->date_acquired ? \Illuminate\Support\Carbon::parse($landholding->date_acquired)->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div class="parcel-landholding-detail-card">
                                    <p class="parcel-landholding-detail-label">Date Transferred</p>
                                    <p class="parcel-landholding-detail-value">{{ $landholding->date_transferred ? \Illuminate\Support\Carbon::parse($landholding->date_transferred)->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div class="parcel-landholding-detail-card">
                                    <p class="parcel-landholding-detail-label">Reference</p>
                                    <p class="parcel-landholding-detail-value">
                                        @if ($landholding->sourceApplication)
                                            <a href="{{ route('staff.applications.show', $landholding->sourceApplication) }}" class="staff-link">
                                                {{ $landholding->sourceApplication->application_code }}
                                            </a>
                                        @elseif ($landholding->source_reference_number ?? false)
                                            {{ $landholding->source_reference_number }}
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                </div>
                                <div class="parcel-landholding-detail-card is-wide">
                                    <p class="parcel-landholding-detail-label">Remarks</p>
                                    <p class="parcel-landholding-detail-value">{{ filled($landholding->remarks) ? $landholding->remarks : 'N/A' }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="staff-panel staff-panel-pad">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
                <div>
                    <h3 class="staff-panel-title">Attached Source Records</h3>
                    <p class="staff-panel-subtitle">
                        Digitized source records attached to this parcel for provenance, traceability, and review support.
                    </p>
                </div>

                <span class="staff-badge staff-badge-slate">
                    {{ $sourcePackages->count() }} package(s) · {{ $legacyRecords->count() }} individual record(s)
                </span>
            </div>

            @if ($sourcePackages->count() === 0 && $legacyRecords->count() === 0)
                <div class="parcel-empty-state">
                    No source records are currently attached to this parcel.
                </div>
            @else
                <div class="parcel-source-summary">
                    <div class="parcel-source-count-card">
                        <p class="parcel-meta-label">Source Packages</p>
                        <strong>{{ $sourcePackages->count() }}</strong>
                    </div>
                    <div class="parcel-source-count-card">
                        <p class="parcel-meta-label">Individual Source Records</p>
                        <strong>{{ $legacyRecords->count() }}</strong>
                    </div>
                </div>
            @endif

            @if ($sourcePackages->count() > 0)
                <div class="mt-5">
                    <h4 class="staff-panel-title text-base">Source Packages</h4>
                    <div class="parcel-source-card-grid">
                        @foreach ($sourcePackages as $package)
                            <article class="parcel-source-card">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="parcel-source-card-title">
                                            <a href="{{ route('staff.source-record-packages.show', $package) }}" class="staff-link">
                                                {{ $package->package_code }}
                                            </a>
                                        </p>
                                        <p class="parcel-source-card-meta">{{ $package->source_record_scope_label ?? 'Source package' }}</p>
                                    </div>
                                    <span class="staff-badge staff-badge-slate">{{ $package->status_label }}</span>
                                </div>

                                <div class="parcel-source-card-row">
                                    <span>Reference</span>
                                    <div>
                                        @if ($package->title_number)
                                            <div><strong>Title:</strong> {{ $package->title_number }}</div>
                                        @endif
                                        @if ($package->landholding_reference_number)
                                            <div><strong>Landholding:</strong> {{ $package->landholding_reference_number }}</div>
                                        @endif
                                        @if ($package->control_number)
                                            <div><strong>Clearance:</strong> {{ $package->control_number }}</div>
                                        @endif
                                        @if (! $package->title_number && ! $package->landholding_reference_number && ! $package->control_number)
                                            N/A
                                        @endif
                                    </div>
                                </div>

                                <div class="parcel-source-card-row">
                                    <span>Source</span>
                                    <div>{{ $package->source_book ?? 'N/A' }} · Page {{ $package->page_number ?? 'N/A' }}</div>
                                </div>

                                <div class="parcel-source-card-row">
                                    <span>Records</span>
                                    <div>{{ $package->records->count() }} created record(s)</div>
                                </div>

                                <div class="parcel-source-card-actions">
                                    <a href="{{ route('staff.source-record-packages.show', $package) }}" class="staff-button staff-button-light">
                                        View Package
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($legacyRecords->count() > 0)
                <div class="mt-5">
                    <h4 class="staff-panel-title text-base">Individual Source Records</h4>
                    <div class="parcel-source-card-grid">
                        @foreach ($legacyRecords as $record)
                            <article class="parcel-source-card">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="parcel-source-card-title">{{ $record->record_type_label }}</p>
                                        <p class="parcel-source-card-meta">{{ $record->source_book ?? 'N/A' }} · Page {{ $record->page_number ?? 'N/A' }}</p>
                                    </div>
                                    <span class="staff-badge
                                        @if ($record->origin === 'encoded') staff-badge-blue
                                        @elseif ($record->origin === 'imported') staff-badge-amber
                                        @else staff-badge-slate
                                        @endif">
                                        {{ $record->origin_label }}
                                    </span>
                                </div>

                                <div class="parcel-source-card-row">
                                    <span>Reference</span>
                                    <div>
                                        @if ($record->title_number)
                                            <div><strong>Title:</strong> {{ $record->title_number }}</div>
                                        @endif
                                        @if ($record->landholding_reference_number)
                                            <div><strong>Landholding:</strong> {{ $record->landholding_reference_number }}</div>
                                        @endif
                                        @if ($record->control_number)
                                            <div><strong>Clearance:</strong> {{ $record->control_number }}</div>
                                        @endif
                                        @if ($record->lot_number)
                                            <div><strong>Lot:</strong> {{ $record->lot_number }}</div>
                                        @endif
                                        @if (! $record->title_number && ! $record->landholding_reference_number && ! $record->control_number && ! $record->lot_number)
                                            N/A
                                        @endif
                                    </div>
                                </div>

                                <div class="parcel-source-card-row">
                                    <span>Package</span>
                                    <div>
                                        @if ($record->package)
                                            <a href="{{ route('staff.source-record-packages.show', $record->package) }}" class="staff-link">
                                                {{ $record->package->package_code }}
                                            </a>
                                        @else
                                            No package
                                        @endif
                                    </div>
                                </div>

                                <div class="parcel-source-card-actions">
                                    <a href="{{ route('staff.legacy-records.show', $record) }}" class="staff-button staff-button-light">
                                        View Record
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </div>

    <div id="parcel-archive-modal" class="archive-modal-backdrop" aria-hidden="true">
        <div class="archive-modal-card" role="dialog" aria-modal="true" aria-labelledby="parcel-archive-modal-title">
            <div class="archive-modal-header">
                <span class="archive-modal-icon" aria-hidden="true"><i class="fa-solid fa-box-archive"></i></span>
                <div>
                    <h2 id="parcel-archive-modal-title" class="archive-modal-title">Archive this parcel record?</h2>
                    <p class="archive-modal-copy">
                        This will remove the parcel from active staff workflows while preserving it for traceability and audit review.
                    </p>
                </div>
            </div>

            <div class="archive-modal-body">
                <div class="archive-modal-warning">
                    This is not a permanent deletion and does not transfer ownership, change landholding ownership, or mutate registry records.
                </div>
            </div>

            <div class="archive-modal-actions">
                <button type="button" class="staff-button staff-button-light" id="parcel-archive-modal-cancel">Cancel</button>
                <form method="POST" action="{{ route('staff.records.parcels.destroy', $parcel) }}" style="margin:0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="staff-button staff-button-danger">
                        <i class="fa-solid fa-box-archive"></i>
                        Archive Record
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const archiveModal = document.getElementById('parcel-archive-modal');
            const archiveOpen = document.getElementById('parcel-archive-modal-open');
            const archiveCancel = document.getElementById('parcel-archive-modal-cancel');

            function openArchiveModal() {
                if (! archiveModal) return;
                archiveModal.classList.add('is-open');
                archiveModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                archiveCancel?.focus();
            }

            function closeArchiveModal() {
                if (! archiveModal) return;
                archiveModal.classList.remove('is-open');
                archiveModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            archiveOpen?.addEventListener('click', openArchiveModal);
            archiveCancel?.addEventListener('click', closeArchiveModal);
            archiveModal?.addEventListener('click', function (event) {
                if (event.target === archiveModal) closeArchiveModal();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && archiveModal?.classList.contains('is-open')) closeArchiveModal();
            });
        });
    </script>

</x-staff-shell>
