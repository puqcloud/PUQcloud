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

use App\Models\ActivityLog;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;

trait ModelActivityLogger
{
    public static $enableLogging = true;

    public static function bootModelActivityLogger()
    {

        static::created(function ($model) {
            if (static::$enableLogging) {
                $data = self::getInitializer();
                $data['model_type'] = get_class($model);
                $data['model_uuid'] = $model->uuid;
                $data['model_new_data'] = json_encode($model->toArray());
                $data['level'] = 'notice';
                $data['action'] = 'create';
                $data['description'] = $data['model_type'].':'.$data['model_uuid'];
                ActivityLog::create($data);
            }
        });

        static::updated(function ($model) {
            if (static::$enableLogging) {
                $data = self::getInitializer();
                $data['model_type'] = get_class($model);
                $data['model_uuid'] = $model->uuid;
                $data['model_old_data'] = json_encode($model->getOriginal());
                $data['model_new_data'] = json_encode($model->getChanges());
                $data['level'] = 'info';
                $data['action'] = 'update';
                $data['description'] = $data['model_type'].':'.$data['model_uuid'];
                ActivityLog::create($data);
            }
        });

        static::deleted(function ($model) {
            if (static::$enableLogging) {
                $data = self::getInitializer();
                $data['model_type'] = get_class($model);
                $data['model_uuid'] = $model->uuid;
                $data['model_old_data'] = json_encode($model->toArray());
                $data['level'] = 'warning';
                $data['action'] = 'delete';
                $data['description'] = $data['model_type'].':'.$data['model_uuid'];
                ActivityLog::create($data);
            }
        });
    }

    protected static function getInitializer(): array
    {
        $initializer = [
            'admin_uuid' => null,
            'user_uuid' => null,
            'client_uuid' => null,
            'ip_address' => null,
        ];
        $admin = Auth::guard('admin')->user();
        $user = Auth::guard('client')->user();

        if ($admin) {
            $initializer['admin_uuid'] = $admin->uuid;
            $initializer['ip_address'] = request()->ip();
        }
        if ($user) {
            $initializer['user_uuid'] = $user->uuid;
            $initializer['ip_address'] = request()->ip();
        }

        if (session()->has('client_uuid')) {
            $uuid = session('client_uuid');
            if (Client::where('uuid', $uuid)->exists()) {
                $initializer['client_uuid'] = $uuid;
            }
        }

        return $initializer;
    }
}
