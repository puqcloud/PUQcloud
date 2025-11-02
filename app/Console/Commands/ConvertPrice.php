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

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class ConvertPrice extends Command
{

    protected $signature = 'Products:ConvertPrice';
    protected $description = 'Automatically converts all prices at the rate specified in currencies';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {

        // Delete all prices if currency was removed
        Price::whereDoesntHave('currency')->delete();

        $products = Product::where('convert_price', true)->get();
        foreach ($products as $product) {
            $product->convertPrice();
        }

        $product_option_groups = ProductOptionGroup::where('convert_price', true)->get();
        foreach ($product_option_groups as $product_option_group) {
            $product_options = $product_option_group->productOptions;
            foreach ($product_options as $product_option) {
                $product_option->convertPrice();
            }
        }
    }
}
