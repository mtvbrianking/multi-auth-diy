<?php

namespace Seller\Http\Controllers\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Seller\Http\Controllers\Controller;
use Seller\Http\Requests\Auth\EmailVerificationRequest;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated seller's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user('seller')->hasVerifiedEmail()) {
            return redirect()->intended('/seller?verified=1');
        }

        if ($request->user('seller')->markEmailAsVerified()) {
            event(new Verified($request->user('seller')));
        }

        return redirect()->intended('/seller.?verified=1');
    }
}
