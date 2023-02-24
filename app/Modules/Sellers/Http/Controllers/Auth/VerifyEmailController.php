<?php

namespace App\Modules\Sellers\Http\Controllers\Auth;

use App\Modules\Sellers\Http\Controllers\Controller;
use App\Modules\Sellers\Http\Requests\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;

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
