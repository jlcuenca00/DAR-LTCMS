<?php

namespace App\Http\Controllers;

use App\Models\Landholding;

class LandholdingController extends Controller
{
    public function index()
    {
        $landholdings = Landholding::orderBy('id', 'desc')->get();
        return view('landholdings.index', compact('landholdings'));
    }
}
