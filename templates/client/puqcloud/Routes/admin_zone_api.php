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

Route::get('layout_options', [AdminZoneController::class, 'getLayoutOptions'])
    ->name('layout_options.get')
    ->middleware('WebPermission:adminTemplate-layoutOptions');

Route::put('layout_options', [AdminZoneController::class, 'putLayoutOptions'])
    ->name('layout_options.put')
    ->middleware('WebPermission:clientTemplate-layoutOptions');

Route::get('login_layout_options', [AdminZoneController::class, 'getLoginLayoutOptions'])
    ->name('login_layout_options.get')
    ->middleware('WebPermission:adminTemplate-layoutOptions');

Route::put('login_layout_options', [AdminZoneController::class, 'putLoginLayoutOptions'])
    ->name('login_layout_options.put')
    ->middleware('WebPermission:clientTemplate-layoutOptions');
