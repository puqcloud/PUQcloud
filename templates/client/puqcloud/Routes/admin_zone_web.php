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
use Template\Client\Controllers\AdminZoneController;

Route::get('info', [AdminZoneController::class, 'info'])
    ->name('info')
    ->middleware('WebPermission:adminTemplate-viewInfo');

Route::get('layout_options', [AdminZoneController::class, 'layoutOptions'])
    ->name('layout_options')
    ->middleware('WebPermission:adminTemplate-layoutOptions');

Route::get('login_layout_options', [AdminZoneController::class, 'loginLayoutOptions'])
    ->name('login_layout_options')
    ->middleware('WebPermission:adminTemplate-layoutOptions');
