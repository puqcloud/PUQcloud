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
use Template\Admin\Controllers\AdminTemplateController;

Route::put('layout_options', [AdminTemplateController::class, 'putLayoutOptions'])
    ->name('layout_options')
    ->middleware('WebPermission:adminTemplate-layoutOptions');
