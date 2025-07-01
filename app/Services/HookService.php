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
        if (isset($this->hooks[$hookName])) {

            foreach ($this->hooks[$hookName] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    try {
                        $this->$hookName($callback, $params);
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
    }

    // hooks Admin -----------------------------------------------------------------------------------------------------
    private function AdminBeforeLogin($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function AdminAfterLogout($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function AdminFailedAuthorization($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function AdminBeforeLogout($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function AdminChangePermissions($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function PendingService($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function CreateService($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function CreateServiceError($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function CreateServiceSuccess($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function SuspendService($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function SuspendServiceError($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function SuspendServiceSuccess($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UnsuspendService($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UnsuspendServiceError($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UnsuspendServiceSuccess($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function TerminationService($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function TerminationServiceError($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function TerminationServiceSuccess($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function CancellationService($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function CancellationServiceError($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function CancellationServiceSuccess($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UserBeforeRegister($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UserAfterRegister($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UserBeforeLogin($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UserAfterLogin($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UserBeforeLogout($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UserAfterLogout($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UserFailedAuthorization($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function InvoiceCreated($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function ProformaInvoiceCreated($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    private function UserResetPassword($callback, $params): void
    {
        call_user_func_array($callback, $params);
    }

    // Output ----------------------------------------------------------------------------------------------------------
    private function AdminAreaFooterOutput($callback, $params): void
    {
        $AdminAreaFooterOutput = app('AdminAreaFooterOutput');
        app()->instance('AdminAreaFooterOutput', $AdminAreaFooterOutput.call_user_func_array($callback, $params));
    }

    public static function loadHooks(): void
    {
        self::loadNotificationHooks();
        self::loadAdminTemplateHooks();
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
