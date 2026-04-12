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
        'key' => 'PUQCloudInfo',
        'name' => 'PUQCloud Info',
        'description' => 'Information about PUQCloud',
        'icon' => 'metismenu-icon fa fa-info',
        'width' => 4,
        'height' => 50,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getPUQcloudInfo',
    ],
    [
        'key' => 'AutomationStatus',
        'name' => 'Automation Status',
        'description' => 'Show statuses Cron and Horizon',
        'icon' => 'metismenu-icon fa fa-clock',
        'width' => 3,
        'height' => 17,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getAutomationStatus',
        'permission' => 'automation-settings-view',
    ],
    [
        'key' => 'TaskQueue',
        'name' => 'Task Queue',
        'description' => 'Show Task Queue',
        'icon' => 'metismenu-icon fa fa-clock',
        'width' => 2,
        'height' => 31,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getTaskQueue',
        'permission' => 'task-queue-view',
    ],
    [
        'key' => 'StaffOnline',
        'name' => 'Staff Online',
        'description' => 'Show Staff Online',
        'icon' => 'metismenu-icon fa fa-users',
        'width' => 3,
        'height' => 50,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getStaffOnline',
        'permission' => 'admins-view',
    ],
    [
        'key' => 'ServicesOverview',
        'name' => 'Services Overview',
        'description' => 'Show Services by status',
        'icon' => 'metismenu-icon fa fa-server',
        'width' => 4,
        'height' => 30,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getServicesOverview',
        'permission' => 'clients-view',
    ],
    [
        'key' => 'FinancialSummary',
        'name' => 'Financial Summary',
        'description' => 'Show invoices and payments overview',
        'icon' => 'metismenu-icon fa fa-money-bill-wave',
        'width' => 4,
        'height' => 30,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getFinancialSummary',
        'permission' => 'finance-view',
    ],
    [
        'key' => 'ClientsOverview',
        'name' => 'Clients Overview',
        'description' => 'Show clients statistics',
        'icon' => 'metismenu-icon fa fa-user-friends',
        'width' => 4,
        'height' => 30,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getClientsOverview',
        'permission' => 'clients-view',
    ],
    [
        'key' => 'SslCertificatesStatus',
        'name' => 'SSL Certificates',
        'description' => 'Show SSL certificates by status',
        'icon' => 'metismenu-icon fa fa-lock',
        'width' => 3,
        'height' => 30,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getSslCertificatesStatus',
        'permission' => 'ssl-manager-ssl-certificates',
    ],
    [
        'key' => 'TicketsOverview',
        'name' => 'Tickets Overview',
        'description' => 'Show support tickets statistics',
        'icon' => 'metismenu-icon fa fa-life-ring',
        'width' => 4,
        'height' => 17,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getTicketsOverview',
        'permission' => 'tickets-view',
    ],
    [
        'key' => 'RecentActivity',
        'name' => 'Recent Activity',
        'description' => 'Show recent activity log',
        'icon' => 'metismenu-icon fa fa-history',
        'width' => 5,
        'height' => 50,
        'handler' => 'app/Http/Controllers/Admin/AdminWidgetsController.php@getRecentActivity',
        'permission' => 'activity-log-view',
    ],

];
