<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Application Review
        </h2>
    </x-slot>

    <div class="p-6 space-y-6">

        {{-- Success Message --}}
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-3 rounded">
                <div class="font-semibold mb-2">Upload failed:</div>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Application Summary --}}
        <div class="bg-white shadow rounded p-4 border space-y-1">
            <div><strong>Application Code:</strong> {{ $application->application_code }}</div>
            <div><strong>Transferor:</strong> {{ $application->transferor_name }}</div>
            <div><strong>Transferee:</strong> {{ $application->transferee_name }}</div>
            <div><strong>Location:</strong> {{ $application->barangay ?? '—' }}, {{ $application->municipality ?? '—' }}</div>
            <div><strong>Status:</strong> {{ strtoupper($application->status) }}</div>
        </div>

        {{-- Completion Counter --}}
        @php
            $totalReq = $transferorRequirements->count() + $transfereeRequirements->count();
            $uploadedCount = $uploaded->count();
        @endphp

        <div class="bg-white shadow rounded p-4 border">
            <strong>Checklist Completion:</strong>
            {{ $uploadedCount }} / {{ $totalReq }} uploaded
            <div class="text-sm text-gray-600 mt-1">
                Note: Incomplete requirements can still be saved, but will be flagged during review.
            </div>
        </div>

        {{-- ======================= --}}
        {{-- TRANSFEROR REQUIREMENTS --}}
        {{-- ======================= --}}

        <div class="bg-white shadow rounded border">
            <div class="p-4 border-b font-semibold">
                Transferor Requirements (DAR A.O. No. 4, s. 2021)
            </div>

            <div class="p-4 space-y-4">
                @foreach ($transferorRequirements as $req)

                    @php
                        $doc = $uploaded->get($req->id);
                        $isUploaded = !is_null($doc);
                    @endphp

                    <div class="border p-4 rounded">
                        <div class="font-semibold text-gray-900">
                            {{ $req->name }}
                            @if (!$req->is_mandatory)
                                <span class="text-xs text-yellow-700"> (If applicable)</span>
                            @endif
                        </div>

                        <div class="mt-2 text-sm">
                            @if ($isUploaded)
                                <div class="text-green-700 font-semibold">Uploaded</div>
                                <div class="text-gray-700">File: <span class="font-mono">{{ $doc->original_filename }}</span></div>
                                @if ($doc->annex_reference)
                                    <div class="text-gray-700">Annex: {{ $doc->annex_reference }}</div>
                                @endif
                            @else
                                <div class="text-red-700 font-semibold">Not uploaded</div>
                            @endif
                        </div>

                        {{-- Upload Form (always visible) --}}
                        <form
                            method="POST"
                            action="{{ route('staff.applications.documents.store', ['application' => $application->id, 'requiredDocument' => $req->id]) }}"
                            enctype="multipart/form-data"
                            style="margin-top:12px; padding-top:12px; border-top:1px solid #e5e7eb;"
                        >
                            @csrf

                            <div style="margin-bottom:10px;">
                                <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                                    Choose file (required)
                                </label>
                                <input type="file" name="file" required>
                            </div>

                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
                                <div>
                                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                                        Annex reference (optional)
                                    </label>
                                    <input type="text" name="annex_reference" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;">
                                </div>

                                <div>
                                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                                        Remarks (optional)
                                    </label>
                                    <input type="text" name="remarks" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;">
                                </div>
                            </div>

                            <button
                                type="submit"
                                style="background:#111827; color:#fff; padding:10px 14px; border-radius:8px; font-weight:600; cursor:pointer;"
                            >
                                Upload / Replace
                            </button>
                        </form>
                    </div>

                @endforeach
            </div>
        </div>

        {{-- ======================= --}}
        {{-- TRANSFEREE REQUIREMENTS --}}
        {{-- ======================= --}}

        <div class="bg-white shadow rounded border">
            <div class="p-4 border-b font-semibold">
                Transferee Requirements (DAR A.O. No. 4, s. 2021)
            </div>

            <div class="p-4 space-y-4">
                @foreach ($transfereeRequirements as $req)

                    @php
                        $doc = $uploaded->get($req->id);
                        $isUploaded = !is_null($doc);
                    @endphp

                    <div class="border p-4 rounded">
                        <div class="font-semibold text-gray-900">
                            {{ $req->name }}
                            @if (!$req->is_mandatory)
                                <span class="text-xs text-yellow-700"> (If applicable)</span>
                            @endif
                        </div>

                        <div class="mt-2 text-sm">
                            @if ($isUploaded)
                                <div class="text-green-700 font-semibold">Uploaded</div>
                                <div class="text-gray-700">File: <span class="font-mono">{{ $doc->original_filename }}</span></div>
                                @if ($doc->annex_reference)
                                    <div class="text-gray-700">Annex: {{ $doc->annex_reference }}</div>
                                @endif
                            @else
                                <div class="text-red-700 font-semibold">Not uploaded</div>
                            @endif
                        </div>

                        {{-- Upload Form (always visible) --}}
                        <form
                            method="POST"
                            action="{{ route('staff.applications.documents.store', ['application' => $application->id, 'requiredDocument' => $req->id]) }}"
                            enctype="multipart/form-data"
                            style="margin-top:12px; padding-top:12px; border-top:1px solid #e5e7eb;"
                        >
                            @csrf

                            <div style="margin-bottom:10px;">
                                <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                                    Choose file (required)
                                </label>
                                <input type="file" name="file" required>
                            </div>

                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
                                <div>
                                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                                        Annex reference (optional)
                                    </label>
                                    <input type="text" name="annex_reference" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;">
                                </div>

                                <div>
                                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                                        Remarks (optional)
                                    </label>
                                    <input type="text" name="remarks" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;">
                                </div>
                            </div>

                            <button
                                type="submit"
                                style="background:#111827; color:#fff; padding:10px 14px; border-radius:8px; font-weight:600; cursor:pointer;"
                            >
                                Upload / Replace
                            </button>
                        </form>
                    </div>

                @endforeach
            </div>
            @if ($transfereeOwner)
    <div class="bg-white shadow rounded p-4 border">
        <div class="font-semibold text-gray-900">5-Hectare Validation (Assistive)</div>
        <div class="text-sm text-gray-700 mt-2 space-y-1">
            <div><strong>Transferee:</strong> {{ $transfereeOwner->full_name }}</div>
            <div><strong>Current Approved Total:</strong> {{ number_format($currentApprovedTotal, 4) }} ha</div>
            <div><strong>Pending Incoming Total:</strong> {{ number_format($pendingIncomingTotal, 4) }} ha</div>
            <div><strong>This Application Total:</strong> {{ number_format($thisApplicationTotal, 4) }} ha</div>

            <div class="pt-2">
                <strong>Projected Total:</strong> {{ number_format($projectedTotal, 4) }} ha
            </div>

            @if ($exceedsFiveHectares)
                <div class="pt-2 text-red-700 font-semibold">
                    ⚠️ Flag: Projected total exceeds the 5-hectare limit.
                </div>
            @else
                <div class="pt-2 text-green-700 font-semibold">
                    ✅ Within the 5-hectare limit.
                </div>
            @endif

            <div class="pt-2 text-xs text-gray-500">
                Note: This is an assistive validation. Final approval remains a staff decision.
            </div>
        </div>
    </div>
@endif
        </div>

    </div>
</x-app-layout>