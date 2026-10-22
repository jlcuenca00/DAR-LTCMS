<x-staff-shell title="Review Landholding Conversion" active="source-records">
    <div class="mx-auto max-w-4xl space-y-5">
        <a href="{{ route('staff.source-record-packages.show', $package) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
            &larr; Back to source package {{ $package->package_code }}
        </a>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Individual source-to-master conversion</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Review Landholding</h1>
            <p class="mt-2 text-sm text-slate-600">
                Confirm the source evidence and record relationships before creating an administrative Landholding.
                This does not transfer legal ownership or modify official registries.
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <strong>Cannot complete the conversion:</strong>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Source package</dt><dd class="font-semibold">{{ $package->package_code }}</dd></div>
                <div><dt class="text-slate-500">Source scope</dt><dd class="font-semibold">{{ $package->source_record_scope_label }}</dd></div>
                <div><dt class="text-slate-500">Linked landowner</dt><dd class="font-semibold">{{ $package->landowner?->full_name ?? 'Not linked' }}</dd></div>
                <div><dt class="text-slate-500">Linked parcel</dt><dd class="font-semibold">{{ $package->parcel?->parcel_code ?? 'Not linked' }}</dd></div>
                <div><dt class="text-slate-500">Landholding reference</dt><dd class="font-semibold">{{ $package->landholding_reference_number ?? 'Not supplied' }}</dd></div>
                <div><dt class="text-slate-500">Proposed record status</dt><dd class="font-semibold">{{ ucfirst($proposedStatus) }}</dd></div>
                <div><dt class="text-slate-500">Source area</dt><dd class="font-semibold">{{ $package->area_hectares ?? 'Not supplied' }} ha</dd></div>
                <div><dt class="text-slate-500">Current allocated active area</dt><dd class="font-semibold">{{ number_format($allocatedArea, 4) }} ha</dd></div>
            </dl>
        </div>

        @if (! $eligible)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                <strong>Review required before conversion.</strong>
                @if (! $package->landowner || ! $package->parcel)
                    Link an existing or newly created Landowner and Parcel on the source package page first.
                @elseif (! $hasHoldingEvidence)
                    This package does not include a landholding source record.
                @elseif ($package->landholding || $existingPair)
                    A Landholding already exists for this source package or Landowner–Parcel pair.
                @else
                    Reference-only packages cannot create master Landholding records. Verify and correct the source scope first.
                @endif
            </div>
        @elseif ($proposedStatus === 'active' && $overlappingExposure)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                An existing incoming clearance application references this Landowner and Parcel.
                Reconcile its projected area before recording a new active Landholding to prevent double-counting.
            </div>
        @else
            <form method="POST" action="{{ route('staff.source-record-packages.landholding.store', $package) }}"
                  class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-submit-feedback>
                @csrf
                <input type="hidden" name="expected_record_revision" value="{{ $package->record_revision }}">

                <div>
                    <label for="area_hectares" class="block text-sm font-semibold text-slate-800">Verified landholding share (hectares) *</label>
                    <input id="area_hectares" name="area_hectares" type="number" min="0.0001" step="0.0001"
                           value="{{ old('area_hectares', $package->area_hectares) }}" required
                           class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="mt-1 text-xs text-slate-600">Confirm the landowner's share, not necessarily the parcel's full area. Active shares cannot exceed the parcel's recorded area.</p>
                </div>

                <div>
                    <label for="date_acquired" class="block text-sm font-semibold text-slate-800">Date acquired (only if verified)</label>
                    <input id="date_acquired" name="date_acquired" type="date" value="{{ old('date_acquired') }}"
                           class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-800">
                    <input type="checkbox" name="verified_source" value="1" required class="mt-1">
                    <span>I verified the source evidence, the linked Landowner and Parcel, and the entered share. I understand this records administrative information only, not legal ownership transfer.</span>
                </label>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Confirm &amp; Create Landholding
                    </button>
                    <a href="{{ route('staff.source-record-packages.show', $package) }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">
                        Cancel
                    </a>
                </div>
            </form>
        @endif
    </div>
</x-staff-shell>
