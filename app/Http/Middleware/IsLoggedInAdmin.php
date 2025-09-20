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

        // check if user is not an admin
        if (! $user->hasRole(['admin'])) {
          return redirect()->route('dashboard.index');
        }

        // check if user is not logged in as tutor
        if (! $request->session()->has('tutor')) {
            session(['url.intended' => url()->current()]);
            return redirect()->route('dashboard.tutor.login');
        }

      return $next($request);
    }
}
