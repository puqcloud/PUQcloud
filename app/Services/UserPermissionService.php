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

namespace App\Services;

use Illuminate\Support\Facades\Config;

class UserPermissionService
{
    public static function system(): array
    {
        $permission = [];
        foreach (Config::get('userPermissions') as $value) {
            $permission[] = [
                'key' => $value['key'],
                'name' => __('main.'.$value['name']),
                'description' => __('main.'.$value['description']),
            ];
        }

        return $permission;
    }

    public static function all(): array
    {
        $merged = array_merge(self::system());
        $uniqueItems = [];
        foreach ($merged as $item) {
            $uniqueItems[$item['key']] = $item;
        }

        return array_values($uniqueItems);
    }

    public static function allKey(): array
    {
        $merged = array_merge(self::system());

        return array_column($merged, 'key');
    }

    public static function findByKey($key)
    {
        $permissions = self::all();
        foreach ($permissions as $permission) {
            if ($permission['key'] === $key) {
                return $permission;
            }
        }

        return null;
    }

    public static function exists($key): bool
    {
        return self::findByKey($key) !== null;
    }

    public static function groupHasPermission($group, $permissionKey): bool
    {
        return in_array($permissionKey, $group->permissions());
    }

    public static function userHasPermission($user, $permissionKey): bool
    {
        foreach ($user->groups as $group) {
            if (self::groupHasPermission($group, $permissionKey)) {
                return true;
            }
        }

        return false;
    }
}
