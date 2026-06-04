<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

namespace Modules\Product\puqProxmox\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\puqProxmox\Models\PuqPmAppInstance;
use Modules\Product\puqProxmox\Models\PuqPmScriptLog;
use OpenApi\Attributes as OA;

class puqPmAppInstanceController extends Controller
{
    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.puq_pm_app_instance.deploy_status.get',
        description: 'Returns the current deployment status of an application instance.',
        summary: 'Get deployment status',
        security: [['apiKeyAuth' => []]],
        tags: ['App Instance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the application instance',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deployment status returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'status',
                                    description: 'Deployment status',
                                    type: 'string',
                                    example: 'success'
                                ),
                                new OA\Property(
                                    property: 'progress',
                                    description: 'Deployment progress percentage (0-100)',
                                    type: 'integer',
                                    example: 100
                                ),
                                new OA\Property(
                                    property: 'started_at',
                                    description: 'Deployment start timestamp',
                                    type: 'string',
                                    format: 'date-time',
                                    example: '2025-12-16 13:08:39'
                                ),
                                new OA\Property(
                                    property: 'finished_at',
                                    description: 'Deployment finish timestamp',
                                    type: 'string',
                                    format: 'date-time',
                                    example: '2025-12-16 13:18:54',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'current_step',
                                    description: 'Current deployment step',
                                    type: 'string',
                                    example: 'Finish'
                                ),
                                new OA\Property(
                                    property: 'error',
                                    description: 'Error message if deployment failed',
                                    type: 'string',
                                    example: null,
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'logs',
                                    description: 'Detailed deployment logs with timestamps and step information',
                                    type: 'string',
                                    example: "[2025-12-16 13:08:39] Deploy job started\n[2025-12-16 13:08:39] Step 'Create APP Instance' finished - success\n[2025-12-16 13:08:39] Start step: Create SSL Certificates\n[2025-12-16 13:08:39] Finish step: Create SSL Certificates - success\n[2025-12-16 13:18:54] Deploy job finished successfully\n"
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getDeployStatus(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $model->getDeployStatus(),
        ]);
    }

    #[OA\Put(
        path: 'admin.api.Product.puqProxmox.puq_pm_app_instance.web_proxy_config.put',
        description: 'Queues a task to deploy/reload web proxy configuration for the application instance. The task will be executed asynchronously in the background.',
        summary: 'Load web proxy configuration',
        security: [['apiKeyAuth' => []]],
        tags: ['App Instance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the application instance',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task queued successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'The task has been queued for execution'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putLoadWebProxyConfig(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = [
            'module' => $model,
            'method' => 'webProxyDeploy',        // The method name that should be executed inside the job
            'callback' => '',
            // Optional. The method name in the module that will be executed after the main method is finished.
            // Receives the result and jobId as parameters.
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 3600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,
        ];
        $tags = [
            'webProxyDeploy',
        ];
        Task::add('ModuleJob', 'puqProxmox-LoadBalancer', $data, $tags);

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.The task has been queued for execution'),
        ]);

    }

    #[OA\Put(
        path: 'admin.api.Product.puqProxmox.puq_pm_app_instance.dns_records.put',
        description: 'Creates DNS records for the application instance. This operation configures necessary DNS entries for the instance to be accessible.',
        summary: 'Create DNS records',
        security: [['apiKeyAuth' => []]],
        tags: ['App Instance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the application instance',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'DNS records task sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'The task was sent successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'DNS records creation failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['Failed to create DNS records', 'DNS provider unavailable']
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putDnsRecords(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $pur_dns_records = $model->createDnsRecords();
        if ($pur_dns_records['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $pur_dns_records['errors'],
            ], $pur_dns_records['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.The task was sent successfully'),
        ]);
    }

    #[OA\Put(
        path: 'admin.api.Product.puqProxmox.puq_pm_app_instance.reboot_lxc.put',
        summary: 'Reboot LXC container',
        description: 'Reboots the LXC container associated with the application instance. This will restart the container and all services running inside it.',
        security: [['apiKeyAuth' => []]],
        tags: ['App Instance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                description: 'UUID of the application instance',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'LXC reboot task sent successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'The task was sent successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'LXC reboot failed',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['Failed to reboot LXC container', 'Container is not running']
                        )
                    ]
                )
            )
        ]
    )]
    public function putRebootLxc(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $reboot_lxc = $model->rebootLXC();
        if ($reboot_lxc['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $reboot_lxc['errors'],
            ], $reboot_lxc['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.The task was sent successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.puq_pm_app_instance.script_log.get',
        summary: 'Get script execution log',
        description: 'Retrieves detailed log information for a specific script execution associated with the application instance.',
        security: [['apiKeyAuth' => []]],
        tags: ['App Instance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                description: 'UUID of the application instance',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'log_uuid',
                in: 'path',
                description: 'UUID of the script log',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Script log returned successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'uuid',
                                    type: 'string',
                                    format: 'uuid',
                                    description: 'Log UUID',
                                    example: 'e3ffa17c-8ae0-446a-94cd-66b9706d6968'
                                ),
                                new OA\Property(
                                    property: 'puq_pm_script_uuid',
                                    type: 'string',
                                    format: 'uuid',
                                    description: 'Script UUID',
                                    example: 'fd0fdc9a-214d-46b4-8151-109557713e5e'
                                ),
                                new OA\Property(
                                    property: 'input',
                                    type: 'string',
                                    description: 'Script input/source code',
                                    example: "#!/bin/bash\nset -euo pipefail\n\n# Configure system\necho \"Script running\""
                                ),
                                new OA\Property(
                                    property: 'output',
                                    type: 'string',
                                    description: 'Script execution output',
                                    example: "success\n"
                                ),
                                new OA\Property(
                                    property: 'errors',
                                    type: 'array',
                                    description: 'Execution errors (empty if successful)',
                                    items: new OA\Items(type: 'string'),
                                    example: []
                                ),
                                new OA\Property(
                                    property: 'status',
                                    type: 'string',
                                    description: 'Execution status',
                                    example: 'success'
                                ),
                                new OA\Property(
                                    property: 'duration',
                                    type: 'integer',
                                    description: 'Execution duration in seconds',
                                    example: 4
                                ),
                                new OA\Property(
                                    property: 'model',
                                    type: 'string',
                                    description: 'Associated model class name',
                                    example: 'Modules\\Product\\puqProxmox\\Models\\PuqPmLxcInstance'
                                ),
                                new OA\Property(
                                    property: 'model_uuid',
                                    type: 'string',
                                    format: 'uuid',
                                    description: 'Associated model UUID',
                                    example: '886bb343-9b2f-4c3d-9b83-bcf4de7b6d0e'
                                ),
                                new OA\Property(
                                    property: 'created_at',
                                    type: 'string',
                                    format: 'date-time',
                                    description: 'Log creation timestamp',
                                    example: '2025-12-16T19:09:08.000000Z'
                                ),
                                new OA\Property(
                                    property: 'updated_at',
                                    type: 'string',
                                    format: 'date-time',
                                    description: 'Log update timestamp',
                                    example: '2025-12-16T19:09:08.000000Z'
                                )
                            ]
                        )
                    ]
                )
            ),
        ]
    )]
    public function getScriptLog(Request $request, $uuid, $log_uuid): JsonResponse
    {
        $model = PuqPmAppInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $log = PuqPmScriptLog::query()->where('uuid', $log_uuid)->first();
        if (empty($log)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Log Not Found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $log,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.Product.puqProxmox.puq_pm_app_instance.retry_deploy.put',
        summary: 'Retry failed deployment',
        description: 'Retries a failed deployment for the application instance. This endpoint can only be used when the deployment status is "failed".',
        security: [['apiKeyAuth' => []]],
        tags: ['App Instance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                description: 'UUID of the application instance',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Retry deploy task sent successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'The task was sent successfully')
                    ]
                )
            )
        ]
    )]
    public function putRetryDeploy(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if ($model->deploy_status != 'failed') {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deploy Status must be Failed')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.The task was sent successfully'),
        ]);
    }
}
