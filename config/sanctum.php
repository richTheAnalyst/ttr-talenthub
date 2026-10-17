<?php

use Laravel\Sanctum\Sanctum;

return [

    // Token-only API: no stateful SPA domains.
    'stateful' => [],

    'guard' => ['web'],

    // Minutes until an API token expires (null = never).
    'expiration' => env('SANCTUM_TOKEN_EXPIRATION') ? (int) env('SANCTUM_TOKEN_EXPIRATION') : null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
