<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Hash
    |--------------------------------------------------------------------------
    |
    | The bcrypt hash of the access lock password. Set this by running:
    |   php artisan access-lock:set-password
    |
    | This will write ACCESS_LOCK_PASSWORD_HASH to your .env file automatically.
    |
    */
    'password_hash' => env('ACCESS_LOCK_PASSWORD_HASH', null),

    /*
    |--------------------------------------------------------------------------
    | Session Key
    |--------------------------------------------------------------------------
    |
    | The session key used to track whether the visitor has unlocked access.
    | When set to true the middleware will let the request pass through.
    |
    */
    'session_key' => 'access_lock_unlocked',

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URL prefix used for the unlock page routes.
    | Defaults to "access-lock" which produces /access-lock.
    |
    */
    'route_prefix' => 'access-lock',

    /*
    |--------------------------------------------------------------------------
    | Bypass Conditions
    |--------------------------------------------------------------------------
    |
    | List query string keys or header names that bypass the lock when ALL
    | listed items are present and non-empty on the incoming request.
    |
    | No value matching — just presence is enough.
    |
    | The session is permanently unlocked once a bypass condition is met;
    | subsequent requests from that visitor pass through without needing
    | the params or headers again.
    |
    */
    'bypass' => [

        // Query string keys that must ALL be present and non-empty.
        // e.g. visiting /?ssoKey=anything&userId=123 will unlock the session.
        'query' => [
            // 'ssoKey',
            // 'userId',
        ],

        // Header names that must ALL be present and non-empty.
        // e.g. sending X-SSO-Key: anything will unlock the session.
        'headers' => [
            // 'X-SSO-Key',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | API Support
    |--------------------------------------------------------------------------
    |
    | A pre-shared static token for stateless API authentication.
    | Set ACCESS_LOCK_API_TOKEN in your .env and send it as a
    | Bearer token or X-Access-Lock-Token header on every request
    | to routes protected by the "access.lock.api" middleware.
    |
    */
    'api' => [

        // The pre-shared static token for API authentication.
        'token' => env('ACCESS_LOCK_API_TOKEN', null),

    ],

];
