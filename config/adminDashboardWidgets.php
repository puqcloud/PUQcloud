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

];
