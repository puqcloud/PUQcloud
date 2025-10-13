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

namespace App\Modules;

use Illuminate\Support\Facades\File;

class Module
{
    public string $log_level = 'error'; // 'error', 'info', 'debug'

    public array $module_data = [];

    public string $module_name = '';

    public string $module_path = '';

    public string $module_type = '';

    public array $config = [];

    public function __construct()
    {
        $this->module_name = class_basename(get_called_class());
        $this->module_path = base_path('modules').'/'.$this->module_type.'/'.$this->module_name.'/';
        $this->getConfig();
    }

    private function getConfig(): void
    {
        $configFilePath = $this->module_path.'/config.php';
        if (File::exists($configFilePath)) {
            $this->config = include $configFilePath;
        }
    }

    public function config($key)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        } else {
            return '';
        }
    }

    public function activate(): string
    {
        return 'success';
    }

    public function deactivate(): string
    {
        return 'success';
    }

    public function update(): string
    {
        return 'success';
    }

    public function adminPermissions(): array
    {
        return [];
    }

    public function adminSidebar(): array
    {
        return [];
    }

    public function adminWebRoutes(): array
    {
        return [];
    }

    public function adminApiRoutes(): array
    {
        return [];
    }

    public function clientWebRoutes(): array
    {
        return [];
    }

    public function clientApiRoutes(): array
    {
        return [];
    }

    public function scheduler(): array
    {
        return [];
    }

    public function queues(): array
    {
        return [];
    }

    public function logInfo(string $action, mixed $request = [], mixed $response = []): void
    {
        if ($this->log_level == 'info' or $this->log_level == 'debug') {
            logModule(
                $this->module_type,
                $this->module_name,
                $action,
                'info',
                $request,
                $response
            );
        }
    }

    public function logError(string $action, mixed $request = [], mixed $response = []): void
    {
        logModule(
            $this->module_type,
            $this->module_name,
            $action,
            'error',
            $request,
            $response
        );
    }

    public function logDebug(string $action, mixed $request = [], mixed $response = []): void
    {
        if ($this->log_level == 'debug') {
            logModule(
                $this->module_type,
                $this->module_name,
                $action,
                'debug',
                $request,
                $response
            );
        }
    }
}
