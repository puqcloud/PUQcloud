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

use App\Services\TranslationService;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class WebLoginMiddleware
{
    public function handle($request, Closure $next)
    {
        if (! in_array('*', config('app.admin_allowed_ips'))) {
            if (! in_array($request->ip(), config('app.admin_allowed_ips'))) {
                return redirect('https://puqcloud.com/');
            }
        }

        if (Auth::guard('admin')->check()) {
            return redirect(route('admin.web.dashboard'));
        }

        session(['locale' => config('locale.admin.default')]);
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');

        $to_template['js_translations'] = trans('js');
        view()->composer('*', function ($view) use ($to_template) {
            $view->with($to_template);
        });

        return $next($request);
    }
}
