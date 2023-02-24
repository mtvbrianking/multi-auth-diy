<?php

namespace Seller\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Seller\Http\Controllers\Controller;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user('seller')->hasVerifiedEmail()
                    ? redirect()->intended('/seller')
                    : view('seller::auth.verify-email');
    }
}
