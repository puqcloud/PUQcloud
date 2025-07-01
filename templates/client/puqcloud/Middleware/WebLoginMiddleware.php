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
use Illuminate\Support\Facades\Auth;
use LoginLayoutOptions;

class WebLoginMiddleware
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('client')->check()) {
            return redirect(route('client.web.panel.dashboard'));
        }

        $to_template['login_layout_options'] = new LoginLayoutOptions;

        view()->composer('*', function ($view) use ($to_template) {
            $view->with($to_template);
        });

        return $next($request);
    }
}
