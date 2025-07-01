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

use App\Models\Country;
use Illuminate\Console\Command;

class Regions extends Command
{
    protected $signature = 'puqcloud:regions';

    protected $description = '';

    public function handle()
    {
        $countries = Country::withCount('regions')->get();

        foreach ($countries as $country) {
            if ($country->regions_count == 0) {
                echo "Country: {$country->name}, Regions Count: {$country->regions_count}\n";
            }
        }
    }
}
