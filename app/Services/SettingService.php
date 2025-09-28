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

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingService
{
    protected const CACHE_TTL = 60 * 60;

    public static function get(string $key, string $group = 'settings'): mixed
    {
        // Try to get default from config, using the group
        $default = config("{$group}.{$key}.default", '');

        // Build cache key including the group
        $cacheKey = "{$group}.{$key}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group, $key, $default) {
            $dbKey = "{$group}.{$key}";
            $parameter = DB::table('settings')
                ->where('key', $dbKey)
                ->first();

            return $parameter ? $parameter->value : $default;
        });
    }

    public static function set(string $key, $value, string $group = 'settings'): void
    {

        $dbKey = "{$group}.{$key}";
        $cacheKey = $dbKey;

        $existing = DB::table('settings')->where('key', $dbKey)->first();

        if ($existing) {
            DB::table('settings')
                ->where('key', $dbKey)
                ->update(['value' => $value, 'updated_at' => now()]);
        } else {
            DB::table('settings')
                ->insert([
                    'key' => $dbKey,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        // Update cache
        Cache::put($cacheKey, $value, self::CACHE_TTL);
    }


    private static function isValidKey($key): bool
    {
        if (str_contains($key, '.')) {
            return (bool) config('settings.'.$key);
        }

        return true;
    }

    public static function getValuesByGroup($group): array
    {
        $general_settings = [];
        foreach (config('settings') as $key => $settings) {
            if ($key == $group) {
                foreach ($settings as $name => $setting) {
                    $general_settings[$name] = self::get($key.'.'.$name) ?? $setting['default'];
                }
            }
        }

        return $general_settings;
    }

    public static function getSettings(): array
    {
        $general_settings = [];
        foreach (config('settings') as $key => $settings) {
            foreach ($settings as $name => $setting) {
                $setting['label'] = __('main.'.$setting['label']);
                $setting['description'] = __('main.'.$setting['description']);
                if (empty($setting['class'])) {
                    $setting['class'] = 'col-12 col-md-6 col-lg-4';
                }
                if ($setting['type'] == 'select') {
                    foreach ($setting['options'] as $option_key => $value) {
                        $setting['options'][$option_key] = __('main.'.$value);
                    }
                }
                $general_settings[$key][$name] = $setting;
            }
        }

        return $general_settings;
    }
}
