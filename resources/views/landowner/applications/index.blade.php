@php
    use App\Models\LandTransferApplication;

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

<x-landowner-shell title="My Applications" active="applications">
    @push('styles')
        <style>
            .lo-app-page {
                display: grid;
                gap: 18px;
            }

            .lo-app-overview {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                padding: 20px 22px;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
            }

            .lo-app-kicker {
                margin: 0;
                color: var(--lo-green-800);
                font-size: 10px;
                font-weight: 900;
                letter-spacing: .16em;
                text-transform: uppercase;
            }

            .lo-app-title {
                margin: 5px 0 0;
                color: var(--lo-ink);
                font-size: 22px;
                line-height: 1.2;
                font-weight: 900;
            }

            .lo-app-copy {
                margin: 6px 0 0;
                max-width: 780px;
                color: var(--lo-muted);
                font-size: 13px;
                line-height: 1.5;
            }

            .lo-app-count {
                min-width: 94px;
                padding: 12px 14px;
                border: 1px solid #bbf7d0;
                border-radius: 12px;
                background: var(--lo-green-50);
                color: var(--lo-green-900);
                text-align: center;
            }

            .lo-app-count strong {
                display: block;
                font-size: 25px;
                line-height: 1;
            }

            .lo-app-count span {
                display: block;
                margin-top: 5px;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .lo-app-list {
                display: grid;
                gap: 14px;
            }

            .lo-app-card {
                overflow: hidden;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
            }

            .lo-app-card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                padding: 16px 18px;
                border-bottom: 1px solid #e8eeea;
                background: #fbfcfb;
            }

            .lo-app-identity {
                min-width: 0;
            }

            .lo-app-code {
                margin: 0;
                color: var(--lo-green-900);
                font-size: 17px;
                font-weight: 900;
                line-height: 1.2;
                overflow-wrap: anywhere;
            }

            .lo-app-record-id {
                margin: 4px 0 0;
                color: #667085;
                font-size: 11px;
                font-weight: 750;
            }

            .lo-app-header-meta {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 12px;
                flex-wrap: wrap;
            }

            .lo-status-badge {
                display: inline-flex;
                align-items: center;
                width: fit-content;
                min-height: 28px;
                padding: 0 10px;
                border: 1px solid #e2e8f0;
                border-radius: 999px;
                background: #f8fafc;
                color: #475569;
                font-size: 10px;
                font-weight: 900;
                line-height: 1.2;
                white-space: nowrap;
            }

            .lo-status-badge.is-green { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
            .lo-status-badge.is-red { background: #fee2e2; border-color: #fecaca; color: #b91c1c; }
            .lo-status-badge.is-blue { background: #dbeafe; border-color: #bfdbfe; color: #1d4ed8; }
            .lo-status-badge.is-violet { background: #ede9fe; border-color: #ddd6fe; color: #6d28d9; }
            .lo-status-badge.is-amber { background: #ffedd5; border-color: #fed7aa; color: #c2410c; }

            .lo-app-updated {
                color: #667085;
                font-size: 11px;
                font-weight: 750;
                white-space: nowrap;
            }

            .lo-app-card-body {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(280px, .82fr);
                gap: 22px;
                padding: 18px;
            }

            .lo-app-section {
                min-width: 0;
                padding: 0;
            }

            .lo-app-section + .lo-app-section {
                border-left: 0;
            }

            .lo-app-section-label {
                margin: 0 0 10px;
                color: #667085;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: .12em;
                text-transform: uppercase;
            }

            .lo-party-stack {
                display: grid;
                gap: 9px;
            }

            .lo-party-row {
                display: grid;
                grid-template-columns: 64px minmax(0, 1fr);
                gap: 10px;
                align-items: start;
            }

            .lo-party-role {
                color: #667085;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: .05em;
                text-transform: uppercase;
            }

            .lo-party-name {
                color: #1f2937;
                font-size: 13px;
                font-weight: 800;
                line-height: 1.4;
                overflow-wrap: anywhere;
            }

            .lo-app-location {
                color: #1f2937;
                font-size: 13px;
                font-weight: 850;
                line-height: 1.45;
            }

            .lo-parcel-tags {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 10px;
            }

            .lo-parcel-tag {
                display: inline-flex;
                align-items: center;
                min-height: 25px;
                padding: 0 8px;
                border: 1px solid #d7ded9;
                border-radius: 999px;
                background: #fbfcfb;
                color: #475569;
                font-size: 10px;
                font-weight: 800;
            }

            .lo-app-empty-reference {
                color: #667085;
                font-size: 12px;
            }

            .lo-decision-stack {
                display: grid;
                gap: 10px;
                align-content: start;
            }

            .lo-denial-reason {
                border: 0;
                border-radius: 8px;
                background: #fef2f2;
                padding: 10px 11px;
                color: #7f1d1d;
                font-size: 12px;
                line-height: 1.45;
            }

            .lo-denial-reason strong {
                display: block;
                margin-bottom: 3px;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: .07em;
                text-transform: uppercase;
            }

            .lo-clearance-link {
                min-height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: fit-content;
                padding: 0 13px;
                border: 1px solid var(--lo-green-700);
                border-radius: 9px;
                background: var(--lo-green-700);
                color: #ffffff;
                text-decoration: none;
                font-size: 11px;
                font-weight: 900;
                white-space: nowrap;
            }

            .lo-clearance-link:hover {
                background: var(--lo-green-900);
                border-color: var(--lo-green-900);
            }

            .lo-output-state {
                display: inline-flex;
                align-items: center;
                width: fit-content;
                min-height: 29px;
                padding: 0 9px;
                border: 1px solid #e2e8f0;
                border-radius: 999px;
                background: #f8fafc;
                color: #667085;
                font-size: 10px;
                font-weight: 800;
                white-space: nowrap;
            }

            .lo-app-empty {
                padding: 28px 20px;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                background: #ffffff;
                color: var(--lo-muted);
                font-size: 13px;
                line-height: 1.5;
            }

            @media (max-width: 1080px) {
                .lo-app-card-body {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .lo-app-section:last-child {
                    grid-column: 1 / -1;
                    border-left: 0;
                    border-top: 0;
                }
            }

            @media (max-width: 760px) {
                .lo-app-overview,
                .lo-app-card-header {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .lo-app-count {
                    min-width: 100%;
                    text-align: left;
                }

                .lo-app-count strong,
                .lo-app-count span {
                    display: inline;
                }

                .lo-app-count span {
                    margin-left: 6px;
                }

                .lo-app-header-meta {
                    justify-content: flex-start;
                }

                .lo-app-card-body {
                    grid-template-columns: 1fr;
                }

                .lo-app-section + .lo-app-section,
                .lo-app-section:last-child {
                    grid-column: auto;
                    border-left: 0;
                    border-top: 0;
                }

                .lo-party-row {
                    grid-template-columns: 56px minmax(0, 1fr);
                }
            }
        </style>
    @endpush

    <section class="lo-app-page">
        <article class="lo-app-overview">
            <div>
                <p class="lo-app-kicker">Status Monitoring</p>
                <h2 class="lo-app-title">My Clearance Applications</h2>
                <p class="lo-app-copy">
                    Track applications linked to your landowner record. Encoding, document review, workflow decisions, and clearance processing remain with authorized DAR staff.
                </p>
            </div>

            <div class="lo-app-count">
                <strong>{{ $applications->count() }}</strong>
                <span>Linked records</span>
            </div>
        </article>

        @if ($applications->isEmpty())
            <div class="lo-app-empty">No clearance applications are currently linked to your landowner account.</div>
        @else
            <div class="lo-app-list">
                @foreach ($applications as $application)
                    <article class="lo-app-card">
                        <header class="lo-app-card-header">
                            <div class="lo-app-identity">
                                <h3 class="lo-app-code">{{ $application->application_code }}</h3>
                                <p class="lo-app-record-id">Record #{{ $application->id }}</p>
                            </div>

                            <div class="lo-app-header-meta">
                                <span class="lo-status-badge {{ $statusClass($application->status) }}">{{ $application->statusLabel() }}</span>
                                <span class="lo-app-updated">
                                    Updated {{ $application->updated_at?->format('M d, Y · h:i A') ?? 'N/A' }}
                                </span>
                            </div>
                        </header>

                        <div class="lo-app-card-body">
                            <section class="lo-app-section">
                                <p class="lo-app-section-label">Parties</p>
                                <div class="lo-party-stack">
                                    <div class="lo-party-row">
                                        <span class="lo-party-role">From</span>
                                        <span class="lo-party-name">{{ $application->transferorDisplayName() ?: 'N/A' }}</span>
                                    </div>
                                    <div class="lo-party-row">
                                        <span class="lo-party-role">To</span>
                                        <span class="lo-party-name">{{ $application->transfereeDisplayName() ?: 'N/A' }}</span>
                                    </div>
                                </div>
                            </section>

                            <section class="lo-app-section">
                                <p class="lo-app-section-label">Property Reference</p>
                                <div class="lo-app-location">
                                    {{ $application->barangay ?? 'N/A' }}, {{ $application->municipality ?? 'N/A' }}
                                </div>

                                <div class="lo-parcel-tags">
                                    @forelse ($application->applicationParcels as $applicationParcel)
                                        <span class="lo-parcel-tag">
                                            {{ $applicationParcel->parcel?->parcel_code ?? $applicationParcel->parcel_code ?? 'Parcel reference' }}
                                        </span>
                                    @empty
                                        <span class="lo-app-empty-reference">No parcel reference linked.</span>
                                    @endforelse
                                </div>
                            </section>

                            <section class="lo-app-section">
                                <p class="lo-app-section-label">Decision and Output</p>
                                <div class="lo-decision-stack">
                                    @if (
                                        in_array(
                                            $application->status,
                                            [LandTransferApplication::STATUS_DENIED, LandTransferApplication::STATUS_NOT_APPROVED],
                                            true
                                        )
                                        && filled($application->decision_reason)
                                    )
                                        <div class="lo-denial-reason">
                                            <strong>Reason for denial</strong>
                                            {{ $application->decision_reason }}
                                        </div>
                                    @endif

                                    @if ($application->isFinalized() && $application->clearance)
                                        <a href="{{ route('landowner.applications.clearance.show', $application) }}" class="lo-clearance-link">
                                            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                                            View Decision Output
                                        </a>
                                    @elseif ($application->isFinalized())
                                        <span class="lo-output-state">Decision output pending</span>
                                    @else
                                        <span class="lo-output-state">Awaiting final decision</span>
                                    @endif
                                </div>
                            </section>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-landowner-shell>
