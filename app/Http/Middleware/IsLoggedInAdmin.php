<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsLoggedInAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // get auth user
        $user = $request->user();

        // check if user is no tutor or is not logged in as tutor
        if (! $user->hasRole(['admin', 'esa', 'stage tutor', 'tutor']) || ! $request->session()->has('tutor')) {
            return redirect()->route('dashboard.index');
        }

        // check if user is not a admin
        if (! $user->hasRole(['admin'])) {
            return redirect()->route('dashboard.index');
        }

        return $next($request);
    }
}
