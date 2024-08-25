<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'force_https' => env('APP_FORCE_HTTPS', false),

    'event_type' => env('APP_EVENT_TYPE', 'demo'),

    'tutor_password' => env('TUTOR_PASSWORD', 'password'),

    'admin_password' => env('ADMIN_PASSWORD', 'admin'),

    'public_api_secret' => env('PUBLIC_API_SECRET', 'secret'),


];
