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
    'admin' => [
        'name' => env('TEMPLATE_ADMIN', 'puqcloud'),
        'view' => 'templates.admin.'.env('TEMPLATE_ADMIN', 'puqcloud').'/views',
        'base_path' => base_path('templates/admin/'.env('TEMPLATE_ADMIN', 'puqcloud')),
    ],
    'client' => [
        'name' => env('TEMPLATE_CLIENT', 'puqcloud'),
        'view' => 'templates.client.'.env('TEMPLATE_CLIENT', 'puqcloud').'/views',
        'base_path' => base_path('templates/client/'.env('TEMPLATE_CLIENT', 'puqcloud')),
    ],
];
