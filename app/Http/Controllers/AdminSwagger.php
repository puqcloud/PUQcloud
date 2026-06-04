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

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'PUQcloud REST API for cloud billing, provisioning, automation and service management.',
    title: 'PUQcloud Admin API',
    contact: new OA\Contact(
        name: 'PUQcloud Community',
        url: 'https://community.puqcloud.com/',
        email: 'info@puqcloud.com'
    ),
    license: new OA\License(
        name: 'GPLv3',
        url: 'https://www.gnu.org/licenses/gpl-3.0.html'
    )
)]

/**
 * SECURITY
 */
#[OA\SecurityScheme(
    securityScheme: 'apiKeyAuth',
    type: 'apiKey',
    description: 'PUQcloud API Key authentication',
    name: 'X-API-Key',
    in: 'header'
)]

/**
 * TAGS
 */
#[OA\Tag(
    name: 'Admins',
    description: 'Endpoints for managing administrators. Includes operations for creating, reading, updating, and deleting admin accounts, managing admin groups, permissions, and access control.'
)]
#[OA\Tag(
    name: 'Auth',
    description: 'Authentication and authorization'
)]
#[OA\Tag(
    name: 'Monitoring',
    description: 'Node and service monitoring'
)]
#[OA\Tag(
    name: 'Billing',
    description: 'Invoices, payments, taxes, credits'
)]
#[OA\Tag(
    name: 'Provisioning',
    description: 'Service provisioning and lifecycle'
)]

/**
 * GLOBAL SCHEMAS
 */
#[OA\Schema(
    schema: 'SuccessResponse',
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'success'),
        new OA\Property(property: 'message', type: 'string', nullable: true, example: 'Operation completed successfully'),
        new OA\Property(property: 'data', type: 'object', nullable: true),
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'error'),
        new OA\Property(
            property: 'errors',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['Error message']
        ),
        new OA\Property(property: 'message', type: 'string', nullable: true, example: null),
    ],
    type: 'object'
)]
abstract class AdminSwagger
{
    #[OA\Get(
        path: 'default',
        description: 'default',
        summary: 'default',
        security: [['apiKeyAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'default',
            ),
        ]
    )]
    public function default() {}
}
