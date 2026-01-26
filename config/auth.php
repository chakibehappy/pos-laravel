<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | These are the defaults Laravel will use when you call Auth::user()
    | or use the `auth` middleware without specifying a guard.
    |
    | We default to the PLATFORM user (dashboard / SaaS).
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | A guard defines HOW users are authenticated and
    | WHICH provider (table/model) is used.
    |
    */

    'guards' => [

        /*
         * Platform / Dashboard users
         * Table: users
         * Login: email + password
         */
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        /*
         * POS users (cashier / supervisor)
         * Table: pos_users
         * Login: PIN
         */
        'pos' => [
            'driver' => 'session',
            'provider' => 'pos_users',
        ],
        // Update this part:
        'api' => [
            'driver' => 'sanctum',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Providers tell Laravel WHERE to get the user data from.
    | Each provider maps to ONE table/model.
    |
    */

    'providers' => [

        /*
         * Platform users
         */
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        /*
         * POS store staff
         */
        'pos_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\PosUser::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | ONLY platform users can reset passwords.
    | POS users use PINs and do NOT reset passwords.
    |
    */

    'passwords' => [

        'users' => [
            'provider' => 'users',
            'table' => env(
                'AUTH_PASSWORD_RESET_TOKEN_TABLE',
                'password_reset_tokens'
            ),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
