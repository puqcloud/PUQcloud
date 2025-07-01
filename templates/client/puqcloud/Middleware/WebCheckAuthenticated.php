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

class WebCheckAuthenticated
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

        $locale = session('locale', config('locale.client.default'));
        App::setLocale($locale);

        $user = null;
        $to_template = [];
        if (Auth::guard('client')->check()) {

            $user = Auth::guard('client')->user();
            app()->instance('user', $user);

            if (empty($user->language) or ! in_array($user->language, array_keys(config('locale.client.locales')))) {
                $user->language = config('locale.client.default');
                $user->save();
            }
            session(['locale' => $user->language]);
            App::setLocale($user->language);

            if ($user->disable) {
                Auth::guard('client')->logout();

                return redirect(route('client.web.home'));
            }
        }

        TranslationService::init('client');

        $to_template['user'] = $user;
        view()->composer('*', function ($view) use ($to_template) {
            $view->with($to_template);
        });

        return $next($request);
    }
}
