<x-staff-shell
    title="Land Transfer Clearance Applications"
    subtitle="Search, review, and monitor staff-encoded clearance application records."
    active="applications"
>
    <style>
        .records-toolbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .records-toolbar-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .application-filter-grid {
            display: grid;
            grid-template-columns: minmax(260px, 1.8fr) repeat(4, minmax(150px, 1fr));
            gap: .8rem;
            align-items: end;
        }

        .application-filter-actions {
            display: flex;
            align-items: center;
            gap: .55rem;
            grid-column: 1 / -1;
        }

        .application-parties {
            display: grid;
            gap: .28rem;
            min-width: 220px;
        }

        .application-party {
            display: grid;
            grid-template-columns: 4.7rem minmax(0, 1fr);
            gap: .55rem;
            align-items: baseline;
        }

        .application-party-label {
            color: #64748b;
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .application-party-name {
            color: #0f172a;
            font-size: .86rem;
            font-weight: 750;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .application-reference {
            margin-top: .28rem;
            color: #64748b;
            font-size: .76rem;
        }

        .application-mobile-list {
            display: none;
        }

        .application-mobile-card {
            display: grid;
            gap: 14px;
            padding: 16px;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .application-mobile-card:first-child {
            border-top: 0;
        }

        .application-mobile-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .application-mobile-code {
            display: inline-block;
            color: #166534;
            font-size: .92rem;
            font-weight: 900;
            text-decoration: none;
        }

        .application-mobile-code:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .application-mobile-reference {
            margin-top: 3px;
            color: #64748b;
            font-size: .72rem;
        }

        .application-mobile-section {
            display: grid;
            gap: 7px;
        }

        .application-mobile-label {
            color: #64748b;
            font-size: .65rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .application-mobile-party {
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr);
            gap: 8px;
            align-items: start;
        }

        .application-mobile-party-direction {
            color: #64748b;
            font-size: .67rem;
            font-weight: 900;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .application-mobile-party-name {
            min-width: 0;
            color: #0f172a;
            font-size: .88rem;
            font-weight: 750;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .application-mobile-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .application-mobile-meta-item {
            min-width: 0;
            padding: 11px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .application-mobile-value {
            margin-top: 4px;
            color: #0f172a;
            font-size: .83rem;
            font-weight: 750;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .application-mobile-location-sub {
            display: block;
            margin-top: 2px;
            color: #64748b;
            font-size: .75rem;
            font-weight: 650;
        }

        .application-mobile-action .staff-button {
            width: 100%;
            min-height: 44px;
        }

        .application-mobile-empty {
            padding: 28px 16px;
            border-top: 1px solid #e5e7eb;
            color: #64748b;
            font-size: .88rem;
            text-align: center;
        }

        @media (max-width: 1280px) {
            .application-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .application-filter-grid {
                grid-template-columns: 1fr;
            }

            .application-filter-actions,
            .records-toolbar-actions {
                width: 100%;
            }

            .application-filter-actions .staff-button,
            .records-toolbar-actions .staff-button {
                flex: 1 1 auto;
                justify-content: center;
            }

            .application-desktop-table {
                display: none !important;
            }

            .application-mobile-list {
                display: block;
            }
        }

        @media (max-width: 430px) {
            .application-mobile-card {
                padding: 14px;
            }

            .application-mobile-head {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
            }

            .application-mobile-head .staff-badge {
                width: fit-content;
                max-width: 100%;
                white-space: normal;
            }

            .application-mobile-meta {
                grid-template-columns: 1fr;
            }

            .application-filter-actions,
            .records-toolbar-actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .application-filter-actions .staff-button,
            .records-toolbar-actions .staff-button {
                width: 100%;
            }
        }
    </style>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @php
        $statusLabels = \App\Models\LandTransferApplication::statusLabels();
        $statusBadges = [
            \App\Models\LandTransferApplication::STATUS_RELEASED => 'staff-badge-green',
            \App\Models\LandTransferApplication::STATUS_DENIED => 'staff-badge-red',
            \App\Models\LandTransferApplication::STATUS_FOR_RELEASING => 'staff-badge-blue',
            \App\Models\LandTransferApplication::STATUS_ENDORSED_PARPO => 'staff-badge-blue',
            \App\Models\LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL => 'staff-badge-blue',
            \App\Models\LandTransferApplication::STATUS_ENDORSED_LTI => 'staff-badge-blue',
            \App\Models\LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW => 'staff-badge-amber',
        ];
    @endphp

    <section class="staff-panel staff-panel-pad">
        <div class="records-toolbar">
            <div>
                <h2 class="staff-panel-title">Application Records</h2>
                <p class="staff-panel-subtitle">{{ $applications->total() }} total record(s). Use the filters below to narrow the working list.</p>
            </div>

            <div class="records-toolbar-actions" data-main-card-actions-moved>
                @if (\Illuminate\Support\Facades\Route::has('staff.applications.create'))
                    <a href="{{ route('staff.applications.create') }}" class="staff-button staff-button-primary">
                        <i class="fa-solid fa-plus"></i>
                        Encode New Application
                    </a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('staff.applications.index') }}" class="mt-5 application-filter-grid">
            <div class="staff-filter-field">
                <label for="application-search" class="staff-form-label">SEARCH</label>
                <input
                    id="application-search"
                    type="text"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Application code, transferor, or transferee"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600"
                >
            </div>

            <div class="staff-filter-field">
                <label for="application-status" class="staff-form-label">STATUS</label>
                <select id="application-status" name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                            {{ $statusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field">
                <label for="application-municipality" class="staff-form-label">MUNICIPALITY</label>
                <select id="application-municipality" name="municipality" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All municipalities</option>
                    @foreach ($municipalities as $municipality)
                        <option value="{{ $municipality }}" @selected(($filters['municipality'] ?? '') === $municipality)>{{ $municipality }}</option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field">
                <label for="application-barangay" class="staff-form-label">BARANGAY</label>
                <select id="application-barangay" name="barangay" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All barangays</option>
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay }}" @selected(($filters['barangay'] ?? '') === $barangay)>{{ $barangay }}</option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field">
                <label for="application-document-reference" class="staff-form-label">DOCUMENT REFERENCE</label>
                <input
                    id="application-document-reference"
                    type="text"
                    name="document_reference_number"
                    value="{{ $filters['document_reference_number'] ?? '' }}"
                    placeholder="Title or tax reference"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600"
                >
            </div>

            <div class="application-filter-actions">
                <button type="submit" class="staff-button staff-button-dark">
                    <i class="fa-solid fa-filter"></i>
                    Apply Filters
                </button>
                <a href="{{ route('staff.applications.index') }}" class="staff-button staff-button-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="staff-panel-title">Working List</h2>
                <p class="staff-panel-subtitle">Showing {{ $applications->count() }} of {{ $applications->total() }} matching application record(s).</p>
            </div>
        </div>

        <div class="staff-table-wrap application-desktop-table">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Application</th>
                        <th>Parties</th>
                        <th>Location</th>
                        <th>Current Stage</th>
                        <th>Date Encoded</th>
                        <th class="staff-table-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td>
                                <a href="{{ route('staff.applications.show', $application) }}" class="staff-link">{{ $application->application_code }}</a>
                                <div class="application-reference">Application record #{{ $application->id }}</div>
                            </td>
                            <td>
                                <div class="application-parties">
                                    <div class="application-party">
                                        <span class="application-party-label">From</span>
                                        <span class="application-party-name">{{ $application->transferor_name }}</span>
                                    </div>
                                    <div class="application-party">
                                        <span class="application-party-label">To</span>
                                        <span class="application-party-name">{{ $application->transferee_name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="font-semibold text-gray-900">{{ $application->municipality ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $application->barangay ?? 'N/A' }}</div>
                            </td>
                            <td>
                                @php $badge = $statusBadges[$application->status] ?? 'staff-badge-slate'; @endphp
                                <span class="staff-badge {{ $badge }}">{{ $application->statusLabel() }}</span>
                            </td>
                            <td class="whitespace-nowrap">{{ $application->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="staff-table-action">
                                <div class="staff-table-action-group">
                                    <a href="{{ route('staff.applications.show', $application) }}" class="staff-button staff-button-light">
                                        Open
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-gray-500">No clearance applications found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="application-mobile-list" aria-label="Application working list">
            @forelse ($applications as $application)
                @php $badge = $statusBadges[$application->status] ?? 'staff-badge-slate'; @endphp
                <article class="application-mobile-card">
                    <div class="application-mobile-head">
                        <div>
                            <a href="{{ route('staff.applications.show', $application) }}" class="application-mobile-code">
                                {{ $application->application_code }}
                            </a>
                            <div class="application-mobile-reference">Application record #{{ $application->id }}</div>
                        </div>
                        <span class="staff-badge {{ $badge }}">{{ $application->statusLabel() }}</span>
                    </div>

                    <div class="application-mobile-section">
                        <div class="application-mobile-label">Parties</div>
                        <div class="application-mobile-party">
                            <span class="application-mobile-party-direction">From</span>
                            <span class="application-mobile-party-name">{{ $application->transferor_name }}</span>
                        </div>
                        <div class="application-mobile-party">
                            <span class="application-mobile-party-direction">To</span>
                            <span class="application-mobile-party-name">{{ $application->transferee_name }}</span>
                        </div>
                    </div>

                    <div class="application-mobile-meta">
                        <div class="application-mobile-meta-item">
                            <div class="application-mobile-label">Location</div>
                            <div class="application-mobile-value">
                                {{ $application->municipality ?? 'N/A' }}
                                <span class="application-mobile-location-sub">{{ $application->barangay ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="application-mobile-meta-item">
                            <div class="application-mobile-label">Date Encoded</div>
                            <div class="application-mobile-value">{{ $application->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</div>
                        </div>
                    </div>

                    <div class="application-mobile-action">
                        <a href="{{ route('staff.applications.show', $application) }}" class="staff-button staff-button-light">
                            Open Application
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
            @empty
                <div class="application-mobile-empty">No clearance applications found.</div>
            @endforelse
        </div>

        <div class="border-t border-gray-200 px-5 py-4">
            {{ $applications->withQueryString()->links() }}
        </div>
    </section>
</x-staff-shell>