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

use App\Models\Product;
use App\Models\ProductAttributeGroup;
use App\Models\ProductGroup;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemoDataSeederProducts extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(int $count = 100): void
    {
        // Get all product groups
        $productGroups = ProductGroup::all();
        if ($productGroups->isEmpty()) {
            Log::error('No product groups found. Please run product groups seeder first.');

            return;
        }

        // DEBUG: Output all product group keys
        echo "[DEMO SEED][DEBUG] Product groups in DB:\n";
        foreach ($productGroups as $dbgGroup) {
            echo " - {$dbgGroup->key}\n";
        }

        // Get all attribute groups
        $attributeGroups = ProductAttributeGroup::with('productAttributes')->get();
        if ($attributeGroups->isEmpty()) {
            Log::error('No attribute groups found. Please run attribute groups seeder first.');

            return;
        }

        // Clear used names for fresh start
        \Database\Factories\ProductFactory::resetUsedNames();

        // Update translations for all existing option groups
        $this->updateAllOptionGroupTranslations();

        // Update translations for all existing options
        $this->updateAllOptionTranslations();

        // Ensure all options have prices
        $this->ensureOptionPricesExist();

        // Create products using factory
        for ($i = 0; $i < $count; $i++) {
            try {
                // Select random product group
                $group = $productGroups->random();

                // Create product using factory with realistic data
                $product = Product::factory()
                    ->withTranslations()
                    ->create();

                if (! $product || ! $product->uuid) {
                    echo "[DEMO SEED][ERROR] Product not created or uuid is null\n";

                    continue;
                }

                echo "[DEMO SEED] Created product '{$product->key}' ({$product->uuid}) for group '{$group->key}'\n";

                // Attach product to group
                try {
                    $product->productGroups()->syncWithoutDetaching([$group->uuid => ['order' => $i + 1]]);
                    echo "[DEMO SEED] Attached product '{$product->key}' to group '{$group->key}'\n";
                } catch (\Throwable $e) {
                    echo "[DEMO SEED][ERROR] Failed to attach product '{$product->key}' to group '{$group->key}': ".$e->getMessage()."\n";
                }

                // Attach attributes based on group type
                $this->attachAttributesBasedOnGroup($product, $group, $attributeGroups);

                // Create and attach option groups with full options and prices
                $this->createOptionGroupsWithOptionsForProduct($product, $group);

                // Ensure all existing options have prices
                $this->ensureOptionPricesExist();

                // Generate prices using factory
                $this->generatePricesUsingFactory($product);

            } catch (\Exception $e) {
                Log::error('Error creating product: '.$e->getMessage());

                continue;
            }
        }
    }

    /**
     * Attach attributes based on product group type
     */
    private function attachAttributesBasedOnGroup(Product $product, ProductGroup $group, $attributeGroups): void
    {
        // Map group types to relevant attribute groups - check for both base keys and numbered variants
        $groupKey = $group->key;
        $baseGroupKey = preg_replace('/_\d+$/', '', $groupKey); // Remove _1, _2 etc suffixes

        $attributeMap = [
            'vps' => ['compute', 'storage', 'network', 'security', 'backup'],
            'hosting' => ['storage', 'network', 'software', 'security', 'backup'],
            'storage' => ['storage', 'backup', 'network'],
            'license' => ['software', 'support'],
            'backup' => ['storage', 'network', 'security', 'backup'],
            'firewall' => ['network', 'security', 'support'],
            'ssl' => ['security', 'support'],
            'email' => ['storage', 'network', 'security', 'software', 'support'],
            'database' => ['storage', 'network', 'security', 'backup', 'software', 'support'],
            'monitoring' => ['network', 'security', 'software', 'support'],
        ];

        $attributesToAttach = $attributeMap[$baseGroupKey] ?? [];

        foreach ($attributesToAttach as $attributeGroupKey) {
            $attributeGroup = $attributeGroups->firstWhere('key', $attributeGroupKey);
            if ($attributeGroup) {
                foreach ($attributeGroup->productAttributes as $attribute) {
                    $product->productAttributes()->syncWithoutDetaching([$attribute->uuid]);
                }
            }
        }
    }

    /**
     * Create option groups with full options and prices for product
     */
    private function createOptionGroupsWithOptionsForProduct(Product $product, ProductGroup $group): void
    {
        // Map group types to relevant option groups - check for both base keys and numbered variants
        $groupKey = $group->key;
        $baseGroupKey = preg_replace('/_\d+$/', '', $groupKey); // Remove _1, _2 etc suffixes

        $optionGroupMap = [
            'vps' => ['RAM', 'CPU', 'Disk', 'OS', 'Location', 'GPU', 'Firewall', 'Backup'],
            'hosting' => ['Disk', 'OS', 'Location', 'Backup', 'Firewall'],
            'storage' => ['Disk', 'Location', 'Backup'],
            'license' => [],
            'backup' => ['Backup', 'Disk', 'Location'],
            'firewall' => ['Firewall'],
            'ssl' => [],
            'email' => ['Disk', 'Location', 'Backup'],
            'database' => ['Disk', 'Location', 'Backup'],
            'monitoring' => ['Firewall', 'Backup'],
        ];

        $optionGroupsToCreate = $optionGroupMap[$baseGroupKey] ?? [];

        foreach ($optionGroupsToCreate as $optionGroupKey) {
            // Check if option group already exists
            $optionGroup = ProductOptionGroup::where('key', $optionGroupKey)->first();

            if (! $optionGroup) {
                // Create new option group with factory
                $optionGroup = ProductOptionGroup::factory()
                    ->withType($optionGroupKey)
                    ->withTranslations()
                    ->create();

                echo "[DEMO SEED] Created option group '{$optionGroup->key}' ({$optionGroup->uuid})\n";

                // Create all options for this group
                $this->createOptionsForGroup($optionGroup);
            } else {
                // Update translations for existing option group if missing
                $this->updateOptionGroupTranslations($optionGroup);
            }

            // Attach option group to product
            try {
                $product->addProductOptionGroup($optionGroup);
                echo "[DEMO SEED] Attached option group '{$optionGroup->key}' to product '{$product->key}'\n";
            } catch (\Throwable $e) {
                echo "[DEMO SEED][ERROR] Failed to attach option group '{$optionGroup->key}' to product '{$product->key}': ".$e->getMessage()."\n";
            }
        }
    }

    /**
     * Create all options for an option group
     */
    private function createOptionsForGroup(ProductOptionGroup $optionGroup): void
    {
        // Define all options for each group type
        $optionsByGroup = [
            'RAM' => [
                ['key' => 'ram_1gb', 'value' => '1', 'order' => 1],
                ['key' => 'ram_2gb', 'value' => '2', 'order' => 2],
                ['key' => 'ram_4gb', 'value' => '4', 'order' => 3],
                ['key' => 'ram_8gb', 'value' => '8', 'order' => 4],
                ['key' => 'ram_16gb', 'value' => '16', 'order' => 5],
                ['key' => 'ram_32gb', 'value' => '32', 'order' => 6],
            ],
            'CPU' => [
                ['key' => 'cpu_1core', 'value' => '1', 'order' => 1],
                ['key' => 'cpu_2cores', 'value' => '2', 'order' => 2],
                ['key' => 'cpu_4cores', 'value' => '4', 'order' => 3],
                ['key' => 'cpu_8cores', 'value' => '8', 'order' => 4],
                ['key' => 'cpu_16cores', 'value' => '16', 'order' => 5],
            ],
            'Disk' => [
                ['key' => 'disk_20gb', 'value' => '20', 'order' => 1],
                ['key' => 'disk_50gb', 'value' => '50', 'order' => 2],
                ['key' => 'disk_100gb', 'value' => '100', 'order' => 3],
                ['key' => 'disk_200gb', 'value' => '200', 'order' => 4],
                ['key' => 'disk_500gb', 'value' => '500', 'order' => 5],
                ['key' => 'disk_1tb', 'value' => '1000', 'order' => 6],
            ],
            'OS' => [
                ['key' => 'ubuntu_22_04', 'value' => 'ubuntu-22.04', 'order' => 1],
                ['key' => 'centos_8', 'value' => 'centos-8', 'order' => 2],
                ['key' => 'debian_11', 'value' => 'debian-11', 'order' => 3],
                ['key' => 'windows_server_2022', 'value' => 'windows-server-2022', 'order' => 4],
                ['key' => 'rocky_linux_9', 'value' => 'rocky-linux-9', 'order' => 5],
            ],
            'Location' => [
                ['key' => 'us_east', 'value' => 'us-east-1', 'order' => 1],
                ['key' => 'us_west', 'value' => 'us-west-1', 'order' => 2],
                ['key' => 'eu_central', 'value' => 'eu-central-1', 'order' => 3],
                ['key' => 'asia_pacific', 'value' => 'ap-southeast-1', 'order' => 4],
                ['key' => 'canada_central', 'value' => 'ca-central-1', 'order' => 5],
            ],
            'GPU' => [
                ['key' => 'nvidia_gtx_1060', 'value' => 'gtx-1060', 'order' => 1],
                ['key' => 'nvidia_rtx_3080', 'value' => 'rtx-3080', 'order' => 2],
                ['key' => 'nvidia_tesla_v100', 'value' => 'tesla-v100', 'order' => 3],
                ['key' => 'amd_radeon_pro', 'value' => 'radeon-pro', 'order' => 4],
            ],
            'Firewall' => [
                ['key' => 'basic_firewall', 'value' => 'basic', 'order' => 1],
                ['key' => 'advanced_firewall', 'value' => 'advanced', 'order' => 2],
                ['key' => 'enterprise_firewall', 'value' => 'enterprise', 'order' => 3],
            ],
            'Backup' => [
                ['key' => 'daily_backup', 'value' => 'daily', 'order' => 1],
                ['key' => 'weekly_backup', 'value' => 'weekly', 'order' => 2],
                ['key' => 'realtime_backup', 'value' => 'realtime', 'order' => 3],
                ['key' => 'monthly_backup', 'value' => 'monthly', 'order' => 4],
            ],
            'Bandwidth' => [
                ['key' => 'bandwidth_100mbps', 'value' => '100', 'order' => 1],
                ['key' => 'bandwidth_1gbps', 'value' => '1000', 'order' => 2],
                ['key' => 'bandwidth_10gbps', 'value' => '10000', 'order' => 3],
                ['key' => 'bandwidth_unlimited', 'value' => 'unlimited', 'order' => 4],
            ],
            'Support' => [
                ['key' => 'basic_support', 'value' => 'basic', 'order' => 1],
                ['key' => 'priority_support', 'value' => 'priority', 'order' => 2],
                ['key' => 'enterprise_support', 'value' => 'enterprise', 'order' => 3],
                ['key' => 'premium_support', 'value' => 'premium', 'order' => 4],
            ],
        ];

        $optionsData = $optionsByGroup[$optionGroup->key] ?? [];

        foreach ($optionsData as $optionData) {
            // Check if option already exists
            $existingOption = ProductOption::where('key', $optionData['key'])
                ->where('product_option_group_uuid', $optionGroup->uuid)
                ->first();

            if (! $existingOption) {
                // Create option using factory
                $option = ProductOption::factory()
                    ->forGroup($optionGroup)
                    ->withTranslations()
                    ->create([
                        'key' => $optionData['key'],
                        'value' => $optionData['value'],
                        'order' => $optionData['order'],
                    ]);

                echo "[DEMO SEED] Created option '{$option->key}' for group '{$optionGroup->key}'\n";

                // Create prices for this option
                $this->createPricesForOption($option);
            } else {
                // Option exists, check if it has prices
                if ($existingOption->prices()->count() === 0) {
                    echo "[DEMO SEED] Adding prices to existing option '{$existingOption->key}'\n";
                    $this->createPricesForOption($existingOption);
                }

                // Update translations for existing option if missing
                $this->updateOptionTranslations($existingOption);
            }
        }
    }

    /**
     * Create prices for a product option
     */
    private function createPricesForOption(ProductOption $option): void
    {
        $periods = ['monthly', 'quarterly', 'annually'];

        // Define price multipliers based on option type and value
        $priceMultipliers = $this->getPriceMultipliersForOption($option);

        foreach ($periods as $period) {
            try {
                $basePrice = $this->getBasePriceForPeriod($period);
                $multiplier = $priceMultipliers[$period] ?? 1;
                $finalPrice = round($basePrice * $multiplier);

                $price = \App\Models\Price::factory()
                    ->period($period)
                    ->create([
                        'base' => $finalPrice,
                    ]);

                // Attach price to option using direct SQL to avoid Eloquent attach() issues
                DB::table('product_option_x_price')->insert([
                    'product_option_uuid' => $option->uuid,
                    'price_uuid' => $price->uuid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                echo "[DEMO SEED] Created {$period} price: {$price->base} for option '{$option->key}'\n";
            } catch (\Exception $e) {
                Log::error("Error creating price for option {$option->key}: ".$e->getMessage());
            }
        }
    }

    /**
     * Get price multipliers for option based on its type and value
     */
    private function getPriceMultipliersForOption(ProductOption $option): array
    {
        $optionGroup = $option->productOptionGroup;
        $groupKey = $optionGroup ? $optionGroup->key : '';
        $optionKey = $option->key;

        // Extract numeric value from option key for pricing
        $numericValue = $this->extractNumericValue($optionKey, $groupKey);

        // Price multipliers based on option type and value
        $multipliers = [
            'RAM' => [
                '1' => ['monthly' => 0.5, 'quarterly' => 1.4, 'annually' => 5.0],
                '2' => ['monthly' => 1.0, 'quarterly' => 2.7, 'annually' => 9.0],
                '4' => ['monthly' => 2.0, 'quarterly' => 5.4, 'annually' => 18.0],
                '8' => ['monthly' => 4.0, 'quarterly' => 10.8, 'annually' => 36.0],
                '16' => ['monthly' => 8.0, 'quarterly' => 21.6, 'annually' => 72.0],
                '32' => ['monthly' => 16.0, 'quarterly' => 43.2, 'annually' => 144.0],
            ],
            'CPU' => [
                '1' => ['monthly' => 1.0, 'quarterly' => 2.7, 'annually' => 9.0],
                '2' => ['monthly' => 2.0, 'quarterly' => 5.4, 'annually' => 18.0],
                '4' => ['monthly' => 4.0, 'quarterly' => 10.8, 'annually' => 36.0],
                '8' => ['monthly' => 8.0, 'quarterly' => 21.6, 'annually' => 72.0],
                '16' => ['monthly' => 16.0, 'quarterly' => 43.2, 'annually' => 144.0],
            ],
            'Disk' => [
                '20' => ['monthly' => 0.2, 'quarterly' => 0.54, 'annually' => 1.8],
                '50' => ['monthly' => 0.5, 'quarterly' => 1.35, 'annually' => 4.5],
                '100' => ['monthly' => 1.0, 'quarterly' => 2.7, 'annually' => 9.0],
                '200' => ['monthly' => 2.0, 'quarterly' => 5.4, 'annually' => 18.0],
                '500' => ['monthly' => 5.0, 'quarterly' => 13.5, 'annually' => 45.0],
                '1000' => ['monthly' => 10.0, 'quarterly' => 27.0, 'annually' => 90.0],
            ],
            'GPU' => [
                'gtx-1060' => ['monthly' => 20.0, 'quarterly' => 54.0, 'annually' => 180.0],
                'rtx-3080' => ['monthly' => 50.0, 'quarterly' => 135.0, 'annually' => 450.0],
                'tesla-v100' => ['monthly' => 100.0, 'quarterly' => 270.0, 'annually' => 900.0],
                'radeon-pro' => ['monthly' => 30.0, 'quarterly' => 81.0, 'annually' => 270.0],
            ],
            'Firewall' => [
                'basic' => ['monthly' => 1.0, 'quarterly' => 2.7, 'annually' => 9.0],
                'advanced' => ['monthly' => 3.0, 'quarterly' => 8.1, 'annually' => 27.0],
                'enterprise' => ['monthly' => 10.0, 'quarterly' => 27.0, 'annually' => 90.0],
            ],
            'Backup' => [
                'daily' => ['monthly' => 2.0, 'quarterly' => 5.4, 'annually' => 18.0],
                'weekly' => ['monthly' => 1.0, 'quarterly' => 2.7, 'annually' => 9.0],
                'realtime' => ['monthly' => 5.0, 'quarterly' => 13.5, 'annually' => 45.0],
                'monthly' => ['monthly' => 0.5, 'quarterly' => 1.35, 'annually' => 4.5],
            ],
            'Support' => [
                'basic' => ['monthly' => 0.0, 'quarterly' => 0.0, 'annually' => 0.0],
                'priority' => ['monthly' => 5.0, 'quarterly' => 13.5, 'annually' => 45.0],
                'enterprise' => ['monthly' => 15.0, 'quarterly' => 40.5, 'annually' => 135.0],
                'premium' => ['monthly' => 25.0, 'quarterly' => 67.5, 'annually' => 225.0],
            ],
        ];

        // Default multipliers for OS and Location (usually free or low cost)
        $defaultMultipliers = ['monthly' => 0.0, 'quarterly' => 0.0, 'annually' => 0.0];

        if ($groupKey === 'OS' || $groupKey === 'Location') {
            return $defaultMultipliers;
        }

        return $multipliers[$groupKey][$numericValue] ?? ['monthly' => 1.0, 'quarterly' => 2.7, 'annually' => 9.0];
    }

    /**
     * Get base price for period
     */
    private function getBasePriceForPeriod(string $period): float
    {
        return match ($period) {
            'monthly' => 10.0,
            'quarterly' => 27.0,
            'annually' => 90.0,
            default => 10.0,
        };
    }

    /**
     * Extract numeric value from option key for pricing calculations
     */
    private function extractNumericValue(string $optionKey, string $groupKey): string
    {
        switch ($groupKey) {
            case 'RAM':
                // Extract from "2GiB", "ram_2gb", etc.
                if (preg_match('/(\d+)/', $optionKey, $matches)) {
                    return $matches[1];
                }
                break;
            case 'CPU':
                // Extract from "3 Cores", "cpu_4cores", etc.
                if (preg_match('/(\d+)/', $optionKey, $matches)) {
                    return $matches[1];
                }
                break;
            case 'Disk':
                // Extract from "40GB SSD", "disk_100gb", etc.
                if (preg_match('/(\d+)/', $optionKey, $matches)) {
                    return $matches[1];
                }
                break;
            case 'GPU':
                // Map GPU names to numeric values
                $gpuMap = [
                    '1 vGPU' => 'gtx-1060',
                    '2 vGPU' => 'rtx-3080',
                    '3 vGPU' => 'tesla-v100',
                ];

                return $gpuMap[$optionKey] ?? $optionKey;
            case 'Firewall':
                // Map firewall levels to categories
                $firewallMap = [
                    'Firewall level 1' => 'basic',
                    'Firewall level 2' => 'advanced',
                    'Firewall level 3' => 'enterprise',
                ];

                return $firewallMap[$optionKey] ?? 'basic';
            case 'Backup':
                // Map backup types to categories
                $backupMap = [
                    '1 daily snapshot' => 'daily',
                    '2 daily snapshots' => 'weekly',
                    '3 daily snapshots' => 'realtime',
                ];

                return $backupMap[$optionKey] ?? 'daily';
            default:
                return $optionKey;
        }

        return $optionKey;
    }

    /**
     * Ensure all existing options have prices
     */
    private function ensureOptionPricesExist(): void
    {
        $optionsWithoutPrices = ProductOption::whereDoesntHave('prices')->get();

        foreach ($optionsWithoutPrices as $option) {
            echo "[DEMO SEED] Adding prices to existing option '{$option->key}'\n";
            $this->createPricesForOption($option);
            $this->updateOptionTranslations($option);
        }
    }

    /**
     * Update translations for existing options
     */
    private function updateOptionTranslations(ProductOption $option): void
    {
        // Check if option already has translations
        if ($option->name || $option->short_description || $option->description) {
            return; // Already has translations
        }

        $optionGroup = $option->productOptionGroup;
        $groupKey = $optionGroup ? $optionGroup->key : 'general';

        $optionData = $this->generateOptionData($groupKey, $option->key, $option->value);

        if (! empty($optionData)) {
            $option->name = $optionData['name'];
            $option->short_description = $optionData['short_description'];
            $option->description = $optionData['description'];
            $option->save();

            echo "[DEMO SEED] Updated translations for option '{$option->key}'\n";
        }
    }

    /**
     * Generate option data for translations
     */
    private function generateOptionData(string $groupKey, string $optionKey, string $value): array
    {
        $optionTemplates = [
            'RAM' => [
                '1GiB' => ['name' => '1 GB RAM', 'short_description' => '1 GB Memory', 'description' => '1 GB of RAM memory allocation for basic applications and light workloads.'],
                '2GiB' => ['name' => '2 GB RAM', 'short_description' => '2 GB Memory', 'description' => '2 GB of RAM memory allocation for small to medium applications.'],
                '3GiB' => ['name' => '3 GB RAM', 'short_description' => '3 GB Memory', 'description' => '3 GB of RAM memory allocation for standard applications and development work.'],
                'ram_1gb' => ['name' => '1 GB RAM', 'short_description' => '1 GB Memory', 'description' => '1 GB of RAM memory allocation for basic applications and light workloads.'],
                'ram_2gb' => ['name' => '2 GB RAM', 'short_description' => '2 GB Memory', 'description' => '2 GB of RAM memory allocation for small to medium applications.'],
                'ram_4gb' => ['name' => '4 GB RAM', 'short_description' => '4 GB Memory', 'description' => '4 GB of RAM memory allocation for standard applications and development work.'],
                'ram_8gb' => ['name' => '8 GB RAM', 'short_description' => '8 GB Memory', 'description' => '8 GB of RAM memory allocation for demanding applications and multitasking.'],
                'ram_16gb' => ['name' => '16 GB RAM', 'short_description' => '16 GB Memory', 'description' => '16 GB of RAM memory allocation for high-performance applications and heavy workloads.'],
                'ram_32gb' => ['name' => '32 GB RAM', 'short_description' => '32 GB Memory', 'description' => '32 GB of RAM memory allocation for enterprise applications and intensive computing tasks.'],
            ],
            'CPU' => [
                '1 Core' => ['name' => '1 Core', 'short_description' => 'Single core', 'description' => 'Single CPU core for light applications and basic computing tasks.'],
                '2 Cores' => ['name' => '2 Cores', 'short_description' => 'Dual core', 'description' => '2 CPU cores for improved performance and parallel processing capabilities.'],
                '3 Cores' => ['name' => '3 Cores', 'short_description' => 'Triple core', 'description' => '3 CPU cores for enhanced performance and parallel processing capabilities.'],
                'cpu_1core' => ['name' => '1 Core', 'short_description' => 'Single core', 'description' => 'Single CPU core for light applications and basic computing tasks.'],
                'cpu_2cores' => ['name' => '2 Cores', 'short_description' => 'Dual core', 'description' => '2 CPU cores for improved performance and parallel processing capabilities.'],
                'cpu_4cores' => ['name' => '4 Cores', 'short_description' => 'Quad core', 'description' => '4 CPU cores for demanding applications and multi-threaded workloads.'],
                'cpu_8cores' => ['name' => '8 Cores', 'short_description' => 'Octa core', 'description' => '8 CPU cores for high-performance computing and intensive applications.'],
                'cpu_16cores' => ['name' => '16 Cores', 'short_description' => 'Sixteen cores', 'description' => '16 CPU cores for enterprise-grade performance and maximum processing power.'],
            ],
            'Disk' => [
                '20GB SSD' => ['name' => '20 GB SSD', 'short_description' => '20 GB SSD', 'description' => '20 GB of high-speed SSD storage for basic file storage and small applications.'],
                '40GB SSD' => ['name' => '40 GB SSD', 'short_description' => '40 GB SSD', 'description' => '40 GB of high-speed SSD storage for moderate data storage needs.'],
                '60GB SSD' => ['name' => '60 GB SSD', 'short_description' => '60 GB SSD', 'description' => '60 GB of high-speed SSD storage for standard applications and data.'],
                'disk_20gb' => ['name' => '20 GB SSD', 'short_description' => '20 GB SSD', 'description' => '20 GB of high-speed SSD storage for basic file storage and small applications.'],
                'disk_50gb' => ['name' => '50 GB SSD', 'short_description' => '50 GB SSD', 'description' => '50 GB of high-speed SSD storage for moderate data storage needs.'],
                'disk_100gb' => ['name' => '100 GB SSD', 'short_description' => '100 GB SSD', 'description' => '100 GB of high-speed SSD storage for standard applications and data.'],
                'disk_200gb' => ['name' => '200 GB SSD', 'short_description' => '200 GB SSD', 'description' => '200 GB of high-speed SSD storage for larger applications and databases.'],
                'disk_500gb' => ['name' => '500 GB SSD', 'short_description' => '500 GB SSD', 'description' => '500 GB of high-speed SSD storage for extensive data storage requirements.'],
                'disk_1tb' => ['name' => '1 TB SSD', 'short_description' => '1 TB SSD', 'description' => '1 TB of high-speed SSD storage for enterprise applications and large datasets.'],
            ],
            'OS' => [
                'Ubuntu 22.04' => ['name' => 'Ubuntu 22.04', 'short_description' => 'Ubuntu Linux', 'description' => 'Ubuntu 22.04 LTS - Popular, user-friendly Linux distribution with long-term support.'],
                'Debian 12' => ['name' => 'Debian 12', 'short_description' => 'Debian Linux', 'description' => 'Debian 12 - Stable and reliable Linux distribution known for its robustness.'],
                'CentOS 9' => ['name' => 'CentOS 9', 'short_description' => 'CentOS Linux', 'description' => 'CentOS 9 - Enterprise-class Linux distribution based on Red Hat Enterprise Linux.'],
                'ubuntu_22_04' => ['name' => 'Ubuntu 22.04', 'short_description' => 'Ubuntu Linux', 'description' => 'Ubuntu 22.04 LTS - Popular, user-friendly Linux distribution with long-term support.'],
                'centos_8' => ['name' => 'CentOS 9', 'short_description' => 'CentOS Linux', 'description' => 'CentOS 9 - Enterprise-class Linux distribution based on Red Hat Enterprise Linux.'],
                'debian_11' => ['name' => 'Debian 12', 'short_description' => 'Debian Linux', 'description' => 'Debian 12 - Stable and reliable Linux distribution known for its robustness.'],
                'windows_server_2022' => ['name' => 'Windows Server 2022', 'short_description' => 'Windows Server', 'description' => 'Windows Server 2022 - Latest Microsoft server operating system with advanced features.'],
                'rocky_linux_9' => ['name' => 'Rocky Linux 9', 'short_description' => 'Rocky Linux', 'description' => 'Rocky Linux 9 - Enterprise Linux distribution compatible with Red Hat Enterprise Linux.'],
            ],
            'Location' => [
                'Frankfurt' => ['name' => 'Frankfurt', 'short_description' => 'Europe', 'description' => 'Data center located in Frankfurt, Germany for optimal performance in European markets.'],
                'Warsaw' => ['name' => 'Warsaw', 'short_description' => 'Poland', 'description' => 'Data center located in Warsaw, Poland for optimal performance in Central European markets.'],
                'Toronto' => ['name' => 'Toronto', 'short_description' => 'Canada', 'description' => 'Data center located in Toronto, Canada for optimal performance in North American markets.'],
                'us_east' => ['name' => 'US East (Virginia)', 'short_description' => 'US East Coast', 'description' => 'Data center located on the US East Coast for optimal performance in North America.'],
                'us_west' => ['name' => 'US West (California)', 'short_description' => 'US West Coast', 'description' => 'Data center located on the US West Coast for optimal performance in the Pacific region.'],
                'eu_central' => ['name' => 'EU Central (Frankfurt)', 'short_description' => 'Central Europe', 'description' => 'Data center located in Central Europe for optimal performance in European markets.'],
                'asia_pacific' => ['name' => 'Asia Pacific (Singapore)', 'short_description' => 'Asia Pacific', 'description' => 'Data center located in Asia Pacific region for optimal performance in Asian markets.'],
                'canada_central' => ['name' => 'Canada Central (Toronto)', 'short_description' => 'Central Canada', 'description' => 'Data center located in Central Canada for optimal performance in Canadian markets.'],
            ],
            'GPU' => [
                '1 vGPU' => ['name' => '1 vGPU', 'short_description' => 'Virtual GPU', 'description' => '1 virtual GPU unit for graphics acceleration and compute workloads.'],
                '2 vGPU' => ['name' => '2 vGPU', 'short_description' => 'Virtual GPU', 'description' => '2 virtual GPU units for enhanced graphics acceleration and parallel compute tasks.'],
                '3 vGPU' => ['name' => '3 vGPU', 'short_description' => 'Virtual GPU', 'description' => '3 virtual GPU units for high-performance computing and intensive graphics workloads.'],
                'nvidia_gtx_1060' => ['name' => 'NVIDIA GTX 1060', 'short_description' => 'Mid-range GPU', 'description' => 'NVIDIA GTX 1060 for moderate graphics acceleration and compute workloads.'],
                'nvidia_rtx_3080' => ['name' => 'NVIDIA RTX 3080', 'short_description' => 'High-end GPU', 'description' => 'NVIDIA RTX 3080 for enhanced graphics acceleration and parallel compute tasks.'],
                'nvidia_tesla_v100' => ['name' => 'NVIDIA Tesla V100', 'short_description' => 'Enterprise GPU', 'description' => 'NVIDIA Tesla V100 for high-performance computing and intensive graphics workloads.'],
            ],
            'Firewall' => [
                'Firewall level 1' => ['name' => 'Firewall level 1', 'short_description' => 'Basic firewall', 'description' => 'Basic firewall protection with standard security rules and monitoring.'],
                'Firewall level 2' => ['name' => 'Firewall level 2', 'short_description' => 'Advanced firewall', 'description' => 'Advanced firewall protection with enhanced security features and threat detection.'],
                'Firewall level 3' => ['name' => 'Firewall level 3', 'short_description' => 'Enterprise firewall', 'description' => 'Enterprise-grade firewall protection with comprehensive security features and advanced threat prevention.'],
                'basic_firewall' => ['name' => 'Firewall level 1', 'short_description' => 'Basic firewall', 'description' => 'Basic firewall protection with standard security rules and monitoring.'],
                'advanced_firewall' => ['name' => 'Firewall level 2', 'short_description' => 'Advanced firewall', 'description' => 'Advanced firewall protection with enhanced security features and threat detection.'],
                'enterprise_firewall' => ['name' => 'Firewall level 3', 'short_description' => 'Enterprise firewall', 'description' => 'Enterprise-grade firewall protection with comprehensive security features and advanced threat prevention.'],
            ],
            'Backup' => [
                '1 daily snapshot' => ['name' => '1 daily snapshot', 'short_description' => 'Daily backup', 'description' => 'Daily automated backup with 1 snapshot retention for data protection.'],
                '2 daily snapshots' => ['name' => '2 daily snapshots', 'short_description' => 'Backup twice daily', 'description' => '2 daily automated backups with extended snapshot retention for enhanced data protection.'],
                '3 daily snapshots' => ['name' => '3 daily snapshots', 'short_description' => 'Multiple daily backups', 'description' => '3 daily automated backups with comprehensive snapshot retention for maximum data protection.'],
                'daily_backup' => ['name' => '1 daily snapshot', 'short_description' => 'Daily backup', 'description' => 'Daily automated backup with 1 snapshot retention for data protection.'],
                'weekly_backup' => ['name' => '2 daily snapshots', 'short_description' => 'Backup twice daily', 'description' => '2 daily automated backups with extended snapshot retention for enhanced data protection.'],
                'realtime_backup' => ['name' => '3 daily snapshots', 'short_description' => 'Multiple daily backups', 'description' => '3 daily automated backups with comprehensive snapshot retention for maximum data protection.'],
            ],
        ];

        $groupTemplates = $optionTemplates[$groupKey] ?? [];

        // Try to find by exact key match first
        if (isset($groupTemplates[$optionKey])) {
            return $groupTemplates[$optionKey];
        }

        // Try to find by value match for cases where keys don't match exactly
        foreach ($groupTemplates as $template) {
            if ($template['name'] === $value || str_contains($template['name'], $value)) {
                return $template;
            }
        }

        // Default fallback
        return [
            'name' => ucwords(str_replace('_', ' ', $value)),
            'short_description' => 'Service option',
            'description' => 'Additional service configuration option with value: '.$value,
        ];
    }

    /**
     * Generate prices for product using PriceFactory
     */
    private function generatePricesUsingFactory(Product $product): void
    {
        $periods = ['monthly', 'quarterly', 'annually'];
        $periodsForProduct = collect($periods)->random(rand(1, count($periods)))->all();

        foreach ($periodsForProduct as $period) {
            try {
                $price = \App\Models\Price::factory()
                    ->period($period)
                    ->create();

                // Use direct SQL insert instead of attach() to avoid Eloquent issues
                DB::table('product_x_price')->insert([
                    'product_uuid' => $product->uuid,
                    'price_uuid' => $price->uuid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                echo "[DEMO SEED] Created {$period} price: {$price->base} for product '{$product->key}'\n";
            } catch (\Exception $e) {
                Log::error("Error creating price for product {$product->key}: ".$e->getMessage());
                echo "[DEMO SEED][ERROR] Failed to create price for product '{$product->key}': ".$e->getMessage()."\n";
            }
        }
    }

    /**
     * Update translations for all existing option groups
     */
    private function updateAllOptionGroupTranslations(): void
    {
        $optionGroups = ProductOptionGroup::all();

        foreach ($optionGroups as $optionGroup) {
            $this->updateOptionGroupTranslations($optionGroup);
        }
    }

    /**
     * Update translations for all existing options
     */
    private function updateAllOptionTranslations(): void
    {
        $options = ProductOption::all();

        foreach ($options as $option) {
            $this->updateOptionTranslations($option);
        }
    }

    /**
     * Update translations for existing option group if missing
     */
    private function updateOptionGroupTranslations(ProductOptionGroup $optionGroup): void
    {
        // Check if option group already has translations
        if ($optionGroup->name || $optionGroup->short_description || $optionGroup->description) {
            return; // Already has translations
        }

        $groupKey = $optionGroup->key;

        $optionGroupData = $this->generateOptionGroupData($groupKey);

        if (! empty($optionGroupData)) {
            $optionGroup->name = $optionGroupData['name'];
            $optionGroup->short_description = $optionGroupData['short_description'];
            $optionGroup->description = $optionGroupData['description'];
            $optionGroup->save();

            echo "[DEMO SEED] Updated translations for option group '{$optionGroup->key}'\n";
        }
    }

    /**
     * Generate option group data for translations
     */
    private function generateOptionGroupData(string $groupKey): array
    {
        $optionGroupTemplates = [
            'RAM' => [
                'name' => 'Memory (RAM)',
                'short_description' => 'System memory allocation',
                'description' => 'Choose the amount of RAM memory for your service. More memory allows for better performance and handling of concurrent processes.',
            ],
            'CPU' => [
                'name' => 'CPU Cores',
                'short_description' => 'Processor cores',
                'description' => 'Select the number of CPU cores for your service. More cores provide better performance for multi-threaded applications.',
            ],
            'Disk' => [
                'name' => 'Storage Space',
                'short_description' => 'Disk storage capacity',
                'description' => 'Choose the amount of disk space for your files, applications, and data storage needs.',
            ],
            'OS' => [
                'name' => 'Operating System',
                'short_description' => 'Server operating system',
                'description' => 'Select the operating system that will be installed on your server. Different OS options provide different features and compatibility.',
            ],
            'Location' => [
                'name' => 'Data Center Location',
                'short_description' => 'Server location',
                'description' => 'Choose the geographic location of your server. Closer locations typically provide better latency for your users.',
            ],
            'GPU' => [
                'name' => 'Graphics Processing Unit',
                'short_description' => 'GPU acceleration',
                'description' => 'Add GPU acceleration for compute-intensive tasks like machine learning, rendering, or scientific computing.',
            ],
            'Firewall' => [
                'name' => 'Firewall Protection',
                'short_description' => 'Network security',
                'description' => 'Enable advanced firewall protection to secure your server against unauthorized access and malicious traffic.',
            ],
            'Backup' => [
                'name' => 'Backup Service',
                'short_description' => 'Data backup options',
                'description' => 'Choose backup frequency and retention options to protect your data against loss or corruption.',
            ],
            'Bandwidth' => [
                'name' => 'Network Bandwidth',
                'short_description' => 'Network speed',
                'description' => 'Select the network bandwidth allocation for your service, affecting data transfer speeds and capacity.',
            ],
            'Support' => [
                'name' => 'Support Level',
                'short_description' => 'Technical support tier',
                'description' => 'Choose your support level for different response times and support channel access.',
            ],
        ];

        return $optionGroupTemplates[$groupKey] ?? [
            'name' => ucwords($groupKey),
            'short_description' => 'Service option',
            'description' => 'Additional service configuration option for your product.',
        ];
    }
}
