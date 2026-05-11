<x-staff-shell
    title="Land Transfer Clearance Applications"
    subtitle="Search, filter, review, and monitor staff-encoded clearance application records."
    active="applications"
>
    <x-slot name="actions">
        @if (\Illuminate\Support\Facades\Route::has('staff.applications.create'))
            <a href="{{ route('staff.applications.create') }}" class="staff-button staff-button-primary">
                <i class="fa-solid fa-plus"></i>
                Encode New Application
            </a>
        @endif
    </x-slot>

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

    <section class="staff-scope-banner">
        <div>
            <h3>Application Records</h3>
            <p>
                Search and Filter Applications for administrative processing and monitoring only. Approval records the clearance result and does not automatically transfer land ownership or mutate registry records.
            </p>
        </div>
        <span class="staff-scope-pill">Clearance Processing Only</span>
    </section>

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="staff-panel-title">Search and Filter Applications</h2>
                <p class="staff-panel-subtitle">Filter by party name, application code, location, workflow status, or document reference number.</p>
            </div>
            <p class="text-sm font-bold text-gray-500">{{ $applications->total() }} record(s)</p>
        </div>

        <form method="GET" action="{{ route('staff.applications.index') }}" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Application code, transferor, or transferee" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                            {{ ucwords(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Municipality</label>
                <select name="municipality" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All municipalities</option>
                    @foreach ($municipalities as $municipality)
                        <option value="{{ $municipality }}" @selected(($filters['municipality'] ?? '') === $municipality)>{{ $municipality }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Barangay</label>
                <select name="barangay" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All barangays</option>
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay }}" @selected(($filters['barangay'] ?? '') === $barangay)>{{ $barangay }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Document Reference</label>
                <input type="text" name="document_reference_number" value="{{ $filters['document_reference_number'] ?? '' }}" placeholder="Title / tax ref." class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div class="md:col-span-3 xl:col-span-6 flex flex-wrap gap-2">
                <button type="submit" class="staff-button staff-button-dark"><i class="fa-solid fa-filter"></i>Apply Filters</button>
                <a href="{{ route('staff.applications.index') }}" class="staff-button staff-button-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="staff-panel-title">Application List</h2>
                <p class="staff-panel-subtitle">Showing {{ $applications->count() }} of {{ $applications->total() }} application record(s).</p>
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
                                    $badge = match ($application->status) {
                                        \App\Models\LandTransferApplication::STATUS_APPROVED => 'staff-badge-green',
                                        \App\Models\LandTransferApplication::STATUS_NOT_APPROVED => 'staff-badge-red',
                                        \App\Models\LandTransferApplication::STATUS_PENDING_REVIEW => 'staff-badge-amber',
                                        default => 'staff-badge-slate',
                                    };
                                @endphp
                                <span class="staff-badge {{ $badge }}">
                                    {{ $application->status === 'approved' ? 'Approved Clearance' : ucwords(str_replace('_', ' ', $application->status)) }}
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
