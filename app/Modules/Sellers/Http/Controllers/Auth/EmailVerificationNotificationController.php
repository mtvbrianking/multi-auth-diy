<?php

namespace App\Modules\Sellers\Http\Controllers\Auth;

use App\Modules\Sellers\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
