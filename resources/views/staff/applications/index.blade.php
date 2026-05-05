<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Land Transfer Clearance Applications
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 text-green-800 p-3 rounded border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 text-red-800 p-3 rounded border border-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Application Records
                        </h3>

                        <p class="text-sm text-gray-600 mt-1">
                            Search, filter, review, and monitor land transfer clearance applications.
                        </p>

                        <p class="text-xs text-gray-500 mt-2">
                            This page is for application processing and monitoring only. Approval does not automatically transfer land ownership or mutate registry records.
                        </p>
                    </div>

                    @if (\Illuminate\Support\Facades\Route::has('staff.applications.create'))
                        <a href="{{ route('staff.applications.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-gray-800">
                            New Application
                        </a>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Search and Filter Applications
                </h3>

                <form method="GET"
                      action="{{ route('staff.applications.index') }}"
                      class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Search
                        </label>

                        <input type="text"
                               name="search"
                               value="{{ $filters['search'] ?? '' }}"
                               placeholder="Application code, transferor, or transferee"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>

                        <select name="status"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All statuses</option>

                            @foreach ($statuses as $status)
                                <option value="{{ $status }}"
                                    {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Municipality
                        </label>

                        <select name="municipality"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All municipalities</option>

                            @foreach ($municipalities as $municipality)
                                <option value="{{ $municipality }}"
                                    {{ ($filters['municipality'] ?? '') === $municipality ? 'selected' : '' }}>
                                    {{ $municipality }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Barangay
                        </label>

                        <select name="barangay"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All barangays</option>

                            @foreach ($barangays as $barangay)
                                <option value="{{ $barangay }}"
                                    {{ ($filters['barangay'] ?? '') === $barangay ? 'selected' : '' }}>
                                    {{ $barangay }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Document Reference Number
                        </label>

                        <input type="text"
                               name="document_reference_number"
                               value="{{ $filters['document_reference_number'] ?? '' }}"
                               placeholder="Title no., tax declaration no., etc."
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">
                            Apply Filters
                        </button>

                        <a href="{{ route('staff.applications.index') }}"
                           class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">
                        Application List
                    </h3>

                    <div class="text-sm text-gray-500">
                        Showing {{ $applications->count() }} of {{ $applications->total() }} application(s)
                    </div>
                </div>

                @if ($applications->isEmpty())
                    <p class="text-sm text-gray-500">
                        No applications found.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Application Code</th>
                                    <th class="border px-3 py-2 text-left">Transferor</th>
                                    <th class="border px-3 py-2 text-left">Transferee</th>
                                    <th class="border px-3 py-2 text-left">Location</th>
                                    <th class="border px-3 py-2 text-left">Status</th>
                                    <th class="border px-3 py-2 text-left">Created</th>
                                    <th class="border px-3 py-2 text-left">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($applications as $application)
                                    <tr class="align-top">
                                        <td class="border px-3 py-2 font-mono">
                                            {{ $application->application_code }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            {{ $application->transferor_name ?? 'N/A' }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            {{ $application->transferee_name ?? 'N/A' }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            <div>
                                                {{ $application->barangay ?? 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $application->municipality ?? 'N/A' }}
                                            </div>
                                        </td>

                                        <td class="border px-3 py-2">
                                            @php
                                                $statusLabel = ucwords(str_replace('_', ' ', $application->status));
                                            @endphp

                                            @if ($application->status === 'approved')
                                                <span class="inline-flex px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-semibold">
                                                    {{ $statusLabel }}
                                                </span>
                                            @elseif ($application->status === 'not_approved')
                                                <span class="inline-flex px-2 py-1 rounded bg-red-100 text-red-800 text-xs font-semibold">
                                                    {{ $statusLabel }}
                                                </span>
                                            @elseif ($application->status === 'pending_review')
                                                <span class="inline-flex px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs font-semibold">
                                                    {{ $statusLabel }}
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs font-semibold">
                                                    {{ $statusLabel }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="border px-3 py-2 whitespace-nowrap">
                                            {{ $application->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            <a href="{{ route('staff.applications.show', $application) }}"
                                               class="text-blue-700 hover:underline font-semibold">
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $applications->withQueryString()->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>