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
        'icon' => 'fas fa-user',
        'label' => __('main.My Account'),
        'url' => route('client.web.panel.user.profile'),
    ],
    [
        'icon' => 'fas fa-key',
        'label' => __('main.Change Password'),
        'url' => route('client.web.panel.user.change_password'),
    ],
    ['divider' => true],
    [
        'icon' => 'fas fa-shield-alt',
        'label' => __('main.Verification Center'),
        'url' => route('client.web.panel.user.verification_center'),
    ],
    [
        'icon' => 'fas fa-lock',
        'label' => __('main.Two Factor Authentication'),
        'url' => route('client.web.panel.user.two_factor_authentication'),
    ],
];
