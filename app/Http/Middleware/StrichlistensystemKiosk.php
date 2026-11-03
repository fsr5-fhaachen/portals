<?php

namespace App\Http\Middleware;

use App\Models\StrichlistensystemKioskDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StrichlistensystemKiosk
{
    /**
     * Handle an incoming request.
     *
     * Every kiosk request must send the cookie of a paired kiosk device. A tablet is paired once
     * with the pairing url of the device, a deleted device or a new token locks the tablet out.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $device = StrichlistensystemKioskDevice::findByCookie($request->cookie(StrichlistensystemKioskDevice::COOKIE_NAME));

        if (! $device) {
            abort(401, 'Dieses Gerät ist nicht als Kiosk freigeschaltet.');
        }

        $device->touchLastUsed();
        $request->attributes->set('kioskDevice', $device);

        return $next($request);
    }
}
