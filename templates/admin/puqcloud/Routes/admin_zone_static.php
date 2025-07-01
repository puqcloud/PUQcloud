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
use Illuminate\Support\Facades\Route;

Route::get('puqcloud.js', function () {
    return response()->file(config('template.admin.base_path').'/views/js/puqcloud.js', [
        'Content-Type' => 'application/javascript',
    ]);
})->name('puqcloud.js');
