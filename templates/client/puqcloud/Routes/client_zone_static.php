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

use App\Models\File;
use Illuminate\Support\Facades\Route;
use Template\Client\Controllers\PanelController;

Route::get('file/img/{uuid}', function ($uuid) {
    $file = File::findOrFail($uuid);

    if (! $file->is_public) {
        abort(403, 'Access denied');
    }

    return $file->streamResponse();
})->name('file.img');

Route::get('puqcloud.js', function () {
    return response()->file(config('template.client.base_path').'/views/js/puqcloud.js', [
        'Content-Type' => 'application/javascript',
    ]);
})->name('puqcloud.js');

Route::get('/', function () {
    return redirect()->route('client.login');
})->name('home');

// Module ------------------------------------------------------------------------------------------------------
Route::prefix('module')
    ->name('module.')
    ->group(function () {
        Route::get('/{type}/{name}/{method}/{uuid?}', [PanelController::class, 'moduleClientStatic'])->name('get');
        Route::post('/{type}/{name}/{method}/{uuid?}', [PanelController::class, 'moduleClientStatic'])->name('post');
        Route::put('/{type}/{name}/{method}/{uuid?}', [PanelController::class, 'moduleClientStatic'])->name('put');
        Route::delete('/{type}/{name}/{method}/{uuid?}', [PanelController::class, 'moduleClientStatic'])->name('delete');
    });
