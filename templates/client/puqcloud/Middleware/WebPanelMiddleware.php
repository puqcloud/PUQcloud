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
use Illuminate\Support\Facades\Auth;
use LayoutOptions;
use Symfony\Component\HttpFoundation\Response;
use Template\Client\Services\NavigationService;

class WebPanelMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('client')->check()) {
            return redirect(route('client.web.home'));
        }

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
                return response('No client found for this user', 403);
            }
        }

        app()->instance('client', $client);

        $to_template['client'] = $client;
        $to_template['navigation'] = new NavigationService;
        $to_template['layout_options'] = new LayoutOptions;

        view()->composer('*', function ($view) use ($to_template) {
            $view->with($to_template);
        });

        return $next($request);
    }
}
