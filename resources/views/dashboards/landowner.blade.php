@php
    $displayName = $landowner?->full_name ?? auth()->user()->name;

    $statusClass = function (string $status): string {
        return match ($status) {
            \App\Models\LandTransferApplication::STATUS_APPROVED => 'status-approved',
            \App\Models\LandTransferApplication::STATUS_NOT_APPROVED => 'status-not-approved',
            \App\Models\LandTransferApplication::STATUS_PENDING_REVIEW => 'status-pending',
            default => 'status-draft',
        };
    };

    $statusLabel = function (string $status): string {
        return match ($status) {
            \App\Models\LandTransferApplication::STATUS_APPROVED => 'Approved Clearance',
            \App\Models\LandTransferApplication::STATUS_NOT_APPROVED => 'Not Approved',
            \App\Models\LandTransferApplication::STATUS_PENDING_REVIEW => 'Pending Review',
            default => 'Draft',
        };
    };
@endphp

<x-landowner-shell
    title="Landowner Dashboard"
    active="dashboard"
>
    <x-slot name="head">
        <style>
            .landowner-dashboard {
                display: grid;
                gap: 22px;
            }

            .hero-panel {
                border: 1px solid #d9dee5;
                background: #ffffff;
                border-radius: 14px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                overflow: hidden;
            }

            .hero-inner {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 300px;
                gap: 22px;
                align-items: stretch;
                padding: 26px;
                background:
                    linear-gradient(135deg, rgba(20, 83, 45, 0.96), rgba(22, 101, 52, 0.92)),
                    radial-gradient(circle at top right, rgba(187, 247, 208, 0.35), transparent 36%);
                color: #ffffff;
            }

            .hero-eyebrow {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                color: #bbf7d0;
            }

            .hero-title {
                margin: 10px 0 0;
                font-family: var(--heading-font);
                font-size: clamp(1.75rem, 3vw, 2.55rem);
                line-height: 1.08;
                font-weight: 900;
                color: #ffffff;
            }

            .hero-copy {
                margin: 12px 0 0;
                max-width: 760px;
                font-size: 14px;
                line-height: 1.7;
                color: #ecfdf5;
            }

            .access-panel {
                border: 1px solid rgba(255, 255, 255, 0.20);
                background: rgba(255, 255, 255, 0.10);
                border-radius: 14px;
                padding: 18px;
                display: grid;
                align-content: center;
                gap: 12px;
            }

            .access-label {
                margin: 0;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.16em;
                text-transform: uppercase;
                color: #bbf7d0;
            }

            .access-title {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 18px;
                font-weight: 900;
                color: #ffffff;
            }

            .access-note {
                margin: 0;
                font-size: 13px;
                line-height: 1.55;
                color: #dcfce7;
            }

            .scope-banner {
                display: flex;
                justify-content: space-between;
                gap: 18px;
                align-items: flex-start;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                border-radius: 12px;
                padding: 15px 18px;
                color: #14532d;
            }

            .scope-banner h2 {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 14px;
                font-weight: 900;
            }

            .scope-banner p {
                margin: 5px 0 0;
                max-width: 960px;
                font-size: 12.5px;
                line-height: 1.6;
                font-weight: 600;
            }

            .scope-pill {
                flex: 0 0 auto;
                border: 1px solid #bbf7d0;
                background: #dcfce7;
                color: #14532d;
                border-radius: 999px;
                padding: 6px 11px;
                font-size: 11px;
                font-weight: 900;
                white-space: nowrap;
            }

            .cards-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 16px;
            }

            .metric-card {
                border: 1px solid #d9dee5;
                background: #ffffff;
                border-radius: 12px;
                padding: 19px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                display: flex;
                justify-content: space-between;
                gap: 14px;
                min-height: 126px;
            }

            .metric-label {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.13em;
                text-transform: uppercase;
                color: #64748b;
            }

            .metric-value {
                margin: 11px 0 0;
                font-family: var(--heading-font);
                font-size: 32px;
                line-height: 1;
                font-weight: 900;
                color: #111827;
            }

            .metric-description {
                margin: 10px 0 0;
                font-size: 12px;
                line-height: 1.45;
                color: #6b7280;
            }

            .metric-icon {
                width: 44px;
                height: 44px;
                flex: 0 0 44px;
                border-radius: 11px;
                display: grid;
                place-items: center;
                color: white;
                font-size: 17px;
            }

            .tone-green { background: #166534; }
            .tone-slate { background: #334155; }
            .tone-amber { background: #d97706; }
            .tone-blue { background: #1d4ed8; }

            .dashboard-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.8fr);
                gap: 22px;
                align-items: start;
            }

            .stack {
                display: grid;
                gap: 22px;
            }

            .panel {
                border: 1px solid #d9dee5;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                overflow: hidden;
            }

            .panel-header {
                padding: 20px 22px 0;
                display: flex;
                justify-content: space-between;
                gap: 16px;
                align-items: flex-start;
            }

            .panel-title {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 17px;
                font-weight: 900;
                color: #111827;
            }

            .panel-subtitle {
                margin: 5px 0 0;
                font-size: 13px;
                line-height: 1.5;
                color: #6b7280;
            }

            .panel-link {
                color: #166534;
                text-decoration: none;
                font-size: 13px;
                font-weight: 900;
                white-space: nowrap;
            }

            .panel-link:hover {
                text-decoration: underline;
            }

            .action-grid {
                padding: 20px 22px 22px;
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
            }

            .action-card {
                min-height: 150px;
                text-decoration: none;
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                border-radius: 12px;
                padding: 17px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                transition: 160ms ease;
            }

            .action-card:hover {
                border-color: #86efac;
                background: #f0fdf4;
                transform: translateY(-1px);
                box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
            }

            .action-icon {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                display: grid;
                place-items: center;
                background: #166534;
                color: #ffffff;
                font-size: 16px;
            }

            .action-title {
                margin: 14px 0 0;
                font-size: 15px;
                font-weight: 900;
                color: #14532d;
            }

            .action-desc {
                margin: 5px 0 0;
                font-size: 12.5px;
                line-height: 1.5;
                color: #6b7280;
            }

            .action-arrow {
                margin-top: 14px;
                font-size: 13px;
                font-weight: 900;
                color: #166534;
            }

            .table-wrap {
                padding: 16px 22px 22px;
                overflow-x: auto;
            }

            .data-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }

            .data-table th {
                text-align: left;
                padding: 12px 10px;
                border-bottom: 1px solid #d1d5db;
                font-size: 11px;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #64748b;
                white-space: nowrap;
            }

            .data-table td {
                padding: 13px 10px;
                border-bottom: 1px solid #e5e7eb;
                color: #374151;
                white-space: nowrap;
            }

            .code-link {
                color: #166534;
                font-weight: 900;
                text-decoration: none;
            }

            .code-link:hover {
                text-decoration: underline;
            }

            .status-badge {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 5px 10px;
                font-size: 11px;
                font-weight: 900;
                border: 1px solid;
                white-space: nowrap;
            }

            .status-approved {
                background: #dcfce7;
                border-color: #bbf7d0;
                color: #166534;
            }

            .status-pending {
                background: #ffedd5;
                border-color: #fed7aa;
                color: #c2410c;
            }

            .status-not-approved {
                background: #fee2e2;
                border-color: #fecaca;
                color: #b91c1c;
            }

            .status-draft {
                background: #f1f5f9;
                border-color: #cbd5e1;
                color: #475569;
            }

            .profile-list,
            .status-list,
            .record-list,
            .notice-list {
                padding: 18px 22px 22px;
                display: grid;
                gap: 11px;
            }

            .profile-row,
            .status-row,
            .record-row {
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 13px 14px;
                background: #ffffff;
            }

            .profile-label {
                margin: 0;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #64748b;
            }

            .profile-value {
                margin: 5px 0 0;
                font-size: 14px;
                font-weight: 800;
                color: #111827;
            }

            .status-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }

            .status-name {
                display: flex;
                align-items: center;
                gap: 9px;
                font-size: 13px;
                font-weight: 800;
                color: #374151;
            }

            .status-dot {
                width: 10px;
                height: 10px;
                border-radius: 999px;
                background: #94a3b8;
            }

            .status-dot.approved { background: #16a34a; }
            .status-dot.pending_review { background: #d97706; }
            .status-dot.not_approved { background: #dc2626; }
            .status-dot.draft { background: #94a3b8; }

            .status-count {
                font-size: 14px;
                font-weight: 900;
                color: #111827;
            }

            .record-title {
                margin: 0;
                font-size: 13px;
                font-weight: 900;
                color: #111827;
            }

            .record-meta {
                margin: 4px 0 0;
                font-size: 12px;
                line-height: 1.5;
                color: #6b7280;
            }

            .linked-pill {
                display: inline-flex;
                border-radius: 999px;
                padding: 5px 10px;
                font-size: 11px;
                font-weight: 900;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #166534;
            }

            .linked-pill.bad {
                border-color: #fecaca;
                background: #fef2f2;
                color: #b91c1c;
            }

            .notice-card {
                border-radius: 12px;
                padding: 16px;
                border: 1px solid;
            }

            .notice-card.green {
                border-color: #bbf7d0;
                background: #f0fdf4;
                color: #14532d;
            }

            .notice-card.amber {
                border-color: #fde68a;
                background: #fffbeb;
                color: #92400e;
            }

            .notice-card h3 {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 14px;
                font-weight: 900;
            }

            .notice-card p {
                margin: 6px 0 0;
                font-size: 12.5px;
                line-height: 1.6;
                font-weight: 600;
            }

            .empty-state {
                padding: 26px;
                text-align: center;
                color: #6b7280;
                font-size: 13px;
            }

            @media (max-width: 1180px) {
                .hero-inner,
                .dashboard-grid {
                    grid-template-columns: 1fr;
                }

                .cards-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 860px) {
                .action-grid {
                    grid-template-columns: 1fr;
                }

                .scope-banner {
                    flex-direction: column;
                }
            }

            @media (max-width: 620px) {
                .hero-inner {
                    padding: 20px;
                }

                .cards-grid {
                    grid-template-columns: 1fr;
                }

                .metric-card {
                    min-height: auto;
                }

                .panel-header {
                    flex-direction: column;
                }
            }
        </style>
    </x-slot>

    <div class="landowner-dashboard">
        <section class="hero-panel">
            <div class="hero-inner">
                <div>
                    <p class="hero-eyebrow">Landowner Read-Only Access</p>
                    <h2 class="hero-title">Welcome, {{ $displayName }}</h2>
                    <p class="hero-copy">
                        This portal lets you view your own linked parcel records, mapped parcel references,
                        and clearance application status. Landowners do not create applications or edit DAR records from this portal.
                    </p>
                </div>

                <aside class="access-panel">
                    <p class="access-label">Access Level</p>
                    <h3 class="access-title">Own Records Only</h3>
                    <p class="access-note">
                        Access is privacy-filtered to records connected to your landowner account.
                    </p>
                </aside>
            </div>
        </section>

        <section class="scope-banner">
            <div>
                <h2>DAR-LTCMS Scope Reminder</h2>
                <p>
                    This system supports clearance application monitoring and record viewing only. Clearance approval does not automatically transfer land ownership,
                    mutate registry records, or finalize legal land transfer.
                </p>
            </div>

            <span class="scope-pill">Read-only Portal</span>
        </section>

        <section class="cards-grid">
            @foreach ($dashboardCards as $card)
                <article class="metric-card">
                    <div>
                        <p class="metric-label">{{ $card['label'] }}</p>
                        <p class="metric-value">{{ number_format($card['value']) }}</p>
                        <p class="metric-description">{{ $card['description'] }}</p>
                    </div>

                    <div class="metric-icon tone-{{ $card['tone'] }}">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Landowner Portal Actions</h2>
                    <p class="panel-subtitle">Open the read-only records and status pages available to your account.</p>
                </div>
            </div>

            <div class="action-grid">
                <a href="{{ route('landowner.parcel-map.index') }}" class="action-card">
                    <div>
                        <div class="action-icon">
                            <i class="fa-solid fa-map"></i>
                        </div>
                        <h3 class="action-title">My Parcel Map</h3>
                        <p class="action-desc">View mapped parcel records linked to your landowner account.</p>
                    </div>
                    <span class="action-arrow">Open map →</span>
                </a>

                <a href="{{ route('landowner.parcels.index') }}" class="action-card">
                    <div>
                        <div class="action-icon">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <h3 class="action-title">My Parcels</h3>
                        <p class="action-desc">Review your linked parcel and landholding reference records.</p>
                    </div>
                    <span class="action-arrow">View records →</span>
                </a>

                <a href="{{ route('landowner.applications.index') }}" class="action-card">
                    <div>
                        <div class="action-icon">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <h3 class="action-title">My Applications</h3>
                        <p class="action-desc">Track clearance application status where your record is linked.</p>
                    </div>
                    <span class="action-arrow">Check status →</span>
                </a>
            </div>
        </section>

        <section class="dashboard-grid">
            <main class="stack">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Recent Clearance Applications</h2>
                            <p class="panel-subtitle">Latest applications where your landowner record is listed as transferor or transferee.</p>
                        </div>

                        <a href="{{ route('landowner.applications.index') }}" class="panel-link">View All →</a>
                    </div>

                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Role</th>
                                    <th>Other Party</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentApplications as $application)
                                    @php
                                        $isTransferor = $landowner && $application->transferor_landowner_id === $landowner->id;
                                        $role = $isTransferor ? 'Transferor' : 'Transferee';
                                        $otherParty = $isTransferor
                                            ? ($application->transferee_name ?? 'Not recorded')
                                            : ($application->transferor_name ?? 'Not recorded');
                                    @endphp

                                    <tr>
                                        <td>{{ $application->application_code }}</td>
                                        <td>{{ $role }}</td>
                                        <td>{{ $otherParty }}</td>
                                        <td>
                                            <span class="status-badge {{ $statusClass($application->status) }}">
                                                {{ $statusLabel($application->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $application->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">No linked clearance applications found yet.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Linked Parcel Snapshot</h2>
                            <p class="panel-subtitle">Recent landholding and parcel records connected to your account.</p>
                        </div>

                        <a href="{{ route('landowner.parcels.index') }}" class="panel-link">View Parcels →</a>
                    </div>

                    <div class="record-list">
                        @forelse ($recentLandholdings as $landholding)
                            <div class="record-row">
                                <p class="record-title">
                                    {{ $landholding->parcel?->parcel_code ?? 'No parcel code recorded' }}
                                </p>
                                <p class="record-meta">
                                    {{ $landholding->parcel?->municipality ?? 'Municipality not recorded' }}
                                    @if ($landholding->parcel?->barangay)
                                        · {{ $landholding->parcel->barangay }}
                                    @endif
                                    @if ($landholding->area_hectares)
                                        · {{ number_format((float) $landholding->area_hectares, 4) }} ha
                                    @endif
                                </p>
                            </div>
                        @empty
                            <div class="empty-state">No linked parcel or landholding records found yet.</div>
                        @endforelse
                    </div>
                </section>
            </main>

            <aside class="stack">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Landowner Profile</h2>
                            <p class="panel-subtitle">Basic record linked to this account.</p>
                        </div>
                    </div>

                    <div class="profile-list">
                        <div class="profile-row">
                            <p class="profile-label">Name</p>
                            <p class="profile-value">{{ $displayName }}</p>
                        </div>

                        <div class="profile-row">
                            <p class="profile-label">Municipality</p>
                            <p class="profile-value">{{ $landowner?->municipality ?? 'Not recorded' }}</p>
                        </div>

                        <div class="profile-row">
                            <p class="profile-label">Barangay</p>
                            <p class="profile-value">{{ $landowner?->barangay ?? 'Not recorded' }}</p>
                        </div>

                        <div class="profile-row">
                            <p class="profile-label">Account Link</p>
                            <p class="profile-value">
                                <span class="linked-pill {{ $landowner ? '' : 'bad' }}">
                                    {{ $landowner ? 'Linked' : 'Not Linked' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Application Status Summary</h2>
                            <p class="panel-subtitle">Status counts for your linked applications.</p>
                        </div>
                    </div>

                    <div class="status-list">
                        @foreach ($statusSummary as $item)
                            <div class="status-row">
                                <div class="status-name">
                                    <span class="status-dot {{ $item['status'] }}"></span>
                                    {{ $item['label'] }}
                                </div>
                                <div class="status-count">{{ number_format($item['count']) }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="notice-list">
                    <div class="notice-card green">
                        <h3>Privacy Rule</h3>
                        <p>
                            You can only view parcel and application information linked to your own landowner account.
                            Other landowner records remain restricted.
                        </p>
                    </div>

                    <div class="notice-card amber">
                        <h3>Portal Limitations</h3>
                        <p>
                            No application creation, record editing, approval controls, ownership transfer, or registry mutation functions are available in the landowner portal.
                        </p>
                    </div>
                </section>
            </aside>
        </section>
    </div>
</x-landowner-shell>
