<?php

namespace Seller\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Seller\Http\Controllers\Controller;

class PasswordController extends Controller
{
    /**
     * Update the seller's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password:seller'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user('seller')->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
