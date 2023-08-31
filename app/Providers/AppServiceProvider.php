<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // check if production and app debug is false
        if (env('APP_ENV') === 'production' && env('APP_DEBUG') === false) {
            URL::forceScheme('https');
        }
    }
}
