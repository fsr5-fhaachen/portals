<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsStudent;
use App\Http\Middleware\IsTutor;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use OwenIt\Auditing\AuditingServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        AuditingServiceProvider::class,
        PermissionServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('app.login'));
        $middleware->redirectUsersTo(AppServiceProvider::HOME);

        $middleware->throttleApi();

        $middleware->web(HandleInertiaRequests::class);

        $middleware->alias([
            'isAdmin' => IsAdmin::class,
            'isStudent' => IsStudent::class,
            'isTutor' => IsTutor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
