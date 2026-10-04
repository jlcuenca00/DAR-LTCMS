<?php

namespace App\Http\Controllers\Landowner;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LandownerPortalController extends Controller
{
    /**
     * Landowner: View only their own parcel/landholding records.
     */
    public function parcels()
    {
        $landownerIds = Landowner::where('user_id', Auth::id())->pluck('id');

        $landholdings = Landholding::with(['parcel', 'landowner'])
            ->whereIn('landowner_id', $landownerIds)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('landowner.parcels.index', compact('landholdings'));
    }

    /**
     * Landowner: View only their own application status.
     *
     * A landowner may be either transferor or transferee.
     */
    public function applications(Request $request)
    {
        $validated = $request->validate(['application' => ['nullable', 'integer', 'min:1']]);
        $focusedApplicationId = $validated['application'] ?? null;
        $landownerIds = Landowner::where('user_id', Auth::id())->pluck('id');

        $applications = LandTransferApplication::with([
                'transferorLandowner',
                'transfereeLandowner',
                'applicationParcels.parcel',
                'clearance',
                'activeComplianceNotice',
            ])
            ->linkedToLandownerIds($landownerIds)
            ->when($focusedApplicationId, fn ($query) => $query->whereKey($focusedApplicationId))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('landowner.applications.index', compact('applications', 'focusedApplicationId'));
    }
}