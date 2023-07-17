<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Closure;
use Illuminate\Http\Request;

class RedirectIfTutor
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // get auth user
        $user = $request->user();

        // check if user is tutor and is logged in as tutor
        if ($user->is_tutor && $request->session()->has('tutor')) {
            return redirect()->route('dashboard.tutor.index');
        }

        return $next($request);
    }
}
