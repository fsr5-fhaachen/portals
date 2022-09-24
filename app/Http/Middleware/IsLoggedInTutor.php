<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class IsLoggedInTutor
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
        // get auth user
        $user = $request->user();

        // check if user is no tutor or is not logged in as tutor
        if (!$user->is_tutor || !$request->session()->has('tutor')) {
            return redirect()->route('dashboard.index');
        }

        return $next($request);
    }
}
