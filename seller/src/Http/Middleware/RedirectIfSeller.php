<?php

namespace Seller\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see \Http\Middleware\RedirectIfAuthenticated
 */
class RedirectIfSeller
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $guard = 'seller'): Response
    {
        if (Auth::guard($guard)->check()) {
            return redirect('/seller');
        }

        return $next($request);
    }
}
