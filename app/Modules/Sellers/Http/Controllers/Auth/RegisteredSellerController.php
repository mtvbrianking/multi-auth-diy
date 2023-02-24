<?php

namespace App\Modules\Sellers\Http\Controllers\Auth;

use App\Modules\Sellers\Http\Controllers\Controller;
use App\Modules\Sellers\Models\Seller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredSellerController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('seller::auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.Seller::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $seller = Seller::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($seller));

        Auth::guard('seller')->login($seller);

        return redirect('/seller');
    }
}
