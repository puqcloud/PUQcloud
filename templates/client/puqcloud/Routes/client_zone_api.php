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

Route::get('logout', [AuthController::class, 'getLogout'])->name('logout');

Route::get('languages/select', [PanelController::class, 'getLanguagesSelect'])->name('languages.select.get');
Route::get('countries/select', [PanelController::class, 'getCountriesSelect'])->name('countries.select.get');
Route::get('regions/select', [PanelController::class, 'getRegionsSelect'])->name('regions.select.get');
Route::get('/verification', [PanelController::class, 'getVerification'])->name('verification.get');

// User ----------------------------------------------------------------------------------------------------------------
Route::prefix('user')
    ->name('user.')
    ->group(function () {
        Route::get('/profile', [UserController::class, 'getProfile'])->name('profile.get');
        Route::put('/profile', [UserController::class, 'putProfile'])->name('profile.put');
        Route::put('/change_password', [UserController::class, 'putChangePassword'])->name('change_password.put');

        Route::get('/verifications', [UserController::class, 'getVerifications'])->name('verifications.get');
        Route::get('/verification/{uuid}', [UserController::class, 'getVerification'])->name('verification.get');
        Route::get('/verification/{uuid}/verify', [UserController::class, 'getVerificationVerify'])->name('verification.verify.get');
        Route::post('/verification/verify', [UserController::class, 'postVerificationVerify'])->name('verification.verify.post');
        Route::post('/verification/default', [UserController::class, 'postVerificationDefault'])->name('verification.default.post');
        Route::delete('/verification/{uuid}', [UserController::class, 'deleteVerification'])->name('verification.delete');

        Route::get('/verification/totp/add', [UserController::class, 'getVerificationTotpAdd'])->name('verification.totp.add.get');
        Route::post('/verification/totp/add', [UserController::class, 'postVerificationTotpAdd'])->name('verification.totp.add.post');

        Route::post('/two_factor_authentication/enable', [UserController::class, 'postTwoFactorAuthenticationEnable'])->name('two_factor_authentication.enable.post');
        Route::post('/two_factor_authentication/disable', [UserController::class, 'postTwoFactorAuthenticationDisable'])->name('two_factor_authentication.disable.post');
    });

// Client --------------------------------------------------------------------------------------------------------------
Route::prefix('client')
    ->name('client.')
    ->group(function () {
        Route::get('/profile', [ClientController::class, 'getProfile'])->name('profile.get');
        Route::put('/profile', [ClientController::class, 'putProfile'])->name('profile.put');

        Route::get('/invoice/{uuid}', [ClientController::class, 'getInvoice'])->name('invoice.get');
        Route::get('/invoice/{uuid}/items', [ClientController::class, 'getInvoiceItems'])->name('invoice.items.get');
        Route::get('/invoice/{uuid}/transactions', [ClientController::class, 'getInvoiceTransactions'])->name('invoice.transactions.get');
        Route::get('/invoices', [ClientController::class, 'getInvoices'])->name('invoices.get');
        Route::get('/invoice/{uuid}/pdf', [ClientController::class, 'getInvoicePdf'])->name('invoice.pdf.get');

        Route::get('/invoice/{uuid}/payment_gateways', [ClientController::class, 'getInvoicePaymentGateways'])->name('invoice.payment_gateways.get');
        Route::get('/invoice/{uuid}/payment_gateway/{pg_uuid}', [ClientController::class, 'getInvoicePaymentGateway'])->name('invoice.payment_gateway.get');

        Route::get('/transactions', [ClientController::class, 'getTransactions'])->name('transactions.get');

        Route::post('/add_funds/top_up', [ClientController::class, 'postAddFundsTopUp'])->name('add_funds.top_up.post');
    });

// Cloud ------------------------------------------------------------------------------------------------------
Route::prefix('cloud')
    ->name('cloud.')
    ->group(function () {
        Route::get('/group/{uuid}/products/select', [CloudController::class, 'cloudProductGroupProductsSelect'])->name('group.products.select.get');

        Route::get('/group/{uuid}/list/{method}', [CloudController::class, 'cloudProductGroupListApi'])->name('group.list.get');
        Route::post('/group/{uuid}/list/{method}', [CloudController::class, 'cloudProductGroupListApi'])->name('group.list.post');
        Route::put('/group/{uuid}/list/{method}', [CloudController::class, 'cloudProductGroupListApi'])->name('group.list.put');
        Route::delete('/group/{uuid}/list/{method}', [CloudController::class, 'cloudProductGroupListApi'])->name('group.list.delete');

        Route::get('/group/{uuid}/order/{method}', [CloudController::class, 'cloudProductGroupOrderApi'])->name('group.order.get');
        Route::post('/group/{uuid}/order/{method}', [CloudController::class, 'cloudProductGroupOrderApi'])->name('group.order.post');
        Route::put('/group/{uuid}/order/{method}', [CloudController::class, 'cloudProductGroupOrderApi'])->name('group.order.put');
        Route::delete('/group/{uuid}/order/{method}', [CloudController::class, 'cloudProductGroupOrderApi'])->name('group.order.delete');

        Route::get('/service/{uuid}/manage/{method}', [CloudController::class, 'cloudServiceManageApi'])->name('service.manage.get');
        Route::post('/service/{uuid}/manage/{method}', [CloudController::class, 'cloudServiceManageApi'])->name('service.manage.post');
        Route::put('/service/{uuid}/manage/{method}', [CloudController::class, 'cloudServiceManageApi'])->name('service.manage.put');
        Route::delete('/service/{uuid}/manage/{method}', [CloudController::class, 'cloudServiceManageApi'])->name('service.manage.delete');

        Route::get('/service/{uuid}/module/{method}', [CloudController::class, 'cloudServiceModuleApi'])->name('service.module.get');
        Route::post('/service/{uuid}/module/{method}', [CloudController::class, 'cloudServiceModuleApi'])->name('service.module.post');
        Route::put('/service/{uuid}/module/{method}', [CloudController::class, 'cloudServiceModuleApi'])->name('service.module.put');
        Route::delete('/service/{uuid}/module/{method}', [CloudController::class, 'cloudServiceModuleApi'])->name('service.module.delete');

    });

// Module ------------------------------------------------------------------------------------------------------
Route::prefix('module')
    ->name('module.')
    ->group(function () {
        Route::get('/{type}/{name}/{method}/{uuid?}', [PanelController::class, 'moduleClientApi'])->name('get');
        Route::post('/{type}/{name}/{method}/{uuid?}', [PanelController::class, 'moduleClientApi'])->name('post');
        Route::put('/{type}/{name}/{method}/{uuid?}', [PanelController::class, 'moduleClientApi'])->name('put');
        Route::delete('/{type}/{name}/{method}/{uuid?}', [PanelController::class, 'moduleClientApi'])->name('delete');
    });

// Module ------------------------------------------------------------------------------------------------------
Route::prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        Route::get('/services', [PanelController::class, 'dashboardServicesApi'])->name('services.get');
        Route::get('/calculate_recurring_payments_breakdown', [PanelController::class, 'dashboardCalculateRecurringPaymentsBreakdownApi'])->name('calculate_recurring_payments_breakdown.get');

    });
