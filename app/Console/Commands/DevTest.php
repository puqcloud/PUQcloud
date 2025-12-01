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

use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Modules\Product\puqProxmox\Models\PuqPmScript;

class DevTest extends Command
{
    protected $signature = 'Dev:test';
    protected $description = 'some console command';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
//        $sss = PuqPmScript::query()->select('uuid', 'model', 'model_uuid')->where('model_uuid','587cfdcc-9cbf-44ca-87e2-ac543f737eb0')->get();
//        print_r($sss->toArray());
    }

}
