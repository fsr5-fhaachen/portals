<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Closure;
use Illuminate\Http\Request;

class IsLoggedInTutor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // get auth user
        $user = $request->user();

        // check if user is no tutor or is not logged in as tutor
        if (! $user->is_tutor || ! $request->session()->has('tutor')) {
            return redirect()->route('dashboard.index');
        }

        return $next($request);
    }
}
