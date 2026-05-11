<x-staff-shell
    title="Encode Source Package"
    subtitle="Staff-side administrative screen for DAR-LTCMS processing, records management, monitoring, and auditability."
    active="source-records"
>
<div>
        <div class="space-y-5">

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
                    Source Package Encoding
                </h3>

                <p class="text-sm text-gray-600 mt-1 max-w-4xl">
                    Encode a connected set of source records from official DAR files. A package may include title,
                    landholding, parcel source, and historical clearance details. The package may later be linked to
                    an existing parcel or used by authorized staff to create a main parcel record. This does not
                    automatically transfer ownership or mutate Registry of Deeds records.
                </p>
            </div>

            <form method="POST" action="{{ route('staff.source-record-packages.store') }}"
                  class="bg-white border shadow-sm rounded-lg p-5 space-y-6">
                @csrf

                <div>
                    <h4 class="font-semibold text-gray-900 mb-3">
                        Included Source Sections
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <label class="flex items-center gap-2 rounded border p-3 bg-gray-50">
                            <input type="checkbox"
                                   name="include_title"
                                   value="1"
                                   @checked(old('include_title', true))
                                   class="rounded border-gray-300 text-green-700 focus:ring-green-600">
                            <span class="text-sm font-semibold text-gray-800">Title</span>
                        </label>

                        <label class="flex items-center gap-2 rounded border p-3 bg-gray-50">
                            <input type="checkbox"
                                   name="include_landholding"
                                   value="1"
                                   @checked(old('include_landholding', true))
                                   class="rounded border-gray-300 text-green-700 focus:ring-green-600">
                            <span class="text-sm font-semibold text-gray-800">Landholding</span>
                        </label>

                        <label class="flex items-center gap-2 rounded border p-3 bg-gray-50">
                            <input type="checkbox"
                                   name="include_parcel_source"
                                   value="1"
                                   @checked(old('include_parcel_source', true))
                                   class="rounded border-gray-300 text-green-700 focus:ring-green-600">
                            <span class="text-sm font-semibold text-gray-800">Parcel Source</span>
                        </label>

                        <label class="flex items-center gap-2 rounded border p-3 bg-gray-50">
                            <input type="checkbox"
                                   name="include_historical_clearance"
                                   value="1"
                                   @checked(old('include_historical_clearance'))
                                   class="rounded border-gray-300 text-green-700 focus:ring-green-600">
                            <span class="text-sm font-semibold text-gray-800">Historical Clearance</span>
                        </label>
                    </div>

                    <p class="text-xs text-gray-500 mt-2">
                        Select the sections supported by the source file being encoded. At least one section is required.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 mb-3">
                        Core Parcel / Owner Details
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Landowner / Owner Name *</label>
                            <input name="landowner_name"
                                   value="{{ old('landowner_name') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Parcel Reference Code</label>
                            <input name="parcel_code"
                                   value="{{ old('parcel_code') }}"
                                   placeholder="e.g. SRC-PCL-003"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title Number</label>
                            <input name="title_number"
                                   value="{{ old('title_number') }}"
                                   placeholder="e.g. TCT-000123"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Landholding Reference Number</label>
                            <input name="landholding_reference_number"
                                   value="{{ old('landholding_reference_number') }}"
                                   placeholder="e.g. LH-2026-001"
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
                        Historical Clearance Details
                    </h4>

                    <p class="text-sm text-gray-600 mb-4">
                        Fill this only when the source package includes an old or current clearance reference.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Clearance Control Number</label>
                            <input name="control_number"
                                   value="{{ old('control_number') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Transferor Name</label>
                            <input name="transferor_name"
                                   value="{{ old('transferor_name') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Transferee Name</label>
                            <input name="transferee_name"
                                   value="{{ old('transferee_name') }}"
                                   class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 mb-3">
                        Source Scope / Parcel Link
                    </h4>

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
                                Linking attaches this package as documentary/provenance support. It does not change ownership.
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 mb-3">
                        Parcel Source / Geometry Details
                    </h4>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Boundary / Technical Description Notes
                            </label>
                            <textarea name="boundary_description"
                                      rows="3"
                                      class="mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('boundary_description') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Source GeoJSON Geometry
                            </label>

                            <textarea name="source_geometry_geojson"
                                      rows="7"
                                      placeholder='Example polygon: {"type":"Polygon","coordinates":[[[123.3048,9.3064],[123.3058,9.3064],[123.3058,9.3072],[123.3048,9.3072],[123.3048,9.3064]]]}'
                                      class="mt-1 w-full border-gray-300 rounded-md shadow-sm font-mono text-xs">{{ old('source_geometry_geojson') }}</textarea>

                            <p class="text-xs text-gray-500 mt-1">
                                This does not appear on the map by itself. It can be copied into a main Parcel Record
                                after staff confirmation.
                            </p>
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
                        Save Source Package
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
