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

class ApiCheckAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (! in_array('*', config('app.client_allowed_ips'))) {
            if (! in_array($request->ip(), config('app.client_allowed_ips'))) {
                return redirect('https://puqcloud.com/');
            }
        }

        session(['locale' => config('locale.client.default')]);
        App::setLocale(config('locale.client.default'));

        if (! Auth::guard('client')->check()) {
            TranslationService::init('client');

            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Unauthorized')],
                'redirect' => route('client.web.home'),
            ], 401);

        }

        $user = Auth::guard('client')->user();
        app()->instance('user', $user);

        if (empty($user->language) or ! in_array($user->language, array_keys(config('locale.client.locales')))) {
            $user->language = config('locale.client.default');
            $user->save();
        }
        session(['locale' => $user->language]);
        App::setLocale($user->language);
        TranslationService::init('client');

        if ($user->disable) {
            Auth::guard('user')->logout();

            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Your account is disabled')],
                'redirect' => route('client.web.home'),
            ], 401);
        }

        return $next($request);
    }
}
