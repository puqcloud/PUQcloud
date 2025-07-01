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
    'product' => [
        'one-time' => [
            'prices' => ['setup', 'base'],
            'priority' => null,
        ],
        'daily' => [
            'prices' => ['setup', 'base', 'idle', 'switch_down', 'switch_up', 'uninstall'],
            'priority' => 24,
        ],
        'weekly' => [
            'prices' => ['setup', 'base', 'idle', 'switch_down', 'switch_up', 'uninstall'],
            'priority' => 168,
        ],
        'bi-weekly' => [
            'prices' => ['setup', 'base', 'idle', 'switch_down', 'switch_up', 'uninstall'],
            'priority' => 336,
        ],
        'monthly' => [
            'prices' => ['setup', 'base', 'idle', 'switch_down', 'switch_up', 'uninstall'],
            'priority' => 720,
        ],
        'quarterly' => [
            'prices' => ['setup', 'base', 'idle', 'switch_down', 'switch_up', 'uninstall'],
            'priority' => 2160,
        ],
        'semi-annually' => [
            'prices' => ['setup', 'base', 'idle', 'switch_down', 'switch_up', 'uninstall'],
            'priority' => 4320,
        ],
        'annually' => [
            'prices' => ['setup', 'base', 'idle', 'switch_down', 'switch_up', 'uninstall'],
            'priority' => 8760,
        ],
        'biennially' => [
            'prices' => ['setup', 'base', 'idle', 'switch_down', 'switch_up', 'uninstall'],
            'priority' => 17520,
        ],
        'triennially' => [
            'prices' => ['setup', 'base', 'idle', 'switch_down', 'uninstall'],
            'priority' => 26280,
        ],
    ],
];
