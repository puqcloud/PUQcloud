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
        'icon' => 'fas fa-building',
        'label' => __('main.Profile'),
        'url' => route('client.web.panel.client.profile'),
    ],
    [
        'icon' => 'fas fa-file-invoice',
        'label' => __('main.Invoices'),
        'url' => route('client.web.panel.client.invoices'),
    ],
    [
        'icon' => 'fas fa-credit-card',
        'label' => __('main.Transactions'),
        'url' => route('client.web.panel.client.transactions'),
    ],
];
