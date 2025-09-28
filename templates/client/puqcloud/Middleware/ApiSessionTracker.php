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

use App\Models\ClientSessionLog;
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
        $user = app('user');
        $client = app('client');

        if (session()->get('login_as_client_owner') == $client->uuid) {
            return $next($request);
        }

        $ipAddress = $request->ip();
        $sessionId = $request->session()->getId();
        $userAgent = $request->header('User-Agent');
        $user->updateIpAddress($ipAddress);

        $log = new ClientSessionLog([
            'user_uuid' => $user->uuid,
            'client_uuid' => $client->uuid,
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
