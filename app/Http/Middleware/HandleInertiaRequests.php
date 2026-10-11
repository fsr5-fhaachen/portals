<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        // the props are lazy (closures), so that partial reloads (e.g. infinite scroll of tables) do not execute the queries
        return array_merge(parent::share($request), [
            'appEventType' => config('app.event_type'),
            'user' => function () use ($request) {
                $user = $request->user();
                if ($user) {
                    // get all roles of the user
                    $user->rolesArray = $user->roles->pluck('name')->toArray();

                    // get all permissions of the user
                    $user->permissionsArray = $user->getAllPermissions()->pluck('name')->toArray();
                }

                return $user;
            },
            // get all modules and store them in a array with $module->key => $module
            'modules' => fn () => Module::all()->keyBy('key')->all(),
            'strichlistensystemEnabled' => (bool) config('strichlistensystem.enabled'),
            // start time for the live update of tables
            'serverTime' => fn () => now()->timestamp,
            'message' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'pages' => fn () => Page::orderBy('sort_order')->get(),
        ]);
    }
}
