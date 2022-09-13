<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class IsStudent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // check if user is not logged in as student
        if ($request->session()->missing('student')) {
            // check if user is logged in as tutor
            if ($request->session()->has('tutor')) {
                return Redirect::to('/tutor/overview');
            }

            return Redirect::to('/');
        }

        return $next($request);
    }
}
