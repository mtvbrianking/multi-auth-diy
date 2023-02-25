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
        $this->injectAuthConfiguration();
        $this->registerAuthDrivers('sellers', 'seller', Seller::class);
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
     * @see \Illuminate\Auth\AuthManager
     * @see https://www.devrohit.com/custom-authentication-in-laravel
     */
    protected function registerAuthDrivers(string $provider, string $guard, string $model)
    {
        Auth::provider('seller_provider_driver', function ($app) use ($model) {
            return new SellerUserProvider($app['hash'], $model);
        });

        /* AuthManager->createSessionDriver() */
        Auth::extend('seller_guard_driver', function ($app) use ($provider, $guard) {
            $userProvider = Auth::createUserProvider($provider);

            $sellerGuard = new SellerGuard($guard, $userProvider, $app['session.store']);

            if (method_exists($sellerGuard, 'setCookieJar')) {
                $sellerGuard->setCookieJar($this->app['cookie']);
            }

            if (method_exists($sellerGuard, 'setDispatcher')) {
                $sellerGuard->setDispatcher($this->app['events']);
            }

            if (method_exists($sellerGuard, 'setRequest')) {
                $sellerGuard->setRequest($this->app->refresh('request', $sellerGuard, 'setRequest'));
            }

            if (isset($config['remember'])) {
                $sellerGuard->setRememberDuration($config['remember']);
            }

            return $sellerGuard;
        });
    }

    protected function injectAuthConfiguration()
    {
        $this->app['config']->set('auth.guards.seller', [
            // 'driver' => 'session',
            'driver' => 'seller_guard_driver',
            'provider' => 'sellers',
        ]);

        $this->app['config']->set('auth.providers.sellers', [
            // 'driver' => 'eloquent',
            'driver' => 'seller_provider_driver',
            'model' => Models\Seller::class,
        ]);

        $this->app['config']->set('auth.passwords.sellers', [
            'provider' => 'sellers',
            'table' => 'seller_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ]);

        // dd($this->app['config']->get('auth'));
    }
}
