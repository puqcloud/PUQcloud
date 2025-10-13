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

use App\Modules\Notification;

class puqBell extends Notification
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getSettingsPage(array $data = []): string
    {
        return $this->view('configuration', $data);
    }

    public function send(array $data = []): array
    {
        return [
            'status' => 'success',
            'data' => ['bell' => true],
        ];

    }
}
