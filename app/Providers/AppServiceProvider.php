<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

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
        // check if force https is enabled
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        $this->bootRoute();
    }

    public function bootRoute(): void
    {
        // check if app debug mode is not enabled and then apply rate limiter
        if (! config('app.debug')) {
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(1200)->by($request->user()?->id ?: $request->ip());
            });
        }

    }
}
