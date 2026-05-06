<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Source Records Archive
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="bg-green-100 text-green-800 p-3 rounded border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white border shadow-sm rounded-lg p-5">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Source Records Archive
                        </h3>
                        <p class="text-sm text-gray-600 mt-1 max-w-3xl">
                            Digitized title, landholding, parcel, and clearance source records used for DAR-LTCMS review,
                            monitoring, traceability, and record support. These records may support current parcel data,
                            but they do not automatically transfer land ownership or mutate Registry of Deeds records.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
    <a href="{{ route('staff.source-record-packages.create') }}"
       class="inline-flex items-center px-4 py-2 bg-green-700 text-white rounded-md text-sm font-semibold hover:bg-green-800">
        Encode Source Package
    </a>

    <a href="{{ route('staff.source-record-package-imports.create') }}"
       class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-black">
        Import Source Packages
    </a>

    <a href="{{ route('staff.legacy-records.create', ['record_type' => 'historical_clearance']) }}"
       class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-200">
        Encode Single Clearance
    </a>
</div>
                </div>
            </div>

            <div class="bg-white border shadow-sm rounded-lg p-5">
                <form method="GET" action="{{ route('staff.legacy-records.index') }}"
                      class="grid grid-cols-1 md:grid-cols-5 gap-3">

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Search
                        </label>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Title, control no., parcel code, landowner, lot no."
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Type
                        </label>
                        <select name="record_type" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All Types</option>
                            @foreach ($recordTypes as $value => $label)
                                <option value="{{ $value }}" @selected(request('record_type') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Origin
                        </label>
                        <select name="origin" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All Origins</option>
                            @foreach ($origins as $value => $label)
                                <option value="{{ $value }}" @selected(request('origin') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Municipality
                        </label>
                        <input type="text"
                               name="municipality"
                               value="{{ request('municipality') }}"
                               placeholder="e.g. Dumaguete"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div class="md:col-span-5 flex flex-wrap gap-2">
                        <button type="submit"
                                class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-black">
                            Apply Filters
                        </button>

                        <a href="{{ route('staff.legacy-records.index') }}"
                           class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-200">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white border shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-left">Reference</th>
                                <th class="px-4 py-3 text-left">Names</th>
                                <th class="px-4 py-3 text-left">Location</th>
                                <th class="px-4 py-3 text-left">Source</th>
                                <th class="px-4 py-3 text-left">Origin</th>
                                <th class="px-4 py-3 text-left">Linked Parcel</th>
                                <th class="px-4 py-3 text-left">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($records as $record)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold text-gray-900">
                                        {{ $record->record_type_label }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        @if ($record->parcel_code)
                                            <div><strong>Parcel Ref:</strong> {{ $record->parcel_code }}</div>
                                        @endif

                                        @if ($record->title_number)
                                            <div><strong>Title:</strong> {{ $record->title_number }}</div>
                                        @endif

                                        @if ($record->control_number)
                                            <div><strong>Control:</strong> {{ $record->control_number }}</div>
                                        @endif

                                        @if ($record->application_reference_number)
                                            <div><strong>Application Ref:</strong> {{ $record->application_reference_number }}</div>
                                        @endif

                                        @if ($record->landholding_reference_number)
                                            <div><strong>Landholding Ref:</strong> {{ $record->landholding_reference_number }}</div>
                                        @endif

                                        @if ($record->lot_number)
                                            <div><strong>Lot:</strong> {{ $record->lot_number }}</div>
                                        @endif

                                        @if (! $record->parcel_code && ! $record->title_number && ! $record->control_number && ! $record->application_reference_number && ! $record->landholding_reference_number && ! $record->lot_number)
                                            —
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        @if ($record->landowner_name)
                                            <div><strong>Owner:</strong> {{ $record->landowner_name }}</div>
                                        @endif

                                        @if ($record->transferor_name)
                                            <div><strong>Transferor:</strong> {{ $record->transferor_name }}</div>
                                        @endif

                                        @if ($record->transferee_name)
                                            <div><strong>Transferee:</strong> {{ $record->transferee_name }}</div>
                                        @endif

                                        @if (! $record->landowner_name && ! $record->transferor_name && ! $record->transferee_name)
                                            —
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->barangay ?? '—' }},
                                        {{ $record->municipality ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        <div>{{ $record->source_book }}</div>
                                        <div class="text-xs text-gray-500">
                                            Page: {{ $record->page_number ?? '—' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                            @if ($record->origin === 'encoded') bg-blue-100 text-blue-800
                                            @elseif ($record->origin === 'imported') bg-purple-100 text-purple-800
                                            @else bg-gray-100 text-gray-700
                                            @endif">
                                            {{ $record->origin_label }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        @if ($record->parcel)
                                            <a href="{{ route('staff.records.parcels.show', $record->parcel) }}"
                                               class="text-green-700 font-semibold hover:underline">
                                                {{ $record->parcel->parcel_code }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">Not linked</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        <a href="{{ route('staff.legacy-records.show', $record) }}"
                                           class="inline-flex items-center px-3 py-1.5 bg-gray-900 text-white rounded-md text-xs font-semibold hover:bg-black">
                                            View / Link
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                        No source records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t">
                    {{ $records->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>