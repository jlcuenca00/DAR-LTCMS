@php
    $cardValue = fn (string $label) => (int) (collect($dashboardCards)->firstWhere('label', $label)['value'] ?? 0);
    $totalParcels = $cardValue('Parcel References');
    $mappedParcels = $cardValue('Mapped Parcels');
    $coveragePercent = $totalParcels > 0 ? min(100, round(($mappedParcels / $totalParcels) * 100)) : 0;
@endphp

<x-geodetic-shell title="Geodetic Dashboard" active="dashboard">
    <style>
        .geo-dashboard-stack { display: grid; gap: 18px; }

        .geo-dashboard-hero {
            background: linear-gradient(120deg, #0f4b25 0%, #166534 62%, #1f7a3c 100%);
            border: 1px solid rgba(5, 46, 22, .28);
            border-radius: 16px;
            padding: 26px 28px;
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(390px, .9fr);
            gap: 24px;
            align-items: center;
            color: #ffffff;
            box-shadow: 0 12px 28px rgba(15, 75, 37, .13);
        }

        .geo-dashboard-kicker {
            margin: 0;
            color: #bbf7d0;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .17em;
            text-transform: uppercase;
        }

        .geo-dashboard-title {
            margin: 8px 0 0;
            color: #ffffff;
            font-size: clamp(25px, 3vw, 36px);
            line-height: 1.08;
            font-weight: 900;
        }

        .geo-dashboard-copy {
            margin: 10px 0 0;
            max-width: 680px;
            color: #dcfce7;
            font-size: 13px;
            line-height: 1.55;
        }

        .geo-dashboard-action {
            margin-top: 18px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 9px;
            background: #ffffff;
            color: #14532d;
            text-decoration: none;
            font-size: 12px;
            font-weight: 900;
        }

        .geo-hero-summary {
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 13px;
            background: rgba(5, 46, 22, .24);
            overflow: hidden;
        }

        .geo-hero-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .geo-hero-stat {
            padding: 15px 14px;
            border-right: 1px solid rgba(255, 255, 255, .14);
        }

        .geo-hero-stat:last-child { border-right: 0; }
        .geo-hero-stat-value { display: block; font-size: 24px; line-height: 1; font-weight: 900; }
        .geo-hero-stat-label { display: block; margin-top: 7px; color: #bbf7d0; font-size: 10px; font-weight: 800; line-height: 1.3; }

        .geo-coverage { padding: 13px 14px 15px; border-top: 1px solid rgba(255, 255, 255, .14); }
        .geo-coverage-top { display: flex; justify-content: space-between; gap: 12px; color: #dcfce7; font-size: 11px; font-weight: 800; }
        .geo-coverage-track { height: 7px; margin-top: 9px; border-radius: 999px; background: rgba(255, 255, 255, .16); overflow: hidden; }
        .geo-coverage-fill { height: 100%; border-radius: 999px; background: #ffffff; }

        .geo-dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.75fr) minmax(310px, .75fr);
            gap: 18px;
            align-items: start;
        }

        .geo-dashboard-panel {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .geo-dashboard-panel-header {
            padding: 18px 20px 15px;
            border-bottom: 1px solid #e8eeea;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
        }

        .geo-dashboard-panel-title { margin: 0; color: var(--geo-ink); font-size: 17px; line-height: 1.25; font-weight: 900; }
        .geo-dashboard-panel-copy { margin: 4px 0 0; color: var(--geo-muted); font-size: 12px; line-height: 1.45; }
        .geo-dashboard-link { color: var(--geo-green-800); text-decoration: none; font-size: 12px; font-weight: 900; white-space: nowrap; }
        .geo-dashboard-link:hover { text-decoration: underline; }

        .geo-recent-list { display: grid; }
        .geo-recent-row {
            display: grid;
            grid-template-columns: minmax(125px, .65fr) minmax(210px, 1.15fr) minmax(130px, .65fr) minmax(100px, .5fr);
            gap: 16px;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #edf1ee;
        }
        .geo-recent-row:last-child { border-bottom: 0; }
        .geo-recent-code { color: var(--geo-green-900); font-size: 13px; font-weight: 900; text-decoration: none; }
        .geo-recent-code:hover { text-decoration: underline; }
        .geo-recent-meta { color: #475569; font-size: 12px; line-height: 1.4; }
        .geo-recent-sub { margin-top: 2px; color: #667085; font-size: 11px; }
        .geo-recent-area { color: #0f172a; font-size: 12px; font-weight: 900; white-space: nowrap; }

        .geo-state-badge {
            display: inline-flex;
            width: fit-content;
            min-height: 26px;
            align-items: center;
            padding: 0 9px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }
        .geo-state-badge.is-mapped { background: #dcfce7; border-color: #bbf7d0; color: #166534; }

        .geo-municipality-list { padding: 8px 18px 13px; display: grid; }
        .geo-municipality-row { padding: 11px 0; border-bottom: 1px solid #edf1ee; }
        .geo-municipality-row:last-child { border-bottom: 0; }
        .geo-municipality-top { display: flex; justify-content: space-between; gap: 12px; color: #475569; font-size: 12px; }
        .geo-municipality-name { font-weight: 800; }
        .geo-municipality-count { color: var(--geo-green-900); font-weight: 900; }
        .geo-municipality-track { height: 6px; margin-top: 7px; border-radius: 999px; background: #eef2f0; overflow: hidden; }
        .geo-municipality-fill { height: 100%; border-radius: 999px; background: var(--geo-green-700); }
        .geo-dashboard-empty { padding: 24px 20px; color: var(--geo-muted); font-size: 13px; line-height: 1.5; }

        @media (max-width: 1120px) {
            .geo-dashboard-hero,
            .geo-dashboard-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 760px) {
            .geo-dashboard-hero { padding: 22px 20px; }
            .geo-hero-stats { grid-template-columns: 1fr; }
            .geo-hero-stat { border-right: 0; border-bottom: 1px solid rgba(255, 255, 255, .14); }
            .geo-hero-stat:last-child { border-bottom: 0; }
            .geo-recent-row { grid-template-columns: 1fr; gap: 7px; }
        }
    </style>

    <section class="geo-dashboard-stack">
        <article class="geo-dashboard-hero">
            <div>
                <p class="geo-dashboard-kicker">Geodetic Review Workspace</p>
                <h2 class="geo-dashboard-title">Parcel and map reference overview</h2>
                <p class="geo-dashboard-copy">Review parcel boundaries, encoded references, and linked landholding information through the limited read-only geodetic workspace.</p>
                <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-dashboard-action">
                    <i class="fa-solid fa-map-location-dot"></i>
                    Open Parcel Map
                </a>
            </div>

            <div class="geo-hero-summary" aria-label="Geodetic record summary">
                <div class="geo-hero-stats">
                    <div class="geo-hero-stat">
                        <span class="geo-hero-stat-value">{{ $totalParcels }}</span>
                        <span class="geo-hero-stat-label">Parcel references</span>
                    </div>
                    <div class="geo-hero-stat">
                        <span class="geo-hero-stat-value">{{ $mappedParcels }}</span>
                        <span class="geo-hero-stat-label">Mapped parcels</span>
                    </div>
                    <div class="geo-hero-stat">
                        <span class="geo-hero-stat-value">{{ $cardValue('Landholding References') }}</span>
                        <span class="geo-hero-stat-label">Landholding references</span>
                    </div>
                </div>
                <div class="geo-coverage">
                    <div class="geo-coverage-top"><span>Map geometry coverage</span><strong>{{ $coveragePercent }}%</strong></div>
                    <div class="geo-coverage-track"><div class="geo-coverage-fill" style="width: {{ $coveragePercent }}%;"></div></div>
                </div>
            </div>
        </article>

        <section class="geo-dashboard-grid">
            <article class="geo-dashboard-panel">
                <header class="geo-dashboard-panel-header">
                    <div>
                        <h2 class="geo-dashboard-panel-title">Recently Updated Parcel Records</h2>
                        <p class="geo-dashboard-panel-copy">Latest parcel references available for technical and map review. Select any row to review its details.</p>
                    </div>
                    <a href="{{ route('geodetic.parcels.index') }}" class="geo-dashboard-link">View all →</a>
                </header>

                @if ($recentParcels->isEmpty())
                    <div class="geo-dashboard-empty">No parcel records are currently available.</div>
                @else
                    <div class="geo-recent-list">
                        @foreach ($recentParcels as $parcel)
                            <div
                                class="geo-recent-row"
                                data-record-row-href="{{ route('geodetic.parcels.show', $parcel) }}"
                                aria-label="Review parcel record {{ $parcel->parcel_code }}"
                            >
                                <a href="{{ route('geodetic.parcels.show', $parcel) }}" class="geo-recent-code">{{ $parcel->parcel_code }}</a>
                                <div class="geo-recent-meta">
                                    {{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }}
                                    <div class="geo-recent-sub">{{ $parcel->title_no ?? 'No title reference' }} · {{ $parcel->tax_decl_no ?? 'No tax declaration' }}</div>
                                </div>
                                <span class="geo-state-badge {{ $parcel->geometry_geojson ? 'is-mapped' : '' }}">{{ $parcel->geometry_geojson ? 'Mapped' : 'No Geometry' }}</span>
                                <div class="geo-recent-area">{{ $parcel->area_hectares ? number_format((float) $parcel->area_hectares, 4).' ha' : 'N/A' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <aside class="geo-dashboard-panel">
                <header class="geo-dashboard-panel-header">
                    <div>
                        <h2 class="geo-dashboard-panel-title">Municipality Coverage</h2>
                        <p class="geo-dashboard-panel-copy">Largest parcel-record groups by municipality.</p>
                    </div>
                </header>

                @if ($municipalityBreakdown->isEmpty())
                    <div class="geo-dashboard-empty">No municipality breakdown is available.</div>
                @else
                    @php($maxMunicipalityCount = max(1, (int) $municipalityBreakdown->max('total')))
                    <div class="geo-municipality-list">
                        @foreach ($municipalityBreakdown as $item)
                            @php($barWidth = min(100, round(($item['total'] / $maxMunicipalityCount) * 100)))
                            <div class="geo-municipality-row">
                                <div class="geo-municipality-top">
                                    <span class="geo-municipality-name">{{ $item['municipality'] }}</span>
                                    <span class="geo-municipality-count">{{ $item['total'] }}</span>
                                </div>
                                <div class="geo-municipality-track"><div class="geo-municipality-fill" style="width: {{ $barWidth }}%;"></div></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </aside>
        </section>
    </section>

    <x-record-row-navigation />
</x-geodetic-shell>
