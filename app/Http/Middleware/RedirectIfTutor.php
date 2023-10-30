<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfTutor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // get auth user
        $user = $request->user();

        // check if user is tutor and is logged in as tutor
        if ($user->hasRole(['admin', 'esa', 'stage tutor', 'tutor']) && $request->session()->has('tutor')) {
            return redirect()->route('dashboard.tutor.index');
        }

        return $next($request);
    }
}
