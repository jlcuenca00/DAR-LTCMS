<x-staff-shell
    title="Land Transfer Clearance Applications"
    subtitle="Search, filter, review, and monitor staff-encoded clearance application records."
    active="applications"
>
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

            // Temporary legacy compatibility during the phased flow revision.
                    ];
    @endphp

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="staff-panel-title">Search and Filter Applications</h2>
                <p class="staff-panel-subtitle">Filter by party name, application code, location, workflow status, or document reference number.</p>
            </div>
            <p class="text-sm font-bold text-gray-500">{{ $applications->total() }} record(s)</p>
        </div>

        <form method="GET" action="{{ route('staff.applications.index') }}" class="mt-5 staff-filter-grid filter-grid-5">
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
                <select
                    id="application-status"
                    name="status"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600"
                >
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
                <select
                    id="application-municipality"
                    name="municipality"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600"
                >
                    <option value="">All municipalities</option>
                    @foreach ($municipalities as $municipality)
                        <option value="{{ $municipality }}" @selected(($filters['municipality'] ?? '') === $municipality)>
                            {{ $municipality }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field">
                <label for="application-barangay" class="staff-form-label">BARANGAY</label>
                <select
                    id="application-barangay"
                    name="barangay"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600"
                >
                    <option value="">All barangays</option>
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay }}" @selected(($filters['barangay'] ?? '') === $barangay)>
                            {{ $barangay }}
                        </option>
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
                    placeholder="Title / tax ref."
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600"
                >
            </div>

            <div class="staff-filter-actions">
                <button type="submit" class="staff-button staff-button-dark">
                    <i class="fa-solid fa-filter"></i>
                    Apply Filters
                </button>

                <a href="{{ route('staff.applications.index') }}" class="staff-button staff-button-light">
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="staff-panel-title">Application List</h2>
                <p class="staff-panel-subtitle">Showing {{ $applications->count() }} of {{ $applications->total() }} application record(s).</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 lg:justify-end" data-main-card-actions-moved>
                @if (\Illuminate\Support\Facades\Route::has('staff.applications.create'))
                    <a href="{{ route('staff.applications.create') }}" class="staff-button staff-button-primary">
                        <i class="fa-solid fa-plus"></i>
                        Encode New Application
                    </a>
                @endif
            </div>
        </div>

        <div class="staff-table-wrap">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Application Code</th>
                        <th>Transferor</th>
                        <th>Transferee</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Date Encoded</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td><a href="{{ route('staff.applications.show', $application) }}" class="staff-link">{{ $application->application_code }}</a></td>
                            <td>{{ $application->transferor_name }}</td>
                            <td>{{ $application->transferee_name }}</td>
                            <td>
                                <div>{{ $application->municipality ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $application->barangay ?? 'N/A' }}</div>
                            </td>
                            <td>
                                @php
                                    $badge = $statusBadges[$application->status] ?? 'staff-badge-slate';
                                @endphp
                                <span class="staff-badge {{ $badge }}">
                                    {{ $application->statusLabel() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">{{ $application->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="text-right"><a href="{{ route('staff.applications.show', $application) }}" class="staff-button staff-button-light">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-gray-500">No clearance applications found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4">
            {{ $applications->withQueryString()->links() }}
        </div>
    </section>
</x-staff-shell>
