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
        $landholdings = Landholding::query()
            ->select([
                'id',
                'landowner_id',
                'parcel_id',
                'area_hectares',
                'status',
                'created_at',
            ])
            ->with([
                'parcel:id,parcel_code,title_no,tax_decl_no,survey_plan_number,municipality,barangay,province,agricultural_status,status,geometry_geojson',
                'landowner:id,first_name,middle_name,last_name,suffix',
            ])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('geodetic.parcels.index', compact('landholdings'));
    }
}
