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
namespace Modules\Product\puqProxmox\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Proxmox Module Client REST API for managing LXC containers, applications, backups, networking, firewall, and rescaling.',
    title: 'PUQcloud Proxmox Module Client API',
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

#[OA\Tag(name: 'LXC', description: 'LXC container management — deploy, status, start, stop, console, rebuild')]
#[OA\Tag(name: 'Application', description: 'Application container management — deploy, info, control, custom pages')]
#[OA\Tag(name: 'Backup', description: 'Backup management — schedule, list, create, restore, delete')]
#[OA\Tag(name: 'Networks', description: 'Network management — public and private networks')]
#[OA\Tag(name: 'Firewall', description: 'Firewall management — policies and rules')]
#[OA\Tag(name: 'Rescale', description: 'Rescaling — options, pricing, execution')]

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
