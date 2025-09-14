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

        'client' => env('REDIS_CLIENT', 'phpredis-sentinel'),

        'default' => [
            'sentinel_host' => env('REDIS_SENTINEL_HOST', '127.0.0.1'),
            'sentinel_port' => (int) env('REDIS_SENTINEL_PORT', 26379),
            'sentinel_service' => env('REDIS_SENTINEL_SERVICE', 'mymaster'),
            'sentinel_timeout' => (float) env('REDIS_SENTINEL_TIMEOUT', 0),
            'sentinel_persistent' => env('REDIS_SENTINEL_PERSISTENT'),
            'sentinel_retry_interval' => (int) env('REDIS_SENTINEL_RETRY_INTERVAL', 0),
            'sentinel_read_timeout' => (float) env('REDIS_SENTINEL_READ_TIMEOUT', 0),
            'sentinel_username' => env('REDIS_SENTINEL_USERNAME'),
            'sentinel_password' => env('REDIS_SENTINEL_PASSWORD'),

            'connector_retry_attempts' => env('REDIS_CONNECTOR_RETRY_ATTEMPTS'),
            'connector_retry_delay' => env('REDIS_CONNECTOR_RETRY_DELAY'),

            'password' => env('REDIS_PASSWORD'),
            'database' => (int) env('REDIS_DB', 0),
        ],

        'cache' => [
            'sentinel_host' => env('REDIS_SENTINEL_HOST', '127.0.0.1'),
            'sentinel_port' => (int) env('REDIS_SENTINEL_PORT', 26379),
            'sentinel_service' => env('REDIS_SENTINEL_SERVICE', 'mymaster'),
            'sentinel_timeout' => (float) env('REDIS_SENTINEL_TIMEOUT', 0),
            'sentinel_persistent' => env('REDIS_SENTINEL_PERSISTENT'),
            'sentinel_retry_interval' => (int) env('REDIS_SENTINEL_RETRY_INTERVAL', 0),
            'sentinel_read_timeout' => (float) env('REDIS_SENTINEL_READ_TIMEOUT', 0),
            'sentinel_username' => env('REDIS_SENTINEL_USERNAME'),
            'sentinel_password' => env('REDIS_SENTINEL_PASSWORD'),

            'connector_retry_attempts' => env('REDIS_CONNECTOR_RETRY_ATTEMPTS'),
            'connector_retry_delay' => env('REDIS_CONNECTOR_RETRY_DELAY'),

            'password' => env('REDIS_PASSWORD'),
            'database' => (int) env('REDIS_DB', 1),
        ],

        // 'client' => env('REDIS_CLIENT', 'phpredis'),

        // 'options' => [
        //     'cluster' => env('REDIS_CLUSTER', 'redis'),
        //     'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        // ],

        // 'default' => [
        //     'url' => env('REDIS_URL'),
        //     'host' => env('REDIS_HOST', '127.0.0.1'),
        //     'username' => env('REDIS_USERNAME'),
        //     'password' => env('REDIS_PASSWORD'),
        //     'port' => env('REDIS_PORT', '6379'),
        //     'database' => env('REDIS_DB', '0'),
        // ],

        // 'cache' => [
        //     'url' => env('REDIS_URL'),
        //     'host' => env('REDIS_HOST', '127.0.0.1'),
        //     'username' => env('REDIS_USERNAME'),
        //     'password' => env('REDIS_PASSWORD'),
        //     'port' => env('REDIS_PORT', '6379'),
        //     'database' => env('REDIS_CACHE_DB', '1'),
        // ],

    ],

];
