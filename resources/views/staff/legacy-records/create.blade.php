<x-staff-shell
    title="Encode Source Record"
    subtitle="Staff-side administrative screen for DAR-LTCMS processing, records management, monitoring, and auditability."
    active="source-records"
>
<div>
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

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
                <h3 class="text-lg font-semibold text-gray-900">
                    Manual Source Record Encoding
                </h3>

                <p class="text-sm text-gray-600 mt-1">
                    Encode digitized source records from official DAR files, title records, landholding references,
                    parcel source documents, or historical clearance records. These records support parcel and
                    application review, but they do not automatically transfer land ownership.
                </p>

                <div class="flex flex-wrap gap-2 mt-4">
                    @foreach ($recordTypes as $value => $label)
                        <a href="{{ route('staff.legacy-records.create', ['record_type' => $value]) }}"
                           class="px-3 py-2 rounded-md text-sm font-semibold border
                           {{ $recordType === $value ? 'bg-green-700 text-white border-green-700' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('staff.legacy-records.store') }}"
                  class="bg-white border shadow-sm rounded-lg p-5 space-y-6">
                @csrf

                <input type="hidden" name="record_type" value="{{ $recordType }}">

                <div>
                    <h4 class="font-semibold text-gray-900 mb-3">
                        Record Details
                    </h4>

                    @if ($recordType === 'title')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title Number *</label>
                                <input name="title_number"
                                       value="{{ old('title_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Registered Owner / Landowner Name *</label>
                                <input name="landowner_name"
                                       value="{{ old('landowner_name') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Lot Number</label>
                                <input name="lot_number"
                                       value="{{ old('lot_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Survey Number</label>
                                <input name="survey_number"
                                       value="{{ old('survey_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title / Registration Date</label>
                                <input type="date"
                                       name="record_date"
                                       value="{{ old('record_date') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                    @endif

                    @if ($recordType === 'landholding')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Landholding Reference Number *</label>
                                <input name="landholding_reference_number"
                                       value="{{ old('landholding_reference_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Landowner Name *</label>
                                <input name="landowner_name"
                                       value="{{ old('landowner_name') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title Number</label>
                                <input name="title_number"
                                       value="{{ old('title_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Lot Number</label>
                                <input name="lot_number"
                                       value="{{ old('lot_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Previous DAR Reference Number</label>
                                <input name="previous_dar_reference_number"
                                       value="{{ old('previous_dar_reference_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Record Date</label>
                                <input type="date"
                                       name="record_date"
                                       value="{{ old('record_date') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                    @endif

                    @if ($recordType === 'parcel_source')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Parcel Reference Code *</label>
                                <input name="parcel_code"
                                       value="{{ old('parcel_code') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Owner / Landowner Name *</label>
                                <input name="landowner_name"
                                       value="{{ old('landowner_name') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Lot Number *</label>
                                <input name="lot_number"
                                       value="{{ old('lot_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Survey Number</label>
                                <input name="survey_number"
                                       value="{{ old('survey_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title Number</label>
                                <input name="title_number"
                                       value="{{ old('title_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Record Date</label>
                                <input type="date"
                                       name="record_date"
                                       value="{{ old('record_date') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Boundary / Technical Description Notes
                                </label>
                                <textarea name="boundary_description"
                                          rows="3"
                                          class="mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('boundary_description') }}</textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Source GeoJSON Geometry
                                </label>

                                <textarea name="source_geometry_geojson"
                                          rows="6"
                                          placeholder='Example: {"type":"Point","coordinates":[123.3054,9.3068]}'
                                          class="mt-1 w-full border-gray-300 rounded-md shadow-sm font-mono text-xs">{{ old('source_geometry_geojson') }}</textarea>

                                <p class="text-xs text-gray-500 mt-1">
                                    This does not appear on the map by itself. It can be copied into a main Parcel Record
                                    after staff confirmation.
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($recordType === 'historical_clearance')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Clearance Control Number *</label>
                                <input name="control_number"
                                       value="{{ old('control_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Application Reference Number</label>
                                <input name="application_reference_number"
                                       value="{{ old('application_reference_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Clearance Date *</label>
                                <input type="date"
                                       name="record_date"
                                       value="{{ old('record_date') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Decision / Status</label>
                                <input name="decision_status"
                                       value="{{ old('decision_status') }}"
                                       placeholder="Approved, Not Approved, Released, etc."
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Transferor Name *</label>
                                <input name="transferor_name"
                                       value="{{ old('transferor_name') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Transferee Name *</label>
                                <input name="transferee_name"
                                       value="{{ old('transferee_name') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title Number</label>
                                <input name="title_number"
                                       value="{{ old('title_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Lot Number</label>
                                <input name="lot_number"
                                       value="{{ old('lot_number') }}"
                                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                    @endif
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 mb-3">
                        Source Scope / Parcel Link
                    </h4>

                    <p class="text-sm text-gray-600 mb-4">
                        Link this source record to an existing main parcel record only if staff has confirmed the match.
                        If it is not yet matched, save it as an unlinked source record first.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Source Record Scope *
                            </label>

                            <select name="source_record_scope"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                @foreach ($sourceScopes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('source_record_scope', 'current_active') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Link to Existing Parcel
                            </label>

                            <select name="parcel_id"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Not linked yet</option>

                                @foreach ($parcels as $parcel)
                                    <option value="{{ $parcel->id }}" @selected((string) old('parcel_id') === (string) $parcel->id)>
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

                            <p class="text-xs text-gray-500 mt-1">
                                Linking attaches this source record as documentary/provenance support. It does not change ownership.
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 mb-3">
                        Parcel / Location Information
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Area in Hectares</label>
                            <input type="number"
                                   step="0.0001"
                                   min="0"
                                   name="area_hectares"
                                   value="{{ old('area_hectares') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Crop / Land Use</label>
                            <input name="crop_or_land_use"
                                   value="{{ old('crop_or_land_use') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Province</label>
                            <input name="province"
                                   value="{{ old('province', 'Negros Oriental') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Barangay</label>
                            <input name="barangay"
                                   value="{{ old('barangay') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Municipality</label>
                            <input name="municipality"
                                   value="{{ old('municipality') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 mb-3">
                        Provenance / Source Details
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Source Book / File *</label>
                            <input name="source_book"
                                   value="{{ old('source_book') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Page Number</label>
                            <input name="page_number"
                                   value="{{ old('page_number') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Transcribed By *</label>
                            <input name="transcribed_by"
                                   value="{{ old('transcribed_by', auth()->user()->name) }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Transcription Date *</label>
                            <input type="date"
                                   name="transcription_date"
                                   value="{{ old('transcription_date', now()->toDateString()) }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks</label>
                    <textarea name="remarks"
                              rows="3"
                              class="mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('remarks') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Source Notes</label>
                    <textarea name="source_notes"
                              rows="3"
                              class="mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('source_notes') }}</textarea>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-green-700 text-white rounded-md text-sm font-semibold hover:bg-green-800">
                        Save Source Record
                    </button>

                    <a href="{{ route('staff.legacy-records.index') }}"
                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-200">
                        Cancel
                    </a>
                </div>
            </form>

        </div>
    </div>
</x-staff-shell>
