<x-staff-shell
    title="Parcel Details"
    active="parcel-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.parcel-map.index') }}" class="staff-button staff-button-primary">
            <i class="fa-solid fa-map-location-dot"></i>
            Back to Map
        </a>

        <a href="{{ route('staff.records.parcels.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Parcel Records
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .parcel-hero {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 18px;
                align-items: start;
            }

            .parcel-code-title {
                margin: 4px 0 0;
                font-size: clamp(24px, 3vw, 34px);
                line-height: 1.05;
                font-weight: 900;
                letter-spacing: -0.03em;
                color: #0f172a;
            }

            .parcel-meta-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .parcel-meta-card {
                border: 1px solid #e5e7eb;
                background: #f9fafb;
                border-radius: 11px;
                padding: 14px 15px;
                min-height: 76px;
            }

            .parcel-meta-label,
            .parcel-section-label {
                margin: 0 0 6px;
                font-size: 10.5px;
                font-weight: 900;
                letter-spacing: 0.13em;
                text-transform: uppercase;
                color: #64748b;
            }

            .parcel-meta-value {
                margin: 0;
                font-size: 14px;
                font-weight: 800;
                color: #111827;
                line-height: 1.35;
                word-break: break-word;
            }

            .parcel-meta-subvalue {
                margin-top: 3px;
                font-size: 12.5px;
                color: #6b7280;
                line-height: 1.35;
            }

            .parcel-side-card {
                border-radius: 12px;
                border: 1px solid #facc15;
                background: #fffbeb;
                padding: 18px;
            }

            .parcel-side-title {
                margin: 0;
                font-size: 15px;
                font-weight: 900;
                color: #92400e;
            }

            .parcel-side-copy {
                margin: 8px 0 0;
                font-size: 13px;
                line-height: 1.65;
                color: #92400e;
            }

            .parcel-geometry-card {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
            }

            .parcel-code-box {
                margin-top: 14px;
                border: 1px solid #d1d5db;
                border-radius: 10px;
                overflow: hidden;
                background: #f8fafc;
            }

            .parcel-code-box summary {
                cursor: pointer;
                padding: 13px 15px;
                font-size: 13px;
                font-weight: 900;
                color: #14532d;
                list-style: none;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }

            .parcel-code-box summary::-webkit-details-marker { display: none; }

            .parcel-code-box pre {
                margin: 0;
                max-height: 340px;
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
                border-radius: 11px;
                padding: 18px;
                color: #64748b;
                background: #f8fafc;
                font-size: 13px;
            }

            .parcel-source-summary {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
                margin-top: 14px;
            }

            .parcel-source-count-card {
                border: 1px solid #e5e7eb;
                border-radius: 10px;
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

            @media (max-width: 1024px) {
                .parcel-hero,
                .parcel-main-grid {
                    grid-template-columns: 1fr !important;
                }
            }

            @media (max-width: 760px) {
                .parcel-meta-grid,
                .parcel-geometry-card,
                .parcel-source-summary {
                    grid-template-columns: 1fr;
                }
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
    @endphp

    <section class="staff-scope-banner">
        <div>
            <h3>Parcel Record Scope</h3>
            <p>
                This page displays a main parcel reference record for staff review, mapping, and clearance processing support only.
                It does not transfer ownership, mutate registry records, or finalize any legal land transaction.
            </p>
        </div>
        <span class="staff-scope-pill">Reference Record Only</span>
    </section>

    <section class="staff-panel staff-panel-pad">
        <div class="parcel-hero">
            <div>
                <p class="parcel-section-label">Main Parcel Record</p>
                <h2 class="parcel-code-title">{{ $parcel->parcel_code }}</h2>
                <p class="staff-panel-subtitle">
                    Record ID: {{ $parcel->id }} ·
                    {{ $parcel->municipality ?? 'No municipality' }}{{ $parcel->barangay ? ', '.$parcel->barangay : '' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2 justify-start lg:justify-end">
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
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 parcel-main-grid">
        <section class="staff-panel staff-panel-pad xl:col-span-2">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-5">
                <div>
                    <h3 class="staff-panel-title">Parcel Reference Information</h3>
                    <p class="staff-panel-subtitle">Core encoded reference values used for application review and map display.</p>
                </div>
            </div>

            <div class="parcel-meta-grid">
                <div class="parcel-meta-card">
                    <p class="parcel-meta-label">Title Number</p>
                    <p class="parcel-meta-value">{{ $parcel->title_no ?? 'N/A' }}</p>
                </div>

                <div class="parcel-meta-card">
                    <p class="parcel-meta-label">Tax Declaration Number</p>
                    <p class="parcel-meta-value">{{ $parcel->tax_decl_no ?? 'N/A' }}</p>
                </div>

                <div class="parcel-meta-card">
                    <p class="parcel-meta-label">Area</p>
                    <p class="parcel-meta-value">{{ number_format((float) $parcel->area_hectares, 4) }} hectares</p>
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

            <div class="mt-5 border border-slate-200 rounded-xl p-4 bg-white">
                <p class="parcel-meta-label">Remarks</p>
                <p class="text-sm text-slate-700 leading-relaxed">
                    {{ $parcel->remarks ?? 'No remarks recorded.' }}
                </p>
            </div>
        </section>

        <aside class="staff-panel staff-panel-pad">
            <h3 class="staff-panel-title">Review Summary</h3>
            <p class="staff-panel-subtitle">Quick reference for staff checking and traceability.</p>

            <div class="mt-4 space-y-3">
                <div class="parcel-meta-card bg-green-50 border-green-200">
                    <p class="parcel-meta-label text-green-800">Linked Active Area</p>
                    <p class="parcel-meta-value text-green-950">{{ number_format($activeArea, 4) }} ha</p>
                    <p class="parcel-meta-subvalue">From active landholding records linked to this parcel.</p>
                </div>

                <div class="parcel-meta-card">
                    <p class="parcel-meta-label">Landholding Records</p>
                    <p class="parcel-meta-value">{{ $landholdings->count() }} linked record(s)</p>
                    <p class="parcel-meta-subvalue">{{ $activeLandholdings->count() }} active record(s)</p>
                </div>

                <div class="parcel-meta-card">
                    <p class="parcel-meta-label">Attached Source Records</p>
                    <p class="parcel-meta-value">{{ $sourcePackages->count() + $legacyRecords->count() }} attached</p>
                    <p class="parcel-meta-subvalue">Packages and individual source records.</p>
                </div>
            </div>
        </aside>
    </div>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
            <div>
                <h3 class="staff-panel-title">Linked Landholding Records</h3>
                <p class="staff-panel-subtitle">
                    Administrative landholding references linked to this parcel. These records support hectare monitoring only.
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
            <div class="staff-table-wrap">
                <table class="staff-table">
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
                                <td>
                                    @if ($landholding->landowner)
                                        @if (\Illuminate\Support\Facades\Route::has('staff.records.landowners.show'))
                                            <a href="{{ route('staff.records.landowners.show', $landholding->landowner) }}" class="staff-link">
                                                {{ $landholding->landowner->full_name }}
                                            </a>
                                        @else
                                            <span class="font-bold text-slate-900">{{ $landholding->landowner->full_name }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">No landowner linked</span>
                                    @endif
                                </td>
                                <td class="font-bold text-slate-900">
                                    {{ number_format((float) $landholding->area_hectares, 4) }} ha
                                </td>
                                <td>
                                    <span class="staff-badge
                                        @if ($landholding->status === 'active') staff-badge-green
                                        @elseif ($landholding->status === 'historical') staff-badge-amber
                                        @elseif ($landholding->status === 'inactive') staff-badge-slate
                                        @else staff-badge-slate
                                        @endif">
                                        {{ $landholding->status ? ucwords(str_replace('_', ' ', $landholding->status)) : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <div><span class="text-slate-500">Acquired:</span> {{ $landholding->date_acquired ? \Illuminate\Support\Carbon::parse($landholding->date_acquired)->format('M d, Y') : 'N/A' }}</div>
                                    <div><span class="text-slate-500">Transferred:</span> {{ $landholding->date_transferred ? \Illuminate\Support\Carbon::parse($landholding->date_transferred)->format('M d, Y') : 'N/A' }}</div>
                                </td>
                                <td>
                                    @if ($landholding->sourceApplication)
                                        <a href="{{ route('staff.applications.show', $landholding->sourceApplication) }}" class="staff-link">
                                            {{ $landholding->sourceApplication->application_code }}
                                        </a>
                                    @elseif ($landholding->source_reference_no ?? false)
                                        {{ $landholding->source_reference_no }}
                                    @else
                                        <span class="text-slate-400">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $landholding->remarks ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
            <div>
                <h3 class="staff-panel-title">Geometry Reference</h3>
                <p class="staff-panel-subtitle">
                    Stored map geometry for visualization and parcel reference checking. This is not a registry mutation record.
                </p>
            </div>

            <span class="staff-badge {{ $parcel->geometry_geojson ? 'staff-badge-green' : 'staff-badge-slate' }}">
                {{ $parcel->geometry_geojson ? $geometryType : 'Not encoded' }}
            </span>
        </div>

        @if ($parcel->geometry_geojson)
            <div class="parcel-geometry-card mt-4">
                <div class="parcel-meta-card">
                    <p class="parcel-meta-label">Geometry Type</p>
                    <p class="parcel-meta-value">{{ $geometryType }}</p>
                </div>
                <div class="parcel-meta-card">
                    <p class="parcel-meta-label">Map Display</p>
                    <p class="parcel-meta-value">Available</p>
                </div>
                <div class="parcel-meta-card">
                    <p class="parcel-meta-label">Action</p>
                    <a href="{{ route('staff.parcel-map.index') }}" class="staff-link">Open Parcel Map →</a>
                </div>
            </div>

            <details class="parcel-code-box">
                <summary>
                    <span><i class="fa-solid fa-code mr-2"></i>View raw GeoJSON</span>
                    <span class="text-xs text-slate-500">Developer/reference data</span>
                </summary>
                <pre>{{ $geometryText }}</pre>
            </details>
        @else
            <div class="parcel-empty-state mt-4">
                No geometry data has been encoded for this parcel.
            </div>
        @endif
    </section>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
            <div>
                <h3 class="staff-panel-title">Attached Source Records</h3>
                <p class="staff-panel-subtitle">
                    Digitized source records attached to this parcel for provenance, traceability, and review support only.
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
                <div class="staff-table-wrap mt-3">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Package Code</th>
                                <th>Status</th>
                                <th>References</th>
                                <th>Source</th>
                                <th>Records</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sourcePackages as $package)
                                <tr>
                                    <td>
                                        <a href="{{ route('staff.source-record-packages.show', $package) }}" class="staff-link">
                                            {{ $package->package_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="staff-badge staff-badge-slate">{{ $package->status_label }}</span>
                                    </td>
                                    <td>
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
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $package->source_book ?? 'N/A' }}</div>
                                        <div class="text-xs text-slate-500">Page: {{ $package->page_number ?? 'N/A' }}</div>
                                    </td>
                                    <td>{{ $package->records->count() }}</td>
                                    <td>
                                        <a href="{{ route('staff.source-record-packages.show', $package) }}" class="staff-button staff-button-light">
                                            View Package
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($legacyRecords->count() > 0)
            <div class="mt-5">
                <h4 class="staff-panel-title text-base">Individual Source Records</h4>
                <div class="staff-table-wrap mt-3">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Origin</th>
                                <th>References</th>
                                <th>Source</th>
                                <th>Package</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($legacyRecords as $record)
                                <tr>
                                    <td class="font-bold text-slate-900">{{ $record->record_type_label }}</td>
                                    <td>
                                        <span class="staff-badge
                                            @if ($record->origin === 'encoded') staff-badge-blue
                                            @elseif ($record->origin === 'imported') staff-badge-amber
                                            @else staff-badge-slate
                                            @endif">
                                            {{ $record->origin_label }}
                                        </span>
                                    </td>
                                    <td>
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
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $record->source_book ?? 'N/A' }}</div>
                                        <div class="text-xs text-slate-500">Page: {{ $record->page_number ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        @if ($record->package)
                                            <a href="{{ route('staff.source-record-packages.show', $record->package) }}" class="staff-link">
                                                {{ $record->package->package_code }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">No package</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('staff.legacy-records.show', $record) }}" class="staff-button staff-button-light">
                                            View Record
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
</x-staff-shell>
