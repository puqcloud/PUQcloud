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

use App\Services\AdminPermissionService;
use App\Services\TranslationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiCheckAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        // ---------- ADMIN IP LIMIT (panel) ----------
        if (! in_array('*', config('app.admin_allowed_ips'))) {
            if (! in_array($request->ip(), config('app.admin_allowed_ips'))) {
                return redirect('https://puqcloud.com/');
            }
        }

        session(['locale' => config('locale.admin.default')]);
        App::setLocale(config('locale.admin.default'));

        // ---------- 1. API KEY HAS PRIORITY ----------
        $rawKey = $request->header('X-API-Key');

        if ($rawKey !== null) {

            $hash = hash('sha256', $rawKey);

            $apiKey = \App\Models\AdminApiKey::where('hash', $hash)->first();

            if (! $apiKey) {
                TranslationService::init('admin');
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('error.Invalid API key')],
                ], 401);
            }

            // ---------- IP CHECK ----------
            $allowedIps = $apiKey->ips ?? [];

            if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps)) {
                TranslationService::init('admin');
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('error.IP not allowed')],
                ], 403);
            }

            $admin = $apiKey->admin;

            if (! $admin || $admin->disable) {
                TranslationService::init('admin');
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('error.Unauthorized')],
                ], 401);
            }

            $apiKey->update([
                'last_used_at' => now(),
            ]);

            app()->instance('admin', $admin);

        } else {

            // ---------- 2. SESSION ----------
            if (! Auth::guard('admin')->check()) {
                TranslationService::init('admin');
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('error.Unauthorized')],
                    'redirect' => route('admin.web.login'),
                ], 401);
            }

            $admin = Auth::guard('admin')->user();
            app()->instance('admin', $admin);
        }

        // ---------- LOCALE ----------
        if (empty($admin->language)
            || ! array_key_exists($admin->language, config('locale.admin.locales'))) {

            $admin->language = config('locale.admin.default');
            $admin->save();
        }

        session(['locale' => $admin->language]);
        App::setLocale($admin->language);
        TranslationService::init('admin');

        // ---------- DISABLED ----------
        if ($admin->disable) {
            Auth::guard('admin')->logout();

            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Your account is disabled')],
                'redirect' => route('admin.web.login'),
            ], 401);
        }

        // ---------- PERMISSIONS ----------
        $AdminPermission = new AdminPermissionService;
        app()->instance('AdminPermission', $AdminPermission);

        return $next($request);
    }
}
