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
    [
        'key' => 'ClientServices',
        'name' => 'Client Services',
        'description' => 'List of client services with statuses',
        'icon' => 'metismenu-icon fa fa-server',
        'width' => 4,
        'height' => 45,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getClientServices',
        'permission' => 'clients-view',
    ],
    [
        'key' => 'ClientInvoices',
        'name' => 'Client Invoices',
        'description' => 'Recent invoices for this client',
        'icon' => 'metismenu-icon fa fa-file-invoice-dollar',
        'width' => 4,
        'height' => 45,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getClientInvoices',
        'permission' => 'finance-view',
    ],
    [
        'key' => 'ClientTransactions',
        'name' => 'Client Transactions',
        'description' => 'Recent transactions for this client',
        'icon' => 'metismenu-icon fa fa-exchange-alt',
        'width' => 4,
        'height' => 45,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getClientTransactions',
        'permission' => 'finance-view',
    ],
    [
        'key' => 'ClientNotes',
        'name' => 'Notes',
        'description' => 'Admin and client notes',
        'icon' => 'metismenu-icon fa fa-sticky-note',
        'width' => 4,
        'height' => 35,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getClientNotes',
        'permission' => 'clients-view',
    ],
    [
        'key' => 'ClientRecurringPayments',
        'name' => 'Recurring Payments',
        'description' => 'Recurring payment breakdown',
        'icon' => 'metismenu-icon fa fa-sync-alt',
        'width' => 3,
        'height' => 40,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getClientRecurringPayments',
        'permission' => 'clients-view',
    ],
];
