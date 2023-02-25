<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see \App\Http\Middleware\RedirectIfAuthenticated
 */
class RedirectIfAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $guard = 'admin'): Response
    {
        if (Auth::guard($guard)->check()) {
            return redirect('/admin');
        }

        return $next($request);
    }
}
