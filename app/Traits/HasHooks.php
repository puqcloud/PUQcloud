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

namespace App\Traits;

use App\Services\HookService;

trait HasHooks
{
    public static function bootHasHooks()
    {
        static::creating(function ($model) {
            app(HookService::class)->callHooks('model.creating:'.get_class($model), $model);
        });

        static::created(function ($model) {
            app(HookService::class)->callHooks('model.created:'.get_class($model), $model);
        });
    }
}
