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
use Template\Client\Controllers\ClientController;
use Template\Client\Controllers\CloudController;
use Template\Client\Controllers\PanelController;
use Template\Client\Controllers\UserController;

Route::get('/', function () {
    return redirect()->route('client.web.panel.login');
})->name('home');

Route::get('lang/{locale}', [AuthController::class, 'localeSwitch'])->name('locale.switch');

Route::middleware('panel_login_web')
    ->prefix('panel')
    ->name('panel.')
    ->group(function () {
        Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
        Route::get('/sign_up', [AuthController::class, 'signUpForm'])->name('sign_up');
        Route::get('/password/lost', [AuthController::class, 'passwordLostForm'])->name('password_lost');
        Route::get('/password/lost/requested', [AuthController::class, 'passwordLostRequested'])->name('password_lost.requested');
        Route::get('/password/reset/{token}', [AuthController::class, 'passwordResetForm'])->name('password_reset');
    });

Route::middleware('panel_web')
    ->prefix('panel')
    ->name('panel.')
    ->group(function () {
        Route::get('/dashboard', [PanelController::class, 'dashboard'])->name('dashboard');

        // Module ------------------------------------------------------------------------------------------------------
        Route::prefix('module')
            ->name('module.')
            ->group(function () {
                Route::get('/{type}/{name}/{method}/{uuid?}', [PanelController::class, 'moduleClientWeb'])->name('web');
            });

        // User --------------------------------------------------------------------------------------------------------
        Route::prefix('user')
            ->name('user.')
            ->group(function () {
                Route::get('/profile', [UserController::class, 'profile'])->name('profile');
                Route::get('/change_password', [UserController::class, 'changePassword'])->name('change_password');
                Route::get('/verification_center', [UserController::class, 'verificationCenter'])->name('verification_center');
                Route::get('/two_factor_authentication', [UserController::class, 'twoFactorAuthentication'])->name('two_factor_authentication');
            });

        // Client ------------------------------------------------------------------------------------------------------
        Route::prefix('client')
            ->name('client.')
            ->group(function () {
                Route::get('/profile', [ClientController::class, 'profile'])->name('profile');

                Route::get('/invoices', [ClientController::class, 'invoices'])->name('invoices');
                Route::get('/invoice/{uuid}/details', [ClientController::class, 'invoiceDetails'])->name('invoice.details');
                Route::get('/invoice/{uuid}/payment', [ClientController::class, 'invoicePayment'])->name('invoice.payment');

                Route::get('/transactions', [ClientController::class, 'transactions'])->name('transactions');

                Route::get('/add_funds', [ClientController::class, 'addFunds'])->name('add_funds');

            });

        // Cloud ------------------------------------------------------------------------------------------------------
        Route::prefix('cloud')
            ->name('cloud.')
            ->group(function () {
                Route::get('/group/{uuid}', [CloudController::class, 'cloudProductGroup'])->name('group');
                Route::get('/group/{uuid}/order', [CloudController::class, 'cloudProductGroupOrder'])->name('group.order');

                Route::get('/service/{uuid}/{tab?}', [CloudController::class, 'cloudService'])->name('service');
            });

    });
