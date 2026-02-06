<?php

namespace App\Http\Controllers;

use App\Models\LandTransferApplication;

class LandTransferApplicationController extends Controller
{
    public function index()
    {
        $applications = LandTransferApplication::orderBy('id', 'desc')->get();
        return view('applications.index', compact('applications'));
    }
}
