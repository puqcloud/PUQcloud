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
namespace Modules\Payment\puqPayPal\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'PayPal Payment Module REST API',
    title: 'PUQcloud PayPal Module Admin API',
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
    name: 'PayPal Admin API',
    description: 'Administrative endpoints (management, monitoring, configuration)'
)]

abstract class Admin
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
    public function default(): void {}
}
