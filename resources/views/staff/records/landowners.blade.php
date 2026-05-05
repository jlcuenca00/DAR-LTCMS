<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Landowner Records
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Staff Landowner Record Search
                        </h3>

                        <p class="text-sm text-gray-600 mt-1">
                            Search and review landowner records used for clearance application processing and monitoring.
                        </p>

                        <p class="text-xs text-gray-500 mt-2">
                            This page is for administrative record lookup only. It does not transfer ownership or mutate registry records.
                        </p>
                    </div>

                    <a href="{{ route('staff.records.parcels.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-gray-800">
                        View Parcel Records
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Search and Filter Landowners
                </h3>

                <form method="GET"
                      action="{{ route('staff.records.landowners.index') }}"
                      class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Search
                        </label>

                        <input type="text"
                               name="search"
                               value="{{ $filters['search'] ?? '' }}"
                               placeholder="Name, contact, or address"
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
                            Account Link Status
                        </label>

                        <select name="linked_status"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All records</option>
                            <option value="linked" {{ ($filters['linked_status'] ?? '') === 'linked' ? 'selected' : '' }}>
                                Linked to user account
                            </option>
                            <option value="unlinked" {{ ($filters['linked_status'] ?? '') === 'unlinked' ? 'selected' : '' }}>
                                Not linked to user account
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-4 flex items-end gap-2">
                        <button type="submit"
                                class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">
                            Apply Filters
                        </button>

                        <a href="{{ route('staff.records.landowners.index') }}"
                           class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">
                        Landowner List
                    </h3>

                    <div class="text-sm text-gray-500">
                        Showing {{ $landowners->count() }} of {{ $landowners->total() }} landowner record(s)
                    </div>
                </div>

                @if ($landowners->isEmpty())
                    <p class="text-sm text-gray-500">
                        No landowner records found.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Landowner</th>
                                    <th class="border px-3 py-2 text-left">Contact</th>
                                    <th class="border px-3 py-2 text-left">Address</th>
                                    <th class="border px-3 py-2 text-left">Municipality / Barangay</th>
                                    <th class="border px-3 py-2 text-left">Linked User Account</th>
                                    <th class="border px-3 py-2 text-left">Created</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($landowners as $landowner)
                                    <tr class="align-top">
                                        <td class="border px-3 py-2">
                                            <div class="font-medium text-gray-900">
                                                {{ $landowner->full_name }}
                                            </div>

                                            <div class="text-xs text-gray-500">
                                                Landowner ID: {{ $landowner->id }}
                                            </div>
                                        </td>

                                        <td class="border px-3 py-2">
                                            {{ $landowner->contact_number ?? 'N/A' }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            {{ $landowner->address_line ?? 'N/A' }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            <div>{{ $landowner->municipality ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ $landowner->barangay ?? 'N/A' }}
                                            </div>
                                        </td>

                                        <td class="border px-3 py-2">
                                            @if ($landowner->user)
                                                <div class="font-medium text-gray-900">
                                                    {{ $landowner->user->name }}
                                                </div>

                                                <div class="text-xs text-gray-500">
                                                    {{ $landowner->user->email }}
                                                </div>

                                                @if ($landowner->user->is_active)
                                                    <span class="inline-flex mt-1 px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-semibold">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="inline-flex mt-1 px-2 py-1 rounded bg-red-100 text-red-800 text-xs font-semibold">
                                                        Inactive
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-gray-500">Not linked</span>
                                            @endif
                                        </td>

                                        <td class="border px-3 py-2 whitespace-nowrap">
                                            {{ $landowner->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $landowners->withQueryString()->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>