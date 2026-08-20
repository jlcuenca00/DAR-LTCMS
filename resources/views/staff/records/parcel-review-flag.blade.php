<x-staff-shell
    title="Parcel Review Flag"
    subtitle="Mark parcel records that require administrative or technical verification without changing their lifecycle or ownership information."
    active="parcel-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.records.parcels.show', $parcel) }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Parcel Details
        </a>
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.25fr)_minmax(320px,.75fr)]">
        <section class="staff-panel staff-panel-pad">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Parcel Record</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">{{ $parcel->parcel_code }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $parcel->municipality ?? 'No municipality' }}{{ $parcel->barangay ? ', '.$parcel->barangay : '' }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="staff-badge {{ $parcel->status === 'active' ? 'staff-badge-green' : 'staff-badge-slate' }}">
                        {{ ucfirst($parcel->status ?? 'Unspecified') }}
                    </span>
                    <span class="staff-badge {{ $parcel->is_flagged ? 'staff-badge-red' : 'staff-badge-green' }}">
                        {{ $parcel->is_flagged ? 'Flagged for Review' : 'No Active Flag' }}
                    </span>
                </div>
            </div>

            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                A review flag only indicates that this parcel record requires further administrative or technical checking. It does not invalidate the parcel, change landownership, alter landholding records, decide a clearance application, or mutate official registry records.
            </div>

            @if ($parcel->status === 'inactive')
                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                    This parcel is inactive. Reactivate the parcel record before creating a new review flag.
                </div>
            @else
                <form method="POST" action="{{ route('staff.records.parcels.review-flag.flag', $parcel) }}" class="mt-6 grid gap-5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="flag_reason" class="staff-form-label">Reason for Review Flag</label>
                        <select id="flag_reason" name="flag_reason" required class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                            <option value="">Select a reason</option>
                            @foreach ($flagReasons as $value => $label)
                                <option value="{{ $value }}" @selected(old('flag_reason', $parcel->is_flagged ? $parcel->flag_reason : '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('flag_reason')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="flag_notes" class="staff-form-label">Review Notes <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <textarea id="flag_notes" name="flag_notes" rows="5" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600" placeholder="Describe the discrepancy or item that should be verified.">{{ old('flag_notes', $parcel->is_flagged ? $parcel->flag_notes : '') }}</textarea>
                        @error('flag_notes')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-4">
                        <a href="{{ route('staff.records.parcels.show', $parcel) }}" class="staff-button staff-button-light">Cancel</a>
                        <button type="submit" class="staff-button staff-button-danger">
                            <i class="fa-solid fa-flag"></i>
                            {{ $parcel->is_flagged ? 'Update Review Flag' : 'Flag Parcel for Review' }}
                        </button>
                    </div>
                </form>
            @endif
        </section>

        <aside class="grid content-start gap-5">
            <section class="staff-panel staff-panel-pad">
                <h3 class="staff-panel-title">Current Review State</h3>

                @if ($parcel->is_flagged)
                    <dl class="mt-4 grid gap-4 text-sm">
                        <div>
                            <dt class="text-xs font-black uppercase tracking-wider text-slate-500">Reason</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ $parcel->flag_reason_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black uppercase tracking-wider text-slate-500">Flagged By</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ $parcel->flaggedBy?->name ?? 'Recorded staff user' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black uppercase tracking-wider text-slate-500">Flagged At</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ $parcel->flagged_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                @elseif ($parcel->flag_resolved_at)
                    <dl class="mt-4 grid gap-4 text-sm">
                        <div>
                            <dt class="text-xs font-black uppercase tracking-wider text-slate-500">Last Resolution</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ $parcel->flag_resolved_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black uppercase tracking-wider text-slate-500">Resolved By</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ $parcel->flagResolvedBy?->name ?? 'Recorded staff user' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black uppercase tracking-wider text-slate-500">Resolution Note</dt>
                            <dd class="mt-1 leading-6 text-slate-700">{{ $parcel->flag_resolution_notes }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-3 text-sm leading-6 text-slate-600">No review flag has been recorded for this parcel.</p>
                @endif
            </section>

            @if ($parcel->is_flagged)
                <section class="staff-panel staff-panel-pad">
                    <h3 class="staff-panel-title">Resolve Review Flag</h3>
                    <p class="staff-panel-subtitle">Use this after the flagged concern has been checked. A resolution note is required for traceability.</p>

                    <form method="POST" action="{{ route('staff.records.parcels.review-flag.resolve', $parcel) }}" class="mt-4 grid gap-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="resolution_notes" class="staff-form-label">Resolution Note</label>
                            <textarea id="resolution_notes" name="resolution_notes" rows="4" required class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600" placeholder="State what was verified or corrected.">{{ old('resolution_notes') }}</textarea>
                            @error('resolution_notes')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" class="staff-button staff-button-primary justify-center">
                            <i class="fa-solid fa-check"></i>
                            Resolve Flag
                        </button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</x-staff-shell>
