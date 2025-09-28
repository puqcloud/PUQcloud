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

use App\Models\AdminSessionLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiSessionTracker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $ipAddress = $request->ip();
        $sessionId = $request->session()->getId();
        $userAgent = $request->header('User-Agent');

        $admin = app('admin');
        $admin->updateIpAddress($ipAddress);

        $log = new AdminSessionLog([
            'admin_uuid' => $admin->uuid,
            'ip_address' => $ipAddress,
            'session_id' => $sessionId,
            'action' => 'api',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $userAgent,
        ]);

        $log->save();
        unset($log);

        return $next($request);
    }
}
