<x-staff-shell
    title="Parcel Details"
    subtitle="Staff-side administrative screen for DAR-LTCMS processing, records management, monitoring, and auditability."
    active="parcel-records"
>
<div>
        <div class="space-y-5">

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
                            This page is for parcel record viewing only. It does not transfer ownership,
                            mutate registry records, or finalize legal land transactions.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('staff.parcel-map.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-green-700 text-white rounded-md text-sm font-semibold hover:bg-green-800">
                            Back to Map
                        </a>

                        <a href="{{ route('staff.records.parcels.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-gray-800">
                            Back to Parcel Records
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
                            <dt class="text-gray-500">Area</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ number_format((float) $parcel->area_hectares, 4) }} hectares
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
                        Scope Notice
                    </h3>

                    <p class="text-sm text-amber-800 mt-2">
                        This parcel record may be used as reference when processing land transfer
                        clearance applications. Approval of a clearance application does not mean
                        ownership has already been legally transferred.
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
                        Linked Landholding Records
                    </h3>

                    <span class="text-sm text-gray-500">
                        {{ $parcel->landholdings->count() }} linked record(s)
                    </span>
                </div>

                @if ($parcel->landholdings->isEmpty())
                    <p class="text-sm text-gray-500">
                        No landholding records are currently linked to this parcel.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Landowner</th>
                                    <th class="border px-3 py-2 text-left">Area</th>
                                    <th class="border px-3 py-2 text-left">Status</th>
                                    <th class="border px-3 py-2 text-left">Date Acquired</th>
                                    <th class="border px-3 py-2 text-left">Source Application</th>
                                    <th class="border px-3 py-2 text-left">Remarks</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($parcel->landholdings as $landholding)
                                    <tr class="align-top">
                                        <td class="border px-3 py-2">
                                            {{ $landholding->landowner?->full_name ?? 'N/A' }}
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
                                            @if ($landholding->sourceApplication)
                                                <a href="{{ route('staff.applications.show', $landholding->sourceApplication) }}"
                                                   class="text-green-700 font-semibold hover:underline">
                                                    {{ $landholding->sourceApplication->application_code }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>

                                        <td class="border px-3 py-2">
                                            {{ $landholding->remarks ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900 mb-3">
                    Geometry Reference
                </h3>

                @if ($parcel->geometry_geojson)
                    <pre class="text-xs bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto">{{ json_encode($parcel->geometry_geojson, JSON_PRETTY_PRINT) }}</pre>
                @else
                    <p class="text-sm text-gray-500">
                        No geometry data has been encoded for this parcel.
                    </p>
                @endif
            </div>

        </div>
        <div class="bg-white border shadow-sm rounded-lg p-5 mt-4">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">
                Attached Source Records
            </h3>

            <p class="text-sm text-gray-600 mt-1">
                Digitized source records attached to this parcel for provenance, traceability, and review support.
                These records do not automatically transfer land ownership or mutate Registry of Deeds records.
            </p>
        </div>
    </div>

    @if ($parcel->sourceRecordPackages->count() > 0)
        <div class="mb-6">
            <h4 class="font-semibold text-gray-800 mb-3">
                Source Packages
            </h4>

            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Package Code</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">References</th>
                            <th class="px-4 py-3 text-left">Source</th>
                            <th class="px-4 py-3 text-left">Records</th>
                            <th class="px-4 py-3 text-left">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @foreach ($parcel->sourceRecordPackages as $package)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    {{ $package->package_code }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $package->status_label }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    @if ($package->title_number)
                                        <div><strong>Title:</strong> {{ $package->title_number }}</div>
                                    @endif

                                    @if ($package->landholding_reference_number)
                                        <div><strong>Landholding:</strong> {{ $package->landholding_reference_number }}</div>
                                    @endif

                                    @if ($package->control_number)
                                        <div><strong>Clearance:</strong> {{ $package->control_number }}</div>
                                    @endif

                                    @if (! $package->title_number && ! $package->landholding_reference_number && ! $package->control_number)
                                        —
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    <div>{{ $package->source_book }}</div>
                                    <div class="text-xs text-gray-500">
                                        Page: {{ $package->page_number ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $package->records->count() }}
                                </td>

                                <td class="px-4 py-3">
                                    <a href="{{ route('staff.source-record-packages.show', $package) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-gray-900 text-white rounded-md text-xs font-semibold hover:bg-black">
                                        View Package
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($parcel->legacyRecords->count() > 0)
        <div>
            <h4 class="font-semibold text-gray-800 mb-3">
                Individual Source Records
            </h4>

            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Origin</th>
                            <th class="px-4 py-3 text-left">References</th>
                            <th class="px-4 py-3 text-left">Source</th>
                            <th class="px-4 py-3 text-left">Package</th>
                            <th class="px-4 py-3 text-left">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @foreach ($parcel->legacyRecords as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    {{ $record->record_type_label }}
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
                                    @if ($record->title_number)
                                        <div><strong>Title:</strong> {{ $record->title_number }}</div>
                                    @endif

                                    @if ($record->landholding_reference_number)
                                        <div><strong>Landholding:</strong> {{ $record->landholding_reference_number }}</div>
                                    @endif

                                    @if ($record->control_number)
                                        <div><strong>Clearance:</strong> {{ $record->control_number }}</div>
                                    @endif

                                    @if ($record->lot_number)
                                        <div><strong>Lot:</strong> {{ $record->lot_number }}</div>
                                    @endif

                                    @if (! $record->title_number && ! $record->landholding_reference_number && ! $record->control_number && ! $record->lot_number)
                                        —
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    <div>{{ $record->source_book }}</div>
                                    <div class="text-xs text-gray-500">
                                        Page: {{ $record->page_number ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    @if ($record->package)
                                        <a href="{{ route('staff.source-record-packages.show', $record->package) }}"
                                           class="text-green-700 font-semibold hover:underline">
                                            {{ $record->package->package_code }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">No package</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <a href="{{ route('staff.legacy-records.show', $record) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-gray-900 text-white rounded-md text-xs font-semibold hover:bg-black">
                                        View Record
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($parcel->sourceRecordPackages->count() === 0 && $parcel->legacyRecords->count() === 0)
        <div class="border rounded-lg p-6 text-center text-gray-500">
            No source records are currently attached to this parcel.
        </div>
    @endif
</div>
    </div>
</x-staff-shell>
