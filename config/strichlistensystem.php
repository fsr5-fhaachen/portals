<?php

return [

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
    | PIN Attempts per Kiosk Device
    |--------------------------------------------------------------------------
    |
    | Maximum wrong PIN attempts of all persons at one kiosk device. This
    | stops trying common PINs for many persons. The device is locked for
    | the given seconds after the first wrong attempt of the window.
    |
    */

    'kiosk_device_max_failed_pins' => 15,

    'kiosk_device_lock_seconds' => 600,

    /*
    |--------------------------------------------------------------------------
    | Trivial PINs
    |--------------------------------------------------------------------------
    |
    | PINs that are too easy to guess and can not be set in the self service.
    |
    */

    'trivial_pins' => [
        '0000', '1111', '2222', '3333', '4444', '5555', '6666', '7777', '8888', '9999',
        '1234', '2345', '3456', '4567', '5678', '6789', '0123',
        '4321', '5432', '6543', '7654', '8765', '9876', '3210',
        '1212', '1122', '1010', '2580', '0852', '1004', '2000', '6969', '1337',
    ],

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
