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
        'artisan' => 'System:queueTest',
        'group' => 'System',
        'cron' => '* * * * *',
        'disable' => true,
    ],
    [
        'artisan' => 'System:clearingLostTasks',
        'group' => 'Cleanup',
        'cron' => '*/30 * * * *',
        'disable' => false,
    ],
    [
        'artisan' => 'System:DeleteAllTasks',
        'group' => 'Cleanup',
        'cron' => '*/30 * * * *',
        'disable' => true,
    ],
    [
        'artisan' => 'System:Cleanup',
        'group' => 'Cleanup',
        'cron' => '*/30 * * * *',
        'disable' => false,
    ],
    [
        'artisan' => 'Products:ConvertPrice',
        'group' => 'Products',
        'cron' => '* * * * *',
        'disable' => false,
    ],
    [
        'artisan' => 'Finance:ChargeServices',
        'group' => 'Finance',
        'cron' => '* * * * *',
        'disable' => false,
    ],
    [
        'artisan' => 'Service:CreateServices',
        'group' => 'Service',
        'cron' => '* * * * *',
        'disable' => false,
    ],
    [
        'artisan' => 'Service:SuspendServices',
        'group' => 'Service',
        'cron' => '* * * * *',
        'disable' => false,
    ],
    [
        'artisan' => 'Service:UnsuspendServices',
        'group' => 'Service',
        'cron' => '* * * * *',
        'disable' => false,
    ],
    [
        'artisan' => 'Service:TerminationServices',
        'group' => 'Service',
        'cron' => '* * * * *',
        'disable' => false,
    ],
    [
        'artisan' => 'Service:CancellationServices',
        'group' => 'Service',
        'cron' => '* * * * *',
        'disable' => false,
    ],
];
