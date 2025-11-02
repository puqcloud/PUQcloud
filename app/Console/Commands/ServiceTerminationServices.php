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

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class ServiceTerminationServices extends Command
{

    protected $signature = 'Service:TerminationServices';
    protected $description = 'Termination clients suspended services if insufficient funds';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        Service::query()
            ->whereIn('status', ['active', 'suspended'])
            ->chunk(100, function ($services) {
                foreach ($services as $service) {
                    $service->termination();
                }
            });
    }
}
