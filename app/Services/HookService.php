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

use App\Models\NotificationSender;
use Illuminate\Support\Facades\Log;

class HookService
{
    protected array $hooks = [];

    public function addHook(string $hookName, callable $callback, int $priority = 10): void
    {
        try {
            $this->hooks[$hookName][$priority][] = $callback;
            krsort($this->hooks[$hookName]);
        } catch (\Throwable $e) {
            logModule(
                'system_hooks',
                $hookName,
                'addHook',
                'error',
                '',
                $e->getMessage()
            );
            Log::error('Stack Trace: '.$e->getTraceAsString());
        }
    }

    public function callHooks(string $hookName, ...$params): void
    {
        if (! isset($this->hooks[$hookName])) {
            return;
        }

        foreach ($this->hooks[$hookName] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    // Special hooks (e.g. Output accumulators) define their own dispatcher.
                    // Plain pass-through hooks just invoke the callback directly.
                    if (method_exists($this, $hookName)) {
                        $this->$hookName($callback, $params);
                    } else {
                        call_user_func_array($callback, $params);
                    }
                } catch (\Throwable $e) {
                    logModule(
                        'system_hooks',
                        $hookName,
                        'callHooks',
                        'error',
                        '',
                        $e->getMessage()
                    );
                    Log::error('Stack Trace: '.$e->getTraceAsString());
                }
            }
        }
    }

    // Output ----------------------------------------------------------------------------------------------------------
    private function AdminAreaHeadOutput($callback, $params): void
    {
        $output = app('AdminAreaHeadOutput');
        app()->instance('AdminAreaHeadOutput', $output.call_user_func_array($callback, $params));
    }

    private function AdminAreaHeaderOutput($callback, $params): void
    {
        $output = app('AdminAreaHeaderOutput');
        app()->instance('AdminAreaHeaderOutput', $output.call_user_func_array($callback, $params));
    }

    private function AdminAreaFooterOutput($callback, $params): void
    {
        $AdminAreaFooterOutput = app('AdminAreaFooterOutput');
        app()->instance('AdminAreaFooterOutput', $AdminAreaFooterOutput.call_user_func_array($callback, $params));
    }

    private function ClientAreaHeadOutput($callback, $params): void
    {
        $output = app('ClientAreaHeadOutput');
        app()->instance('ClientAreaHeadOutput', $output.call_user_func_array($callback, $params));
    }

    private function ClientAreaHeaderOutput($callback, $params): void
    {
        $output = app('ClientAreaHeaderOutput');
        app()->instance('ClientAreaHeaderOutput', $output.call_user_func_array($callback, $params));
    }

    private function ClientAreaFooterOutput($callback, $params): void
    {
        $output = app('ClientAreaFooterOutput');
        app()->instance('ClientAreaFooterOutput', $output.call_user_func_array($callback, $params));
    }

    public static function loadHooks(): void
    {
        self::loadNotificationHooks();
        self::loadAdminTemplateHooks();
        self::loadClientTemplateHooks();
        self::loadModulesHooks();
    }

    protected static function loadNotificationHooks()
    {
        try {
            NotificationService::hooks();
        } catch (\Exception $e) {
            logModule(
                'system_hooks',
                '',
                'loadNotificationHooks',
                'error',
                '',
                $e->getMessage()
            );
            Log::error('Error loading hooks: '.$e->getMessage());
        }
    }

    protected static function loadAdminTemplateHooks(): void
    {
        $filePath = config('template.admin.base_path').'/hooks.php';

        if (file_exists($filePath)) {
            try {
                require_once $filePath;
            } catch (\Exception $e) {
                logModule(
                    'system_hooks',
                    '',
                    'loadAdminTemplateHooks',
                    'error',
                    '',
                    $e->getMessage()
                );

                Log::error('Error loading hooks: '.$e->getMessage());
            }
        }
    }

    protected static function loadClientTemplateHooks(): void
    {
        $filePath = config('template.client.base_path').'/hooks.php';

        if (file_exists($filePath)) {
            try {
                require_once $filePath;
            } catch (\Exception $e) {
                logModule(
                    'system_hooks',
                    '',
                    'loadClientTemplateHooks',
                    'error',
                    '',
                    $e->getMessage()
                );

                Log::error('Error loading hooks: '.$e->getMessage());
            }
        }
    }

    protected static function loadModulesNotificationHooks(): void
    {
        try {
            $notification_senders = NotificationSender::all();
        } catch (\Exception $e) {
            Log::error('Error get NotificationSender: '.$e->getMessage());

            return;
        }

        foreach ($notification_senders as $notification_sender) {
            $filePath = base_path('modules/Notification/'.$notification_sender->module.'/hooks.php');
            if (file_exists($filePath)) {
                try {
                    require_once $filePath;
                } catch (\Exception $e) {
                    logModule(
                        'system_hooks',
                        '',
                        'loadModulesNotificationHooks',
                        'error',
                        '',
                        $e->getMessage()
                    );
                    Log::error('Error loading hooks: '.$e->getMessage());
                }
            }
        }
    }

    protected static function loadModulesHooks(): void
    {
        $modules = app('Modules');
        foreach ($modules as $module) {
            if ($module->status != 'active') {
                continue;
            }
            $filePath = base_path('modules/'.$module->type.'/'.$module->name.'/hooks.php');
            if (file_exists($filePath)) {
                try {
                    require_once $filePath;
                } catch (\Exception $e) {
                    logModule(
                        'system_hooks',
                        $module->name,
                        'loadModuleHooks',
                        'error',
                        '',
                        $e->getMessage()
                    );
                    Log::error('Error loading hooks: '.$e->getMessage());
                }
            }
        }
    }
}
