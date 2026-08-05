<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'username' => $request->user()->username,
            'password' => $request->input('password'),
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended($this->dashboardRouteFor($request));
    }

    private function dashboardRouteFor(Request $request): string
    {
        return match ($request->user()->role) {
            'staff' => route('staff.dashboard'),
            'geodetic' => route('geodetic.dashboard'),
            default => route('landowner.dashboard'),
        };
    }
}
