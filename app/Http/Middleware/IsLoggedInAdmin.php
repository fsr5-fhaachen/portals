<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsLoggedInAdmin
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // get auth user
        $user = $request->user();

        // check if user is no tutor or is not logged in as tutor
        if (! $user->is_tutor || ! $request->session()->has('tutor')) {
            return redirect()->route('dashboard.index');
        }

        // check if user is not a admin
        if (! $user->is_admin) {
            return redirect()->route('dashboard.index');
        }

        return $next($request);
    }
}
