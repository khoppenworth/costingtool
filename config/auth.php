<?php
return [
    'session_name' => env('SESSION_NAME', 'tcsa_session'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', 7200),
];
