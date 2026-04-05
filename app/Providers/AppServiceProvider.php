<?php

namespace App\Providers;
use App\Services\Auth\JwtGuard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;



use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //  Auth::extend('jwt', function (Application $app, string $name, array $config) {
        //     // Return an instance of Illuminate\Contracts\Auth\Guard...
 
        //     return new JwtGuard(Auth::createUserProvider($config['provider']));
        // });
        // die("kldjalkfjds");
        Auth::extend('jwt', function ($app, $name, array $config) {
        return new JwtGuard(
            Auth::createUserProvider($config['provider']),
            $app['request']
        );
    });
    }
}
