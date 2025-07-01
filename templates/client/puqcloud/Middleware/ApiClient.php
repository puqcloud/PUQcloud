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

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = app('user');

        $client = null;

        if (session()->has('client_uuid')) {
            $client = $user->clients()->where('uuid', session('client_uuid'))->first();
        }

        if ($user->clients()->count() == 0) {
            $user->createFirstClient();
        }

        if (! $client) {
            $client = $user->ownedClients()->first();
            if (! $client) {
                $client = $user->clients()->first();
            }

            if ($client) {
                session()->put('client_uuid', $client->uuid);
            } else {
                return response()->json([
                    'status' => 'error',
                    'errors' => [__('error.No client found for this user')],
                ], 403);
            }
        }

        app()->instance('client', $client);

        return $next($request);
    }
}
