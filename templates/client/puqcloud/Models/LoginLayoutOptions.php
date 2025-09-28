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
use App\Services\SettingService;
use App\Traits\HasFiles;

class LoginLayoutOptions
{
    use HasFiles;

    public array $images = [];

    const IMAGES = [
        'favicon' => ['label' => 'Favicon', 'order' => 1],
        'logo' => ['label' => 'Logo', 'order' => 2],
        'background' => ['label' => 'Background', 'order' => 3],
    ];

    public ?string $header_color_scheme;

    public string $uuid = 'pol0vy1r-usl4-anpu-qcl0-ud9b33f0a1d2';

    public function __construct()
    {
        $this->header_color_scheme = SettingService::get('clientAreaLayoutOptionHeaderColorScheme');
        $this->images = $this->getImages();
    }
}
