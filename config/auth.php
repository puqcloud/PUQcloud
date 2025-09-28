<?php

return [
    'defaults' => [
        'guard' => 'admin',
        'passwords' => 'admin',
    ],
    'guards' => [
        'client' => [
            'driver' => 'session',
            'provider' => 'client',
            'remember' => 525600,
        ],
        'admin' => [
            'driver' => 'session',
            'provider' => 'admin',
            'remember' => 525600,
        ],
    ],
    'providers' => [
        'client' => [
            'driver' => 'eloquent',
            'model' => \App\Models\User::class,
        ],
        'admin' => [
            'driver' => 'eloquent',
            'model' => \App\Models\Admin::class,
        ],
    ],
    'passwords' => [
        'client' => [
            'provider' => 'clients',
            'table' => 'clients_password_reset_tokens',
            'expire' => 120,
            'throttle' => 120,
        ],
        'admin' => [
            'provider' => 'admins',
            'table' => 'admins_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    'password_timeout' => 10800,
];
