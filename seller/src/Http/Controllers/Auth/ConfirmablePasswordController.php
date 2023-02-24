<?php

namespace Seller\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Seller\Http\Controllers\Controller;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): View
    {
        return view('seller::auth.confirm-password');
    }

    /**
     * Confirm the seller's password.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('seller')->validate([
            'email' => $request->user('seller')->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('seller.auth.password_confirmed_at', time());

        return redirect()->intended('/seller');
    }
}
