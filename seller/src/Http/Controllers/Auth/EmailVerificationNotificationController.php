<?php

namespace Seller\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Seller\Http\Controllers\Controller;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user('seller')->hasVerifiedEmail()) {
            return redirect()->intended('/seller');
        }

        $request->user('seller')->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
