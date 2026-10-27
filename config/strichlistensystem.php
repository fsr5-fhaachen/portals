<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kiosk Token
    |--------------------------------------------------------------------------
    |
    | Every request to /strichlistensystem/kiosk must send this token as
    | "Authorization: Bearer <token>" header. If no token is configured,
    | the kiosk is not reachable at all.
    |
    */

    'kiosk_token' => env('STRICHLISTENSYSTEM_KIOSK_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Kiosk Session Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds of inactivity after which a person that unlocked the kiosk with
    | the PIN has to enter the PIN again.
    |
    */

    'kiosk_session_timeout' => 120,

    /*
    |--------------------------------------------------------------------------
    | PIN Attempts
    |--------------------------------------------------------------------------
    |
    | Maximum wrong PIN attempts per person and minute in the kiosk.
    |
    */

    'pin_max_attempts' => 5,

    /*
    |--------------------------------------------------------------------------
    | Article Limit
    |--------------------------------------------------------------------------
    |
    | The max order amount of an article is the maximum amount a person can
    | book per kiosk login (from entering the pin until logout or timeout).
    | In the self service it is the maximum amount per time window (seconds).
    | Cancelled bookings do not count.
    |
    */

    'self_service_limit_window' => 60,

    /*
    |--------------------------------------------------------------------------
    | Cancel Window
    |--------------------------------------------------------------------------
    |
    | Seconds in which a booking can be cancelled. The server accepts a
    | cancellation for 10 additional seconds to compensate for latency.
    |
    */

    'cancel_window' => 60,

    /*
    |--------------------------------------------------------------------------
    | Article Icons
    |--------------------------------------------------------------------------
    |
    | Font Awesome solid icons that can be selected for articles. Every icon
    | listed here must also be registered in resources/js/app.js.
    |
    */

    'icons' => [
        'beer-mug-empty',
        'lemon',
        'wine-bottle',
        'wine-glass',
        'glass-water',
        'bottle-water',
        'faucet',
        'martini-glass-citrus',
        'whiskey-glass',
        'champagne-glasses',
        'mug-hot',
        'mug-saucer',
        'cookie',
        'pizza-slice',
        'hotdog',
        'ice-cream',
    ],

];
