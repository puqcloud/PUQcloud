<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Dmytro Kravchenko <dmytro.kravchenko@ihostmi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Price;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeederProductOptions extends Seeder
{
    public function run(int $count = 3, array $groupKeys = [])
    {
        // Retrieve the default currency for pricing
        $currency = Currency::getDefaultCurrency();

        // Define default product option groups
        $defaultGroups = [
            'RAM', 'CPU', 'Disk', 'OS', 'Location', 'GPU', 'Firewall', 'Backup',
        ];

        // Use default groups if no specific group keys are provided
        if (empty($groupKeys)) {
            $groupKeys = $defaultGroups;
        }

        // Check existing option groups
        $existingGroups = ProductOptionGroup::whereIn('key', $groupKeys)->pluck('key')->toArray();
        $groupsToCreate = array_diff($groupKeys, $existingGroups);

        // Iterate through each group to create and process
        foreach ($groupKeys as $groupKey) {
            // Check if the group exists
            $group = ProductOptionGroup::firstOrCreate(
                ['key' => $groupKey],
                ['convert_price' => $groupKey !== 'OS'] // OS is considered free
            );

            // Get existing options for this group
            $existingOptions = ProductOption::where('product_option_group_uuid', $group->uuid)
                ->pluck('key')
                ->toArray();

            // Create missing options
            for ($i = 1; $i <= $count; $i++) {
                $optionKey = $this->generateOptionKey($groupKey, $i);

                // Skip if the option already exists
                if (in_array($optionKey, $existingOptions)) {
                    continue;
                }

                // Create option in a separate transaction
                DB::transaction(function () use ($group, $i, $groupKey, $optionKey, $currency) {
                    $value = $this->generateOptionValue($groupKey, $i);
                    $price = $this->generateOptionPrice($groupKey);

                    // Create a new product option
                    $option = ProductOption::create([
                        'product_option_group_uuid' => $group->uuid,
                        'key' => $optionKey,
                        'value' => $value,
                        'order' => $i,
                    ]);

                    // If the group requires price conversion, create a price model and attach it
                    if ($group->convert_price) {
                        $priceModel = Price::create([
                            'currency_uuid' => $currency->uuid,
                            'period' => 'monthly',
                            'base' => $price,
                        ]);
                        $option->prices()->attach($priceModel);
                        $option->convertPrice();
                    }
                });

                // Add a small delay to avoid load
                usleep(1000);
            }
        }
    }

    // Generate a unique key for each product option based on its group and index
    private function generateOptionKey(string $groupKey, int $i): string
    {
        return match ($groupKey) {
            'RAM' => "{$i}GiB",
            'CPU' => "{$i} Core".($i > 1 ? 's' : ''),
            'Disk' => 20 * $i.'GB SSD',
            'OS' => match ($i) {
                1 => 'Ubuntu 22.04',
                2 => 'Debian 12',
                3 => 'CentOS 9',
                default => "Linux Distro {$i}"
            },
            'GPU' => "{$i} vGPU",
            'Location' => match ($i) {
                1 => 'Frankfurt',
                2 => 'Warsaw',
                3 => 'Toronto',
                default => "Location {$i}"
            },
            'Firewall' => "Firewall level {$i}",
            'Backup' => "{$i} daily snapshot".($i > 1 ? 's' : ''),
            default => "{$groupKey} Option {$i}",
        };
    }

    // Generate a value for each product option based on its group and index
    private function generateOptionValue(string $groupKey, int $i): mixed
    {
        return match ($groupKey) {
            'RAM', 'CPU', 'GPU', 'Disk' => $i,
            default => strtolower(Str::slug($this->generateOptionKey($groupKey, $i)))
        };
    }

    // Generate a price for each product option based on its group
    private function generateOptionPrice(string $groupKey): int
    {
        return match ($groupKey) {
            'RAM', 'CPU', 'Disk', 'GPU', 'Backup' => rand(1, 10),
            default => 0,
        };
    }
}
