<?php

namespace App\Http\Controllers;

use App\Models\Landholding;
use App\Models\ParcelListing;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParcelListingController extends Controller
{
    public function map()
    {
        return view('parcel-listings.map');
    }

    public function features()
    {
        $features = ParcelListing::query()
            ->where('status', ParcelListing::PUBLISHED)
            ->where(fn ($query) => $query->where('is_demo', true)
                ->orWhere(fn ($real) => $real->whereNotNull('consented_at')->whereNotNull('mobile')))
            ->current()
            ->when(app()->isProduction(), fn ($query) => $query->where('is_demo', false))
            ->with('landholding.parcel')
            ->orderBy('id')
            ->cursorPaginate(200);

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features->getCollection()->map(function (ParcelListing $listing) {
                $holding = $listing->landholding;
                $parcel = $holding?->parcel;
                if (! $parcel || $holding->status !== Landholding::STATUS_ACTIVE
                    || $parcel->status !== 'active' || $parcel->is_flagged
                    || (int) $listing->holding_revision !== $holding->record_revision
                    || (int) $listing->parcel_revision !== $parcel->record_revision
                    || ! ($geometry = ParcelListing::publicGeometry($parcel))) {
                    return null;
                }

                return [
                    'type' => 'Feature',
                    'geometry' => $geometry,
                    'properties' => [
                        'municipality' => $parcel->municipality,
                        'barangay' => $parcel->barangay,
                        'area_hectares' => $parcel->area_hectares,
                        'display_name' => $listing->display_name,
                        'mobile' => $listing->mobile,
                        'demo' => $listing->is_demo,
                    ],
                ];
            })->filter()->values(),
            'next' => $features->nextPageUrl(),
        ])->header('Cache-Control', 'no-store');
    }

    public function ownerIndex(Request $request)
    {
        $this->requireApprovedOwner($request);

        $holdings = $this->ownerHoldings($request)
            ->with(['parcel', 'parcelListing'])
            ->orderByDesc('id')->paginate(20);

        return view('parcel-listings.owner', compact('holdings'));
    }

    public function submit(Request $request, int $holding)
    {
        $this->requireApprovedOwner($request);
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'regex:/\A(?:09|\+639)\d{9}\z/'],
            'publish_consent' => ['accepted'],
        ]);

        DB::transaction(function () use ($request, $holding, $data) {
            $record = $this->ownerHoldings($request)->whereKey($holding)->lockForUpdate()->firstOrFail();
            $parcel = $record->parcel()->lockForUpdate()->firstOrFail();
            if ($record->status !== Landholding::STATUS_ACTIVE || $parcel->status !== 'active'
                || $parcel->is_flagged || ! ParcelListing::publicGeometry($parcel)
                || $parcel->landholdings()->active()->whereKeyNot($record->id)->exists()
                || (string) $record->area_hectares !== (string) $parcel->area_hectares) {
                throw ValidationException::withMessages(['listing' => 'The parcel needs an active, mapped, unflagged record and a single active holding covering its full area.']);
            }

            $mobile = str_starts_with($data['mobile'], '0') ? '+63'.substr($data['mobile'], 1) : $data['mobile'];
            $listing = $record->parcelListing()->updateOrCreate([], [
                'display_name' => $data['display_name'],
                'mobile' => $mobile,
                'status' => ParcelListing::PENDING,
                'consented_at' => now(),
                'parcel_revision' => $parcel->record_revision,
                'holding_revision' => $record->record_revision,
            ]);
            AuditLogger::record('parcel_listing.submitted', auditable: $listing);
        }, 3);

        return back()->with('status', 'Listing submitted for review.');
    }

    public function withdraw(Request $request, int $holding)
    {
        $this->requireApprovedOwner($request);
        DB::transaction(function () use ($request, $holding) {
            $record = $this->ownerHoldings($request)->whereKey($holding)->lockForUpdate()->firstOrFail();
            $listing = $record->parcelListing()->lockForUpdate()->firstOrFail();
            $this->withdrawListing($listing, 'parcel_listing.owner_withdrew');
        }, 3);

        return back()->with('status', 'Listing withdrawn.');
    }

    public function staffIndex()
    {
        $listings = ParcelListing::query()->with(['landholding.parcel', 'landholding.landowner'])
            ->whereIn('status', [ParcelListing::PENDING, ParcelListing::PUBLISHED])
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(20);

        return view('parcel-listings.staff', compact('listings'));
    }

    public function review(Request $request, int $listing)
    {
        $data = $request->validate(['decision' => ['required', 'in:publish,withdraw']]);

        DB::transaction(function () use ($listing, $data) {
            $reference = ParcelListing::findOrFail($listing);
            $holding = Landholding::query()->lockForUpdate()->findOrFail($reference->landholding_id);
            $parcel = $holding->parcel()->lockForUpdate()->firstOrFail();
            $record = ParcelListing::query()->lockForUpdate()->findOrFail($listing);
            if ($data['decision'] === 'withdraw') {
                $this->withdrawListing($record, 'parcel_listing.staff_withdrew');

                return;
            }

            if ($record->status !== ParcelListing::PENDING || ! $record->consented_at || ! $record->mobile
                || (int) $record->holding_revision !== $holding->record_revision
                || (int) $record->parcel_revision !== $parcel->record_revision
                || $holding->status !== Landholding::STATUS_ACTIVE
                || $parcel->status !== 'active' || $parcel->is_flagged
                || ! ParcelListing::publicGeometry($parcel)
                || $parcel->landholdings()->active()->whereKeyNot($holding->id)->exists()) {
                throw ValidationException::withMessages(['listing' => 'This listing is no longer eligible. The owner must submit it again.']);
            }

            $record->update(['status' => ParcelListing::PUBLISHED]);
            AuditLogger::record('parcel_listing.published', auditable: $record);
        }, 3);

        return back()->with('status', 'Listing review saved.');
    }

    private function requireApprovedOwner(Request $request): void
    {
        abort_unless($request->user()->registration_status === User::REGISTRATION_APPROVED, 403);
    }

    private function ownerHoldings(Request $request)
    {
        return Landholding::query()->whereHas('landowner', fn ($query) => $query
            ->where('user_id', $request->user()->id));
    }

    private function withdrawListing(ParcelListing $listing, string $action): void
    {
        if ($listing->status !== ParcelListing::WITHDRAWN) {
            $listing->update(['status' => ParcelListing::WITHDRAWN, 'consented_at' => null, 'mobile' => null]);
            AuditLogger::record($action, auditable: $listing);
        }
    }
}
