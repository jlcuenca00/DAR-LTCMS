<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Source Record Details
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="bg-green-100 text-green-800 p-3 rounded border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 text-red-800 p-3 rounded border border-red-200">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border shadow-sm rounded-lg p-5">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-green-700 font-semibold">
                            {{ $record->record_type_label }}
                        </p>

                        <h3 class="text-2xl font-bold text-gray-900 mt-1">
                            Source Record #{{ $record->id }}
                        </h3>

                        <p class="text-sm text-gray-600 mt-2 max-w-3xl">
                            This source record stores documentary/provenance information. It may be linked to a main parcel record
                            or used by staff to create a main parcel record. It does not automatically transfer land ownership
                            or mutate Registry of Deeds records.
                        </p>
                    </div>

                    <a href="{{ route('staff.legacy-records.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-200">
                        Back to Source Records
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                <div class="lg:col-span-2 bg-white border shadow-sm rounded-lg p-5">
                    <h4 class="font-semibold text-gray-900 mb-4">
                        Encoded Source Information
                    </h4>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Origin</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->origin_label }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Scope</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->source_record_scope_label }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Parcel Reference Code</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->parcel_code ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Title Number</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->title_number ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Clearance Control Number</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->control_number ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Application Reference Number</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->application_reference_number ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Landholding Reference Number</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->landholding_reference_number ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Lot Number</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->lot_number ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Survey Number</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->survey_number ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Landowner / Owner Name</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->landowner_name ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Transferor</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->transferor_name ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Transferee</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->transferee_name ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Area</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $record->area_hectares ? $record->area_hectares . ' ha' : '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Crop / Land Use</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->crop_or_land_use ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Barangay</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->barangay ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Municipality</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->municipality ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Record / Clearance Date</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $record->record_date?->format('F d, Y') ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Decision / Status</dt>
                            <dd class="font-semibold text-gray-900">{{ $record->decision_status ?? '—' }}</dd>
                        </div>
                    </dl>

                    @if ($record->remarks || $record->boundary_description || $record->source_notes)
                        <div class="mt-5 border-t pt-4 space-y-3 text-sm">
                            @if ($record->remarks)
                                <div>
                                    <div class="font-semibold text-gray-700">Remarks</div>
                                    <p class="text-gray-700 mt-1">{{ $record->remarks }}</p>
                                </div>
                            @endif

                            @if ($record->boundary_description)
                                <div>
                                    <div class="font-semibold text-gray-700">Boundary / Technical Description</div>
                                    <p class="text-gray-700 mt-1">{{ $record->boundary_description }}</p>
                                </div>
                            @endif

                            @if ($record->source_notes)
                                <div>
                                    <div class="font-semibold text-gray-700">Source Notes</div>
                                    <p class="text-gray-700 mt-1">{{ $record->source_notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="space-y-4">

                    <div class="bg-white border shadow-sm rounded-lg p-5">
                        <h4 class="font-semibold text-gray-900">
                            Source / Provenance
                        </h4>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Source Book / File</dt>
                                <dd class="font-semibold text-gray-900">{{ $record->source_book }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Page Number</dt>
                                <dd class="font-semibold text-gray-900">{{ $record->page_number ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Transcribed By</dt>
                                <dd class="font-semibold text-gray-900">{{ $record->transcribed_by }}</dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Transcription Date</dt>
                                <dd class="font-semibold text-gray-900">
                                    {{ $record->transcription_date?->format('F d, Y') }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white border shadow-sm rounded-lg p-5">
                        <h4 class="font-semibold text-gray-900">
                            Linked Parcel
                        </h4>

                        @if ($record->parcel)
                            <p class="text-sm text-gray-600 mt-2">
                                This source record is attached to this main parcel record:
                            </p>

                            <a href="{{ route('staff.records.parcels.show', $record->parcel) }}"
                               class="block mt-3 p-3 rounded border bg-green-50 text-green-900 font-semibold hover:bg-green-100">
                                {{ $record->parcel->parcel_code }}
                            </a>
                        @else
                            <p class="text-sm text-gray-600 mt-2">
                                This source record is not yet linked to a main parcel record.
                            </p>
                        @endif
                    </div>

                </div>
            </div>

            @if (! $record->parcel)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                    <div class="bg-white border shadow-sm rounded-lg p-5">
                        <h4 class="font-semibold text-gray-900">
                            Link to Existing Parcel
                        </h4>

                        <p class="text-sm text-gray-600 mt-1">
                            Use this if the parcel already exists in the main Parcel Records module.
                        </p>

                        <form method="POST"
                              action="{{ route('staff.legacy-records.link-parcel', $record) }}"
                              class="mt-4 space-y-4">
                            @csrf

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Existing Parcel
                                </label>

                                <select name="parcel_id" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">Select parcel</option>

                                    @foreach ($parcels as $parcel)
                                        <option value="{{ $parcel->id }}">
                                            {{ $parcel->parcel_code }}
                                            @if ($parcel->title_no)
                                                — {{ $parcel->title_no }}
                                            @endif
                                            @if ($parcel->barangay || $parcel->municipality)
                                                — {{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit"
                                    class="px-4 py-2 bg-green-700 text-white rounded-md text-sm font-semibold hover:bg-green-800">
                                Link Source Record
                            </button>
                        </form>
                    </div>

                    <div class="bg-white border shadow-sm rounded-lg p-5">
                        <h4 class="font-semibold text-gray-900">
                            Create Main Parcel Record From Source
                        </h4>

                        <p class="text-sm text-gray-600 mt-1">
                            Use this only after staff has confirmed that the source record represents a real parcel
                            that should be added to the main Parcel Records module.
                        </p>

                        <form method="POST"
                              action="{{ route('staff.legacy-records.create-parcel', $record) }}"
                              class="mt-4 space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Parcel Code *</label>
                                    <input name="parcel_code"
                                           value="{{ old('parcel_code', $record->parcel_code) }}"
                                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Title Number</label>
                                    <input name="title_no"
                                           value="{{ old('title_no', $record->title_number) }}"
                                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Area</label>
                                    <input type="number"
                                           step="0.0001"
                                           min="0"
                                           name="area_hectares"
                                           value="{{ old('area_hectares', $record->area_hectares) }}"
                                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status *</label>
                                    <select name="status" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Barangay</label>
                                    <input name="barangay"
                                           value="{{ old('barangay', $record->barangay) }}"
                                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Municipality</label>
                                    <input name="municipality"
                                           value="{{ old('municipality', $record->municipality) }}"
                                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Link Existing Landowner as Active Landholding
                                    </label>

                                    <select name="landowner_id" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="">Do not link landowner yet</option>

                                        @foreach ($landowners as $landowner)
                                            <option value="{{ $landowner->id }}">
                                                {{ $landowner->full_name }}
                                                @if ($landowner->barangay || $landowner->municipality)
                                                    — {{ $landowner->barangay ?? 'N/A' }}, {{ $landowner->municipality ?? 'N/A' }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    <p class="text-xs text-gray-500 mt-1">
                                        The map gets owner names through active landholding records.
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date Acquired</label>
                                    <input type="date"
                                           name="date_acquired"
                                           value="{{ old('date_acquired') }}"
                                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Province</label>
                                    <input name="province"
                                           value="{{ old('province', $record->province ?? 'Negros Oriental') }}"
                                           class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Parcel GeoJSON Geometry
                                    </label>

                                    <textarea name="geometry_geojson"
                                              rows="6"
                                              class="mt-1 w-full border-gray-300 rounded-md shadow-sm font-mono text-xs">{{ old('geometry_geojson', $record->source_geometry_geojson ? json_encode($record->source_geometry_geojson) : '') }}</textarea>

                                    <p class="text-xs text-gray-500 mt-1">
                                        Only main Parcel Records with saved geometry appear on the Parcel Map Viewer.
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Remarks</label>
                                    <textarea name="remarks"
                                              rows="3"
                                              class="mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('remarks', 'Created from source record #' . $record->id . '.') }}</textarea>
                                </div>
                            </div>

                            <button type="submit"
                                    class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-black">
                                Create Parcel Record
                            </button>
                        </form>
                    </div>

                </div>
            @endif

        </div>
    </div>
</x-app-layout>