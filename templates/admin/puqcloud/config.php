<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */
return [
    'name' => 'PUQcloud Admin Template',
    'version' => '1.0.0',
    'author' => [
        'name' => 'Ruslan Polovyi',
        'email' => 'info@puqcloud.com',
        'website' => 'https://puqcloud.com',
    ],
    'description' => 'This is the admin default template for the PUQcloud.',
    'logo' => '',

    'requirements' => [
        'PUQcloud' => '1',
        'php' => '8.2',
    ],

    'license' => [
        'type' => 'MIT',
        'url' => 'https://opensource.org/licenses/MIT',
    ],

    'support' => [
        'documentation' => 'https://puqcloud.com/docs',
        'changelog' => 'https://puqcloud.com/changelog',
        'support_email' => 'support@puqcloud.com',
    ],

    'timestamps' => [
        'created_at' => '2024-10-01',
        'updated_at' => '2024-10-07',
    ],
];
