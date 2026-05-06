<x-app-layout>
    @php
        $landowner = auth()->user()->landowner;
    @endphp

    @push('styles')
        <style>
            .landowner-page {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            .lo-card {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 1rem;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            }

            .lo-card-pad {
                padding: 1.5rem;
            }

            @media (min-width: 768px) {
                .lo-card-pad {
                    padding: 2rem;
                }
            }

            .lo-hero {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1.5rem;
                align-items: center;
            }

            @media (min-width: 1024px) {
                .lo-hero {
                    grid-template-columns: 1fr 280px;
                }
            }

            .lo-eyebrow {
                font-size: 0.75rem;
                font-weight: 700;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #15803d;
            }

            .lo-title {
                margin-top: 0.75rem;
                font-size: 1.875rem;
                line-height: 2.25rem;
                font-weight: 800;
                color: #111827;
            }

            .lo-muted {
                color: #4b5563;
                font-size: 0.875rem;
                line-height: 1.6;
            }

            .lo-access-box {
                background: #f0fdf4;
                border: 1px solid #bbf7d0;
                border-radius: 0.875rem;
                padding: 1rem 1.25rem;
            }

            .lo-access-label {
                font-size: 0.7rem;
                font-weight: 800;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: #15803d;
            }

            .lo-access-title {
                margin-top: 0.25rem;
                font-size: 1.05rem;
                font-weight: 800;
                color: #052e16;
            }

            .lo-layout {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            @media (min-width: 1024px) {
                .lo-layout {
                    grid-template-columns: minmax(0, 1.8fr) minmax(320px, 0.9fr);
                    align-items: start;
                }
            }

            .lo-section-head {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                padding: 1.5rem 1.5rem 1.25rem;
                border-bottom: 1px solid #f3f4f6;
            }

            @media (min-width: 768px) {
                .lo-section-head {
                    flex-direction: row;
                    justify-content: space-between;
                    align-items: center;
                }
            }

            .lo-section-title {
                font-size: 1.05rem;
                font-weight: 800;
                color: #111827;
            }

            .lo-badge {
                display: inline-flex;
                align-items: center;
                width: fit-content;
                padding: 0.25rem 0.75rem;
                border-radius: 999px;
                font-size: 0.75rem;
                font-weight: 700;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #166534;
            }

            .lo-actions {
                padding: 1.5rem;
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .lo-action {
                display: block;
                text-decoration: none;
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 0.875rem;
                padding: 1.25rem;
                transition: 150ms ease;
            }

            .lo-action:hover {
                background: #ffffff;
                border-color: #86efac;
                box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
                transform: translateY(-1px);
            }

            .lo-action-row {
                display: grid;
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            @media (min-width: 768px) {
                .lo-action-row {
                    grid-template-columns: 1fr auto;
                    align-items: center;
                }
            }

            .lo-action-kicker {
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .lo-action-kicker.green {
                color: #15803d;
            }

            .lo-action-kicker.blue {
                color: #1d4ed8;
            }

            .lo-action-kicker.amber {
                color: #b45309;
            }

            .lo-action-title {
                margin-top: 0.25rem;
                font-size: 1rem;
                font-weight: 800;
                color: #111827;
            }

            .lo-action:hover .lo-action-title {
                color: #166534;
            }

            .lo-action-link {
                color: #15803d;
                font-size: 0.875rem;
                font-weight: 800;
                white-space: nowrap;
            }

            .lo-notice {
                border-radius: 1rem;
                padding: 1.25rem 1.5rem;
                border: 1px solid;
            }

            .lo-notice.amber {
                background: #fffbeb;
                border-color: #fde68a;
            }

            .lo-notice.green {
                background: #f0fdf4;
                border-color: #bbf7d0;
            }

            .lo-notice-title {
                font-weight: 800;
                color: #111827;
            }

            .lo-profile-row {
                padding: 0.85rem 0;
                border-bottom: 1px solid #f3f4f6;
            }

            .lo-profile-row:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .lo-label {
                font-size: 0.78rem;
                color: #6b7280;
            }

            .lo-value {
                margin-top: 0.2rem;
                font-size: 0.92rem;
                font-weight: 800;
                color: #111827;
            }

            .lo-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.25rem 0.7rem;
                border-radius: 999px;
                font-size: 0.75rem;
                font-weight: 800;
            }

            .lo-pill.ok {
                background: #f0fdf4;
                border: 1px solid #bbf7d0;
                color: #166534;
            }

            .lo-pill.bad {
                background: #fef2f2;
                border: 1px solid #fecaca;
                color: #b91c1c;
            }

            .lo-limit-list {
                margin-top: 1rem;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            .lo-limit-item {
                display: flex;
                gap: 0.75rem;
                align-items: flex-start;
                font-size: 0.875rem;
                color: #374151;
            }

            .lo-dot {
                margin-top: 0.45rem;
                width: 0.45rem;
                height: 0.45rem;
                border-radius: 999px;
                background: #9ca3af;
                flex-shrink: 0;
            }
        </style>
    @endpush

    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Landowner Dashboard
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                View your linked parcel records and clearance application status.
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 landowner-page">

            <section class="lo-card lo-card-pad">
                <div class="lo-hero">
                    <div>
                        <p class="lo-eyebrow">
                            DAR Negros Oriental Provincial Office
                        </p>

                        <h3 class="lo-title">
                            Welcome, {{ $landowner?->full_name ?? auth()->user()->name }}
                        </h3>

                        <p class="lo-muted" style="margin-top: 0.75rem; max-width: 760px;">
                            This portal provides read-only access to parcel records and clearance application
                            status information linked to your landowner account.
                        </p>
                    </div>

                    <div class="lo-access-box">
                        <p class="lo-access-label">
                            Access Level
                        </p>

                        <p class="lo-access-title">
                            Landowner Portal
                        </p>

                        <p class="lo-muted" style="margin-top: 0.25rem; color: #166534;">
                            Own linked records only
                        </p>
                    </div>
                </div>
            </section>

            <div class="lo-layout">

                <main class="landowner-page">

                    <section class="lo-card">
                        <div class="lo-section-head">
                            <div>
                                <h3 class="lo-section-title">
                                    Landowner Portal Actions
                                </h3>

                                <p class="lo-muted" style="margin-top: 0.25rem;">
                                    Choose what you want to view from your linked records.
                                </p>
                            </div>

                            <span class="lo-badge">
                                Read-only
                            </span>
                        </div>

                        <div class="lo-actions">
                            <a href="{{ route('landowner.parcel-map.index') }}" class="lo-action">
                                <div class="lo-action-row">
                                    <div>
                                        <p class="lo-action-kicker green">
                                            Parcel Map
                                        </p>

                                        <h4 class="lo-action-title">
                                            My Parcel Map
                                        </h4>

                                        <p class="lo-muted" style="margin-top: 0.35rem;">
                                            View mapped parcel records linked to your landowner account and open
                                            available parcel reference details.
                                        </p>
                                    </div>

                                    <span class="lo-action-link">
                                        Open map →
                                    </span>
                                </div>
                            </a>

                            <a href="{{ route('landowner.parcels.index') }}" class="lo-action">
                                <div class="lo-action-row">
                                    <div>
                                        <p class="lo-action-kicker blue">
                                            Parcel Records
                                        </p>

                                        <h4 class="lo-action-title">
                                            My Parcels
                                        </h4>

                                        <p class="lo-muted" style="margin-top: 0.35rem;">
                                            Review parcel and landholding reference records connected to your profile.
                                        </p>
                                    </div>

                                    <span class="lo-action-link">
                                        View records →
                                    </span>
                                </div>
                            </a>

                            <a href="{{ route('landowner.applications.index') }}" class="lo-action">
                                <div class="lo-action-row">
                                    <div>
                                        <p class="lo-action-kicker amber">
                                            Clearance Status
                                        </p>

                                        <h4 class="lo-action-title">
                                            My Applications
                                        </h4>

                                        <p class="lo-muted" style="margin-top: 0.35rem;">
                                            Track the progress or final status of clearance applications involving your records.
                                        </p>
                                    </div>

                                    <span class="lo-action-link">
                                        Check status →
                                    </span>
                                </div>
                            </a>
                        </div>
                    </section>

                    <section class="lo-notice amber">
                        <h3 class="lo-notice-title" style="color: #78350f;">
                            System Scope Reminder
                        </h3>

                        <p class="lo-muted" style="margin-top: 0.5rem; color: #92400e;">
                            This system supports clearance processing, clearance generation, monitoring,
                            reporting, and record viewing only. Approval of a clearance application does
                            not automatically transfer land ownership, mutate registry records, or finalize
                            legal land transfer.
                        </p>
                    </section>

                </main>

                <aside class="landowner-page">

                    <section class="lo-card lo-card-pad">
                        <h3 class="lo-section-title">
                            Landowner Profile
                        </h3>

                        <div style="margin-top: 1rem;">
                            <div class="lo-profile-row">
                                <p class="lo-label">Name</p>
                                <p class="lo-value">
                                    {{ $landowner?->full_name ?? auth()->user()->name }}
                                </p>
                            </div>

                            <div class="lo-profile-row">
                                <p class="lo-label">Municipality</p>
                                <p class="lo-value">
                                    {{ $landowner?->municipality ?? 'Not recorded' }}
                                </p>
                            </div>

                            <div class="lo-profile-row">
                                <p class="lo-label">Barangay</p>
                                <p class="lo-value">
                                    {{ $landowner?->barangay ?? 'Not recorded' }}
                                </p>
                            </div>

                            <div class="lo-profile-row">
                                <p class="lo-label">Account Link</p>

                                <p style="margin-top: 0.35rem;">
                                    <span class="lo-pill {{ $landowner ? 'ok' : 'bad' }}">
                                        {{ $landowner ? 'Linked' : 'Not linked' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="lo-notice green">
                        <h3 class="lo-notice-title" style="color: #052e16;">
                            Privacy Rule
                        </h3>

                        <p class="lo-muted" style="margin-top: 0.5rem; color: #166534;">
                            Landowners can only view parcel and application information linked to their own
                            landowner account. Other landowner records are restricted.
                        </p>
                    </section>

                    <section class="lo-card lo-card-pad">
                        <h3 class="lo-section-title">
                            Portal Limitations
                        </h3>

                        <div class="lo-limit-list">
                            <div class="lo-limit-item">
                                <span class="lo-dot"></span>
                                <p>No record editing from this portal</p>
                            </div>

                            <div class="lo-limit-item">
                                <span class="lo-dot"></span>
                                <p>No clearance approval controls</p>
                            </div>

                            <div class="lo-limit-item">
                                <span class="lo-dot"></span>
                                <p>No ownership transfer function</p>
                            </div>

                            <div class="lo-limit-item">
                                <span class="lo-dot"></span>
                                <p>No registry mutation function</p>
                            </div>
                        </div>
                    </section>

                </aside>

            </div>

        </div>
    </div>
</x-app-layout>