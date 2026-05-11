<x-staff-shell
    title="Encode New Clearance Application"
    subtitle="Staff-side administrative screen for DAR-LTCMS processing, records management, monitoring, and auditability."
    active="applications"
>
<div class="py-6 bg-gray-100 min-h-screen">
        <div class="space-y-5">

            <x-system-scope-notice
                title="Application Encoding Scope"
                variant="green"
            />

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm">
                    <p class="font-bold mb-2">Please correct the following:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('staff.applications.store') }}"
                  class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                @csrf

                <div class="p-6 border-b border-gray-200">
                    <h3 class="font-heading text-lg font-bold text-gray-900">
                        Application Details
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Encoded applications begin as draft records. Submission, review, and final decision happen separately.
                    </p>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Transferor Landowner Record
                        </label>
                        <select name="transferor_landowner_id"
                                class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">No linked landowner record</option>
                            @foreach ($landowners as $landowner)
                                <option value="{{ $landowner->id }}"
                                    @selected(old('transferor_landowner_id') == $landowner->id)>
                                    {{ $landowner->full_name }} — {{ $landowner->municipality ?? 'No municipality' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Optional, but recommended for validation and traceability.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Transferee Landowner Record
                        </label>
                        <select name="transferee_landowner_id"
                                class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">No linked landowner record</option>
                            @foreach ($landowners as $landowner)
                                <option value="{{ $landowner->id }}"
                                    @selected(old('transferee_landowner_id') == $landowner->id)>
                                    {{ $landowner->full_name }} — {{ $landowner->municipality ?? 'No municipality' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Used for assistive validation such as landholding area checks.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Transferor Name <span class="text-red-600">*</span>
                        </label>
                        <input type="text"
                               name="transferor_name"
                               value="{{ old('transferor_name') }}"
                               required
                               class="w-full rounded-md border-gray-300 text-sm"
                               placeholder="Enter transferor name">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Transferee Name <span class="text-red-600">*</span>
                        </label>
                        <input type="text"
                               name="transferee_name"
                               value="{{ old('transferee_name') }}"
                               required
                               class="w-full rounded-md border-gray-300 text-sm"
                               placeholder="Enter transferee name">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Municipality
                        </label>
                        <input type="text"
                               name="municipality"
                               value="{{ old('municipality') }}"
                               class="w-full rounded-md border-gray-300 text-sm"
                               placeholder="Example: Dumaguete City">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Barangay
                        </label>
                        <input type="text"
                               name="barangay"
                               value="{{ old('barangay') }}"
                               class="w-full rounded-md border-gray-300 text-sm"
                               placeholder="Example: Barangay Alpha">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Date Filed
                        </label>
                        <input type="date"
                               name="date_filed"
                               value="{{ old('date_filed', now()->toDateString()) }}"
                               class="w-full rounded-md border-gray-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Date of Intended Transfer
                        </label>
                        <input type="date"
                               name="date_of_transfer"
                               value="{{ old('date_of_transfer') }}"
                               class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                </div>

                <div class="p-6 border-t border-gray-200">
                    <h3 class="font-heading text-lg font-bold text-gray-900">
                        Parcel Reference
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        This links a main parcel record to the application for review only. It does not transfer ownership.
                    </p>

                    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">
                                Main Parcel Record
                            </label>
                            <select name="parcel_id"
                                    class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">No parcel linked yet</option>
                                @foreach ($parcels as $parcel)
                                    <option value="{{ $parcel->id }}"
                                        @selected(old('parcel_id') == $parcel->id)>
                                        {{ $parcel->parcel_code }}
                                        @if ($parcel->title_no)
                                            — {{ $parcel->title_no }}
                                        @endif
                                        @if ($parcel->municipality)
                                            — {{ $parcel->municipality }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">
                                Application Area in Hectares
                            </label>
                            <input type="number"
                                   step="0.0001"
                                   min="0"
                                   name="area_hectares"
                                   value="{{ old('area_hectares') }}"
                                   class="w-full rounded-md border-gray-300 text-sm"
                                   placeholder="Leave blank to use parcel area">
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-1">
                        Remarks
                    </label>
                    <textarea name="remarks"
                              rows="4"
                              class="w-full rounded-md border-gray-300 text-sm"
                              placeholder="Optional staff notes for application encoding">{{ old('remarks') }}</textarea>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between gap-4">
                    <a href="{{ route('staff.applications.index') }}"
                       class="text-sm font-bold text-gray-600 hover:text-gray-900">
                        Cancel
                    </a>

                    <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 rounded-md bg-green-700 text-white text-sm font-bold hover:bg-green-800">
                        Save Draft Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-staff-shell>
