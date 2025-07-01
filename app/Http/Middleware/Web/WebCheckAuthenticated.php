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

use App\Services\AdminPermissionService;
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
        if (! in_array('*', config('app.admin_allowed_ips'))) {
            if (! in_array($request->ip(), config('app.admin_allowed_ips'))) {
                return redirect('https://puqcloud.com/');
            }
        }

        if (! Auth::guard('admin')->check()) {
            return redirect(route('admin.web.login'));
        }

        $to_template = [];
        session(['locale' => config('locale.admin.default')]);
        App::setLocale(config('locale.admin.default'));

        $admin = Auth::guard('admin')->user();
        app()->instance('admin', $admin);

        if (empty($admin->language) or ! in_array($admin->language, array_keys(config('locale.admin.locales')))) {
            $admin->language = config('locale.admin.default');
            $admin->save();
        }

        session(['locale' => $admin->language]);
        App::setLocale($admin->language);

        $to_template['admin'] = $admin;

        if ($admin->disable) {
            Auth::guard('admin')->logout();

            return redirect(route('admin.web.login'));
        }

        TranslationService::init('admin');

        $to_template['js_translations'] = trans('js');
        view()->composer('*', function ($view) use ($to_template) {
            $view->with($to_template);
        });

        // boot AdminPermission
        $AdminPermission = new AdminPermissionService;
        app()->instance('AdminPermission', $AdminPermission);

        return $next($request);
    }
}
