<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandholdingController;
use App\Http\Controllers\LandTransferApplicationController;

Route::get('/landholdings', [LandholdingController::class, 'index']);
Route::get('/applications', [LandTransferApplicationController::class, 'index']);
