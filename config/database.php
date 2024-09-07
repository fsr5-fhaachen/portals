<?php

use Illuminate\Support\Str;

return [

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => false, // disable to preserve original behavior for existing applications
    ],

    'testingSeeder' => [
        'event_id' => env('SEEDER_EVENT_ID', 1),
        'course_id' => env('SEEDER_COURSE_ID', 1),
        'slot_id' => env('SEEDER_SLOT_ID', null),
        'regs_total' => env('SEEDER_TOTAL', 1),
        'regs_nd' => env('SEEDER_NONDRINKERS', 1),
    ],

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
