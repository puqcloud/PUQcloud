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
use Illuminate\Support\Str;

class AdminPermissionService
{
    private array $systemPermissions;

    private array $adminTemplatePermissions;

    private array $clientTemplatePermissions;

    private array $adminModulesPermissions;

    public function __construct()
    {
        $this->systemPermissions = $this->loadSystemPermissions();
        $this->adminTemplatePermissions = $this->loadAdminTemplatePermissions();
        $this->clientTemplatePermissions = $this->loadClientTemplatePermissions();
        $this->adminModulesPermissions = $this->loadModulesPermissions();
    }

    private function loadSystemPermissions(): array
    {
        $permissions = [];
        foreach (Config::get('adminPermissions') as $value) {
            $permissions[] = [
                'key' => $value['key'],
                'name' => __('main.'.$value['name']),
                'description' => __('main.'.$value['description']),
                'key_group' => __('main.'.$value['group']),
            ];
        }

        return $permissions;
    }

    private function loadAdminTemplatePermissions(): array
    {
        $permissions = [];
        $filePath = config('template.admin.base_path').'/permissions.php';

        if (file_exists($filePath)) {
            $permissionDefinitions = include $filePath;

            if (is_array($permissionDefinitions)) {
                foreach ($permissionDefinitions as $handler) {
                    if (empty($handler['key']) ||
                        empty($handler['name']) ||
                        empty($handler['description'])) {
                        continue;
                    }

                    $permissions[] = [
                        'key' => 'adminTemplate-'.$handler['key'],
                        'name' => $handler['name'],
                        'description' => $handler['description'],
                        'key_group' => __('main.permission_adminTemplate'),
                    ];
                }
            }
        }

        return $permissions;
    }

    private function loadClientTemplatePermissions(): array
    {
        $permissions = [];
        $filePath = config('template.client.base_path').'/config/adminPermissions.php';

        if (file_exists($filePath)) {
            $permissionDefinitions = include $filePath;

            if (is_array($permissionDefinitions)) {
                foreach ($permissionDefinitions as $handler) {
                    if (empty($handler['key']) ||
                        empty($handler['name']) ||
                        empty($handler['description'])) {
                        continue;
                    }

                    $permissions[] = [
                        'key' => 'clientTemplate-'.$handler['key'],
                        'name' => $handler['name'],
                        'description' => $handler['description'],
                        'key_group' => __('main.permission_clientTemplate'),
                    ];
                }
            }
        }

        return $permissions;
    }

    private function loadModulesPermissions(): array
    {
        $permissions = [];

        $modules = app('Modules');

        foreach ($modules as $module) {
            $module_permissions = $module->modulePermissions();
            foreach ($module_permissions as $module_permission) {
                if (empty($module_permission) ||
                    empty($module_permission['key']) ||
                    empty($module_permission['name']) ||
                    empty($module_permission['description'])) {
                    continue;
                }
                $module_name = ! empty($module->module_data['name']) ? $module->module_data['name'] : $module->name;

                $prefix = $module->type.'.'.$module->name.'.';
                $name = Str::replaceFirst($prefix, '', __($prefix.$module_permission['name']));
                $description = Str::replaceFirst($prefix, '', __($prefix.$module_permission['description']));
                $module_name = Str::replaceFirst($prefix, '', __($prefix.$module_name));

                $permissions[] = [
                    'key' => $module->type.'-'.$module->name.'-'.$module_permission['key'],
                    'name' => $name,
                    'description' => $description,
                    'key_group' => $module->type.' / '.$module_name,
                ];
            }
        }

        return $permissions;
    }

    public function getSystemPermissions(): array
    {
        return $this->systemPermissions;
    }

    public function getAdminTemplatePermissions(): array
    {
        return $this->adminTemplatePermissions;
    }

    public function getClientTemplatePermissions(): array
    {
        return $this->clientTemplatePermissions;
    }

    public function getModulesPermissions(): array
    {
        return $this->adminModulesPermissions;
    }

    public function getAllPermissions(): array
    {
        $merged = array_merge($this->systemPermissions, $this->adminTemplatePermissions, $this->clientTemplatePermissions, $this->adminModulesPermissions);
        $uniqueItems = [];

        foreach ($merged as $item) {
            $uniqueItems[$item['key']] = $item;
        }

        return array_values($uniqueItems);
    }

    public function findPermissionByKey(string $key): ?array
    {
        foreach ($this->getAllPermissions() as $permission) {
            if ($permission['key'] === $key) {
                return $permission;
            }
        }

        return null;
    }

    public function permissionExists(string $key): bool
    {
        return $this->findPermissionByKey($key) !== null;
    }

    public function groupHasPermission($group, string $permissionKey): bool
    {
        return in_array($permissionKey, $group->permissions());
    }

    public function userHasPermission($user, string $permissionKey): bool
    {
        foreach ($user->groups as $group) {
            if ($this->groupHasPermission($group, $permissionKey)) {
                return true;
            }
        }

        return false;
    }
}
