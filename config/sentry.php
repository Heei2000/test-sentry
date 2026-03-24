<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    // Release & environment
    'release' => env('SENTRY_RELEASE'),
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'local')),

    // Performance tracing
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

    // Optional: event retention (if supported by your Sentry plan)
    'send_default_pii' => false,

    // Laravel specific options
    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => false,
        'queue_info' => true,
        'command_info' => true,
    ],

    // Enable Laravel job/command/listener auto-instrumentation
    'tracing' => [
        'queue_job_middleware' => true,
        'console_commands' => true,
        'sql_queries' => true,
        'redis_commands' => true,
        'http_client' => true,
        'views' => true,
    ],
];
