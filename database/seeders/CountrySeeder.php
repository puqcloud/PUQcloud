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

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countriesPath = base_path('database/Countries/countries.json');
        $regionsPath = base_path('database/Countries/regions.json');

        if (! File::exists($countriesPath)) {
            $this->command->error("File $countriesPath not found");

            return;
        }

        if (! File::exists($regionsPath)) {
            $this->command->error("File $regionsPath not found");

            return;
        }

        $countriesData = json_decode(File::get($countriesPath), true);
        $regionsData = json_decode(File::get($regionsPath), true);

        foreach ($countriesData as $code => $data) {
            $country = Country::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'] ?? '',
                    'calling_code' => $data['calling_code'] ?? '',
                    'native_name' => $data['native_name'] ?? '',
                ]
            );

            if (isset($regionsData[$code]) && is_array($regionsData[$code])) {
                foreach ($regionsData[$code] as $regionData) {
                    Region::updateOrCreate(
                        ['code' => $regionData['code'], 'country_uuid' => $country->uuid],
                        [
                            'name' => $regionData['name'],
                            'native_name' => $regionData['native_name'] ?? '',
                        ]
                    );
                }
            }
        }
    }
}
