<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use App\Models\Module;
use Closure;
use Illuminate\Http\Request;

class ActiveModule
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if ($module && ! Module::where('key', $module)->first()->active) {
            return redirect()->route('app.index');
        }

        return $next($request);
    }
}
