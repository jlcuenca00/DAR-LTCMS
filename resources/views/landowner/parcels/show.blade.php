<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My Parcel Details
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Read-only parcel and landholding record linked to your landowner account.
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-green-700">
                            DAR Negros Oriental Provincial Office
                        </p>

                        <h3 class="text-2xl font-bold text-gray-900 mt-1">
                            {{ $parcel->parcel_code }}
                        </h3>

                        <p class="text-sm text-gray-600 mt-2">
                            This page only displays parcel and landholding records linked to your account.
                            It does not allow editing, clearance approval, ownership transfer, or registry mutation.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('landowner.parcel-map.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-green-700 text-white rounded-md text-sm font-semibold hover:bg-green-800">
                            Back to Map
                        </a>

                        <a href="{{ route('landowner.parcels.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-gray-800">
                            Back to My Parcels
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg p-6 border">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        Parcel Reference Information
                    </h3>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Parcel Code</dt>
                            <dd class="font-semibold text-gray-900">{{ $parcel->parcel_code }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'N/A' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Title Number</dt>
                            <dd class="font-semibold text-gray-900">{{ $parcel->title_no ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Tax Declaration Number</dt>
                            <dd class="font-semibold text-gray-900">{{ $parcel->tax_decl_no ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Parcel Area</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $parcel->area_hectares ? number_format((float) $parcel->area_hectares, 4) . ' hectares' : 'N/A' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Province</dt>
                            <dd class="font-semibold text-gray-900">{{ $parcel->province ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Municipality</dt>
                            <dd class="font-semibold text-gray-900">{{ $parcel->municipality ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Barangay</dt>
                            <dd class="font-semibold text-gray-900">{{ $parcel->barangay ?? 'N/A' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6">
                        <h4 class="font-semibold text-gray-900">
                            Remarks
                        </h4>

                        <p class="text-sm text-gray-700 mt-2">
                            {{ $parcel->remarks ?? 'No remarks recorded.' }}
                        </p>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
                    <h3 class="font-semibold text-amber-900">
                        Landowner Access Notice
                    </h3>

                    <p class="text-sm text-amber-800 mt-2">
                        You may only view parcel and application information linked to your own landowner account.
                        Other landowner records are not accessible from this portal.
                    </p>

                    <div class="mt-5 border-t border-amber-200 pt-4">
                        <p class="text-xs uppercase tracking-wide font-semibold text-amber-700">
                            Map Geometry
                        </p>

                        <p class="text-sm font-semibold text-amber-900 mt-1">
                            {{ $parcel->geometry_geojson ? 'Available' : 'Not yet encoded' }}
                        </p>
                    </div>
                </div>

            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">
                        My Linked Landholding Record(s)
                    </h3>

                    <span class="text-sm text-gray-500">
                        {{ $landholdings->count() }} linked record(s)
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 text-left">Landowner</th>
                                <th class="border px-3 py-2 text-left">Area</th>
                                <th class="border px-3 py-2 text-left">Status</th>
                                <th class="border px-3 py-2 text-left">Date Acquired</th>
                                <th class="border px-3 py-2 text-left">Remarks</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($landholdings as $landholding)
                                <tr class="align-top">
                                    <td class="border px-3 py-2">
                                        {{ $landowner->full_name }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ number_format((float) $landholding->area_hectares, 4) }} ha
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $landholding->status ? ucwords(str_replace('_', ' ', $landholding->status)) : 'N/A' }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $landholding->date_acquired ?? 'N/A' }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $landholding->remarks ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900">
                    Scope Reminder
                </h3>

                <p class="text-sm text-gray-600 mt-2">
                    This system supports clearance processing, generation, monitoring, and record viewing only.
                    Approval of a clearance application does not automatically transfer ownership or mutate registry records.
                </p>
            </div>

        </div>
    </div>
</x-app-layout>