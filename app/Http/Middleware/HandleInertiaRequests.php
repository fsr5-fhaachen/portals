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
        // get all modules and store them in a array with $module->key => $module
        $modules = Module::all();
        $modulesArray = [];
        foreach ($modules as $module) {
            $modulesArray[$module->key] = $module;
        }

        // get user
        $user = $request->user();
        if ($user) {
            // get all roles of the user
            $user->rolesArray = $user->roles->pluck('name')->toArray();

            // get all permissions of the user
            $user->permissionsArray = $user->getAllPermissions()->pluck('name')->toArray();
        }

        return array_merge(parent::share($request), [
            'appEventType' => config('app.event_type'),
            'user' => $user,
            'modules' => $modulesArray,
            'message' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'pages' => Page::orderBy('sort_order')->get(),
        ]);
    }
}
