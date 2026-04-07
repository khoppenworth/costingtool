<?php
return [
    'session_name' => env('SESSION_NAME', 'tcsa_session'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', 7200),
    'secure_cookie' => (bool) env('SESSION_SECURE_COOKIE', false),
    'same_site' => (string) env('SESSION_SAME_SITE', 'Lax'),
];
