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

class Notification extends Module
{
    public array $module_data = [];

    public function __construct()
    {
        $this->module_type = 'Notification';
        parent::__construct();
    }

    public function getModuleData(array $data = []): array
    {
        return $data;
    }

    public function getSettingsPage(array $data = []): string
    {
        return __('message.No data to display');
    }

    public function saveModuleData(array $data = []): array
    {
        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    public function view(string $template, array $data = []): string
    {
        $templatePath = 'modules.Notification.'.$this->module_name.'.views.'.$template;

        if (view()->exists($templatePath)) {
            try {
                return view($templatePath, $data)->render();
            } catch (\Throwable $e) {
                return 'Error rendering template: '.$e->getMessage();
            }
        }

        return "Template '{$template}' not found.";
    }

    public function send(array $data = []): array
    {
        return ['status' => 'success'];
    }
}
