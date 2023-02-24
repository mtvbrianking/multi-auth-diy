<?php

namespace Seller\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Seller\Http\Requests\ProfileUpdateRequest;

class ProfileController extends Controller
{
    /**
     * Display the seller's profile form.
     */
    public function edit(Request $request): View
    {
        return view('seller::profile.edit', [
            'user' => $request->user('seller'),
        ]);
    }

    /**
     * Update the seller's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user('seller')->fill($request->validated());

        if ($request->user('seller')->isDirty('email')) {
            $request->user('seller')->email_verified_at = null;
        }

        $request->user('seller')->save();

        return Redirect::route('seller.profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the seller's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password:seller'],
        ]);

        $seller = $request->user('seller');

        Auth::guard('seller')->logout();

        $seller->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/seller');
    }
}
