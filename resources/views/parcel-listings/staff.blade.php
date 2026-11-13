<x-staff-shell title="Parcel Listing Review" active="parcel-listings">
    <div class="mx-auto max-w-4xl space-y-4">
        <h1 class="text-2xl font-bold">Parcel Listing Review</h1>
        <p>Review the owner's public details before publishing. Publication applies to the listing only.</p>
        @include('parcel-listings.messages')
        @forelse ($listings as $listing)
            @php($holding = $listing->landholding)
            <section class="space-y-2 rounded-xl border bg-white p-4">
                <h2 class="font-semibold">{{ $holding->parcel->parcel_code }} · {{ $holding->parcel->barangay }}, {{ $holding->parcel->municipality }}</h2>
                <p>{{ ucfirst($listing->status) }}{{ $listing->is_demo ? ' · Demo only' : '' }}</p>
                <dl class="grid gap-1 text-sm">
                    <div><dt class="inline font-semibold">Landowner record</dt> <dd class="inline">{{ $holding->landowner->full_name }}</dd></div>
                    <div><dt class="inline font-semibold">Public display name</dt> <dd class="inline">{{ $listing->display_name }}</dd></div>
                    <div><dt class="inline font-semibold">Public mobile</dt> <dd class="inline">{{ $listing->mobile ?? 'Unavailable' }}</dd></div>
                    <div><dt class="inline font-semibold">Parcel area</dt> <dd class="inline">{{ $holding->parcel->area_hectares }} ha</dd></div>
                </dl>
                <form method="post" action="{{ route('staff.parcel-listings.review', $listing) }}" class="flex flex-wrap gap-2">
                    @csrf
                    @if ($listing->status === 'pending')
                        <button name="decision" value="publish" class="rounded bg-emerald-800 px-4 py-2 text-white">Publish listing</button>
                    @endif
                    <button name="decision" value="withdraw" class="rounded border px-3 py-2">Withdraw listing</button>
                </form>
            </section>
        @empty
            <p>There are no listings awaiting review or published.</p>
        @endforelse
        {{ $listings->links() }}
    </div>
</x-staff-shell>
