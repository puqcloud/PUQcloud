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

namespace App\Models;

use App\Traits\ConvertsTimezone;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class Module extends Model
{
    use ConvertsTimezone;

    protected $table = 'modules';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static $directories = ['Plugin', 'Notification', 'Product', 'Payment'];

    public $module;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        static::retrieved(function ($model) {
            $model->loadModuleInstance();
        });

        static::saving(function ($model) {
            unset($model->module_data);
        });
    }

    protected $fillable = [
        'name',
        'type',
        'version',
        'config',
        'status',
    ];

    public static function syncModulesWithDatabase(): void
    {
        $basePath = base_path('modules');
        foreach (self::$directories as $type) {
            $typePath = $basePath.'/'.$type;
            if (!is_dir($typePath)) {
                continue;
            }

            $folders = scandir($typePath);
            foreach ($folders as $folder) {
                if ($folder === '.' || $folder === '..') {
                    continue;
                }

                $configPath = $typePath.'/'.$folder.'/'.'config.php';

                if (!file_exists($configPath)) {
                    continue;
                }

                $config = include $configPath;
                $version = $config['version'] ?? 'unknown';

                if (!self::where('name', $folder)->where('type', $type)->exists()) {
                    self::create([
                        'name' => $folder,
                        'type' => $type,
                        'version' => $version,
                        'status' => 'inactive',
                    ]);
                }
            }
        }
    }

    public static function all($columns = ['*']): Collection
    {
        self::syncModulesWithDatabase();

        return parent::all($columns);
    }

    public static function get($columns = ['*']): Collection
    {
        self::syncModulesWithDatabase();

        return parent::get($columns);
    }

    public function loadModuleInstance(): void
    {
        $this->module_data = [];
        $classFilePath = base_path('modules').'/'.$this->type.'/'.$this->name.'/'.$this->name.'.php';
        if (file_exists($classFilePath)) {
            try {
                require_once $classFilePath;
                $className = $this->name;

                if (class_exists($className)) {
                    $this->module = new $className;
                    $config = [
                        'name' => $this->module->config('name'),
                        'description' => $this->module->config('description'),
                        'version' => $this->module->config('version'),
                        'author' => $this->module->config('author'),
                        'email' => $this->module->config('email'),
                        'website' => $this->module->config('website'),
                        'logo' => $this->loadLogo($this->module->config('logo')),
                        'icon' => $this->module->config('icon'),
                    ];
                    $this->module_data = array_merge($this->module_data, $config);

                    if ($this->status != 'inactive' and $this->version !== $this->module->config('version')) {
                        $this->status = 'restricted';
                    }

                } else {
                    Log::warning("Class '$className' not found in '$classFilePath'.");
                    $this->status = 'error';
                }
            } catch (Throwable $e) {

                Log::error("Failed to read instance for module '{$this->name}': ".$e->getMessage());
                $this->status = 'error';
            }
        } else {
            Log::warning("Class file '$classFilePath' not found for module '{$this->name}'.");
            $this->status = 'error';
        }
    }

    public function loadLogo($logoPath): string
    {
        if (file_exists($logoPath)) {
            $logoContent = file_get_contents($logoPath);
            if ($logoContent !== false) {
                $logoBase64 = base64_encode($logoContent);
                $mimeType = mime_content_type($logoPath);

                return 'data:'.$mimeType.';base64,'.$logoBase64;
            }
        }

        return '';
    }

    public function moduleActivate(): string
    {
        if (empty($this->module)) {
            logActivity('error', 'Module '.$this->name.' activate Error: Failed to create module object', 'activate');

            return 'Failed to create module object';
        }
        if ($this->status == 'active') {
            return 'success';
        }
        try {
            $activate = $this->module->activate();
            if ($activate != 'success') {
                logActivity('error', 'Module '.$this->name.' activate Error: '.$activate, 'activate');

                return $activate;
            }
        } catch (Exception $e) {
            logActivity('error', 'Module '.$this->name.' activate Error: '.$e->getMessage(), 'activate');

            return $e->getMessage();
        }

        $this->status = 'active';
        $this->save();
        logActivity('info', 'Module '.$this->name.' activate Successfully', 'activate');

        return 'success';
    }

    public function moduleDeactivate(): string
    {
        if (empty($this->module)) {
            logActivity('error', 'Module '.$this->name.' deactivate Error: Failed to create module object',
                'deactivate');

            return 'Failed to create module object';
        }

        if ($this->status == 'inactive') {
            return 'success';
        }
        try {
            $deactivate = $this->module->deactivate();
            if ($deactivate != 'success') {
                logActivity('error', 'Module '.$this->name.' deactivate Error: '.$deactivate, 'deactivate');

                return $deactivate;
            }
        } catch (Exception $e) {
            logActivity('error', 'Module '.$this->name.' deactivate Error: '.$e->getMessage(), 'deactivate');

            return $e->getMessage();
        }

        $this->status = 'inactive';
        $this->save();
        logActivity('info', 'Module '.$this->name.' deactivated Successfully', 'deactivate');

        return 'success';
    }

    public function moduleUpdate(): string
    {
        if (empty($this->module)) {
            logActivity('error', 'Module '.$this->name.' deactivate Error: Failed to create module object', 'update');

            return 'Failed to create module object';
        }

        if ($this->status == 'active') {
            return 'success';
        }
        try {
            $update = $this->module->update();
            if ($update != 'success') {
                logActivity('error', 'Module '.$this->name.' update Error: '.$update, 'update');

                return $update;
            }
        } catch (Exception $e) {
            logActivity('error', 'Module '.$this->name.' update Error: '.$e->getMessage(), 'update');

            return $e->getMessage();
        }

        $this->status = 'active';
        $this->version = $this->module->config('version');
        $this->save();
        logActivity('info', 'Module '.$this->name.' updated Successfully to version '.$this->version, 'update');

        return 'success';
    }

    public function modulePermissions(): array
    {
        if ($this->status != 'active') {
            return [];
        }

        if (empty($this->module)) {
            return [];
        }

        $permissions = $this->module->adminPermissions();
        if (empty($permissions) or !is_array($permissions)) {
            return [];
        }

        return $permissions;
    }

    public function moduleSidebar(): array
    {
        if ($this->status != 'active') {
            return [];
        }

        if (empty($this->module)) {
            return [];
        }

        $sidebar = $this->module->adminSidebar();
        if (empty($sidebar) or !is_array($sidebar)) {
            return [];
        }

        return $sidebar;
    }

    public function moduleWebRoutes(): array
    {
        if ($this->status != 'active') {
            return [];
        }

        if (empty($this->module)) {
            return [];
        }

        $routes = $this->module->adminWebRoutes();
        if (empty($routes) or !is_array($routes)) {
            return [];
        }

        return $routes;
    }

    public function moduleApiRoutes(): array
    {
        if ($this->status != 'active') {
            return [];
        }

        if (empty($this->module)) {
            return [];
        }

        $routes = $this->module->adminApiRoutes();
        if (empty($routes) or !is_array($routes)) {
            return [];
        }

        return $routes;
    }

    public function moduleSchedulers(): array
    {
        if ($this->status != 'active') {
            return [];
        }

        if (empty($this->module)) {
            return [];
        }

        $sidebar = $this->module->scheduler();
        if (empty($sidebar) or !is_array($sidebar)) {
            return [];
        }

        return $sidebar;
    }

    public function moduleExecute(string $methodName, ...$parameters): JsonResponse|array
    {
        if (empty($this->module)) {
            return [
                'status' => 'error',
                'message' => 'Failed to create module object',
                'errors' => ['Failed to create module object'],
            ];
        }

        if ($this->status != 'active') {
            return [
                'status' => 'error',
                'message' => 'The module is not in active status. Status: '.$this->status,
                'errors' => ['The module is not in active status. Status: '.$this->status],
            ];
        }

        if (!method_exists($this->module, $methodName)) {
            return [
                'status' => 'error',
                'message' => "Method '{$methodName}' does not exist",
                'errors' => ["Method '{$methodName}' does not exist"],
            ];
        }

        try {
            $data = $this->module->{$methodName}(...$parameters);


            if ($data instanceof JsonResponse) {
                return $data;
            }

            if (isset($data['status'])) {
                return $data;
            }

            return [
                'status' => 'success',
                'data' => $data,
            ];

        } catch (Throwable $e) {
            Log::error("Error executing method '{$methodName}': {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'message' => "Error executing method '{$methodName}': {$e->getMessage()}",
                'errors' => ["Error executing method '{$methodName}': {$e->getMessage()}"],
            ];
        }
    }
}
