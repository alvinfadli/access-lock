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

];
