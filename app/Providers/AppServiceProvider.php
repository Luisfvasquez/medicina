<?php

namespace App\Providers;

use App\Guards\CookieJwtGuard;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
        |----------------------------------------------------------------------
        | Rate Limiters
        |----------------------------------------------------------------------
        */
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        /*
        |----------------------------------------------------------------------
        | Custom JWT Guard
        |----------------------------------------------------------------------
        */
        /** @var AuthManager $authManager */
        $authManager = $this->app->make(AuthManager::class);

        $authManager->extend('cookie_jwt', function ($app, $name, $config) use ($authManager) {
            return new CookieJwtGuard(
                $app->make('tymon.jwt'),
                $authManager->createUserProvider($config['provider'] ?? null),
                $app->make('request'),
            );
        });

        /*
        |----------------------------------------------------------------------
        | Observers
        |----------------------------------------------------------------------
        */
        \App\Models\PharmacyInventory::observe(\App\Observers\AuditObserver::class);
        \App\Models\PharmacyInventoryBatch::observe(\App\Observers\AuditObserver::class);
        \App\Models\QuoteOffer::observe(\App\Observers\AuditObserver::class);
    }
}
