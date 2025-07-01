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
        'title' => __('client_template.Info'),
        'route' => 'info',
        'active_links' => ['info'],
        'permission' => 'viewInfo',
    ],
    [
        'title' => __('client_template.Layout Options'),
        'route' => 'layout_options',
        'active_links' => ['layout_options'],
        'permission' => 'layoutOptions',
    ],
    [
        'title' => __('client_template.Login Page'),
        'route' => 'login_layout_options',
        'active_links' => ['login_layout_options'],
        'permission' => 'layoutOptions',
    ],
];
