<?php

namespace Seller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Seller\Auth\SellerGuard;
use Seller\Auth\SellerUserProvider;
use Seller\Models\Seller;

class SellerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/seller.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'seller');

        Blade::component('seller-app-layout', View\Components\SellerAppLayout::class);
        Blade::component('seller-guest-layout', View\Components\SellerGuestLayout::class);
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->registerMiddleware();
        $this->registerAuthDrivers();
        $this->injectSellerAuth();
    }

    /**
     * @see https://laracasts.com/discuss/channels/general-discussion/register-middleware-via-service-provider
     */
    protected function registerMiddleware()
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('seller.auth', \Seller\Http\Middleware\RedirectIfNotSeller::class);
        $router->aliasMiddleware('seller.guest', \Seller\Http\Middleware\RedirectIfSeller::class);
        $router->aliasMiddleware('seller.verified', \Seller\Http\Middleware\EnsureSellerEmailIsVerified::class);
        $router->aliasMiddleware('seller.password.confirm', \Seller\Http\Middleware\RequireSellerPassword::class);
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
            'driver' => 'seller_guard_driver', // 'session',
            'provider' => 'sellers',
        ]);

        $this->app['config']->set('auth.providers.sellers', [
            'driver' => 'seller_provider_driver', // 'eloquent',
            'model' => \Seller\Models\Seller::class,
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
