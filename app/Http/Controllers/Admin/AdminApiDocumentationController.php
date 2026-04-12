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

/*
 * WEB Routes:
 *   GET  api_documentation/admin                              -> admin()                       [admin.web.api_documentation.admin]
 *   GET  api_documentation/admin/{module}/{name}              -> adminApiDocumentation()        [admin.web.api_documentation.admin_api_documentation]
 *   GET  api_documentation/client                             -> client()                      [admin.web.api_documentation.client]
 *   GET  api_documentation/client/{module}/{name}             -> clientApiDocumentation()       [admin.web.api_documentation.client_api_documentation]
 *
 * API Routes:
 *   GET  api_documentations/admin/                            -> getAdminApiDocumentations()    [admin.api.api_documentations.admin.get]
 *   GET  api_documentation/admin/{module}/{name}              -> getAdminApiDocumentation()     [admin.api.api_documentation.admin.swagger.get]
 *   GET  api_documentations/client/                           -> getClientApiDocumentations()   [admin.api.api_documentations.client.get]
 *   GET  api_documentation/client/{module}/{name}             -> getClientApiDocumentation()    [admin.api.api_documentation.client.swagger.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use OpenApi\Attributes as OA;
use OpenApi\Generator;

class AdminApiDocumentationController extends Controller
{
    // Admin ------------------------------------------------------------------------------------------------------------------
    public function admin(): View
    {
        $title = __('main.Admin API Documentation');

        return view_admin('api_documentation.admin', compact('title'));
    }

    public function adminApiDocumentation(Request $request, $module, $name): View
    {
        $title = __('main.Admin API Documentation');

        return view_admin('api_documentation.admin_swagger', compact('title', 'module', 'name'));
    }

    #[OA\Get(
        path: 'admin.api.api_documentations.admin.get',
        description: 'Returns the list of available admin API documentations including modules.',
        summary: 'Get admin API documentations list',
        security: [['apiKeyAuth' => []]],
        tags: ['API Documentation'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API documentations list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getAdminApiDocumentations(Request $request): JsonResponse
    {
        $general_openapi = Generator::scan([base_path('app/Http/Controllers/AdminSwagger.php')]);
        $api_documentations = [
            [
                'module' => 'general',
                'name' => 'general',
                'info' => $general_openapi->info,
                'web_url' => route('admin.web.api_documentation.admin_api_documentation',
                    ['module' => 'general', 'name' => 'general']),
                'api_url' => route('admin.api.api_documentation.admin.swagger.get',
                    ['module' => 'general', 'name' => 'general']),
            ],
        ];

        $api_documentations = array_merge($api_documentations, $this->getModulesAdminSwagger());

        return response()->json(['data' => $api_documentations], 200);
    }

    private function getModulesAdminSwagger(): array
    {
        $modules = app('Modules');
        $result = [];

        foreach ($modules as $module) {
            if ($module->status !== 'active') {
                continue;
            }

            $swaggerFile = base_path(
                "modules/{$module->type}/{$module->name}/Swagger/Admin.php"
            );

            if (! is_file($swaggerFile)) {
                continue;
            }

            try {
                $openapi = Generator::scan([$swaggerFile]);
            } catch (\Throwable $e) {
                continue;
            }

            if (! $openapi->info) {
                continue;
            }

            $result[] = [
                'module' => $module->type,
                'name' => $module->name,
                'info' => $openapi->info,
                'web_url' => route(
                    'admin.web.api_documentation.admin_api_documentation',
                    ['module' => $module->type, 'name' => $module->name]
                ),
                'api_url' => route(
                    'admin.api.api_documentation.admin.swagger.get',
                    ['module' => $module->type, 'name' => $module->name]
                ),
            ];
        }

        return $result;
    }

    #[OA\Get(
        path: 'admin.api.api_documentation.admin.swagger.get',
        description: 'Returns the OpenAPI/Swagger JSON for a specific admin API documentation module.',
        summary: 'Get admin API Swagger JSON',
        security: [['apiKeyAuth' => []]],
        tags: ['API Documentation'],
        parameters: [
            new OA\Parameter(
                name: 'module',
                description: 'Module type (e.g. general)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'name',
                description: 'Module name',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Swagger JSON returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'raw', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getAdminApiDocumentation(Request $request, $module, $name): JsonResponse
    {
        $openapi = null;
        if ($module === 'general') {
            $openapi = Generator::scan([
                base_path('app/Http/Controllers/AdminSwagger.php'),
                base_path('app/Http/Controllers/Admin'),
            ]);
        } else {
            $openapi = $this->loadAdminModuleSwagger($module, $name);
        }

        if (! $openapi) {
            return response()->json([
                'error' => 'OpenAPI documentation not found for this module.',
            ], 404);
        }
        $fixed = $this->fixOpenApi(json_decode($openapi->toJson(), true));

        return response()->json(['raw' => $fixed]);
    }

    private function loadAdminModuleSwagger(string $moduleType, string $moduleName): ?\OpenApi\Annotations\OpenApi
    {
        try {
            $openapi = Generator::scan([
                base_path("modules/{$moduleType}/{$moduleName}/Swagger/Admin.php"),
                base_path("modules/{$moduleType}/{$moduleName}/Controllers/"),
            ]);

            return $openapi;
        } catch (\Throwable $e) {
            logActivity('error', 'Swagger Admin scan error: '.$moduleType.'/'.$moduleName.': '.$e->getMessage(), 'swagger');

            return null;
        }
    }

    // Client ------------------------------------------------------------------------------------------------------------------
    public function client(): View
    {
        $title = __('main.Client API Documentation');

        return view_admin('api_documentation.client', compact('title'));
    }

    public function clientApiDocumentation(Request $request, $module, $name): View
    {
        $title = __('main.Client API Documentation');

        return view_admin('api_documentation.client_swagger', compact('title', 'module', 'name'));
    }

    #[OA\Get(
        path: 'admin.api.api_documentations.client.get',
        description: 'Returns the list of available client API documentations including modules.',
        summary: 'Get client API documentations list',
        security: [['apiKeyAuth' => []]],
        tags: ['API Documentation'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client API documentations list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClientApiDocumentations(Request $request): JsonResponse
    {
        $general_openapi = Generator::scan([base_path('templates/client/'.config('template.client.name').'/Controllers/Swagger.php')]);
        $api_documentations = [
            [
                'module' => 'general',
                'name' => 'general',
                'info' => $general_openapi->info,
                'web_url' => route('admin.web.api_documentation.client_api_documentation',
                    ['module' => 'general', 'name' => 'general']),
                'api_url' => route('admin.api.api_documentation.client.swagger.get',
                    ['module' => 'general', 'name' => 'general']),
            ],
        ];

        $api_documentations = array_merge($api_documentations, $this->getModulesClientSwagger());

        return response()->json(['data' => $api_documentations], 200);
    }

    private function getModulesClientSwagger(): array
    {
        $modules = app('Modules');
        $result = [];

        foreach ($modules as $module) {
            if ($module->status !== 'active') {
                continue;
            }

            $swaggerFile = base_path(
                "modules/{$module->type}/{$module->name}/Swagger/Client.php"
            );

            if (! is_file($swaggerFile)) {
                continue;
            }

            try {
                $openapi = Generator::scan([$swaggerFile]);
            } catch (\Throwable $e) {
                continue;
            }

            if (! $openapi->info) {
                continue;
            }

            $result[] = [
                'module' => $module->type,
                'name' => $module->name,
                'info' => $openapi->info,
                'web_url' => route(
                    'admin.web.api_documentation.client_api_documentation',
                    ['module' => $module->type, 'name' => $module->name]
                ),
                'api_url' => route(
                    'admin.api.api_documentation.client.swagger.get',
                    ['module' => $module->type, 'name' => $module->name]
                ),
            ];
        }

        return $result;
    }

    #[OA\Get(
        path: 'admin.api.api_documentation.client.swagger.get',
        description: 'Returns the OpenAPI/Swagger JSON for a specific client API documentation module.',
        summary: 'Get client API Swagger JSON',
        security: [['apiKeyAuth' => []]],
        tags: ['API Documentation'],
        parameters: [
            new OA\Parameter(
                name: 'module',
                description: 'Module type (e.g. general)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'name',
                description: 'Module name',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Swagger JSON returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'raw', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getClientApiDocumentation(Request $request, $module, $name): JsonResponse
    {
        $openapi = null;
        if ($module === 'general') {
            $openapi = Generator::scan([
                base_path('templates/client/'.config('template.client.name').'/Controllers/'),
            ]);
        } else {
            $openapi = $this->loadClientModuleSwagger($module, $name);
        }

        if (! $openapi) {
            return response()->json([
                'error' => 'OpenAPI documentation not found for this module.',
            ], 404);
        }
        $fixed = $this->fixOpenApi(json_decode($openapi->toJson(), true));

        return response()->json(['raw' => $fixed]);
    }

    private function loadClientModuleSwagger(string $moduleType, string $moduleName): ?\OpenApi\Annotations\OpenApi
    {
        try {
            $openapi = Generator::scan([
                base_path("modules/{$moduleType}/{$moduleName}/Swagger/Client.php"),
                base_path("modules/{$moduleType}/{$moduleName}/{$moduleName}.php"),
            ]);

            return $openapi;
        } catch (\Throwable $e) {
            logActivity('error', 'Swagger Client scan error: '.$moduleType.'/'.$moduleName.': '.$e->getMessage(), 'swagger');

            return null;
        }
    }

    private function fixOpenApi(array $openapi): array
    {
        if (! isset($openapi['paths']) || ! is_array($openapi['paths'])) {
            return $openapi;
        }

        unset($openapi['paths']['default']);

        $fixedPaths = [];

        $laravelRoutes = collect(Route::getRoutes())
            ->mapWithKeys(fn ($route) => [$route->getName() => $route->uri()])
            ->toArray();

        foreach ($openapi['paths'] as $key => $value) {

            // Support virtual paths like 'route.name.methodName' → resolve base route + replace {method}
            $routeKey = $key;
            $methodSuffix = null;

            if (! isset($laravelRoutes[$key])) {
                // Try stripping last segment as method suffix: 'route.name.get.getLxcInfo' → 'route.name.get' + 'getLxcInfo'
                $lastDot = strrpos($key, '.');
                if ($lastDot !== false) {
                    $baseKey = substr($key, 0, $lastDot);
                    $methodSuffix = substr($key, $lastDot + 1);
                    if (isset($laravelRoutes[$baseKey])) {
                        $routeKey = $baseKey;
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            $path = '/'.$laravelRoutes[$routeKey];

            if (isset($value['x-path']) && is_array($value['x-path'])) {
                foreach ($value['x-path'] as $param => $replacement) {
                    $path = str_replace('{'.$param.'}', $replacement, $path);
                }

                unset($value['x-path']);
            }

            // Replace {method} with the method suffix from virtual path
            if ($methodSuffix && str_contains($path, '{method}')) {
                $path = str_replace('{method}', $methodSuffix, $path);
            }

            if (isset($fixedPaths[$path])) {
                $fixedPaths[$path] = array_merge($fixedPaths[$path], $value);
            } else {
                $fixedPaths[$path] = $value;
            }
        }

        $openapi['paths'] = $fixedPaths;

        return $openapi;
    }
}
