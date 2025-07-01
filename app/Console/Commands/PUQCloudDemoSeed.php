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

use Database\Seeders\DemoDataSeederClients;
use Database\Seeders\DemoDataSeederProducts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PUQCloudDemoSeed extends Command
{
    // Command signature with separate options for products and clients
    protected $signature = 'puqcloud:demo_seed
                            {--products= : Number of products to create (0-10000)}
                            {--clients= : Number of clients to create (0-50000)}
                            {--products_group= : Number of product groups to create (0-50)}';

    // Description of the command
    protected $description = 'Generate realistic demo data for PUQCloud hosting platform with products, clients, and product groups';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Get options
        $productCount = $this->option('products');
        $clientCount = $this->option('clients');
        $groupCount = $this->option('products_group');

        // Validate input - at least one option must be provided
        if (! $productCount && ! $clientCount && ! $groupCount) {
            $this->error('You must specify at least one option: --products, --clients or --products_group');
            $this->info('');
            $this->info('<fg=yellow>🚀 PUQCloud Demo Data Generator</fg=yellow>');
            $this->info('');
            $this->info('<fg=cyan>This command generates realistic demo data for your hosting platform:</fg=cyan>');
            $this->info('• <fg=green>Products</fg=green>: VPS, Hosting, Storage services with pricing, options, and translations');
            $this->info('• <fg=green>Clients</fg=green>: Unique customers with realistic contact data and billing information');
            $this->info('• <fg=green>Product Groups</fg=green>: Organized categories like VPS, Hosting, Storage, etc.');
            $this->info('• <fg=green>Options & Pricing</fg=green>: RAM, CPU, Disk configurations with multi-period pricing');
            $this->info('• <fg=green>Translations</fg=green>: Multilingual support for all generated content');
            $this->info('');
            $this->info('<fg=yellow>📋 Usage Examples:</fg=yellow>');
            $this->info('');
            $this->info('<fg=white>Basic usage (single component):</fg=white>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --products=100</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --clients=500</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --products_group=5</fg=green>');
            $this->info('');
            $this->info('<fg=white>Combined usage (recommended):</fg=white>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --products=100 --products_group=5 --clients=50</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --products=500 --products_group=10</fg=green>');
            $this->info('');
            $this->info('<fg=white>Performance testing:</fg=white>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --products=1000 --clients=5000</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --clients=10000</fg=green>');
            $this->info('');
            $this->info('<fg=yellow>💡 Tips:</fg=yellow>');
            $this->info('• Product groups should be created first (automatically handled)');
            $this->info('• Products automatically get option groups, pricing, and translations');
            $this->info('• Client data includes unique emails, company names, and tax IDs');
            $this->info('• Safe to run multiple times - creates new data without duplicates');
            $this->info('');
            $this->info('<fg=yellow>⚡ Generated Data Includes:</fg=yellow>');
            $this->info('• Product attribute groups (CPU, Memory, Storage, Network, Security, Backup)');
            $this->info('• Product option groups (RAM, CPU, Disk, OS, Location, GPU, Firewall, Backup)');
            $this->info('• Realistic hosting product names and configurations');
            $this->info('• Multi-period pricing (monthly, quarterly, annually)');
            $this->info('• Complete translations for all components');
            $this->info('• Client companies from various industries');
            $this->info('• Proper relationships between all entities');
            $this->info('');

            return 1;
        }

        $productCount = $productCount ? (int) $productCount : 0;
        $clientCount = $clientCount ? (int) $clientCount : 0;
        $groupCount = $groupCount ? (int) $groupCount : 0;

        if ($productCount < 0 || $clientCount < 0 || $groupCount < 0) {
            $this->error('Counts must be positive numbers');

            return 1;
        }

        $this->info('=== Starting Demo Data Seeding ===');

        // 1. Создание групп атрибутов
        $this->info('Creating attribute groups...');
        $attributeGroups = $this->createProductAttributeGroups();

        // 2. Создание групп продуктов
        if ($groupCount > 0) {
            $this->info('Creating product groups...');
            $productGroups = $this->createProductGroups($groupCount);
        }

        // 3. Создание опций
        $optionGroups = $this->createProductOptionGroupsAndOptions();

        // 4. Создание продуктов
        if ($productCount > 0) {
            $this->info('Creating products...');
            $seeder = new DemoDataSeederProducts;
            $seeder->run($productCount);
        }

        // 5. Создание клиентов
        if ($clientCount > 0) {
            $this->info('Creating clients...');
            $this->createClients($clientCount);
        }

        $this->info('=== Demo Data Seeding Complete ===');
        if ($groupCount > 0) {
            $this->info("Product groups created: $groupCount");
        }
        if ($productCount > 0) {
            $this->info("Products created: $productCount");
        }
        if ($clientCount > 0) {
            $this->info("Clients created: $clientCount");
        }

        return 0;
    }

    /**
     * Create product attribute groups and attributes with unique keys and human-readable data
     */
    private function createProductAttributeGroups(int $count = 6): array
    {
        $this->info('=== Creating Product Attribute Groups ===');

        // Check if attribute groups already exist
        $existingGroups = \App\Models\ProductAttributeGroup::with('productAttributes')->get();
        if ($existingGroups->count() > 0) {
            $this->info('Product attribute groups already exist: '.$existingGroups->count());

            return $existingGroups->toArray();
        }

        $groupTemplates = [
            ['key' => 'cpu', 'name' => 'CPU', 'short_description' => 'CPU attributes', 'description' => 'Attributes for CPU', 'attributes' => [
                ['key' => 'frequency', 'name' => 'Frequency', 'value' => '3.2 GHz'],
                ['key' => 'cores', 'name' => 'Cores', 'value' => '4'],
                ['key' => 'type', 'name' => 'Type', 'value' => 'Intel Xeon'],
            ]],
            ['key' => 'memory', 'name' => 'Memory', 'short_description' => 'Memory attributes', 'description' => 'Attributes for Memory', 'attributes' => [
                ['key' => 'size', 'name' => 'Size', 'value' => '16 GB'],
                ['key' => 'type', 'name' => 'Type', 'value' => 'DDR4'],
            ]],
            ['key' => 'storage', 'name' => 'Storage', 'short_description' => 'Storage attributes', 'description' => 'Attributes for Storage', 'attributes' => [
                ['key' => 'type', 'name' => 'Type', 'value' => 'NVMe SSD'],
                ['key' => 'capacity', 'name' => 'Capacity', 'value' => '512 GB'],
            ]],
            ['key' => 'network', 'name' => 'Network', 'short_description' => 'Network attributes', 'description' => 'Attributes for Network', 'attributes' => [
                ['key' => 'bandwidth', 'name' => 'Bandwidth', 'value' => '1 Gbps'],
                ['key' => 'ipv4', 'name' => 'IPv4', 'value' => '1 included'],
            ]],
            ['key' => 'security', 'name' => 'Security', 'short_description' => 'Security attributes', 'description' => 'Attributes for Security', 'attributes' => [
                ['key' => 'ddos_protection', 'name' => 'DDoS Protection', 'value' => 'Enabled'],
            ]],
            ['key' => 'backup', 'name' => 'Backup', 'short_description' => 'Backup attributes', 'description' => 'Attributes for Backup', 'attributes' => [
                ['key' => 'frequency', 'name' => 'Frequency', 'value' => 'Daily'],
            ]],
        ];
        $groups = [];
        $usedAttributeKeys = [];
        foreach ($groupTemplates as $template) {
            try {
                $group = new \App\Models\ProductAttributeGroup;
                $group->key = $template['key'];
                $group->hidden = false;
                $group->notes = 'Demo attribute group';
                $group->save();
                $group->name = $template['name'];
                $group->short_description = $template['short_description'];
                $group->description = $template['description'];
                $group->save();
                $groups[] = $group;
                $order = 1;
                foreach ($template['attributes'] as $attr) {
                    $baseKey = $attr['key'];
                    $key = $baseKey;
                    $counter = 1;
                    while (in_array($key, $usedAttributeKeys)) {
                        $key = $baseKey.'_'.$counter;
                        $counter++;
                    }
                    $usedAttributeKeys[] = $key;
                    $attribute = new \App\Models\ProductAttribute;
                    $attribute->product_attribute_group_uuid = $group->uuid;
                    $attribute->key = $key;
                    $attribute->hidden = false;
                    $attribute->order = $order++;
                    $attribute->notes = 'Demo attribute';
                    $attribute->save();
                    $attribute->name = $attr['name'];
                    $attribute->value = $attr['value'];
                    $attribute->save();
                }
            } catch (\Exception $e) {
                Log::error("Error creating attribute group {$template['key']}: ".$e->getMessage());

                continue;
            }
        }
        $this->info('Product attribute groups created: '.count($groups));

        return $groups;
    }

    /**
     * Create product groups with unique keys and human-readable data
     */
    private function createProductGroups(int $count): array
    {
        $this->info('=== Creating Product Groups ===');
        $groupTemplates = ['VPS', 'Hosting', 'Storage', 'License', 'Backup', 'Firewall', 'SSL', 'Email', 'Database', 'Monitoring'];
        $groups = [];
        $existingGroups = \App\Models\ProductGroup::all()->pluck('key')->toArray();
        for ($i = 0; $i < $count; $i++) {
            try {
                $template = $groupTemplates[$i % count($groupTemplates)];
                $baseKey = strtolower(str_replace(' ', '_', $template));
                $key = $baseKey;
                $counter = 1;
                while (in_array($key, $existingGroups)) {
                    $key = $baseKey.'_'.$counter;
                    $counter++;
                }
                $name = $template.($i >= count($groupTemplates) ? ' '.($i + 1) : '');
                $group = new \App\Models\ProductGroup;
                $group->key = $key;
                $group->order = $i + 1;
                $group->hidden = false;
                $group->notes = 'Demo group';
                $group->save();
                $group->name = $name;
                $group->short_description = $name.' group';
                $group->description = 'Group for '.$name.' products';
                $group->save();
                $groups[] = $group;
                $existingGroups[] = $key;
            } catch (\Exception $e) {
                Log::error('Error creating product group: '.$e->getMessage());

                continue;
            }
        }
        $this->info('Product groups created: '.count($groups));

        return $groups;
    }

    /**
     * Create products and assign them to groups and attributes
     */
    private function createProducts(int $count, array $productGroups, array $attributeGroups, array $optionGroups): array
    {
        $this->info('=== Creating Products ===');
        $products = [];
        $productTemplates = [
            'VPS' => [
                'prefixes' => ['Basic', 'Standard', 'Pro', 'Enterprise', 'Ultimate'],
                'attributes' => ['cpu', 'memory', 'storage', 'network', 'security', 'backup'],
                'price_range' => [1000, 10000],
                'descriptions' => [
                    'Basic' => 'Entry-level VPS with balanced resources for small projects',
                    'Standard' => 'Mid-range VPS with enhanced performance for growing applications',
                    'Pro' => 'High-performance VPS for demanding workloads',
                    'Enterprise' => 'Enterprise-grade VPS with maximum resources and priority support',
                    'Ultimate' => 'Top-tier VPS with dedicated resources and premium features',
                ],
            ],
            'Hosting' => [
                'prefixes' => ['Starter', 'Business', 'Professional', 'Premium', 'Enterprise'],
                'attributes' => ['storage', 'network', 'security', 'backup'],
                'price_range' => [500, 5000],
                'descriptions' => [
                    'Starter' => 'Perfect for personal websites and small projects',
                    'Business' => 'Ideal for small business websites and online stores',
                    'Professional' => 'Advanced hosting for growing businesses',
                    'Premium' => 'High-performance hosting for demanding applications',
                    'Enterprise' => 'Enterprise-grade hosting with maximum resources',
                ],
            ],
            'Storage' => ['Storage 100GB', 'Storage 1TB'],
            'License' => ['cPanel License', 'Plesk License'],
            'Backup' => ['Backup Daily', 'Backup Weekly'],
            'Firewall' => ['Firewall Basic', 'Firewall Advanced'],
            'SSL' => ['SSL Standard', 'SSL Wildcard'],
            'Email' => ['Email Basic', 'Email Pro'],
            'Database' => ['DB MySQL', 'DB PostgreSQL'],
            'Monitoring' => ['Monitoring Basic', 'Monitoring Pro'],
        ];
        $groupCount = count($productGroups);
        for ($i = 0; $i < $count; $i++) {
            try {
                $group = $productGroups[$i % $groupCount];
                $groupName = $group->name;
                $template = $productTemplates[$groupName] ?? $productTemplates['VPS'];
                $prefix = $template['prefixes'][array_rand($template['prefixes'])];
                $name = $groupName.' '.$prefix;
                $key = strtolower(str_replace(' ', '_', $name)).'_'.uniqid();
                $shortDescription = $template['descriptions'][$prefix] ?? ($name.' for demo');
                $description = ($template['descriptions'][$prefix] ?? $name).' This product is part of our '.$groupName.' solutions.';
                $product = new \App\Models\Product;
                $product->key = $key;
                $product->module_uuid = null;
                $product->hidden = false;
                $product->notes = 'Demo product';
                $product->save();
                $product->name = $name;
                $product->short_description = $shortDescription;
                $product->description = $description;
                $product->save();
                // Привязка к группе через публичный метод
                $group->addProduct($product);
                // Привязка атрибутов по ключу группы
                foreach ($template['attributes'] as $attributeGroupKey) {
                    $attributeGroup = null;
                    foreach ($attributeGroups as $ag) {
                        if ($ag->key === $attributeGroupKey) {
                            $attributeGroup = $ag;
                            break;
                        }
                    }
                    if ($attributeGroup) {
                        foreach ($attributeGroup->productAttributes as $attribute) {
                            $product->productAttributes()->attach($attribute->uuid);
                        }
                    }
                }
                // Привязка опций к продукту через публичный метод
                $groupToOptionKeys = [
                    'VPS' => ['RAM', 'CPU', 'Disk', 'OS', 'Location', 'GPU', 'Firewall', 'Backup'],
                    'Hosting' => ['Disk', 'OS', 'Location', 'Backup'],
                    'Storage' => ['Disk', 'Backup'],
                    'License' => [],
                    'Backup' => ['Backup'],
                    'Firewall' => ['Firewall'],
                    'SSL' => [],
                    'Email' => [],
                    'Database' => [],
                    'Monitoring' => [],
                ];
                $optionKeys = $groupToOptionKeys[$groupName] ?? [];
                foreach ($optionKeys as $optionGroupKey) {
                    foreach ($optionGroups as $optionGroup) {
                        if ($optionGroup->key === $optionGroupKey) {
                            $product->addProductOptionGroup($optionGroup);
                            foreach ($optionGroup->productOptions as $option) {
                                $product->productOptions()->attach($option->uuid);
                            }
                        }
                    }
                }
                // Генерация цен для продукта
                $this->createDemoPrices($product, $template['price_range']);
                $products[] = $product;
            } catch (\Exception $e) {
                Log::error('Error creating product: '.$e->getMessage());

                continue;
            }
        }
        $this->info('Products created: '.count($products));

        return $products;
    }

    /**
     * Генерация цен для продукта (разные периоды)
     */
    private function createDemoPrices($product, $priceRange)
    {
        $periods = ['month', 'quarter', 'year'];
        foreach ($periods as $period) {
            try {
                $price = new \App\Models\Price;
                $price->type = 'default';
                $price->currency_uuid = null; // Можно доработать для реальных валют
                $price->period = $period;
                $price->setup = rand(0, 1000);
                $price->base = rand($priceRange[0], $priceRange[1]);
                $price->idle = 0;
                $price->switch_down = 0;
                $price->switch_up = 0;
                $price->uninstall = 0;
                $price->save();
                $product->prices()->attach($price->uuid);
            } catch (\Exception $e) {
                Log::error("Error creating price for product {$product->key}: ".$e->getMessage());

                continue;
            }
        }
    }

    /**
     * Create demo clients
     */
    private function createClients(int $count): void
    {
        $this->info('=== Creating Clients ===');

        // Create an instance of the DemoDataSeederClients seeder
        $seeder = new DemoDataSeederClients;
        // Run the seeder with the specified count
        $seeder->run($count);
        // Output information about the seeder execution
        $this->info("DemoDataSeederClients has been run ($count pcs.)");
    }

    private function createProductOptionGroupsAndOptions(int $optionCount = 3): array
    {
        $this->info('=== Creating Product Option Groups & Options ===');
        $groupKeys = ['RAM', 'CPU', 'Disk', 'OS', 'Location', 'GPU', 'Firewall', 'Backup'];
        $createdGroups = [];
        foreach ($groupKeys as $groupKey) {
            $group = \App\Models\ProductOptionGroup::firstOrCreate(
                ['key' => $groupKey],
                ['convert_price' => $groupKey !== 'OS']
            );
            $createdGroups[] = $group;
            $existingOptions = \App\Models\ProductOption::where('product_option_group_uuid', $group->uuid)
                ->pluck('key')
                ->toArray();
            for ($i = 1; $i <= $optionCount; $i++) {
                $optionKey = $this->generateOptionKey($groupKey, $i);
                if (in_array($optionKey, $existingOptions)) {
                    continue;
                }
                $option = new \App\Models\ProductOption;
                $option->product_option_group_uuid = $group->uuid;
                $option->key = $optionKey;
                $option->value = $this->generateOptionValue($groupKey, $i);
                $option->order = $i;
                $option->save();
                // Генерируем цену для опции, если нужно
                if ($group->convert_price) {
                    $price = new \App\Models\Price;
                    $price->currency_uuid = null;
                    $price->period = 'monthly';
                    $price->base = $this->generateOptionPrice($groupKey);
                    $price->save();
                    $option->prices()->attach($price->uuid);
                }
            }
        }
        $this->info('Product option groups created: '.count($createdGroups));

        return $createdGroups;
    }

    private function generateOptionKey(string $groupKey, int $i): string
    {
        switch ($groupKey) {
            case 'RAM':
                return "{$i}GiB";
            case 'CPU':
                return "{$i} Core".($i > 1 ? 's' : '');
            case 'Disk':
                return 20 * $i.'GB SSD';
            case 'OS':
                return match ($i) {
                    1 => 'Ubuntu 22.04',
                    2 => 'Debian 12',
                    3 => 'CentOS 9',
                    default => "Linux Distro {$i}"
                };
            case 'GPU':
                return "{$i} vGPU";
            case 'Location':
                return match ($i) {
                    1 => 'Frankfurt',
                    2 => 'Warsaw',
                    3 => 'Toronto',
                    default => "Location {$i}"
                };
            case 'Firewall':
                return "Firewall level {$i}";
            case 'Backup':
                return "{$i} daily snapshot".($i > 1 ? 's' : '');
            default:
                return "{$groupKey} Option {$i}";
        }
    }

    private function generateOptionValue(string $groupKey, int $i)
    {
        switch ($groupKey) {
            case 'RAM':
            case 'CPU':
            case 'GPU':
            case 'Disk':
                return $i;
            default:
                return strtolower(str_replace(' ', '_', $this->generateOptionKey($groupKey, $i)));
        }
    }

    private function generateOptionPrice(string $groupKey): int
    {
        switch ($groupKey) {
            case 'RAM':
            case 'CPU':
            case 'Disk':
            case 'GPU':
            case 'Backup':
                return rand(1, 10);
            default:
                return 0;
        }
    }
}
