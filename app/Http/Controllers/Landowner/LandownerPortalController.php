<?php

namespace App\Http\Controllers\Landowner;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
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
            ->get();

        return view('landowner.parcels.index', compact('landholdings'));
    }

    /**
     * Landowner: View only their own application status.
     *
     * A landowner may be either transferor or transferee.
     */
    public function applications()
    {
        $landownerIds = Landowner::where('user_id', Auth::id())->pluck('id');

        $applications = LandTransferApplication::with([
                'transferorLandowner',
                'transfereeLandowner',
                'applicationParcels.parcel',
                'clearance',
            ])
            ->where(function ($query) use ($landownerIds) {
                $query->whereIn('transferor_landowner_id', $landownerIds)
                    ->orWhereIn('transferee_landowner_id', $landownerIds);
            })
            ->orderByDesc('created_at')
            ->get();

        return view('landowner.applications.index', compact('applications'));
    }
}