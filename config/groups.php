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
    'types' => [
        [
            'key' => 'system',
            'name' => 'System',
            'description' => 'The group configures access rights to the PUQcloud system',
            'order' => '1',
        ],
        [
            'key' => 'groups',
            'name' => 'Groups',
            'description' => 'The group has the ability to add other groups to itself',
            'order' => '2',
        ],
        [
            'key' => 'notification',
            'name' => 'Notification',
            'description' => 'The group has the ability to configure notifications',
            'order' => '3',
        ],
        [
            'key' => 'adminTemplate',
            'name' => 'Admin Template',
            'description' => 'The group configures access permissions for the Admin Template',
            'order' => '4',
        ],
        [
            'key' => 'clientTemplate',
            'name' => 'Client Template',
            'description' => 'The group configures access permissions for the Client Template',
            'order' => '5',
        ],
        [
            'key' => 'modules',
            'name' => 'Modules',
            'description' => 'The group configures access permissions for a module',
            'order' => '6',
        ],
    ],
];
