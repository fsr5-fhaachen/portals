<?php

namespace App\Http\Middleware;

use App\Models\Tutor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class IsAdmin
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
        //check if user is admin
        $tutor = Tutor::find($request->session()->get('tutor'))->first();
        if (!$tutor->is_admin) {
            return Redirect::to('/tutor/overview');
        }

        return $next($request);
    }
}
