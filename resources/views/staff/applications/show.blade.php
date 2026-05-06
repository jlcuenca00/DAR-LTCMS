<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Application Review
        </h2>
    </x-slot>

    <div class="p-6 space-y-6">

        @php
    $isFinal = $application->isFinalized();
    $lockMsg = 'Application finalized. Uploads, removals, and workflow decisions are locked.';
@endphp

        {{-- Success Message --}}
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error Message (custom) --}}
        @if (session('error'))
            <div class="bg-red-100 text-red-800 p-3 rounded">
                {{ session('error') }}
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

        @if ($isFinal)
    <div class="bg-amber-50 text-gray-900 rounded p-4 border border-amber-300">
        <div class="font-semibold text-lg">
            Final Decision Locked
        </div>

        <div class="text-sm mt-1 text-gray-700">
            This application already has a final decision. Uploads, document removals,
            resubmission, approval, and not-approved actions are locked for audit integrity.
        </div>

        <div class="text-sm mt-3">
            <strong>Status:</strong> {{ strtoupper($application->status) }}
            @if ($application->reviewed_at)
                <span class="ml-3">
                    <strong>Reviewed At:</strong>
                    {{ $application->reviewed_at->format('M d, Y h:i A') }}
                </span>
            @endif
        </div>

        @if ($application->decision_reason || $application->decision_notes)
            <div class="text-sm mt-3 text-gray-700">
                @if ($application->decision_reason)
                    <div><strong>Decision Reason:</strong> {{ $application->decision_reason }}</div>
                @endif

                @if ($application->decision_notes)
                    <div><strong>Decision Notes:</strong> {{ $application->decision_notes }}</div>
                @endif
            </div>
        @endif
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

        <div class="bg-white shadow rounded p-4 border">
    <div class="font-semibold mb-2">Workflow Actions</div>

    @if ($isFinal)
        <div class="rounded bg-gray-100 border border-gray-300 p-3 text-sm text-gray-700">
            No workflow actions are available because this application is finalized.
        </div>
    @else
        <div class="flex flex-wrap gap-2">
            @if ($application->status === 'draft')
                <form method="POST" action="{{ route('staff.applications.submit', $application) }}">
                    @csrf
                    <button type="submit"
                        style="
                            background:#2563eb;
                            color:white;
                            padding:10px 14px;
                            border-radius:8px;
                            font-weight:600;
                            cursor:pointer;
                        "
                        onmouseover="this.style.background='#1d4ed8'"
                        onmouseout="this.style.background='#2563eb'"
                    >
                        Submit for Review
                    </button>
                </form>
            @endif

            @if ($application->status === 'pending_review')
                <form method="POST" action="{{ route('staff.applications.approve', $application) }}">
                    @csrf
                    <input type="text" name="decision_reason" placeholder="Reason (optional)" class="border rounded p-2 text-sm">
                    <input type="text" name="decision_notes" placeholder="Notes (optional)" class="border rounded p-2 text-sm">

                    <button type="submit"
                        style="
                            background:#16a34a;
                            color:white;
                            padding:10px 14px;
                            border-radius:8px;
                            font-weight:600;
                            cursor:pointer;
                        "
                        onmouseover="this.style.background='#15803d'"
                        onmouseout="this.style.background='#16a34a'"
                    >
                        Approve
                    </button>
                </form>

                <form method="POST" action="{{ route('staff.applications.not_approved', $application) }}">
                    @csrf
                    <input type="text" name="decision_reason" placeholder="Reason (optional)" class="border rounded p-2 text-sm">
                    <input type="text" name="decision_notes" placeholder="Notes (optional)" class="border rounded p-2 text-sm">

                    <button type="submit"
                        style="
                            background:#dc2626;
                            color:white;
                            padding:10px 14px;
                            border-radius:8px;
                            font-weight:600;
                            cursor:pointer;
                        "
                        onmouseover="this.style.background='#b91c1c'"
                        onmouseout="this.style.background='#dc2626'"
                    >
                        Mark Not Approved
                    </button>
                </form>
            @endif
        </div>
    @endif

    <div class="text-sm text-gray-600 mt-2">
        Current status: <strong>{{ strtoupper($application->status) }}</strong>
    </div>
</div>
        @if ($isFinal && $application->clearance)
    <div class="bg-white shadow rounded p-4 border">
        <div class="font-semibold mb-2">Generated Clearance</div>

        <div class="text-sm text-gray-700 space-y-1">
            <div><strong>Clearance No.:</strong> {{ $application->clearance->clearance_number }}</div>
            <div><strong>Decision:</strong> {{ strtoupper($application->clearance->decision_status) }}</div>
            <div><strong>Generated At:</strong> {{ optional($application->clearance->generated_at)->format('M d, Y h:i A') ?? '—' }}</div>
        </div>

        <div class="flex flex-wrap gap-2 mt-3">
            <a href="{{ route('staff.applications.clearance.show', $application) }}"
               class="inline-block bg-gray-800 text-white px-4 py-2 rounded">
                Open Print View
            </a>

            <a href="{{ route('staff.applications.clearance.pdf', $application) }}"
               class="inline-block bg-blue-700 text-white px-4 py-2 rounded"
               target="_blank">
                Open PDF
            </a>
        </div>
    </div>
@endif
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

                                @if ($doc->document_reference_number)
                                    <div class="text-gray-700">Reference No.: {{ $doc->document_reference_number }}</div>
                                @endif

                                @if ($doc->document_metadata)
                                    <div class="mt-2 rounded bg-gray-50 border p-3 text-xs text-gray-700">
                                        <div class="font-semibold text-gray-900 mb-1">
                                            Encoded Document Metadata
                                        </div>

                                        @if (data_get($doc->document_metadata, 'title_number'))
                                            <div><strong>Title No.:</strong> {{ data_get($doc->document_metadata, 'title_number') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'tax_declaration_number'))
                                            <div><strong>Tax Declaration No.:</strong> {{ data_get($doc->document_metadata, 'tax_declaration_number') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'document_number'))
                                            <div><strong>Document No.:</strong> {{ data_get($doc->document_metadata, 'document_number') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'issuing_office'))
                                            <div><strong>Issuing Office:</strong> {{ data_get($doc->document_metadata, 'issuing_office') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'date_issued'))
                                            <div><strong>Date Issued:</strong> {{ data_get($doc->document_metadata, 'date_issued') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'reference_lot_or_parcel'))
                                            <div><strong>Reference Lot/Parcel:</strong> {{ data_get($doc->document_metadata, 'reference_lot_or_parcel') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'verification_notes'))
                                            <div><strong>Verification Notes:</strong> {{ data_get($doc->document_metadata, 'verification_notes') }}</div>
                                        @endif

                                        @if ($doc->metadataEncoder || $doc->metadata_encoded_at)
                                            <div class="mt-2 text-gray-500">
                                                Encoded by:
                                                {{ optional($doc->metadataEncoder)->name ?? 'Unknown user' }}
                                                @if ($doc->metadata_encoded_at)
                                                    on {{ $doc->metadata_encoded_at->format('M d, Y h:i A') }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- ✅ REMOVE BUTTON --}}
                                <form method="POST"
                                      action="{{ route('staff.applications.documents.destroy', ['application' => $application->id, 'requiredDocument' => $req->id]) }}"
                                      style="margin-top:10px;"
                                      onsubmit="return confirm('Remove this uploaded document? This cannot be undone.');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        {{ $isFinal ? 'disabled' : '' }}
                                        title="{{ $isFinal ? $lockMsg : 'Remove uploaded file' }}"
                                        style="
                                            background: {{ $isFinal ? '#9ca3af' : '#dc2626' }};
                                            color:white;
                                            padding:8px 12px;
                                            border-radius:8px;
                                            font-weight:600;
                                            cursor: {{ $isFinal ? 'not-allowed' : 'pointer' }};
                                            opacity: {{ $isFinal ? '0.75' : '1' }};
                                        "
                                    >
                                        Remove
                                    </button>
                                </form>
                            @else
                                <div class="text-red-700 font-semibold">Not uploaded</div>
                            @endif
                        </div>

                        {{-- Upload Form --}}
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
                                <input type="file" name="file" required
                                    {{ $isFinal ? 'disabled' : '' }}
                                    title="{{ $isFinal ? $lockMsg : '' }}"
                                >
                            </div>

                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
                                <div>
                                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                                        Annex reference (optional)
                                    </label>
                                    <input type="text" name="annex_reference"
                                           style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                                           {{ $isFinal ? 'disabled' : '' }}
                                           title="{{ $isFinal ? $lockMsg : '' }}"
                                    >
                                </div>

                                <div>
                                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                                        Remarks (optional)
                                    </label>
                                    <input type="text" name="remarks"
                                           style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                                           {{ $isFinal ? 'disabled' : '' }}
                                           title="{{ $isFinal ? $lockMsg : '' }}"
                                    >
                                </div>
                            </div>


                            @include('staff.applications.partials.document-metadata-fields', [
                                'req' => $req,
                                'doc' => $doc,
                                'isFinal' => $isFinal,
                                'lockMsg' => $lockMsg,
                            ])

                            <button
                                type="submit"
                                title="{{ $isFinal ? $lockMsg : '' }}"
                                {{ $isFinal ? 'disabled' : '' }}
                                style="
                                    background: {{ $isFinal ? '#9ca3af' : '#111827' }};
                                    color:#fff;
                                    padding:10px 14px;
                                    border-radius:8px;
                                    font-weight:600;
                                    cursor: {{ $isFinal ? 'not-allowed' : 'pointer' }};
                                    opacity: {{ $isFinal ? '0.75' : '1' }};
                                "
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

                                @if ($doc->document_reference_number)
                                    <div class="text-gray-700">Reference No.: {{ $doc->document_reference_number }}</div>
                                @endif

                                @if ($doc->document_metadata)
                                    <div class="mt-2 rounded bg-gray-50 border p-3 text-xs text-gray-700">
                                        <div class="font-semibold text-gray-900 mb-1">
                                            Encoded Document Metadata
                                        </div>

                                        @if (data_get($doc->document_metadata, 'title_number'))
                                            <div><strong>Title No.:</strong> {{ data_get($doc->document_metadata, 'title_number') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'tax_declaration_number'))
                                            <div><strong>Tax Declaration No.:</strong> {{ data_get($doc->document_metadata, 'tax_declaration_number') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'document_number'))
                                            <div><strong>Document No.:</strong> {{ data_get($doc->document_metadata, 'document_number') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'issuing_office'))
                                            <div><strong>Issuing Office:</strong> {{ data_get($doc->document_metadata, 'issuing_office') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'date_issued'))
                                            <div><strong>Date Issued:</strong> {{ data_get($doc->document_metadata, 'date_issued') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'reference_lot_or_parcel'))
                                            <div><strong>Reference Lot/Parcel:</strong> {{ data_get($doc->document_metadata, 'reference_lot_or_parcel') }}</div>
                                        @endif

                                        @if (data_get($doc->document_metadata, 'verification_notes'))
                                            <div><strong>Verification Notes:</strong> {{ data_get($doc->document_metadata, 'verification_notes') }}</div>
                                        @endif

                                        @if ($doc->metadataEncoder || $doc->metadata_encoded_at)
                                            <div class="mt-2 text-gray-500">
                                                Encoded by:
                                                {{ optional($doc->metadataEncoder)->name ?? 'Unknown user' }}
                                                @if ($doc->metadata_encoded_at)
                                                    on {{ $doc->metadata_encoded_at->format('M d, Y h:i A') }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- ✅ REMOVE BUTTON --}}
                                <form method="POST"
                                      action="{{ route('staff.applications.documents.destroy', ['application' => $application->id, 'requiredDocument' => $req->id]) }}"
                                      style="margin-top:10px;"
                                      onsubmit="return confirm('Remove this uploaded document? This cannot be undone.');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        {{ $isFinal ? 'disabled' : '' }}
                                        title="{{ $isFinal ? $lockMsg : 'Remove uploaded file' }}"
                                        style="
                                            background: {{ $isFinal ? '#9ca3af' : '#dc2626' }};
                                            color:white;
                                            padding:8px 12px;
                                            border-radius:8px;
                                            font-weight:600;
                                            cursor: {{ $isFinal ? 'not-allowed' : 'pointer' }};
                                            opacity: {{ $isFinal ? '0.75' : '1' }};
                                        "
                                    >
                                        Remove
                                    </button>
                                </form>
                            @else
                                <div class="text-red-700 font-semibold">Not uploaded</div>
                            @endif
                        </div>

                        {{-- Upload Form --}}
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
                                <input type="file" name="file" required
                                    {{ $isFinal ? 'disabled' : '' }}
                                    title="{{ $isFinal ? $lockMsg : '' }}"
                                >
                            </div>

                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
                                <div>
                                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                                        Annex reference (optional)
                                    </label>
                                    <input type="text" name="annex_reference"
                                           style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                                           {{ $isFinal ? 'disabled' : '' }}
                                           title="{{ $isFinal ? $lockMsg : '' }}"
                                    >
                                </div>

                                <div>
                                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                                        Remarks (optional)
                                    </label>
                                    <input type="text" name="remarks"
                                           style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                                           {{ $isFinal ? 'disabled' : '' }}
                                           title="{{ $isFinal ? $lockMsg : '' }}"
                                    >
                                </div>
                            </div>


                            @include('staff.applications.partials.document-metadata-fields', [
                                'req' => $req,
                                'doc' => $doc,
                                'isFinal' => $isFinal,
                                'lockMsg' => $lockMsg,
                            ])

                            <button
                                type="submit"
                                title="{{ $isFinal ? $lockMsg : '' }}"
                                {{ $isFinal ? 'disabled' : '' }}
                                style="
                                    background: {{ $isFinal ? '#9ca3af' : '#111827' }};
                                    color:#fff;
                                    padding:10px 14px;
                                    border-radius:8px;
                                    font-weight:600;
                                    cursor: {{ $isFinal ? 'not-allowed' : 'pointer' }};
                                    opacity: {{ $isFinal ? '0.75' : '1' }};
                                "
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

        <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-6 border">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">
                Application Timeline / Status History
            </h3>

            <p class="text-sm text-gray-600 mt-1">
                Read-only timeline of recorded actions for this clearance application.
                This supports traceability, accountability, and monitoring.
            </p>
            <div class="bg-white border shadow-sm rounded-lg p-5 mt-4">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">
                Prior / Source Records
            </h3>

            <p class="text-sm text-gray-600 mt-1">
                Matched digitized source records and source packages related to this application’s parcel,
                title, transferor, or transferee. These records support review and traceability only.
                They do not automatically transfer land ownership or mutate Registry of Deeds records.
            </p>
        </div>
    </div>

    @if ($matchedSourcePackages->count() > 0)
        <div class="mb-6">
            <h4 class="font-semibold text-gray-800 mb-3">
                Matched Source Packages
            </h4>

            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Package Code</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">References</th>
                            <th class="px-4 py-3 text-left">Linked Parcel</th>
                            <th class="px-4 py-3 text-left">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @foreach ($matchedSourcePackages as $package)
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

                                    @if ($package->parcel_code)
                                        <div><strong>Parcel Ref:</strong> {{ $package->parcel_code }}</div>
                                    @endif

                                    @if ($package->landholding_reference_number)
                                        <div><strong>Landholding:</strong> {{ $package->landholding_reference_number }}</div>
                                    @endif

                                    @if ($package->control_number)
                                        <div><strong>Clearance:</strong> {{ $package->control_number }}</div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    @if ($package->parcel)
                                        <a href="{{ route('staff.records.parcels.show', $package->parcel) }}"
                                           class="text-green-700 font-semibold hover:underline">
                                            {{ $package->parcel->parcel_code }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">Not linked</span>
                                    @endif
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

    @if ($matchedSourceRecords->count() > 0)
        <div>
            <h4 class="font-semibold text-gray-800 mb-3">
                Matched Individual Source Records
            </h4>

            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Origin</th>
                            <th class="px-4 py-3 text-left">References</th>
                            <th class="px-4 py-3 text-left">Source</th>
                            <th class="px-4 py-3 text-left">Linked Parcel</th>
                            <th class="px-4 py-3 text-left">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @foreach ($matchedSourceRecords as $record)
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

                                    @if ($record->parcel_code)
                                        <div><strong>Parcel Ref:</strong> {{ $record->parcel_code }}</div>
                                    @endif

                                    @if ($record->landholding_reference_number)
                                        <div><strong>Landholding:</strong> {{ $record->landholding_reference_number }}</div>
                                    @endif

                                    @if ($record->control_number)
                                        <div><strong>Clearance:</strong> {{ $record->control_number }}</div>
                                    @endif

                                    @if (! $record->title_number && ! $record->parcel_code && ! $record->landholding_reference_number && ! $record->control_number)
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

    @if ($matchedSourcePackages->count() === 0 && $matchedSourceRecords->count() === 0)
        <div class="border rounded-lg p-6 text-center text-gray-500">
            No matching source records were found for this application.
        </div>
    @endif
</div>
            <p class="text-xs text-gray-500 mt-2">
                Timeline records are based on audit logs. They do not indicate automatic land ownership transfer or registry mutation.
            </p>
        </div>
    </div>

    @if ($applicationTimeline->isEmpty())
        <p class="text-sm text-gray-500">
            No timeline records found yet.
        </p>
    @else
        <div class="space-y-4">
            @foreach ($applicationTimeline as $timelineEntry)
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900">
                                {{ ucwords(str_replace('_', ' ', $timelineEntry->action)) }}
                            </div>

                            <div class="text-sm text-gray-600 mt-1">
                                By:
                                @if ($timelineEntry->actor)
                                    {{ $timelineEntry->actor->name }}
                                    <span class="text-gray-400">
                                        ({{ $timelineEntry->actor->email }})
                                    </span>
                                @else
                                    Unknown user
                                @endif
                            </div>
                        </div>

                        <div class="text-sm text-gray-500 md:text-right">
                            {{ $timelineEntry->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? 'N/A' }}
                        </div>
                    </div>

                    @if ($timelineEntry->auditable_type)
                        <div class="text-xs text-gray-500 mt-2">
                            Related record:
                            {{ class_basename($timelineEntry->auditable_type) }}
                            @if ($timelineEntry->auditable_id)
                                #{{ $timelineEntry->auditable_id }}
                            @endif
                        </div>
                    @endif

                    @if (! empty($timelineEntry->metadata))
                        <details class="mt-3">
                            <summary class="cursor-pointer text-sm text-blue-700 hover:underline">
                                View action details
                            </summary>

                            <pre class="mt-2 p-3 bg-white border rounded text-xs overflow-x-auto text-gray-700">{{ json_encode($timelineEntry->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

    </div>
</x-app-layout>