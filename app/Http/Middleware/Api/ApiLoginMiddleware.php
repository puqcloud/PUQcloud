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

namespace App\Http\Middleware\Api;

use App\Services\TranslationService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class ApiLoginMiddleware
{
    public function handle(Request $request, Closure $next): JsonResponse|string
    {
        if (! in_array('*', config('app.admin_allowed_ips'))) {
            if (! in_array($request->ip(), config('app.admin_allowed_ips'))) {
                return redirect('https://puqcloud.com/');
            }
        }

        session(['locale' => config('locale.admin.default')]);
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');

        if (Auth::guard('admin')->check()) {
            return response()->json([
                'errors' => [__('error.Already authenticated')],
            ], 401);
        }

        return $next($request);
    }
}
