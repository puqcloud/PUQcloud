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

namespace App\Http\Middleware\Web;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebCheckPermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permissionKey): Response
    {
        $admin = app('admin');

        if ($admin->hasPermission($permissionKey)) {
            return $next($request);
        }

        $title = __('main.Permission Denied');

        return response()->view(config('template.admin.view').'.errors.403', compact('title'));
    }
}
