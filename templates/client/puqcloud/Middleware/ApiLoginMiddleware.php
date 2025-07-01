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

namespace Middleware;

use App\Services\TranslationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiLoginMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('locale.client.default'));
        App::setLocale($locale);
        session(['locale' => $locale]);
        TranslationService::init('client');

        if (Auth::guard('client')->check()) {
            return response()->json([
                'errors' => [__('error.Already authenticated')],
            ], 401);
        }

        return $next($request);
    }
}
