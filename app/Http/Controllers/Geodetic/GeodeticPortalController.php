<?php

namespace App\Http\Controllers\Geodetic;

use App\Http\Controllers\Controller;
use App\Models\Landholding;

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
}
