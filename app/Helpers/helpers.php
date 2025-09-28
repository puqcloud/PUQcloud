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

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

if (! function_exists('asset_admin')) {

    function asset_admin($path, $secure = null): string
    {
        return asset(config('template.admin.name').'/'.$path, $secure);
    }
}

if (! function_exists('view_admin')) {

    function view_admin(
        $view,
        $data = [],
        $mergeData = []
    ): \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\View {
        return view(config('template.admin.view').'.'.$view, $data, $mergeData);
    }
}

if (! function_exists('view_admin_module')) {

    function view_admin_module(
        $type,
        $name,
        $view,
        $data = [],
        $mergeData = []
    ): \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\View {
        return view('modules'.'.'.$type.'.'.$name.'.views.'.$view, $data, $mergeData);
    }
}

if (! function_exists('asset_client')) {

    function asset_client($path, $secure = null): string
    {
        return asset(config('template.client.name').'/'.$path, $secure);
    }
}

if (! function_exists('view_client')) {

    function view_client(
        $view,
        $data = [],
        $mergeData = []
    ): \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\View {
        return view(config('template.client.view').'.'.$view, $data, $mergeData);
    }
}

if (! function_exists('get_gravatar')) {
    function get_gravatar($email, $size = 80): string
    {
        $default = 'https://www.gravatar.com/avatar/'.md5(strtolower(trim('support@puqcloud.com')));
        $email = md5(strtolower(trim($email)));

        return "https://www.gravatar.com/avatar/$email?s=$size&d=$default";
    }
}

if (! function_exists('setting')) {
    function setting($key)
    {
        return App\Services\SettingService::get($key);
    }
}

if (! function_exists('logModule')) {
    /**
     * Log module call.
     *
     * @param  string  $type  The type of the module
     * @param  string  $name  The name of the module
     * @param  string  $action  The name of the action being performed
     * @param  string  $level  The log level: info, error, debug
     * @param  mixed  $request  The input parameters for the API call (array, object, string, etc.)
     * @param  mixed  $response  The response data from the API call (array, object, string, etc.)
     */
    function logModule(
        string $type,
        string $name,
        string $action,
        string $level,
        mixed $request = [],
        mixed $response = []
    ): void {
        $module_log = new App\Models\ModuleLog;
        $module_log->type = $type;
        $module_log->name = $name;
        $module_log->action = $action;
        $module_log->level = $level;

        $module_log->request = safeJson($request);
        $module_log->response = safeJson($response);

        $module_log->save();
    }

    function safeJson($data): string
    {
        try {
            $clean = cleanData($data);

            return json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        } catch (\Throwable $e) {
            return json_encode(['error' => 'Failed to encode data: '.$e->getMessage()]);
        }
    }

    function cleanData($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = cleanData($value);
            }

            return $data;
        }

        if ($data instanceof \Closure) {
            return '[Closure]';
        }

        if (is_object($data)) {
            if ($data instanceof \JsonSerializable) {
                return $data->jsonSerialize();
            }

            if (method_exists($data, '__toString')) {
                return (string) $data;
            }

            return '[Object '.get_class($data).']';
        }

        return $data;
    }

}

if (! function_exists('logActivity')) {
    /**
     * Log activity in the system.
     *
     * This function creates a new activity log entry with the specified details.
     * It captures information about the event, including the log level, message,
     * action taken, and related user and client information.
     *
     * @param  string  $level  The severity level of the log (e.g., 'info', 'warning', 'error').
     * @param  string  $message  A descriptive message detailing the activity being logged.
     * @param  string  $action  The specific action that triggered the log entry (e.g., 'create', 'update', 'delete').
     * @param  string  $admin_uuid  The unique identifier of the administrator performing the action.
     * @param  string  $user_uuid  The unique identifier of the user associated with the action.
     * @param  string  $client_uuid  The unique identifier of the client involved in the action.
     * @param  string  $ip_address  The IP address from which the action was performed.
     * @return void This function does not return a value. It only saves the log entry to the database.
     */
    function logActivity(
        string $level,
        string $message,
        string $action,
        ?string $ip_address = null,
        ?string $admin_uuid = null,
        ?string $user_uuid = null,
        ?string $client_uuid = null
    ): void {
        $activity_log = new App\Models\ActivityLog;
        $activity_log->level = $level;
        $activity_log->description = $message;
        $activity_log->action = $action;
        $activity_log->ip_address = $ip_address;
        $activity_log->admin_uuid = $admin_uuid;
        $activity_log->user_uuid = $user_uuid;
        $activity_log->client_uuid = $client_uuid;

        $admin = Illuminate\Support\Facades\Auth::guard('admin')->user();

        if (! empty($admin->uuid)) {
            $activity_log->admin_uuid = $admin->uuid;
            $activity_log->ip_address = request()->ip();
        }

        $activity_log->save();
    }
}

if (! function_exists('add_hook')) {
    function add_hook(string $hookName, int $priority, callable $callback): void
    {
        app(App\Services\HookService::class)->addHook($hookName, $callback, $priority);
    }
}

if (! function_exists('loadAdminModulesDynamicRoutes')) {
    function loadAdminModulesDynamicRoutes(): void
    {
        $modules = app('Modules');
        foreach ($modules as $module) {
            if ($module->status != 'active') {
                continue;
            }
            $routes = $module->moduleWebRoutes();

            foreach ($routes as $route) {
                try {
                    if (! isset($route['method'], $route['uri'], $route['name'], $route['controller'])) {
                        continue;
                    }

                    [$controller, $method] = explode('@', $route['controller']);

                    $controllerPath = base_path("modules/{$module->type}/{$module->name}/Controllers/{$controller}.php");

                    require_once $controllerPath;
                    $class = str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        substr($controllerPath, strpos($controllerPath, 'modules'))
                    );

                    if (! file_exists($controllerPath)) {
                        Illuminate\Support\Facades\Log::error('Controller file does not exist: '.$controllerPath);

                        continue;
                    }

                    if (! class_exists($class)) {
                        throw new Exception('Class not found: '.$class);
                    }

                    if (! method_exists($class, $method)) {
                        throw new Exception("Method '$method' not found in class: ".$class);
                    }

                    switch (strtoupper($route['method'])) {
                        case 'GET':
                            Illuminate\Support\Facades\Route::get(
                                $module->type.'/'.$module->name.'/'.$route['uri'],
                                [$class, $method]
                            )
                                ->name($module->type.'.'.$module->name.'.'.$route['name'])
                                ->middleware('WebPermission:'.$module->type.'-'.$module->name.'-'.$route['permission']);
                            break;

                        case 'POST':
                            Illuminate\Support\Facades\Route::post(
                                $module->type.'/'.$module->name.'/'.$route['uri'],
                                [$class, $method]
                            )
                                ->name($module->type.'.'.$module->name.'.'.$route['name'])
                                ->middleware('WebPermission:'.$module->type.'-'.$module->name.'-'.$route['permission']);
                            break;

                        case 'PUT':
                            Illuminate\Support\Facades\Route::put(
                                $module->type.'/'.$module->name.'/'.$route['uri'],
                                [$class, $method]
                            )
                                ->name($module->type.'.'.$module->name.'.'.$route['name'])
                                ->middleware('WebPermission:'.$module->type.'-'.$module->name.'-'.$route['permission']);
                            break;

                        case 'DELETE':
                            Illuminate\Support\Facades\Route::delete(
                                $module->type.'/'.$module->name.'/'.$route['uri'],
                                [$class, $method]
                            )
                                ->name($module->type.'.'.$module->name.'.'.$route['name'])
                                ->middleware('WebPermission:'.$module->type.'-'.$module->name.'-'.$route['permission']);
                            break;

                        default:
                            throw new Exception('Unsupported HTTP method: '.$route['method']);
                    }
                } catch (Exception $e) {
                    Illuminate\Support\Facades\Log::error('Error in route registration: '.$e->getMessage());

                    continue;
                }
            }
        }
    }
}

if (! function_exists('loadApiModulesDynamicRoutes')) {
    function loadApiModulesDynamicRoutes(): void
    {
        $modules = app('Modules');
        foreach ($modules as $module) {
            if ($module->status != 'active') {
                continue;
            }
            $routes = $module->moduleApiRoutes();

            foreach ($routes as $route) {
                try {
                    if (! isset($route['method'], $route['uri'], $route['name'], $route['controller'])) {
                        continue;
                    }

                    [$controller, $method] = explode('@', $route['controller']);

                    $controllerPath = base_path("modules/{$module->type}/{$module->name}/Controllers/{$controller}.php");

                    require_once $controllerPath;
                    $class = str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        substr($controllerPath, strpos($controllerPath, 'modules'))
                    );

                    if (! file_exists($controllerPath)) {
                        Illuminate\Support\Facades\Log::error('Controller file does not exist: '.$controllerPath);

                        continue;
                    }

                    if (! class_exists($class)) {
                        throw new Exception('Class not found: '.$class);
                    }

                    if (! method_exists($class, $method)) {
                        throw new Exception("Method '$method' not found in class: ".$class);
                    }

                    switch (strtoupper($route['method'])) {
                        case 'GET':
                            Illuminate\Support\Facades\Route::get(
                                $module->type.'/'.$module->name.'/'.$route['uri'],
                                [$class, $method]
                            )
                                ->name($module->type.'.'.$module->name.'.'.$route['name'])
                                ->middleware('ApiPermission:'.$module->type.'-'.$module->name.'-'.$route['permission']);
                            break;

                        case 'POST':
                            Illuminate\Support\Facades\Route::post(
                                $module->type.'/'.$module->name.'/'.$route['uri'],
                                [$class, $method]
                            )
                                ->name($module->type.'.'.$module->name.'.'.$route['name'])
                                ->middleware('ApiPermission:'.$module->type.'-'.$module->name.'-'.$route['permission']);
                            break;

                        case 'PUT':
                            Illuminate\Support\Facades\Route::put(
                                $module->type.'/'.$module->name.'/'.$route['uri'],
                                [$class, $method]
                            )
                                ->name($module->type.'.'.$module->name.'.'.$route['name'])
                                ->middleware('ApiPermission:'.$module->type.'-'.$module->name.'-'.$route['permission']);
                            break;

                        case 'DELETE':
                            Illuminate\Support\Facades\Route::delete(
                                $module->type.'/'.$module->name.'/'.$route['uri'],
                                [$class, $method]
                            )
                                ->name($module->type.'.'.$module->name.'.'.$route['name'])
                                ->middleware('ApiPermission:'.$module->type.'-'.$module->name.'-'.$route['permission']);
                            break;

                        default:
                            throw new Exception('Unsupported HTTP method: '.$route['method']);
                    }
                } catch (Exception $e) {
                    Illuminate\Support\Facades\Log::error('Error in route registration: '.$e->getMessage());

                    continue;
                }
            }
        }
    }
}

if (! function_exists('loadModulesNamespaces')) {
    function loadModulesNamespaces(): void
    {
        $modules = app('Modules');

        foreach ($modules as $module) {
            if ($module->status != 'active') {
                continue;
            }

            $modelsPath = base_path("modules/{$module->type}/{$module->name}/Models");
            if (is_dir($modelsPath)) {
                foreach (glob($modelsPath.'/*.php') as $file) {
                    require_once $file;
                }
            }

            $controllersPath = base_path("modules/{$module->type}/{$module->name}/Controllers");
            if (is_dir($controllersPath)) {
                foreach (glob($controllersPath.'/*.php') as $file) {
                    require_once $file;
                }
            }

            $servicesPath = base_path("modules/{$module->type}/{$module->name}/Services");
            if (is_dir($servicesPath)) {
                foreach (glob($servicesPath.'/*.php') as $file) {
                    require_once $file;
                }
            }
        }
    }
}

if (! function_exists('loadModulesCommands')) {
    function loadModulesCommands(): void
    {
        $kernel = app(ConsoleKernel::class);
        $modules = app('Modules');

        foreach ($modules as $module) {
            if ($module->status != 'active') {
                continue;
            }

            $commandsPath = base_path("modules/{$module->type}/{$module->name}/Commands");

            if (is_dir($commandsPath)) {
                foreach (glob($commandsPath.'/*.php') as $file) {
                    require_once $file;
                    $className = pathinfo($file, PATHINFO_FILENAME);
                    $namespace = "Modules\\{$module->type}\\{$module->name}\\Commands\\{$className}";

                    if (class_exists($namespace)) {
                        $kernel->registerCommand(app($namespace));
                    }
                }
            }
        }
    }
}

if (! function_exists('loadAdminTemplateNamespaces')) {
    function loadAdminTemplateNamespaces(): void
    {
        $modelsPath = base_path('templates/admin/'.env('TEMPLATE_ADMIN', 'puqcloud')).'/Models/';
        if (is_dir($modelsPath)) {
            foreach (glob($modelsPath.'/*.php') as $file) {
                require_once $file;
            }
        }

        $controllersPath = base_path('templates/admin/'.env('TEMPLATE_ADMIN', 'puqcloud')).'/Controllers/';
        if (is_dir($controllersPath)) {
            foreach (glob($controllersPath.'/*.php') as $file) {
                require_once $file;
            }
        }
    }
}

if (! function_exists('loadClientTemplateNamespaces')) {
    function loadClientTemplateNamespaces(): void
    {
        $modelsPath = base_path('templates/client/'.env('TEMPLATE_CLIENT', 'puqcloud')).'/Models/';
        if (is_dir($modelsPath)) {
            foreach (glob($modelsPath.'/*.php') as $file) {
                require_once $file;
            }
        }

        $controllersPath = base_path('templates/client/'.env('TEMPLATE_CLIENT', 'puqcloud')).'/Controllers/';
        if (is_dir($controllersPath)) {
            foreach (glob($controllersPath.'/*.php') as $file) {
                require_once $file;
            }
        }

        $servicesPath = base_path('templates/client/'.env('TEMPLATE_CLIENT', 'puqcloud')).'/Services/';
        if (is_dir($servicesPath)) {
            foreach (glob($servicesPath.'/*.php') as $file) {
                require_once $file;
            }
        }

    }
}

if (! function_exists('number_format_custom')) {
    function number_format_custom($num, int $decimals = 2, string $format = '1234.56'): string
    {
        $num = (float) $num;

        $num_fixed = number_format($num, 4, '.', '');
        $decimal_part = explode('.', $num_fixed)[1] ?? '';

        if (strlen($decimal_part) >= 4 && $decimal_part[2] === '0' && $decimal_part[3] === '0') {
            $num = round($num, 2,PHP_ROUND_HALF_UP);
            $decimals = 2;
        } else {
            $decimals = 4;
        }

        $decimal_separator = '.';
        $thousands_separator = ',';

        if ($format === '1.234,56') {
            $decimal_separator = ',';
            $thousands_separator = '.';
        } elseif ($format === '1234,56') {
            $decimal_separator = ',';
            $thousands_separator = '';
        } elseif ($format === '1234.56') {
            $decimal_separator = '.';
            $thousands_separator = '';
        }

        return number_format($num, $decimals, $decimal_separator, $thousands_separator);
    }
}

if (! function_exists('formatCurrency')) {
    function formatCurrency($amount, $currency = null, $fallbackCode = 'USD'): string
    {
        if (! $currency || ! is_object($currency)) {
            return number_format_custom($amount ?? 0, 2).' '.$fallbackCode;
        }

        $formatted = number_format_custom($amount ?? 0, 2, $currency->format ?? null);

        $prefix = isset($currency->prefix) && $currency->prefix !== '' ? $currency->prefix : null;
        $suffix = isset($currency->suffix) && $currency->suffix !== '' ? $currency->suffix : null;
        $code = isset($currency->code) && $currency->code !== '' ? $currency->code : $fallbackCode;

        $result = '';

        if ($prefix) {
            $result .= $prefix.' ';
        }

        $result .= $formatted;

        if ($suffix) {
            $result .= ' '.$suffix;
        }

        if (! $prefix && ! $suffix) {
            $result .= ' '.$code;
        }

        return $result;
    }
}

if (! function_exists('renderServiceStatusClass')) {

    function renderServiceStatusClass($status)
    {
        $colorMap = [
            'active' => 'success',
            'completed' => 'success',
            'suspended' => 'warning',
            'pause' => 'warning',
            'pending' => 'info',
            'deploying' => 'info',
            'terminated' => 'dark',
            'fraud' => 'dark',
            'not_found' => 'dark',
            'cancelled' => 'alternate',
            'processing' => 'info',
            'failed' => 'danger',
        ];

        $key = strtolower($status ?? '');
        $color = $colorMap[$key] ?? 'secondary';

        return $color;
    }
}

if (! function_exists('generateStrongPassword')) {

    function generateStrongPassword($length = 8): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghjkmnpqrstuvwxyz';
        $digits = '23456789';
        $special = '@#$%&*-=+';

        $password = $upper[rand(0, strlen($upper) - 1)].
            $lower[rand(0, strlen($lower) - 1)].
            $digits[rand(0, strlen($digits) - 1)].
            $special[rand(0, strlen($special) - 1)];

        $all = $upper.$lower.$digits.$special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[rand(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }
}

if (! function_exists('isValidCronExpression')) {
    function isValidCronExpression(string $expression): bool
    {
        $pattern = '/^(\*|([0-5]?\d))(\/\d+)?(\s+)(\*|([01]?\d|2[0-3]))(\/\d+)?(\s+)(\*|([1-9]|[12]\d|3[01]))(\/\d+)?(\s+)(\*|(1[0-2]|0?[1-9]))(\/\d+)?(\s+)(\*|([0-6]))(\/\d+)?$/';

        return preg_match($pattern, $expression) === 1;
    }
}
