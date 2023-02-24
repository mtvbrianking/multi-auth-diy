<?php

namespace App\Modules\Sellers;

use App\Modules\Sellers\Auth\SellerGuard;
use App\Modules\Sellers\Auth\SellerUserProvider;
use App\Modules\Sellers\Models\Seller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SellerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/seller.php');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'seller');

        Blade::component('seller-app-layout', View\Components\SellerAppLayout::class);
        Blade::component('seller-guest-layout', View\Components\SellerGuestLayout::class);
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->registerMiddleware();
        // $this->registerAuthDrivers();
        $this->injectSellerAuth();
    }

    /**
     * @see https://laracasts.com/discuss/channels/general-discussion/register-middleware-via-service-provider
     */
    protected function registerMiddleware()
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('seller.auth', Http\Middleware\RedirectIfNotSeller::class);
        $router->aliasMiddleware('seller.guest', Http\Middleware\RedirectIfSeller::class);
        $router->aliasMiddleware('seller.verified', Http\Middleware\EnsureSellerEmailIsVerified::class);
        $router->aliasMiddleware('seller.password.confirm', Http\Middleware\RequireSellerPassword::class);
    }

    /**
     * @see https://www.devrohit.com/custom-authentication-in-laravel
     */
    protected function registerAuthDrivers()
    {
        Auth::provider('seller_provider_driver', function ($app) {
            return new SellerUserProvider($app['hash'], Seller::class);
        });

        Auth::extend('seller_guard_driver', function ($app) {
            $provider = Auth::createUserProvider('sellers');

            return new SellerGuard('seller', $provider, $app['session.store']);
        });
    }

    protected function injectSellerAuth()
    {
        $this->app['config']->set('auth.guards.seller', [
            'driver' => 'session',
            // 'driver' => 'seller_guard_driver',
            'provider' => 'sellers',
        ]);

        $this->app['config']->set('auth.providers.sellers', [
            'driver' => 'eloquent',
            // 'driver' => 'seller_provider_driver',
            'model' => Models\Seller::class,
        ]);

        $this->app['config']->set('auth.passwords.seller', [
            'provider' => 'sellers',
            'table' => 'seller_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ]);

        // dd($this->app['config']->get('auth'));
    }
}
