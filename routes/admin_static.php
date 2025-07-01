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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

$templateRoutes = config('template.admin.base_path').'/Routes/admin_zone_static.php';
try {
    if (file_exists($templateRoutes)) {
        Route::prefix('template/')
            ->name('template.')
            ->group($templateRoutes);
    }
} catch (\Exception $e) {
    Log::error('Error connecting admin template routes file: '.$e->getMessage());
}
