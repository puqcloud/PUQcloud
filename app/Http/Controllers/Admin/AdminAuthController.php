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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

class AdminAuthController extends Controller
{
    public function loginForm(): \Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $title = __('login.Login');

        return view_admin('login.login_form', compact('title'));
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        app(HookService::class)->callHooks('AdminBeforeLogin', $credentials);

        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            $admin = Auth::guard('admin')->user();

            app(HookService::class)->callHooks('AdminAfterLogin', ['admin' => $admin]);

            logActivity(
                'info',
                get_class($admin).':'.$admin->uuid,
                'login',
            );

            if ($admin->disable) {

                app(HookService::class)->callHooks('AdminBeforeLogout', ['admin' => $admin]);
                Auth::guard('admin')->logout();
                app(HookService::class)->callHooks('AdminAfterLogout', ['admin' => $admin]);
                logActivity(
                    'info',
                    get_class($admin).':'.$admin->uuid,
                    'logout',
                    request()->ip(),
                    $admin->uuid
                );

                return response()->json([
                    'errors' => [__('error.Your account is disabled')],
                    'message' => ['email' => [__('error.Your account is disabled')], 'password' => [__('error.Your account is disabled')]],
                ], 401);
            }

            return response()->json([
                'message' => __('message.Successful authorization'),
            ], 200);
        }

        $credentials['date'] = Date::now();
        $credentials['ip'] = $request->ip();
        $credentials['r_dns'] = gethostbyaddr($credentials['ip']);
        app(HookService::class)->callHooks('AdminFailedAuthorization', $credentials);
        logActivity(
            'warning',
            'Invalid email or password. Failed Authorization: '.$credentials['email'],
            'login',
            request()->ip(),
        );

        return response()->json([
            'message' => ['email' => [__('error.Invalid email or password')], 'password' => [__('error.Invalid email or password')]],
        ], 401);

    }

    public function logout(): JsonResponse
    {
        if (Auth::guard('admin')->check()) {
            $admin = app('admin');
            app(HookService::class)->callHooks('AdminBeforeLogout', ['admin' => $admin]);
            Auth::guard('admin')->logout();
            app(HookService::class)->callHooks('AdminAfterLogout', ['admin' => $admin]);
            logActivity(
                'info',
                get_class($admin).':'.$admin->uuid,
                'logout',
                request()->ip(),
                $admin->uuid
            );
        }

        return response()->json([
            'message' => __('message.Logout out successfully'),
            'redirect' => route('admin.web.login'),
        ], 200);
    }
}
