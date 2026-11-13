<x-landowner-shell title="My Parcel Listings" active="parcel-listings">
    <div class="mx-auto max-w-4xl space-y-4">
        <h1 class="text-2xl font-bold">My Parcel Listings</h1>
        <p>Choose a parcel and submit its public contact details for staff review.</p>
        @include('parcel-listings.messages')
        @forelse ($holdings as $holding)
            @php($listing = $holding->parcelListing)
            <section class="space-y-3 rounded-xl border bg-white p-4">
                <h2 class="font-semibold">{{ $holding->parcel->parcel_code }} · {{ $holding->parcel->barangay }}, {{ $holding->parcel->municipality }}</h2>
                <p class="text-sm">{{ $holding->parcel->area_hectares }} ha · {{ ucfirst($listing?->status ?? 'Unlisted') }}</p>
                @if ($listing && ((int) $listing->parcel_revision !== $holding->parcel->record_revision || (int) $listing->holding_revision !== $holding->record_revision))
                    <p class="text-sm">This parcel or holding has changed. Submit it again for review.</p>
                @endif
                @if ($holding->status === 'active' && $holding->parcel->status === 'active' && ! $holding->parcel->is_flagged && \App\Models\ParcelListing::publicGeometry($holding->parcel))
                    <details>
                        <summary class="cursor-pointer font-medium text-emerald-800">{{ $listing ? 'Edit and resubmit listing' : 'List this parcel' }}</summary>
                        <form method="post" action="{{ route('landowner.parcel-listings.submit', $holding) }}" class="mt-3 grid gap-3">
                            @csrf
                            <label>Public owner display name
                                <input required name="display_name" maxlength="100" value="{{ $listing?->display_name }}" class="mt-1 block w-full rounded border-slate-300">
                            </label>
                            <label>Public mobile number
                                <input required type="tel" name="mobile" maxlength="13" value="{{ $listing?->mobile }}" placeholder="09XXXXXXXXX" class="mt-1 block w-full rounded border-slate-300">
                            </label>
                            <label class="flex items-start gap-2">
                                <input required type="checkbox" name="publish_consent" value="1" class="mt-1">
                                <span>I agree to make this name, mobile number, parcel outline, barangay, municipality, and approximate area public. Anyone can copy the number or call it. I can withdraw the listing at any time.</span>
                            </label>
                            @if ($listing?->status === 'published')
                                <p class="text-sm">Submitting changes hides the current listing while staff review the update.</p>
                            @endif
                            <button class="w-fit rounded bg-emerald-800 px-4 py-2 text-white">Submit for review</button>
                        </form>
                    </details>
                @endif
                @if ($listing && $listing->status !== 'withdrawn')
                    <form method="post" action="{{ route('landowner.parcel-listings.withdraw', $holding) }}">
                        @csrf
                        @method('DELETE')
                        <button class="rounded border px-3 py-2">Withdraw listing</button>
                    </form>
                @endif
            </section>
        @empty
            <p>There are no landholdings linked to your account.</p>
        @endforelse
        {{ $holdings->links() }}
    </div>
</x-landowner-shell>
