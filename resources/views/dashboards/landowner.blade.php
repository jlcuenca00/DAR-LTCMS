@php
    use App\Models\LandTransferApplication;

    $displayName = $landowner?->full_name ?? auth()->user()->name;
    $cardValue = fn (string $label) => (int) (collect($dashboardCards)->firstWhere('label', $label)['value'] ?? 0);

    $statusClass = function (?string $status): string {
        return match ($status) {
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_APPROVED => 'is-green',
            LandTransferApplication::STATUS_DENIED,
            LandTransferApplication::STATUS_NOT_APPROVED => 'is-red',
            LandTransferApplication::STATUS_FOR_RELEASING => 'is-violet',
            LandTransferApplication::STATUS_ENDORSED_LTI,
            LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
            LandTransferApplication::STATUS_ENDORSED_PARPO => 'is-blue',
            default => 'is-amber',
        };
    };
@endphp

<x-landowner-shell title="Landowner Dashboard" active="dashboard">
    @push('styles')
        <style>
            .lo-dashboard-stack { display: grid; gap: 18px; }

            .lo-dashboard-hero {
                background: linear-gradient(120deg, #0f4b25 0%, #166534 62%, #1f7a3c 100%);
                border: 1px solid rgba(5, 46, 22, .28);
                border-radius: 16px;
                padding: 26px 28px;
                color: #ffffff;
                display: grid;
                grid-template-columns: minmax(0, 1.5fr) minmax(360px, .9fr);
                gap: 24px;
                align-items: center;
                box-shadow: 0 12px 28px rgba(15, 75, 37, .13);
            }

            .lo-dashboard-kicker {
                margin: 0;
                color: #bbf7d0;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: .17em;
                text-transform: uppercase;
            }

            .lo-dashboard-welcome {
                margin: 8px 0 0;
                font-size: clamp(25px, 3vw, 36px);
                line-height: 1.08;
                font-weight: 900;
                color: #ffffff;
            }

            .lo-dashboard-note {
                margin: 10px 0 0;
                max-width: 690px;
                color: #dcfce7;
                font-size: 13px;
                line-height: 1.55;
            }

            .lo-hero-stats {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                border: 1px solid rgba(255, 255, 255, .16);
                border-radius: 13px;
                background: rgba(5, 46, 22, .24);
                overflow: hidden;
            }

            .lo-hero-stat {
                min-width: 0;
                padding: 15px 14px;
                border-right: 1px solid rgba(255, 255, 255, .14);
            }

            .lo-hero-stat:last-child { border-right: 0; }
            .lo-hero-stat-value { display: block; font-size: 24px; line-height: 1; font-weight: 900; }
            .lo-hero-stat-label { display: block; margin-top: 7px; color: #bbf7d0; font-size: 10px; font-weight: 800; line-height: 1.3; }

            .lo-dashboard-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.75fr) minmax(320px, .8fr);
                gap: 18px;
                align-items: start;
            }

            .lo-dashboard-side { display: grid; gap: 18px; }

            .lo-dashboard-panel {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
                overflow: hidden;
            }

            .lo-dashboard-panel-header {
                padding: 18px 20px 15px;
                border-bottom: 1px solid #e8eeea;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 14px;
            }

            .lo-dashboard-panel-title { margin: 0; color: var(--lo-ink); font-size: 17px; line-height: 1.25; font-weight: 900; }
            .lo-dashboard-panel-copy { margin: 4px 0 0; color: var(--lo-muted); font-size: 12px; line-height: 1.45; }
            .lo-dashboard-link { color: var(--lo-green-800); text-decoration: none; font-size: 12px; font-weight: 900; white-space: nowrap; }
            .lo-dashboard-link:hover { text-decoration: underline; }

            .lo-application-list { display: grid; }
            .lo-application-row {
                display: grid;
                grid-template-columns: minmax(115px, .65fr) minmax(240px, 1.35fr) minmax(165px, .8fr) minmax(90px, .55fr);
                gap: 16px;
                align-items: center;
                padding: 15px 20px;
                border-bottom: 1px solid #edf1ee;
            }
            .lo-application-row:last-child { border-bottom: 0; }

            .lo-app-code { color: var(--lo-green-900); font-size: 13px; font-weight: 900; }
            .lo-app-parties { display: grid; gap: 3px; min-width: 0; }
            .lo-app-party { color: #344054; font-size: 12px; line-height: 1.35; overflow-wrap: anywhere; }
            .lo-app-party span { display: inline-block; width: 34px; color: #667085; font-size: 10px; font-weight: 900; text-transform: uppercase; }
            .lo-app-date { color: #667085; font-size: 12px; white-space: nowrap; }

            .lo-status-badge {
                display: inline-flex;
                align-items: center;
                width: fit-content;
                min-height: 26px;
                padding: 0 9px;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #475569;
                font-size: 10px;
                line-height: 1.2;
                font-weight: 900;
            }
            .lo-status-badge.is-green { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
            .lo-status-badge.is-red { background: #fee2e2; border-color: #fecaca; color: #b91c1c; }
            .lo-status-badge.is-blue { background: #dbeafe; border-color: #bfdbfe; color: #1d4ed8; }
            .lo-status-badge.is-violet { background: #ede9fe; border-color: #ddd6fe; color: #6d28d9; }
            .lo-status-badge.is-amber { background: #ffedd5; border-color: #fed7aa; color: #c2410c; }

            .lo-parcel-list { display: grid; }
            .lo-parcel-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 12px;
                align-items: center;
                padding: 14px 18px;
                border-bottom: 1px solid #edf1ee;
            }
            .lo-parcel-row:last-child { border-bottom: 0; }
            .lo-parcel-code { color: var(--lo-green-900); font-size: 13px; font-weight: 900; text-decoration: none; }
            .lo-parcel-code:hover { text-decoration: underline; }
            .lo-parcel-meta { margin-top: 3px; color: #667085; font-size: 11px; line-height: 1.4; }
            .lo-parcel-area { color: #0f172a; font-size: 12px; font-weight: 900; white-space: nowrap; }

            .lo-status-summary { padding: 8px 18px 12px; display: grid; }
            .lo-status-summary-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 10px 0;
                border-bottom: 1px solid #edf1ee;
            }
            .lo-status-summary-row:last-child { border-bottom: 0; }
            .lo-status-summary-label { color: #475569; font-size: 12px; font-weight: 750; }
            .lo-status-summary-count { min-width: 28px; text-align: center; color: var(--lo-green-900); font-size: 14px; font-weight: 900; }

            .lo-dashboard-empty { padding: 24px 20px; color: var(--lo-muted); font-size: 13px; line-height: 1.5; }

            @media (max-width: 1120px) {
                .lo-dashboard-hero,
                .lo-dashboard-grid { grid-template-columns: 1fr; }
            }

            @media (max-width: 760px) {
                .lo-dashboard-hero { padding: 22px 20px; }
                .lo-hero-stats { grid-template-columns: 1fr; }
                .lo-hero-stat { border-right: 0; border-bottom: 1px solid rgba(255, 255, 255, .14); }
                .lo-hero-stat:last-child { border-bottom: 0; }
                .lo-application-row { grid-template-columns: 1fr; gap: 8px; }
            }
        </style>
    @endpush

    <section class="lo-dashboard-stack">
        <article class="lo-dashboard-hero">
            <div>
                <p class="lo-dashboard-kicker">Landowner Portal</p>
                <h2 class="lo-dashboard-welcome">Welcome, {{ $displayName }}.</h2>
                <p class="lo-dashboard-note">Review only the parcel records and clearance applications linked to your account. All records remain subject to DAR review and separate legal or administrative procedures.</p>
            </div>

            <div class="lo-hero-stats" aria-label="Landowner record summary">
                <div class="lo-hero-stat">
                    <span class="lo-hero-stat-value">{{ $cardValue('Linked Parcels') }}</span>
                    <span class="lo-hero-stat-label">Linked parcels</span>
                </div>
                <div class="lo-hero-stat">
                    <span class="lo-hero-stat-value">{{ $cardValue('My Applications') }}</span>
                    <span class="lo-hero-stat-label">Applications</span>
                </div>
                <div class="lo-hero-stat">
                    <span class="lo-hero-stat-value">{{ $cardValue('Mapped Parcels') }}</span>
                    <span class="lo-hero-stat-label">Mapped parcels</span>
                </div>
            </div>
        </article>

        <section class="lo-dashboard-grid">
            <article class="lo-dashboard-panel">
                <header class="lo-dashboard-panel-header">
                    <div>
                        <h2 class="lo-dashboard-panel-title">Recent Application Status</h2>
                        <p class="lo-dashboard-panel-copy">Latest clearance applications linked to your landowner record.</p>
                    </div>
                    <a href="{{ route('landowner.applications.index') }}" class="lo-dashboard-link">View all →</a>
                </header>

                @if ($recentApplications->isEmpty())
                    <div class="lo-dashboard-empty">No clearance applications are currently linked to your account.</div>
                @else
                    <div class="lo-application-list">
                        @foreach ($recentApplications as $application)
                            <div class="lo-application-row">
                                <div class="lo-app-code">{{ $application->application_code }}</div>
                                <div class="lo-app-parties">
                                    <div class="lo-app-party"><span>From</span>{{ $application->transferorDisplayName() ?: 'N/A' }}</div>
                                    <div class="lo-app-party"><span>To</span>{{ $application->transfereeDisplayName() ?: 'N/A' }}</div>
                                </div>
                                <span class="lo-status-badge {{ $statusClass($application->status) }}">{{ $application->statusLabel() }}</span>
                                <div class="lo-app-date">{{ $application->updated_at?->format('M d, Y') ?? 'N/A' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <aside class="lo-dashboard-side">
                <article class="lo-dashboard-panel">
                    <header class="lo-dashboard-panel-header">
                        <div>
                            <h2 class="lo-dashboard-panel-title">My Parcel Snapshot</h2>
                            <p class="lo-dashboard-panel-copy">Recently linked landholding records.</p>
                        </div>
                        <a href="{{ route('landowner.parcels.index') }}" class="lo-dashboard-link">View all →</a>
                    </header>

                    @if ($recentLandholdings->isEmpty())
                        <div class="lo-dashboard-empty">No parcel records are currently linked to your account.</div>
                    @else
                        <div class="lo-parcel-list">
                            @foreach ($recentLandholdings->take(4) as $holding)
                                @php($parcel = $holding->parcel)
                                <div class="lo-parcel-row">
                                    <div>
                                        @if ($parcel)
                                            <a href="{{ route('landowner.parcels.show', $parcel) }}" class="lo-parcel-code">{{ $parcel->parcel_code }}</a>
                                        @else
                                            <span class="lo-parcel-code">Unlinked parcel</span>
                                        @endif
                                        <div class="lo-parcel-meta">{{ $parcel?->barangay ?? 'N/A' }}, {{ $parcel?->municipality ?? 'N/A' }} · {{ $parcel?->geometry_geojson ? 'Mapped' : 'No geometry' }}</div>
                                    </div>
                                    <div class="lo-parcel-area">{{ number_format((float) $holding->area_hectares, 4) }} ha</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>

                <article class="lo-dashboard-panel">
                    <header class="lo-dashboard-panel-header">
                        <div>
                            <h2 class="lo-dashboard-panel-title">Application Status Overview</h2>
                            <p class="lo-dashboard-panel-copy">Count of linked applications by stage.</p>
                        </div>
                    </header>

                    <div class="lo-status-summary">
                        @forelse ($statusSummary->where('count', '>', 0) as $summary)
                            <div class="lo-status-summary-row">
                                <span class="lo-status-summary-label">{{ $summary['label'] }}</span>
                                <span class="lo-status-summary-count">{{ $summary['count'] }}</span>
                            </div>
                        @empty
                            <div class="lo-dashboard-empty" style="padding-left:0; padding-right:0;">No application status data is available.</div>
                        @endforelse
                    </div>
                </article>
            </aside>
        </section>
    </section>
</x-landowner-shell>
