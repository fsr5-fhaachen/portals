<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StrichlistensystemKiosk
{
    /**
     * Handle an incoming request.
     *
     * Every kiosk request must send the configured kiosk token as bearer token.
     * If no kiosk token is configured, the kiosk is not reachable.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $kioskToken = (string) config('strichlistensystem.kiosk_token');
        $bearerToken = (string) $request->bearerToken();

        if ($kioskToken === '' || $bearerToken === '' || ! hash_equals($kioskToken, $bearerToken)) {
            abort(401);
        }

        return $next($request);
    }
}
