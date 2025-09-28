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

class WebMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $to_template['js_translations'] = trans('js');
        view()->composer('*', function ($view) use ($to_template) {
            $view->with($to_template);
        });

        return $next($request);
    }
}
