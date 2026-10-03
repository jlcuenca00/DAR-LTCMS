<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Parcel;

class ParcelMapController extends Controller
{
    public function index(\App\Services\ParcelMapDataService $maps)
    {
        $mapConfig = $maps->config(\Illuminate\Support\Facades\Auth::user());

        return view('staff.maps.parcel-map', compact('mapConfig'));
    }

}
