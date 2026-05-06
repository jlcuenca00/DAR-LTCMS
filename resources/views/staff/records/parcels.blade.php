<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Parcel Records
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Staff Parcel Record Search
                        </h3>

                        <p class="text-sm text-gray-600 mt-1">
                            Search and review parcel records used for clearance application processing, reference checking, and monitoring.
                        </p>

                        <p class="text-xs text-gray-500 mt-2">
                            This page is for administrative record lookup only. It does not transfer ownership or mutate registry records.
                        </p>
                    </div>

                    <a href="{{ route('staff.records.landowners.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-gray-800">
                        View Landowner Records
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Search and Filter Parcels
                </h3>

                <form method="GET"
                      action="{{ route('staff.records.parcels.index') }}"
                      class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Search
                        </label>

                        <input type="text"
                               name="search"
                               value="{{ $filters['search'] ?? '' }}"
                               placeholder="Parcel code, title no., tax declaration no., remarks"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
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

                    <div class="md:col-span-4 flex items-end gap-2">
                        <button type="submit"
                                class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">
                            Apply Filters
                        </button>

                        <a href="{{ route('staff.records.parcels.index') }}"
                           class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">
                        Parcel List
                    </h3>

                    <div class="text-sm text-gray-500">
                        Showing {{ $parcels->count() }} of {{ $parcels->total() }} parcel record(s)
                    </div>
                </div>

                @if ($parcels->isEmpty())
                    <p class="text-sm text-gray-500">
                        No parcel records found.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Parcel Code</th>
                                    <th class="border px-3 py-2 text-left">Title / Tax Declaration</th>
                                    <th class="border px-3 py-2 text-left">Location</th>
                                    <th class="border px-3 py-2 text-left">Area</th>
                                    <th class="border px-3 py-2 text-left">Status</th>
                                    <th class="border px-3 py-2 text-left">Remarks</th>
                                    <th class="border px-3 py-2 text-left">Created</th>
                                    <th class="border px-3 py-2 text-left">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($parcels as $parcel)
                                    <tr class="align-top hover:bg-gray-50">
                                        <td class="border px-3 py-2 font-mono">
                                            <a href="{{ route('staff.records.parcels.show', $parcel) }}"
                                               class="text-green-700 font-semibold hover:underline">
                                                {{ $parcel->parcel_code }}
                                            </a>
                                        </td>

                                        <td class="border px-3 py-2">
                                            <div>
                                                <strong>Title:</strong>
                                                {{ $parcel->title_no ?? 'N/A' }}
                                            </div>

                                            <div class="text-xs text-gray-500">
                                                Tax Dec.:
                                                {{ $parcel->tax_decl_no ?? 'N/A' }}
                                            </div>
                                        </td>

                                        <td class="border px-3 py-2">
                                            <div>{{ $parcel->barangay ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ $parcel->municipality ?? 'N/A' }}
                                            </div>
                                        </td>

                                        <td class="border px-3 py-2">
                                            {{ number_format((float) $parcel->area_hectares, 4) }} ha
                                        </td>

                                        <td class="border px-3 py-2">
                                            @php
                                                $statusLabel = $parcel->status
                                                    ? ucwords(str_replace('_', ' ', $parcel->status))
                                                    : 'N/A';
                                            @endphp

                                            <span class="inline-flex px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs font-semibold">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>

                                        <td class="border px-3 py-2">
                                            {{ $parcel->remarks ?? 'N/A' }}
                                        </td>

                                        <td class="border px-3 py-2 whitespace-nowrap">
                                            {{ $parcel->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}
                                        </td>

                                        <td class="border px-3 py-2 whitespace-nowrap">
                                            <a href="{{ route('staff.records.parcels.show', $parcel) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-gray-900 text-white rounded-md text-xs font-semibold hover:bg-black">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $parcels->withQueryString()->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>