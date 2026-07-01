<?php

namespace App\Providers;

use App\Guards\CookieJwtGuard;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /** @var AuthManager $authManager */
        $authManager = $this->app->make(AuthManager::class);

        $authManager->extend('cookie_jwt', function ($app, $name, $config) use ($authManager) {
            return new CookieJwtGuard(
                $app->make('tymon.jwt'),
                $authManager->createUserProvider($config['provider'] ?? null),
                $app->make('request'),
            );
        });
    }
}
