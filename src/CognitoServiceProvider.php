<?php

namespace Fishdaa\CognitoGuard;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CognitoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cognito.php' => config_path('cognito.php'),
            ], 'config');
        }

        $this->app->singleton(JwksService::class, function (Application $app) {
            return new JwksService();
        });

        $this->app->singleton(TokenService::class, function (Application $app) {
            return new TokenService();
        });

        // Scoped, not singleton: the guard holds the Request and the resolved
        // user, so it must be rebuilt per request under long-lived workers
        // (Laravel Octane / Swoole). Octane flushes scoped instances between requests.
        $this->app->scoped(CognitoGuard::class, function (Application $app) {
            return new CognitoGuard(
                new ProviderRepository($app['auth']->createUserProvider('users')),
                $app['request']
            );
        });

        // Build a fresh guard per resolution so AuthManager's guard cache
        // can never pin a stale request/user across requests.
        $this->app['auth']->extend('cognito', function ($app, $name, array $config) {
            return new CognitoGuard(
                new ProviderRepository($app['auth']->createUserProvider($config['provider'] ?? 'users')),
                $app['request']
            );
        });
    }

    public function register()
    {
        if (! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__.'/../config/cognito.php', 'cognito');
        }
    }
}
