<?php

namespace App\Http\Controllers\Landowner;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Parcel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ParcelMapController extends Controller
{
    public function index(\App\Services\ParcelMapDataService $maps)
    {
        $mapConfig = $maps->config(\Illuminate\Support\Facades\Auth::user());

        return view('landowner.maps.parcel-map', compact('mapConfig'));
    }

    public function show(Parcel $parcel)
    {
        Gate::authorize('view', $parcel);

        $landowner = Auth::user()->landowner;

        if (! $landowner) {
            abort(403);
        }

        $landholdings = Landholding::query()
            ->with('parcel')
            ->where('landowner_id', $landowner->id)
            ->where('parcel_id', $parcel->id)
            ->orderByDesc('created_at')
            ->get();

        if ($landholdings->isEmpty()) {
            abort(403);
        }

        return view('landowner.parcels.show', compact('parcel', 'landholdings', 'landowner'));
    }
}
