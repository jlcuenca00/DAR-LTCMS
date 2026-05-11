<x-geodetic-shell
    title="Geodetic Dashboard"
    subtitle="Read-only parcel, map, landholding, and clearance application reference workspace."
    active="dashboard"
>
    <style>
        .geo-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .geo-card {
            background: #ffffff;
            border: 1px solid #d6ded8;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            min-height: 124px;
        }

        .geo-card-label {
            margin: 0;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            color: #64748b;
        }

        .geo-card-value {
            margin: 12px 0 0;
            font-family: var(--heading-font);
            font-size: 32px;
            line-height: 1;
            font-weight: 900;
            color: #111827;
        }

        .geo-card-description {
            margin: 12px 0 0;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.45;
        }

        .geo-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            color: #ffffff;
            flex: 0 0 auto;
            font-size: 18px;
        }

        .geo-card-icon.green { background: #15803d; }
        .geo-card-icon.blue { background: #0f766e; }
        .geo-card-icon.slate { background: #475569; }
        .geo-card-icon.amber { background: #d97706; }

        .geo-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
            gap: 24px;
        }

        .geo-panel {
            background: #ffffff;
            border: 1px solid #d6ded8;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .geo-panel-header {
            padding: 20px 22px 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .geo-panel-title {
            margin: 0;
            font-family: var(--heading-font);
            font-size: 17px;
            font-weight: 900;
            color: #111827;
        }

        .geo-panel-subtitle {
            margin: 5px 0 0;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
        }

        .geo-panel-link {
            font-size: 13px;
            font-weight: 900;
            color: #0f766e;
            text-decoration: none;
            white-space: nowrap;
        }

        .geo-panel-link:hover {
            text-decoration: underline;
        }

        .geo-quick-list,
        .geo-stack-list {
            padding: 20px 22px 22px;
            display: grid;
            gap: 10px;
        }

        .geo-quick-link {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            text-decoration: none;
            border: 1px solid #d6ded8;
            border-radius: 10px;
            padding: 14px 15px;
            background: #f8faf9;
            transition: 160ms ease;
            min-height: 70px;
        }

        .geo-quick-link:hover {
            border-color: #5eead4;
            background: #f0fdfa;
        }

        .geo-quick-title {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
            color: #115e59;
        }

        .geo-quick-desc {
            margin: 3px 0 0;
            font-size: 12px;
            color: #6b7280;
        }

        .geo-status-row {
            display: grid;
            gap: 7px;
        }

        .geo-status-top {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
            font-size: 13px;
        }

        .geo-status-label {
            font-weight: 800;
            color: #374151;
        }

        .geo-status-count {
            font-weight: 900;
            color: #111827;
        }

        .geo-status-track {
            height: 8px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .geo-status-fill {
            height: 100%;
            border-radius: 999px;
            background: #0f766e;
        }

        .geo-table-wrap {
            padding: 16px 22px 22px;
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
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
        }

        .geo-table td {
            padding: 13px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            white-space: nowrap;
            vertical-align: top;
        }

        .geo-code-link {
            color: #0f766e;
            font-weight: 900;
            text-decoration: none;
        }

        .geo-code-link:hover {
            text-decoration: underline;
        }

        .geo-badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 900;
            border: 1px solid;
        }

        .geo-badge-approved {
            background: #dcfce7;
            border-color: #bbf7d0;
            color: #166534;
        }

        .geo-badge-pending_review {
            background: #ffedd5;
            border-color: #fed7aa;
            color: #c2410c;
        }

        .geo-badge-not_approved {
            background: #fee2e2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .geo-badge-draft {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #475569;
        }

        .geo-muted {
            color: #6b7280;
        }

        .geo-kpi-strip {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            padding: 20px 22px 22px;
        }

        .geo-kpi-item {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 15px;
            background: #ffffff;
        }

        .geo-kpi-label {
            margin: 0;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
        }

        .geo-kpi-value {
            margin: 8px 0 0;
            font-family: var(--heading-font);
            font-size: 24px;
            font-weight: 900;
            color: #111827;
        }

        .geo-list-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 13px 14px;
            background: #ffffff;
        }

        .geo-list-title {
            margin: 0;
            font-size: 13px;
            font-weight: 900;
            color: #111827;
        }

        .geo-list-meta {
            margin: 4px 0 0;
            font-size: 11.5px;
            color: #6b7280;
            line-height: 1.45;
        }

        .geo-empty {
            padding: 24px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
        }

        @media (max-width: 1180px) {
            .geo-dashboard-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .geo-main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .geo-dashboard-grid,
            .geo-kpi-strip {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <section class="geo-dashboard-grid">
        @foreach ($dashboardCards as $card)
            <article class="geo-card">
                <div>
                    <p class="geo-card-label">{{ $card['label'] }}</p>
                    <p class="geo-card-value">{{ number_format($card['value']) }}</p>
                    <p class="geo-card-description">{{ $card['description'] }}</p>
                </div>

                <div class="geo-card-icon {{ $card['tone'] }}">
                    <i class="fa-solid {{ $card['icon'] }}"></i>
                </div>
            </article>
        @endforeach
    </section>

    <section class="geo-main-grid">
        <div class="geo-panel">
            <div class="geo-panel-header">
                <div>
                    <h2 class="geo-panel-title">Recent Parcel References</h2>
                    <p class="geo-panel-subtitle">Latest main parcel records available for read-only technical review.</p>
                </div>

                <a href="{{ route('geodetic.parcels.index') }}" class="geo-panel-link">View Parcels →</a>
            </div>

            <div class="geo-table-wrap">
                <table class="geo-table">
                    <thead>
                        <tr>
                            <th>Parcel Code</th>
                            <th>Title No.</th>
                            <th>Location</th>
                            <th>Area</th>
                            <th>Map</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($recentParcels as $parcel)
                            <tr>
                                <td>
                                    <a href="{{ route('geodetic.parcels.show', $parcel) }}" class="geo-code-link">
                                        {{ $parcel->parcel_code }}
                                    </a>
                                </td>
                                <td>{{ $parcel->title_no ?? 'N/A' }}</td>
                                <td>{{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }}</td>
                                <td>{{ number_format((float) $parcel->area_hectares, 4) }} ha</td>
                                <td>
                                    @if ($parcel->geometry_geojson)
                                        <span class="geo-badge geo-badge-approved">Mapped</span>
                                    @else
                                        <span class="geo-badge geo-badge-draft">No Geometry</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="geo-empty">No parcel reference records found yet.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="geo-panel">
            <div class="geo-panel-header">
                <div>
                    <h2 class="geo-panel-title">Quick Actions</h2>
                    <p class="geo-panel-subtitle">Read-only geodetic reference tools.</p>
                </div>
            </div>

            <div class="geo-quick-list">
                <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-quick-link">
                    <div>
                        <p class="geo-quick-title">Open Parcel Map Viewer</p>
                        <p class="geo-quick-desc">Review mapped parcel references in read-only mode.</p>
                    </div>
                    <i class="fa-solid fa-map"></i>
                </a>

                <a href="{{ route('geodetic.parcels.index') }}" class="geo-quick-link">
                    <div>
                        <p class="geo-quick-title">Review Parcel References</p>
                        <p class="geo-quick-desc">Open parcel and landholding reference records.</p>
                    </div>
                    <i class="fa-solid fa-map-location-dot"></i>
                </a>

                <a href="{{ route('geodetic.applications.index') }}" class="geo-quick-link">
                    <div>
                        <p class="geo-quick-title">Review Application References</p>
                        <p class="geo-quick-desc">View clearance applications without workflow controls.</p>
                    </div>
                    <i class="fa-solid fa-file-lines"></i>
                </a>
            </div>
        </div>
    </section>

    <section class="geo-main-grid">
        <div class="geo-panel">
            <div class="geo-panel-header">
                <div>
                    <h2 class="geo-panel-title">Recent Application References</h2>
                    <p class="geo-panel-subtitle">Clearance applications visible for geodetic context only.</p>
                </div>

                <a href="{{ route('geodetic.applications.index') }}" class="geo-panel-link">View Applications →</a>
            </div>

            <div class="geo-table-wrap">
                <table class="geo-table">
                    <thead>
                        <tr>
                            <th>Application Code</th>
                            <th>Transferor</th>
                            <th>Transferee</th>
                            <th>Status</th>
                            <th>Parcel Count</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($recentApplications as $application)
                            <tr>
                                <td class="geo-code-link">{{ $application->application_code }}</td>
                                <td>{{ $application->transferor_name ?? $application->transferorLandowner?->full_name ?? 'N/A' }}</td>
                                <td>{{ $application->transferee_name ?? $application->transfereeLandowner?->full_name ?? 'N/A' }}</td>
                                <td>
                                    <span class="geo-badge geo-badge-{{ $application->status }}">
                                        {{ $application->status === 'approved'
                                            ? 'Approved Clearance'
                                            : ucwords(str_replace('_', ' ', $application->status)) }}
                                    </span>
                                </td>
                                <td>{{ $application->applicationParcels->count() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="geo-empty">No application reference records found yet.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="geo-panel">
            <div class="geo-panel-header">
                <div>
                    <h2 class="geo-panel-title">Access Summary</h2>
                    <p class="geo-panel-subtitle">Geodetic portal limits and reference totals.</p>
                </div>
            </div>

            <div class="geo-kpi-strip">
                <div class="geo-kpi-item">
                    <p class="geo-kpi-label">Pending Review</p>
                    <p class="geo-kpi-value">{{ number_format($pendingApplications) }}</p>
                </div>

                <div class="geo-kpi-item">
                    <p class="geo-kpi-label">Final Decisions</p>
                    <p class="geo-kpi-value">{{ number_format($finalizedApplications) }}</p>
                </div>
            </div>

            <div class="geo-stack-list">
                @foreach ($statusDistribution as $item)
                    <div class="geo-status-row">
                        <div class="geo-status-top">
                            <span class="geo-status-label">{{ $item['label'] }}</span>
                            <span class="geo-status-count">{{ number_format($item['count']) }}</span>
                        </div>
                        <div class="geo-status-track">
                            <div class="geo-status-fill" style="width: {{ $item['percentage'] }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="geo-main-grid">
        <div class="geo-panel">
            <div class="geo-panel-header">
                <div>
                    <h2 class="geo-panel-title">Municipality Parcel Breakdown</h2>
                    <p class="geo-panel-subtitle">Top parcel reference locations currently encoded in the system.</p>
                </div>
            </div>

            <div class="geo-stack-list">
                @forelse ($municipalityBreakdown as $row)
                    <div class="geo-list-card">
                        <p class="geo-list-title">{{ $row['municipality'] }}</p>
                        <p class="geo-list-meta">{{ number_format($row['total']) }} parcel reference(s)</p>
                    </div>
                @empty
                    <div class="geo-empty">No municipality parcel data available yet.</div>
                @endforelse
            </div>
        </div>

        <div class="geo-panel">
            <div class="geo-panel-header">
                <div>
                    <h2 class="geo-panel-title">Read-Only Restrictions</h2>
                    <p class="geo-panel-subtitle">Controls intentionally unavailable to geodetic users.</p>
                </div>
            </div>

            <div class="geo-stack-list">
                <div class="geo-list-card">
                    <p class="geo-list-title">No approval controls</p>
                    <p class="geo-list-meta">Geodetic users are not primary approving users.</p>
                </div>

                <div class="geo-list-card">
                    <p class="geo-list-title">No document upload or deletion</p>
                    <p class="geo-list-meta">Supporting document management remains staff-side.</p>
                </div>

                <div class="geo-list-card">
                    <p class="geo-list-title">No ownership or registry mutation</p>
                    <p class="geo-list-meta">The system is for clearance processing and monitoring only.</p>
                </div>
            </div>
        </div>
    </section>
</x-geodetic-shell>
