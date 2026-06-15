@php
    use App\Models\LandTransferApplication;

    $statusClass = function (?string $status): string {
        return match ($status) {
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_APPROVED => 'status-released',

            LandTransferApplication::STATUS_DENIED,
            LandTransferApplication::STATUS_NOT_APPROVED => 'status-denied',

            LandTransferApplication::STATUS_ENDORSED_LTI,
            LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
            LandTransferApplication::STATUS_ENDORSED_PARPO => 'status-endorsed',

            LandTransferApplication::STATUS_FOR_RELEASING => 'status-for-releasing',

            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            LandTransferApplication::STATUS_PENDING_REVIEW,
            LandTransferApplication::STATUS_DRAFT => 'status-pending',

            default => 'status-pending',
        };
    };
@endphp

<x-landowner-shell
    title="My Applications"
    active="applications"
>
    @push('styles')
        <style>
            .lo-page-stack {
                display: grid;
                gap: 18px;
            }

            .lo-page-hero {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                padding: 22px 24px;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 18px;
            }

            .lo-hero-label {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.16em;
                text-transform: uppercase;
                color: var(--lo-green-800);
            }

            .lo-hero-title {
                margin: 5px 0 0;
                font-size: 24px;
                line-height: 1.15;
                font-weight: 900;
                color: var(--lo-ink);
            }

            .lo-hero-copy {
                margin: 8px 0 0;
                color: var(--lo-muted);
                font-size: 13px;
                line-height: 1.55;
                max-width: 820px;
            }

            .lo-hero-pill {
                flex: 0 0 auto;
                border: 1px solid #bbf7d0;
                background: var(--lo-green-50);
                color: var(--lo-green-800);
                border-radius: 999px;
                padding: 6px 12px;
                font-size: 12px;
                font-weight: 900;
                white-space: nowrap;
            }

            .lo-panel {
                background: #ffffff;
                border: 1px solid var(--lo-line);
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                overflow: hidden;
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

            .lo-table-wrap { overflow-x: auto; }

            .lo-table {
                width: 100%;
                border-collapse: collapse;
                min-width: 1080px;
                font-size: 13px;
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
                padding: 14px;
                border-bottom: 1px solid #edf0ee;
                color: #344054;
                vertical-align: top;
            }

            .lo-table tbody tr:last-child td { border-bottom: 0; }

            .lo-code {
                color: var(--lo-green-900);
                font-weight: 900;
                white-space: nowrap;
            }

            .lo-muted { color: var(--lo-muted); }

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

            .status-released {
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

            .status-for-releasing {
                background: #ede9fe;
                border: 1px solid #ddd6fe;
                color: #6d28d9;
            }

            .lo-parcel-tags {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .lo-parcel-tag {
                display: inline-flex;
                align-items: center;
                border: 1px solid #d7ded9;
                background: #fbfcfb;
                border-radius: 999px;
                padding: 4px 8px;
                font-size: 12px;
                font-weight: 800;
                color: #344054;
            }

            .lo-empty {
                border: 1px dashed #cbd5d1;
                border-radius: 10px;
                background: #fbfcfb;
                padding: 24px;
                color: var(--lo-muted);
                font-size: 13px;
                line-height: 1.55;
            }


            .lo-output-actions {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
                min-width: 220px;
            }

            .lo-action-button {
                min-height: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                padding: 0 11px;
                border-radius: 9px;
                border: 1px solid var(--lo-green-700);
                background: var(--lo-green-700);
                color: #ffffff;
                text-decoration: none;
                font-size: 12px;
                font-weight: 900;
                white-space: nowrap;
            }

            .lo-action-button.secondary {
                border-color: #d7ded9;
                background: #ffffff;
                color: var(--lo-green-900);
            }

            .lo-output-muted {
                display: inline-flex;
                align-items: center;
                min-height: 30px;
                border-radius: 999px;
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                padding: 0 10px;
                color: #667085;
                font-size: 12px;
                font-weight: 800;
                white-space: nowrap;
            }

            @media (max-width: 760px) {
                .lo-page-hero { flex-direction: column; }
                .lo-hero-pill { align-self: flex-start; }
            }
        </style>
    @endpush

    <section class="lo-page-stack">
        <article class="lo-page-hero">
            <div>
                <p class="lo-hero-label">Landowner Status View</p>
                <h2 class="lo-hero-title">Clearance Application Status</h2>
                <p class="lo-hero-copy">
                    These are clearance applications where your landowner record is listed as the transferor or transferee. This page is for status monitoring only and does not allow application creation or processing.
                </p>
            </div>

            <span class="lo-hero-pill">{{ $applications->count() }} linked</span>
        </article>

        <article class="lo-panel">
            <div class="lo-panel-header">
                <div>
                    <h2 class="lo-panel-title">Linked Applications</h2>
                    <p class="lo-panel-subtitle">Application status, release/denial date, and final decision output if already finalized by DAR staff.</p>
                </div>
            </div>

            <div class="lo-panel-body">
                @if ($applications->isEmpty())
                    <div class="lo-empty">
                        No clearance applications are currently linked to your landowner account.
                    </div>
                @else
                    <div class="lo-table-wrap">
                        <table class="lo-table">
                            <thead>
                                <tr>
                                    <th>Application Code</th>
                                    <th>Transferor</th>
                                    <th>Transferee</th>
                                    <th>Location</th>
                                    <th>Parcels</th>
                                    <th>Status</th>
                                    <th>Release/Denial Date</th>
                                    <th>Decision Output</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($applications as $application)
                                    <tr>
                                        <td><span class="lo-code">{{ $application->application_code }}</span></td>
                                        <td>{{ $application->transferorLandowner?->full_name ?? $application->transferor_name ?? 'N/A' }}</td>
                                        <td>{{ $application->transfereeLandowner?->full_name ?? $application->transferee_name ?? 'N/A' }}</td>
                                        <td>{{ $application->barangay ?? 'N/A' }}, {{ $application->municipality ?? 'N/A' }}</td>
                                        <td>
                                            <div class="lo-parcel-tags">
                                                @forelse ($application->applicationParcels as $applicationParcel)
                                                    <span class="lo-parcel-tag">
                                                        {{ $applicationParcel->parcel?->parcel_code ?? 'Parcel reference' }}
                                                    </span>
                                                @empty
                                                    <span class="lo-muted">No parcel reference</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            <span class="lo-status-pill {{ $statusClass($application->status) }}">
                                                {{ $application->statusLabel() }}
                                            </span>
                                        </td>
                                        <td>{{ $application->reviewed_at?->format('M d, Y') ?? 'Pending' }}</td>
                                        <td>
                                            @if ($application->isFinalized() && $application->clearance)
                                                <div class="lo-output-actions">
                                                    <a href="{{ route('landowner.applications.clearance.show', $application) }}" class="lo-action-button">
                                                        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                                                        View Output
                                                    </a>
                                                    <a href="{{ route('landowner.applications.clearance.pdf', $application) }}" class="lo-action-button secondary" target="_blank">
                                                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                                        PDF
                                                    </a>
                                                </div>
                                            @elseif ($application->isFinalized())
                                                <span class="lo-output-muted">Output pending</span>
                                            @else
                                                <span class="lo-output-muted">Not yet finalized</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>
    </section>
</x-landowner-shell>
