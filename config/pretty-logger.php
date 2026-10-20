<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable the logger
    |--------------------------------------------------------------------------
    |
    | By default the logger follows APP_DEBUG (null = auto). Set a boolean to
    | force it on/off regardless of debug mode. When false, nothing is logged.
    |
    */

    'enabled' => env('PRETTY_LOGGER', null),

    /*
    |--------------------------------------------------------------------------
    | Where to write
    |--------------------------------------------------------------------------
    |
    | local  : stream colored boxes to the `php artisan serve` terminal (stderr)
    | file   : always also append the same output to storage/logs/http.log
    |
    */

    'to_terminal' => env('PRETTY_LOGGER_TERMINAL', true),   // stderr (best on local)
    'to_file' => env('PRETTY_LOGGER_FILE', true),           // storage/logs/http.log
    'file_path' => storage_path('logs/http.log'),

    /*
    |--------------------------------------------------------------------------
    | Body / header verbosity
    |--------------------------------------------------------------------------
    */

    // Show HTML response bodies (truncated). false = just show byte size.
    'html_body' => env('PRETTY_LOGGER_HTML_BODY', false),

    // Max characters of a JSON/text response body to print.
    'max_body' => (int) env('PRETTY_LOGGER_MAX_BODY', 600),

    // Max characters of a single header value before truncating.
    'max_header_value' => 80,

    /*
    |--------------------------------------------------------------------------
    | Security — what to hide
    |--------------------------------------------------------------------------
    */

    // Request/JSON keys whose values are masked with ******.
    'mask_keys' => [
        'password', 'password_confirmation', 'token', 'secret',
        'otp', 'authorization', 'api_key', 'apikey', 'access_token', 'refresh_token',
    ],

    // Headers whose values are always redacted.
    'redact_headers' => [
        'cookie', 'set-cookie', 'authorization', 'x-csrf-token', 'x-xsrf-token',
        'proxy-authorization',
    ],

    // Path prefixes that should never be logged (noise).
    'skip_prefixes' => [
        'build/', 'images/', 'css/', 'js/', 'vendor/', 'storage/',
        '_debugbar', '_wdt', '_ignition', 'favicon.ico', 'hot',
    ],

    /*
    |--------------------------------------------------------------------------
    | Box width (content area)
    |--------------------------------------------------------------------------
    */

    'width' => 78,
];
