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
    [
        'key' => 'ClientInformation',
        'name' => 'Client Information',
        'description' => 'Brief information about the client',
        'icon' => 'metismenu-icon fa fa-info',
        'width' => 3,
        'height' => 45,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getClientInformation',
        'permission' => 'clients-view',
    ],
    [
        'key' => 'ClientFinance',
        'name' => 'Client Finance',
        'description' => "Information about the client's finances",
        'icon' => 'metismenu-icon fa fa-money-check-alt',
        'width' => 3,
        'height' => 45,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getClientFinance',
        'permission' => 'clients-view',
    ],
    [
        'key' => 'Actions',
        'name' => 'Actions',
        'description' => 'Actions that can be launched',
        'icon' => 'metismenu-icon fas fa-play',
        'width' => 2,
        'height' => 40,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getClientActions',
        'permission' => 'clients-view',
    ],
];
