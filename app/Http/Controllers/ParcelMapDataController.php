<?php

namespace App\Http\Controllers;

use App\Services\ParcelMapDataService;
use Illuminate\Http\Request;

class ParcelMapDataController extends Controller
{
    public function features(Request $request, ParcelMapDataService $maps)
    {
        $input = $request->validate([
            'west' => ['required', 'numeric', 'between:-180,180'],
            'east' => ['required', 'numeric', 'between:-180,180', 'gt:west'],
            'south' => ['required', 'numeric', 'between:-90,90'],
            'north' => ['required', 'numeric', 'between:-90,90', 'gt:south'],
        ]);

        return response()->json($maps->features($request->user(), array_map(
            fn ($key) => (float) $input[$key], ['west', 'south', 'east', 'north']
        )));
    }

    public function search(Request $request, ParcelMapDataService $maps)
    {
        $input = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']]);

        return response()->json($maps->search($request->user(), trim($input['q'] ?? ''), (int) ($input['page'] ?? 1)));
    }

    public function feature(Request $request, ParcelMapDataService $maps, int $parcel)
    {
        return response()->json($maps->focus($request->user(), $parcel));
    }
}
