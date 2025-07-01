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

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run()
    {
        $currencies = [
            ['code' => 'USD', 'prefix' => '$', 'suffix' => '', 'exchange_rate' => 1, 'default' => true, 'format' => '1,234.56'],
            ['code' => 'EUR', 'prefix' => '€', 'suffix' => '', 'exchange_rate' => 0.9, 'default' => false, 'format' => '1.234,56'],
            ['code' => 'JPY', 'prefix' => '¥', 'suffix' => '', 'exchange_rate' => 110, 'default' => false, 'format' => '1,234.56'],
            ['code' => 'GBP', 'prefix' => '£', 'suffix' => '', 'exchange_rate' => 0.76, 'default' => false, 'format' => '1,234.56'],
            ['code' => 'AUD', 'prefix' => 'A$', 'suffix' => '', 'exchange_rate' => 1.3, 'default' => false, 'format' => '1,234.56'],
            ['code' => 'CAD', 'prefix' => 'C$', 'suffix' => '', 'exchange_rate' => 1.25, 'default' => false, 'format' => '1,234.56'],
            ['code' => 'CHF', 'prefix' => 'CHF', 'suffix' => '', 'exchange_rate' => 0.92, 'default' => false, 'format' => '1,234.56'],
            ['code' => 'CNY', 'prefix' => '¥', 'suffix' => '', 'exchange_rate' => 6.5, 'default' => false, 'format' => '1,234.56'],
            ['code' => 'SEK', 'prefix' => 'kr', 'suffix' => '', 'exchange_rate' => 8.6, 'default' => false, 'format' => '1,234.56'],
            ['code' => 'NZD', 'prefix' => 'NZ$', 'suffix' => '', 'exchange_rate' => 1.4, 'default' => false, 'format' => '1,234.56'],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        Currency::where('code', 'USD')->update(['default' => true]);
        Currency::where('code', '!=', 'USD')->update(['default' => false]);
    }
}
