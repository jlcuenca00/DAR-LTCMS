<?php

namespace App\Http\Controllers\Geodetic;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\LandTransferApplication;

class GeodeticPortalController extends Controller
{
    /**
     * Geodetic: read-only parcel and landholding reference page.
     */
    public function parcels()
    {
        $landholdings = Landholding::with(['parcel', 'landowner'])
            ->orderByDesc('created_at')
            ->get();

        return view('geodetic.parcels.index', compact('landholdings'));
    }

    /**
     * Geodetic: read-only application reference page.
     */
    public function applications()
    {
        $applications = LandTransferApplication::with([
                'transferorLandowner',
                'transfereeLandowner',
                'applicationParcels.parcel',
                'clearance',
            ])
            ->orderByDesc('created_at')
            ->get();

        return view('geodetic.applications.index', compact('applications'));
    }
}