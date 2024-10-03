<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

# needed to set before autoload.php
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_PHP_DETECTORS=all');
// putenv('OTEL_PHP_RESOURCE_DETECTORS=all');
putenv('OTEL_PROPAGATORS=baggage,tracecontext');
putenv('OTEL_SERVICE_NAME=local-test');
putenv('OTEL_TRACES_EXPORTER=otlp');
// putenv('OTEL_PHP_EXPERIMENTAL_AUTO_ROOT_SPAN=true');
// putenv('OTEL_METRICS_EXPORTER=otlp');
// putenv('OTEL_LOGS_EXPORTER=otlp');
// putenv('OTEL_TRACES_SAMPLER=traceidratio');
// putenv('OTEL_TRACES_SAMPLER_ARG=0.95');
putenv('OTEL_EXPORTER_OTLP_PROTOCOL=grpc');
putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4317');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__ . '/../bootstrap/app.php')
    ->handleRequest(Request::capture());
