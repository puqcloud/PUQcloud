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
use Template\Client\Controllers\AuthController;

$template = config('template.client.name', 'default');

Route::middleware('client_web')
    ->name('client.web.')
    ->group(base_path("templates/client/$template/Routes/client_zone_web.php"));

Route::middleware('client_api')
    ->prefix('api')
    ->name('client.api.')
    ->group(base_path("templates/client/$template/Routes/client_zone_api.php"));

Route::middleware('panel_login_api')
    ->post('api/login', [AuthController::class, 'postLogin'])
    ->name('client.api.login.post');

Route::middleware('panel_login_api')
    ->post('api/sign_up', [AuthController::class, 'postSignUp'])
    ->name('client.api.sign_up.post');

Route::middleware('panel_login_api')
    ->post('api/reset_password', [AuthController::class, 'postResetPassword'])
    ->name('client.api.reset_password.post');

Route::middleware('client_static')
    ->prefix('static')
    ->name('static.')
    ->group(base_path("templates/client/$template/Routes/client_zone_static.php"));
