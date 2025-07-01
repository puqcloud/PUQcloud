<?php

return [

    'default' => 'log',

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'url' => '',
            'host' => '',
            'port' => '',
            'encryption' => '',
            'username' => '',
            'password' => '',
            'timeout' => null,
            'local_domain' => '',
        ],
        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],
    ],

    'from' => [
        'address' => '',
        'name' => '',
    ],

];
