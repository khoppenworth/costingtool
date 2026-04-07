<?php
return [
    'name' => env('APP_NAME', 'TCSA'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'maintenance_exempt_paths' => ['/admin/upgrades'],
];
