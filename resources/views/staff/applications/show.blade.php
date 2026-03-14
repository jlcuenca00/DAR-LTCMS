<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Application Review
        </h2>
    </x-slot>

    <div class="p-6 space-y-6">

        @php
            // 🔒 Finalized applications lock uploads (UI) for audit integrity
            $isFinal = in_array($application->status, ['approved', 'not_approved'], true);
            $lockMsg = 'Application finalized. Uploads locked.';
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

            <div class="text-sm text-gray-600 mt-2">
                Current status: <strong>{{ strtoupper($application->status) }}</strong>
            </div>
        </div>
        @if (in_array($application->status, ['approved', 'not_approved'], true) && $application->clearance)
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

    </div>
</x-app-layout>