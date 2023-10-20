<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicApiSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // check if authorization header is set
        if (! $request->hasHeader('Authorization')) {
            return response()->json([
                'message' => 'Authorization header is missing.',
            ], 401);
        }

        // check if authorization header is valid
        if ($request->header('Authorization') !== config('app.public_api_secret')) {
            return response()->json([
                'message' => 'Authorization header is invalid.',
            ], 401);
        }

        return $next($request);
    }
}
