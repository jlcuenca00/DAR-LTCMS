@php
    use App\Models\LandTransferApplication;

    $displayName = $landowner?->full_name ?? auth()->user()->name;

    $statusLabel = function (?string $status): string {
        return match ($status) {
            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW => 'Pending Review by Legal Officer',
            LandTransferApplication::STATUS_ENDORSED_LTI => 'Endorsed to LTI Division',
            LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL => 'Endorsed to Chief Legal',
            LandTransferApplication::STATUS_ENDORSED_PARPO => 'Endorsed to PARPO II',
            LandTransferApplication::STATUS_FOR_RELEASING => 'For Releasing',
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_APPROVED => 'Released',
            LandTransferApplication::STATUS_DENIED,
            LandTransferApplication::STATUS_NOT_APPROVED => 'Denied',
            LandTransferApplication::STATUS_PENDING_REVIEW,
            LandTransferApplication::STATUS_DRAFT => 'Pending Review by Legal Officer',
            default => $status ? str($status)->replace('_', ' ')->title()->toString() : 'N/A',
        };
    };

    $statusClass = function (?string $status): string {
        return match ($status) {
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_APPROVED => 'status-released',
            LandTransferApplication::STATUS_DENIED,
            LandTransferApplication::STATUS_NOT_APPROVED => 'status-denied',
            LandTransferApplication::STATUS_ENDORSED_LTI,
            LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
            LandTransferApplication::STATUS_ENDORSED_PARPO => 'status-endorsed',
            LandTransferApplication::STATUS_FOR_RELEASING => 'status-releasing',
            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            LandTransferApplication::STATUS_PENDING_REVIEW,
            LandTransferApplication::STATUS_DRAFT => 'status-pending',
            default => 'status-pending',
        };
    };
@endphp

<x-landowner-shell
    title="Landowner Dashboard"
    active="dashboard"
>
    @push('styles')
        <style>
            .lo-dashboard-kpis {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 16px;
            }

            .lo-kpi-card {
                min-height: 118px;
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 12px;
                padding: 18px;
                display: flex;
                justify-content: space-between;
                gap: 16px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            }

            .lo-kpi-label {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #667085;
            }

            .lo-kpi-value {
                margin: 12px 0 0;
                font-size: 31px;
                line-height: 1;
                font-weight: 900;
                color: var(--lo-ink);
            }

            .lo-kpi-description {
                margin: 12px 0 0;
                font-size: 12px;
                color: var(--lo-muted);
                line-height: 1.45;
            }

            .lo-kpi-icon {
                width: 48px;
                height: 48px;
                border-radius: 10px;
                display: grid;
                place-items: center;
                color: #ffffff;
                background: var(--lo-green-800);
                flex: 0 0 auto;
                font-size: 17px;
            }

            .lo-kpi-icon.slate { background: #334155; }
            .lo-kpi-icon.green { background: var(--lo-green-800); }
            .lo-kpi-icon.amber { background: #ea580c; }
            .lo-kpi-icon.blue { background: #0f766e; }

            .lo-dashboard-grid {
                display: grid;
                grid-template-columns: minmax(0, 2fr) minmax(330px, 0.95fr);
                gap: 20px;
                align-items: start;
            }

            .lo-stack {
                display: grid;
                gap: 20px;
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
                line-height: 1.25;
                font-weight: 900;
                color: var(--lo-ink);
            }

            .lo-panel-subtitle {
                margin: 5px 0 0;
                font-size: 13px;
                color: var(--lo-muted);
                line-height: 1.45;
            }

            .lo-panel-body {
                padding: 18px 22px 22px;
            }

            .lo-action-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .lo-action-link {
                min-height: 54px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                border: 1px solid var(--lo-line);
                border-radius: 10px;
                background: #ffffff;
                padding: 12px 14px;
                color: var(--lo-green-900);
                text-decoration: none;
                font-size: 13px;
                font-weight: 900;
                transition: 160ms ease;
            }

            .lo-action-link:hover {
                background: var(--lo-green-50);
                border-color: #bbf7d0;
            }

            .lo-action-link i {
                width: 28px;
                height: 28px;
                display: grid;
                place-items: center;
                border-radius: 8px;
                background: #effaf2;
                border: 1px solid #bbf7d0;
                color: var(--lo-green-800);
                flex: 0 0 auto;
            }

            .lo-table-wrap {
                overflow-x: auto;
            }

            .lo-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
                min-width: 760px;
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
                padding: 13px 14px;
                border-bottom: 1px solid #edf0ee;
                color: #344054;
                vertical-align: top;
            }

            .lo-table tbody tr:last-child td {
                border-bottom: 0;
            }

            .lo-strong-link {
                color: var(--lo-green-900);
                text-decoration: none;
                font-weight: 900;
            }

            .lo-muted {
                color: var(--lo-muted);
            }

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
            }

            .status-released,
            .status-approved {
                background: #dcfce7;
                border: 1px solid #bbf7d0;
                color: #166534;
            }

            .status-denied {
                background: #fee2e2;
                border: 1px solid #fecaca;
                color: #b91c1c;
            }

            .status-pending {
                background: #ffedd5;
                border: 1px solid #fed7aa;
                color: #c2410c;
            }

            .status-endorsed {
                background: #e0f2fe;
                border: 1px solid #bae6fd;
                color: #0369a1;
            }

            .status-releasing {
                background: #ede9fe;
                border: 1px solid #ddd6fe;
                color: #6d28d9;
            }

            .status-draft {
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                color: #334155;
            }

            .lo-summary-list {
                display: grid;
                gap: 10px;
            }

            .lo-summary-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                border: 1px solid #edf0ee;
                background: #fbfcfb;
                border-radius: 10px;
                padding: 12px 14px;
            }

            .lo-summary-label {
                color: #344054;
                font-size: 13px;
                font-weight: 800;
            }

            .lo-summary-count {
                width: 32px;
                height: 32px;
                border-radius: 999px;
                display: grid;
                place-items: center;
                background: #effaf2;
                border: 1px solid #bbf7d0;
                color: var(--lo-green-900);
                font-weight: 900;
            }

            .lo-landholding-list {
                display: grid;
                gap: 10px;
            }

            .lo-landholding-card {
                border: 1px solid #edf0ee;
                border-radius: 10px;
                padding: 13px 14px;
                background: #fbfcfb;
            }

            .lo-landholding-top {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                align-items: flex-start;
            }

            .lo-landholding-code {
                margin: 0;
                color: var(--lo-green-900);
                font-weight: 900;
            }

            .lo-landholding-meta {
                margin: 4px 0 0;
                color: var(--lo-muted);
                font-size: 12px;
                line-height: 1.45;
            }

            .lo-empty {
                border: 1px dashed #cbd5d1;
                border-radius: 10px;
                background: #fbfcfb;
                padding: 22px;
                color: var(--lo-muted);
                font-size: 13px;
                line-height: 1.55;
            }

            @media (max-width: 1180px) {
                .lo-dashboard-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .lo-dashboard-grid { grid-template-columns: 1fr; }
            }

            @media (max-width: 640px) {
                .lo-dashboard-kpis,
                .lo-action-grid { grid-template-columns: 1fr; }
                .lo-panel-header { flex-direction: column; }
            }
        </style>
    @endpush

    <section class="lo-dashboard-kpis">
        @foreach ($dashboardCards as $card)
            <article class="lo-kpi-card">
                <div>
                    <p class="lo-kpi-label">{{ $card['label'] }}</p>
                    <p class="lo-kpi-value">{{ $card['value'] }}</p>
                    <p class="lo-kpi-description">{{ $card['description'] }}</p>
                </div>

                <div class="lo-kpi-icon {{ $card['tone'] ?? 'green' }}">
                    <i class="fa-solid {{ $card['icon'] }}"></i>
                </div>
            </article>
        @endforeach
    </section>

    <section class="lo-dashboard-grid">
        <div class="lo-stack">
            <article class="lo-panel">
                <div class="lo-panel-header">
                    <div>
                        <h2 class="lo-panel-title">My Clearance Applications</h2>
                        <p class="lo-panel-subtitle">Application status records where your landowner account is linked.</p>
                    </div>

                    <a href="{{ route('landowner.applications.index') }}" class="lo-button">View All →</a>
                </div>

                <div class="lo-panel-body">
                    @if ($recentApplications->isEmpty())
                        <div class="lo-empty">
                            No clearance applications are currently linked to your landowner account.
                        </div>
                    @else
                        <div class="lo-table-wrap">
                            <table class="lo-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Transferor</th>
                                        <th>Transferee</th>
                                        <th>Status</th>
                                        <th>Decision Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentApplications as $application)
                                        <tr>
                                            <td>
                                                <span class="lo-strong-link">{{ $application->application_code }}</span>
                                            </td>
                                            <td>{{ $application->transferorLandowner?->full_name ?? $application->transferor_name ?? 'N/A' }}</td>
                                            <td>{{ $application->transfereeLandowner?->full_name ?? $application->transferee_name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="lo-status-pill {{ $statusClass($application->status) }}">
                                                    {{ $statusLabel($application->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $application->reviewed_at?->format('M d, Y') ?? 'Pending' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </article>

            <article class="lo-panel">
                <div class="lo-panel-header">
                    <div>
                        <h2 class="lo-panel-title">Recent Landholding References</h2>
                        <p class="lo-panel-subtitle">Administrative landholding records linked to your account.</p>
                    </div>

                    <a href="{{ route('landowner.parcels.index') }}" class="lo-button">View Parcels →</a>
                </div>

                <div class="lo-panel-body">
                    @if ($recentLandholdings->isEmpty())
                        <div class="lo-empty">
                            No landholding references are currently linked to your account.
                        </div>
                    @else
                        <div class="lo-landholding-list">
                            @foreach ($recentLandholdings as $landholding)
                                <div class="lo-landholding-card">
                                    <div class="lo-landholding-top">
                                        <div>
                                            <p class="lo-landholding-code">{{ $landholding->parcel?->parcel_code ?? 'Unlinked Parcel Reference' }}</p>
                                            <p class="lo-landholding-meta">
                                                {{ $landholding->parcel?->barangay ?? 'N/A' }}, {{ $landholding->parcel?->municipality ?? 'N/A' }}
                                            </p>
                                        </div>

                                        <span class="lo-status-pill status-approved">
                                            {{ $landholding->status ? ucwords(str_replace('_', ' ', $landholding->status)) : 'N/A' }}
                                        </span>
                                    </div>

                                    <p class="lo-landholding-meta">
                                        {{ number_format((float) $landholding->area_hectares, 4) }} ha · Acquired: {{ $landholding->date_acquired?->format('M d, Y') ?? 'N/A' }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        </div>

        <aside class="lo-stack">
            <article class="lo-panel">
                <div class="lo-panel-header">
                    <div>
                        <h2 class="lo-panel-title">Quick Actions</h2>
                        <p class="lo-panel-subtitle">View your linked records and map references.</p>
                    </div>
                </div>

                <div class="lo-panel-body">
                    <div class="lo-action-grid">
                        <a href="{{ route('landowner.parcel-map.index') }}" class="lo-action-link">
                            <span>Open Map</span>
                            <i class="fa-solid fa-map-location-dot"></i>
                        </a>

                        <a href="{{ route('landowner.parcels.index') }}" class="lo-action-link">
                            <span>Parcel Records</span>
                            <i class="fa-solid fa-draw-polygon"></i>
                        </a>

                        <a href="{{ route('landowner.applications.index') }}" class="lo-action-link">
                            <span>Applications</span>
                            <i class="fa-solid fa-file-lines"></i>
                        </a>

                        <a href="{{ route('profile.edit') }}" class="lo-action-link">
                            <span>Profile</span>
                            <i class="fa-solid fa-user-gear"></i>
                        </a>
                    </div>
                </div>
            </article>

            <article class="lo-panel">
                <div class="lo-panel-header">
                    <div>
                        <h2 class="lo-panel-title">Application Status Summary</h2>
                        <p class="lo-panel-subtitle">Current status count for your linked clearance applications.</p>
                    </div>
                </div>

                <div class="lo-panel-body">
                    <div class="lo-summary-list">
                        @foreach ($statusSummary as $summary)
                            <div class="lo-summary-row">
                                <span class="lo-summary-label">{{ $summary['label'] }}</span>
                                <span class="lo-summary-count">{{ $summary['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>
        </aside>
    </section>
</x-landowner-shell>
