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
namespace Modules\Product\puqVPNCP\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'VPNCP Module Client REST API for managing VPN connections — WireGuard, OpenVPN, IKEv2 configurations, one-time links, and traffic statistics.',
    title: 'PUQcloud VPNCP Module Client API',
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

#[OA\SecurityScheme(
    securityScheme: 'apiKeyAuth',
    type: 'apiKey',
    description: 'PUQcloud API Key authentication',
    name: 'X-API-Key',
    in: 'header'
)]

#[OA\Tag(name: 'VPN Client', description: 'VPN client info, configurations, and one-time links')]
#[OA\Tag(name: 'VPN Traffic', description: 'VPN traffic statistics and available periods')]

abstract class Client
{
    #[OA\Get(
        path: 'default',
        description: 'Default endpoint placeholder',
        summary: 'Default',
        security: [['apiKeyAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
        ]
    )]
    public function default(): void {}
}
