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
            .lo-app-page { display: grid; gap: 18px; }

            .lo-app-overview {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                padding: 21px 22px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 18px;
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

            .lo-app-title { margin: 5px 0 0; color: var(--lo-ink); font-size: 22px; line-height: 1.2; font-weight: 900; }
            .lo-app-copy { margin: 6px 0 0; max-width: 780px; color: var(--lo-muted); font-size: 13px; line-height: 1.5; }

            .lo-app-count {
                min-width: 88px;
                border: 1px solid #bbf7d0;
                border-radius: 12px;
                background: var(--lo-green-50);
                padding: 12px 14px;
                text-align: center;
                color: var(--lo-green-900);
            }
            .lo-app-count strong { display: block; font-size: 24px; line-height: 1; }
            .lo-app-count span { display: block; margin-top: 5px; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; }

            .lo-app-panel {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 14px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .07);
                overflow: hidden;
            }

            .lo-app-panel-header {
                padding: 18px 20px 15px;
                border-bottom: 1px solid #e8eeea;
            }
            .lo-app-panel-title { margin: 0; color: var(--lo-ink); font-size: 17px; font-weight: 900; }
            .lo-app-panel-copy { margin: 4px 0 0; color: var(--lo-muted); font-size: 12px; line-height: 1.45; }

            .lo-app-table-wrap { overflow-x: auto; }
            .lo-app-table { width: 100%; min-width: 1020px; border-collapse: collapse; font-size: 13px; }
            .lo-app-table th {
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
            .lo-app-table td { padding: 15px; border-bottom: 1px solid #edf1ee; color: #344054; vertical-align: top; }
            .lo-app-table tbody tr:last-child td { border-bottom: 0; }

            .lo-app-code { color: var(--lo-green-900); font-weight: 900; white-space: nowrap; }
            .lo-app-record-id { margin-top: 4px; color: #667085; font-size: 11px; }

            .lo-app-parties { display: grid; gap: 5px; min-width: 235px; }
            .lo-app-party { display: grid; grid-template-columns: 42px minmax(0, 1fr); gap: 7px; line-height: 1.35; }
            .lo-app-party-label { color: #667085; font-size: 10px; font-weight: 900; text-transform: uppercase; }
            .lo-app-party-name { color: #1f2937; font-weight: 700; overflow-wrap: anywhere; }

            .lo-app-location { color: #1f2937; font-weight: 750; }
            .lo-app-location-sub { margin-top: 3px; color: #667085; font-size: 11px; }

            .lo-parcel-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; }
            .lo-parcel-tag {
                display: inline-flex;
                align-items: center;
                min-height: 23px;
                padding: 0 8px;
                border: 1px solid #d7ded9;
                border-radius: 999px;
                background: #fbfcfb;
                color: #475569;
                font-size: 10px;
                font-weight: 800;
            }

            .lo-status-badge {
                display: inline-flex;
                align-items: center;
                width: fit-content;
                min-height: 27px;
                padding: 0 10px;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #475569;
                font-size: 10px;
                line-height: 1.2;
                font-weight: 900;
                white-space: nowrap;
            }
            .lo-status-badge.is-green { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
            .lo-status-badge.is-red { background: #fee2e2; border-color: #fecaca; color: #b91c1c; }
            .lo-status-badge.is-blue { background: #dbeafe; border-color: #bfdbfe; color: #1d4ed8; }
            .lo-status-badge.is-violet { background: #ede9fe; border-color: #ddd6fe; color: #6d28d9; }
            .lo-status-badge.is-amber { background: #ffedd5; border-color: #fed7aa; color: #c2410c; }

            .lo-app-date { color: #475569; white-space: nowrap; }
            .lo-app-date small { display: block; margin-top: 3px; color: #667085; font-size: 10px; }

            .lo-clearance-link {
                min-height: 32px;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 0 11px;
                border-radius: 9px;
                border: 1px solid var(--lo-green-700);
                background: var(--lo-green-700);
                color: #ffffff;
                text-decoration: none;
                font-size: 11px;
                font-weight: 900;
                white-space: nowrap;
            }

            .lo-denial-reason {
                margin-top: 8px;
                max-width: 260px;
                border-left: 3px solid #dc2626;
                border-radius: 7px;
                background: #fef2f2;
                padding: 8px 10px;
                color: #7f1d1d;
                font-size: 11px;
                line-height: 1.45;
            }
            .lo-denial-reason strong { display: block; margin-bottom: 2px; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; }

            .lo-output-state {
                display: inline-flex;
                align-items: center;
                min-height: 28px;
                padding: 0 9px;
                border: 1px solid #e2e8f0;
                border-radius: 999px;
                background: #f8fafc;
                color: #667085;
                font-size: 10px;
                font-weight: 800;
                white-space: nowrap;
            }

            .lo-app-empty { padding: 28px 20px; color: var(--lo-muted); font-size: 13px; line-height: 1.5; }

            @media (max-width: 760px) {
                .lo-app-overview { flex-direction: column; align-items: flex-start; }
                .lo-app-count { min-width: 100%; text-align: left; }
                .lo-app-count strong, .lo-app-count span { display: inline; }
                .lo-app-count span { margin-left: 6px; }
            }
        </style>
    @endpush

    <section class="lo-app-page">
        <article class="lo-app-overview">
            <div>
                <p class="lo-app-kicker">Status Monitoring</p>
                <h2 class="lo-app-title">My Clearance Applications</h2>
                <p class="lo-app-copy">View applications where your landowner record is linked as a transferor or transferee. Application encoding, review, and decision processing remain with authorized DAR staff.</p>
            </div>

            <div class="lo-app-count">
                <strong>{{ $applications->count() }}</strong>
                <span>Linked records</span>
            </div>
        </article>

        <article class="lo-app-panel">
            <header class="lo-app-panel-header">
                <h2 class="lo-app-panel-title">Application Records</h2>
                <p class="lo-app-panel-copy">Current stage, linked parcels, and clearance availability for each record.</p>
            </header>

            @if ($applications->isEmpty())
                <div class="lo-app-empty">No clearance applications are currently linked to your landowner account.</div>
            @else
                <div class="lo-app-table-wrap">
                    <table class="lo-app-table">
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Parties</th>
                                <th>Location and Parcels</th>
                                <th>Current Stage</th>
                                <th>Last Updated</th>
                                <th>Clearance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($applications as $application)
                                <tr>
                                    <td>
                                        <div class="lo-app-code">{{ $application->application_code }}</div>
                                        <div class="lo-app-record-id">Record #{{ $application->id }}</div>
                                    </td>
                                    <td>
                                        <div class="lo-app-parties">
                                            <div class="lo-app-party"><span class="lo-app-party-label">From</span><span class="lo-app-party-name">{{ $application->transferorDisplayName() ?: 'N/A' }}</span></div>
                                            <div class="lo-app-party"><span class="lo-app-party-label">To</span><span class="lo-app-party-name">{{ $application->transfereeDisplayName() ?: 'N/A' }}</span></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="lo-app-location">{{ $application->barangay ?? 'N/A' }}, {{ $application->municipality ?? 'N/A' }}</div>
                                        <div class="lo-parcel-tags">
                                            @forelse ($application->applicationParcels as $applicationParcel)
                                                <span class="lo-parcel-tag">{{ $applicationParcel->parcel?->parcel_code ?? $applicationParcel->parcel_code ?? 'Parcel reference' }}</span>
                                            @empty
                                                <span class="lo-app-location-sub">No parcel reference</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        <span class="lo-status-badge {{ $statusClass($application->status) }}">{{ $application->statusLabel() }}</span>
                                        @if (in_array($application->status, [LandTransferApplication::STATUS_DENIED, LandTransferApplication::STATUS_NOT_APPROVED], true) && filled($application->decision_reason))
                                            <div class="lo-denial-reason">
                                                <strong>Reason for denial</strong>
                                                {{ $application->decision_reason }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="lo-app-date">
                                        {{ $application->updated_at?->format('M d, Y') ?? 'N/A' }}
                                        <small>{{ $application->updated_at?->format('h:i A') ?? '' }}</small>
                                    </td>
                                    <td>
                                        @if ($application->isFinalized() && $application->clearance)
                                            <a href="{{ route('landowner.applications.clearance.show', $application) }}" class="lo-clearance-link">
                                                <i class="fa-solid fa-file-lines"></i>
                                                View Clearance
                                            </a>
                                        @elseif ($application->isFinalized())
                                            <span class="lo-output-state">Output pending</span>
                                        @else
                                            <span class="lo-output-state">Not yet finalized</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>
</x-landowner-shell>
