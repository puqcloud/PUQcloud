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
                            {--products_group= : Number of product groups to create (0-50)}
                            {--payclient= : Create and pay N proforma invoices for random clients (default 1, max 100)}
                            {--deployservice= : Deploy N services for random clients (default 1, max 100)}
                            {--terminationservice= : Set termination request for N random services (default 1, max 100)}';

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
        $payClient = $this->option('payclient');
        $deployService = $this->option('deployservice');
        $terminationService = $this->option('terminationservice');

        // Validate input - at least one option must be provided
        if (! $productCount && ! $clientCount && ! $groupCount && ! $payClient && ! $deployService && ! $terminationService) {
            $this->error('You must specify at least one option: --products, --clients, --products_group, --payclient, --deployservice or --terminationservice');
            $this->info('');
            $this->info('<fg=yellow>🚀 PUQCloud Demo Data Generator</fg=yellow>');
            $this->info('');
            $this->info('<fg=cyan>This command generates realistic demo data for your hosting platform:</fg=cyan>');
            $this->info('• <fg=green>Products</fg=green>: VPS, Hosting, Storage services with pricing, options, and translations');
            $this->info('• <fg=green>Clients</fg=green>: Unique customers with realistic contact data and billing information');
            $this->info('• <fg=green>Product Groups</fg=green>: Organized categories like VPS, Hosting, Storage, etc.');
            $this->info('• <fg=green>Options & Pricing</fg=green>: RAM, CPU, Disk configurations with multi-period pricing');
            $this->info('• <fg=green>Translations</fg=green>: Multilingual support for all generated content');
            $this->info('• <fg=green>Payment Simulation</fg=green>: Create and pay proforma invoices for testing');
            $this->info('• <fg=green>Service Deployment</fg=green>: Deploy complete services with random configurations');
            $this->info('• <fg=green>Service Termination</fg=green>: Set termination requests for active services');
            $this->info('');
            $this->info('<fg=yellow>📋 Usage Examples:</fg=yellow>');
            $this->info('');
            $this->info('<fg=white>Basic usage (single component):</fg=white>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --products=100</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --clients=500</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --products_group=5</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --payclient=1</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --deployservice=1</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --terminationservice=1</fg=green>');
            $this->info('');
            $this->info('<fg=white>Combined usage (recommended):</fg=white>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --products=100 --products_group=5 --clients=50</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --products=500 --products_group=10</fg=green>');
            $this->info('');
            $this->info('<fg=white>Performance testing:</fg=white>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --products=1000 --clients=5000</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --clients=10000</fg=green>');
            $this->info('');
            $this->info('<fg=white>Payment & Service testing:</fg=white>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --payclient=10</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --deployservice=5</fg=green>');
            $this->info('  <fg=green>php artisan puqcloud:demo_seed --terminationservice=3</fg=green>');
            $this->info('');
            $this->info('<fg=yellow>💡 Tips:</fg=yellow>');
            $this->info('• Product groups should be created first (automatically handled)');
            $this->info('• Products automatically get option groups, pricing, and translations');
            $this->info('• Client data includes unique emails, company names, and tax IDs');
            $this->info('• Safe to run multiple times - creates new data without duplicates');
            $this->info('• --payclient=N creates and pays N proforma invoices for random clients (max 100)');
            $this->info('• --deployservice=N deploys N services for random clients with random configurations (max 100)');
            $this->info('• --terminationservice=N sets termination request for N random services (max 100)');
            $this->info('');
            $this->info('<fg=yellow>⚡ Generated Data Includes:</fg=yellow>');
            $this->info('• Product attribute groups (CPU, Memory, Storage, Network, Security, Backup)');
            $this->info('• Product option groups (RAM, CPU, Disk, OS, Location, GPU, Firewall, Backup)');
            $this->info('• Realistic hosting product names and configurations');
            $this->info('• Multi-period pricing (monthly, quarterly, annually)');
            $this->info('• Complete translations for all components');
            $this->info('• Client companies from various industries');
            $this->info('• Proper relationships between all entities');
            $this->info('• Payment simulation with bank transfer gateway');
            $this->info('• Service deployment with complete automation workflow');
            $this->info('• Service termination requests and lifecycle management');
            $this->info('');

            return 1;
        }

        $productCount = $productCount ? (int) $productCount : 0;
        $clientCount = $clientCount ? (int) $clientCount : 0;
        $groupCount = $groupCount ? (int) $groupCount : 0;
        $payClientCount = $payClient ? (int) $payClient : 0;
        $deployServiceCount = $deployService ? (int) $deployService : 0;
        $terminationServiceCount = $terminationService ? (int) $terminationService : 0;

        if ($productCount < 0 || $clientCount < 0 || $groupCount < 0 || $payClientCount < 0 || $deployServiceCount < 0 || $terminationServiceCount < 0) {
            $this->error('Counts must be positive numbers');
            return 1;
        }

        // Validate service counts limits
        if ($payClientCount > 100) {
            $this->error('Pay client count cannot exceed 100');
            return 1;
        }

        if ($deployServiceCount > 100) {
            $this->error('Deploy service count cannot exceed 100');
            return 1;
        }

        if ($terminationServiceCount > 100) {
            $this->error('Termination service count cannot exceed 100');
            return 1;
        }

        $this->info('=== Starting Demo Data Seeding ===');

        // Handle payment client simulation first if requested
        if ($payClientCount > 0) {
            $this->info("Processing payment client simulation ({$payClientCount} payments)...");
            $this->processPayClient($payClientCount);
        }

        // Handle service deployment if requested
        if ($deployServiceCount > 0) {
            $this->info("Processing service deployment ({$deployServiceCount} services)...");
            $this->processDeployService($deployServiceCount);
        }

        // Handle service termination if requested
        if ($terminationServiceCount > 0) {
            $this->info("Processing service termination ({$terminationServiceCount} services)...");
            $this->processTerminationService($terminationServiceCount);
        }

        // 1. Creating attribute groups
        if ($productCount > 0 || $groupCount > 0) {
            $this->info('Creating attribute groups...');
            $attributeGroups = $this->createProductAttributeGroups();
        }

        // 2. Creating product groups
        if ($groupCount > 0) {
            $this->info('Creating product groups...');
            $productGroups = $this->createProductGroups($groupCount);
        }

        // 3. Creating options
        if ($productCount > 0) {
            $optionGroups = $this->createProductOptionGroupsAndOptions();
        }

        // 4. Creating products
        if ($productCount > 0) {
            $this->info('Creating products...');
            $seeder = new DemoDataSeederProducts;
            $seeder->run($productCount);
        }

        // 5. Creating clients
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
        if ($payClientCount > 0) {
            $this->info("Payment client simulation completed: {$payClientCount} payments");
        }
        if ($deployServiceCount > 0) {
            $this->info("Service deployment completed: {$deployServiceCount} services");
        }
        if ($terminationServiceCount > 0) {
            $this->info("Service termination request completed: {$terminationServiceCount} services");
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
                // Attaching to group through public method
                $group->addProduct($product);
                // Attaching attributes by group key
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
                // Attaching options to product through public method
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
                // Generating prices for product
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
     * Generating prices for product (different periods)
     */
    private function createDemoPrices($product, $priceRange)
    {
        $periods = ['month', 'quarter', 'year'];
        foreach ($periods as $period) {
            try {
                $price = new \App\Models\Price;
                $price->type = 'default';
                $price->currency_uuid = null; // TODO: add real currency
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
                // Generating price for option if needed
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

    /**
     * Process payment client simulation
     */
    private function processPayClient(int $count = 1): void
    {
        $this->info('=== Payment Client Simulation ===');

        $successCount = 0;
        $errorCount = 0;

        // Get all active clients
        $activeClients = \App\Models\Client::where('status', 'active')->get();
        $clientsCount = $activeClients->count();

        if ($clientsCount === 0) {
            $this->error('No active clients found. Please create some clients first.');
            return;
        }

        $this->info("Found {$clientsCount} active clients");
        $this->info("Will create {$count} payments...");

        // Strategy: if we have enough unique clients, use different ones
        // Otherwise repeat clients as needed
        $useUniqueClients = $clientsCount >= $count;
        if ($useUniqueClients) {
            $this->info("Using different clients for each payment");
        } else {
            $this->info("Will repeat clients as needed (not enough unique clients)");
        }

        for ($i = 1; $i <= $count; $i++) {
            $this->info("Processing payment {$i}/{$count}...");

            try {
                // Select client based on strategy
                if ($useUniqueClients) {
                    // Use different clients for each payment
                    $clientIndex = ($i - 1) % $clientsCount;
                    $client = $activeClients[$clientIndex];
                } else {
                    // Not enough unique clients, select random client each time
                    $client = $activeClients->random();
                }

                $this->info("Found active client: {$client->firstname} {$client->lastname} ({$client->uuid})");

                // Generate random amount between 300 and 5000
                $amount = rand(300, 5000);
                $this->info("Creating proforma invoice for amount: {$amount}");

                // Create Add Funds Proforma Invoice
                $proformaUuid = $client->createInvoiceProformaAddFunds($amount);
                $this->info("Created proforma invoice: {$proformaUuid}");

                // Find the created proforma invoice
                $proforma = \App\Models\Invoice::find($proformaUuid);
                if (!$proforma) {
                    $this->error('Failed to find created proforma invoice');
                    $errorCount++;
                    continue;
                }

                $this->info("Proforma invoice total: {$proforma->total}");

                // Find bank transfer payment gateway
                $homeCompany = $proforma->homeCompany;
                $bankTransferGateway = $homeCompany->paymentGateways()
                    ->whereHas('module', function ($query) {
                        $query->where('name', 'puqBankTransfer');
                    })
                    ->first();

                if (!$bankTransferGateway) {
                    // Try to find by key as fallback
                    $bankTransferGateway = $homeCompany->paymentGateways()
                        ->where('key', 'like', '%Bank Transfer%')
                        ->first();
                }

                if (!$bankTransferGateway) {
                    $this->error('Bank Transfer payment gateway not found');
                    $errorCount++;
                    continue;
                }

                $this->info("Found bank transfer gateway: {$bankTransferGateway->name} ({$bankTransferGateway->uuid})");

                // Generate random transaction ID
                $transactionId = 'DEMO-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
                $description = 'automate';

                $this->info("Processing payment with transaction ID: {$transactionId}");

                // Add payment using the payment gateway
                $paymentResult = $proforma->addPaymentByPaymentGateway(
                    (float) $proforma->total, // amount
                    0.00, // fee
                    $transactionId, // transaction_id
                    $description, // description
                    $bankTransferGateway->uuid // payment_gateway_uuid
                );

                if ($paymentResult['status'] === 'success') {
                    $this->info('✅ Payment processed successfully!');
                    $this->info("Client balance updated");
                    $successCount++;

                    // Reload the proforma to check status
                    $proforma->refresh();
                    $this->info("Proforma status: {$proforma->status}");

                    if ($proforma->status === 'invoiced') {
                        $this->info("Regular invoice created: {$proforma->invoice_uuid}");
                    }
                } else {
                    $this->error('❌ Payment processing failed:');
                    foreach ($paymentResult['errors'] ?? ['Unknown error'] as $error) {
                        $this->error("  - {$error}");
                    }
                    $errorCount++;
                }

            } catch (\Exception $e) {
                $this->error("Error during payment client simulation: " . $e->getMessage());
                Log::error('Payment client simulation error: ' . $e->getMessage());
                $errorCount++;
            }
        }

        // Summary
        $this->info("=== Payment Summary ===");
        $this->info("Successfully processed: {$successCount}");
        $this->info("Failed payments: {$errorCount}");
        $this->info("Total attempted: {$count}");
    }

    /**
     * Process service deployment simulation
     */
    private function processDeployService(int $count = 1): void
    {
        $this->info('=== Service Deployment Simulation ===');

        $successCount = 0;
        $errorCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            $this->info("Deploying service {$i}/{$count}...");

            try {
                // Find random active client
                $client = \App\Models\Client::where('status', 'active')->inRandomOrder()->first();

                if (!$client) {
                    $this->error('No active clients found. Please create some clients first.');
                    $errorCount++;
                    continue;
                }

                $this->info("Found active client: {$client->firstname} {$client->lastname} ({$client->uuid})");

                // Find random product with prices available for client's currency
                $product = \App\Models\Product::whereHas('prices', function ($query) use ($client) {
                    $query->where('currency_uuid', $client->currency_uuid);
                })->with(['prices', 'productOptionGroups.productOptions.prices'])->inRandomOrder()->first();

                if (!$product) {
                    $this->error('No products with compatible pricing found for client currency.');
                    $errorCount++;
                    continue;
                }

                $this->info("Found product: {$product->name} ({$product->uuid})");

                // Get available prices for this product and client currency
                $availablePrices = $product->prices()->where('currency_uuid', $client->currency_uuid)->get();

                if ($availablePrices->isEmpty()) {
                    $this->error('No prices available for this product and client currency.');
                    $errorCount++;
                    continue;
                }

                // Select random price
                $selectedPrice = $availablePrices->random();
                $this->info("Selected price: {$selectedPrice->base} {$selectedPrice->currency->code} / {$selectedPrice->period}");

                // Select random options from available option groups
                $selectedOptions = [];
                $optionGroups = $product->productOptionGroups;

                foreach ($optionGroups as $group) {
                    $options = $group->productOptions;
                    if ($options->isNotEmpty()) {
                        // Filter options that have prices for the selected period and currency
                        $compatibleOptions = $options->filter(function ($option) use ($selectedPrice) {
                            return $option->prices()
                                ->where('period', $selectedPrice->period)
                                ->where('currency_uuid', $selectedPrice->currency_uuid)
                                ->exists();
                        });

                        if ($compatibleOptions->isNotEmpty()) {
                            $selectedOption = $compatibleOptions->random();
                            $selectedOptions[] = $selectedOption->uuid;
                            $this->info("Selected option from {$group->key}: {$selectedOption->key}");
                        }
                    }
                }

                // Create service data array
                $serviceData = [
                    'client' => $client->uuid,
                    'product' => $product->uuid,
                    'product_price' => $selectedPrice->uuid,
                    'option' => $selectedOptions,
                ];

                $this->info('Creating service...');

                $result = \App\Models\Service::createFromArray($serviceData);

                if ($result['status'] == 'error') {
                    $this->error("Service creation failed: {$result['error']}");
                    $errorCount++;
                    continue;
                }

                $service = $result['data'];
                $this->info("✅ Service created successfully: {$service->uuid}");
                $this->info("Service status: {$service->status}");

                // Deploy the service (activate it)
                $this->info('Deploying service...');
                $deployResult = $service->create();

                if ($deployResult['status'] === 'success') {
                    $this->info('✅ Service deployed successfully!');
                    $service->refresh();
                    $this->info("Final service status: {$service->status}");
                    if ($service->activated_date) {
                        $this->info("Service activated at: {$service->activated_date}");
                    }
                    $successCount++;
                } else {
                    $this->error('❌ Service deployment failed:');
                    foreach ($deployResult['errors'] ?? ['Unknown error'] as $error) {
                        $this->error("  - {$error}");
                    }
                    $service->refresh();
                    $this->info("Service status: {$service->status}");
                    if ($service->create_error) {
                        $this->error("Create error: {$service->create_error}");
                    }
                    $errorCount++;
                }

            } catch (\Exception $e) {
                $this->error("Error during service deployment: " . $e->getMessage());
                Log::error('Service deployment error: ' . $e->getMessage());
                $errorCount++;
            }
        }

        // Summary
        $this->info("=== Deployment Summary ===");
        $this->info("Successfully deployed: {$successCount}");
        $this->info("Failed deployments: {$errorCount}");
        $this->info("Total attempted: {$count}");
    }

    /**
     * Process service termination request simulation
     */
    private function processTerminationService(int $count = 1): void
    {
        $this->info('=== Service Termination Request Simulation ===');

        $successCount = 0;
        $errorCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            $this->info("Processing termination request {$i}/{$count}...");

            try {
            // Find clients with active services that can be terminated
            $client = \App\Models\Client::where('status', 'active')
                ->whereHas('services', function ($query) {
                    $query->whereIn('status', ['active', 'pending', 'suspended'])
                          ->where('termination_request', false);
                })
                ->with(['services' => function ($query) {
                    $query->whereIn('status', ['active', 'pending', 'suspended'])
                          ->where('termination_request', false);
                }])
                ->inRandomOrder()
                ->first();

                if (!$client) {
                    $this->error('No active clients with terminable services found.');
                    $errorCount++;
                    continue;
                }

                $this->info("Found client: {$client->firstname} {$client->lastname} ({$client->uuid})");

                // Get services that can be terminated
                $terminableServices = $client->services()
                    ->whereIn('status', ['active', 'pending', 'suspended'])
                    ->where('termination_request', false)
                    ->with('product')
                    ->get();

                if ($terminableServices->isEmpty()) {
                    $this->error('No terminable services found for this client.');
                    $errorCount++;
                    continue;
                }

                $this->info("Found {$terminableServices->count()} terminable services");

                // Select random service to terminate
                $service = $terminableServices->random();
                $product = $service->product;

                $this->info("Selected service for termination:");
                $this->info("  Service UUID: {$service->uuid}");
                $this->info("  Product: {$product->name}");
                $this->info("  Service Label: {$service->admin_label}");
                $this->info("  Current Status: {$service->status}");

                // Check termination conditions (same as in ManageController)
                if ($service->termination_request) {
                    $this->error('The service is already awaiting termination');
                    $errorCount++;
                    continue;
                }

                if (!in_array($service->status, ['active', 'pending', 'suspended'])) {
                    $this->error('Service status does not allow termination request');
                    $errorCount++;
                    continue;
                }

                // Set termination request
                $this->info('Setting termination request...');
                $service->termination_request = true;
                $service->save();

                $this->info('✅ Termination request set successfully!');
                $successCount++;

                // Show termination timing information
                $terminationTime = $service->getTerminationTime();
                if ($terminationTime['seconds_left'] !== null) {
                    $hours = floor($terminationTime['seconds_left'] / 3600);
                    $minutes = floor(($terminationTime['seconds_left'] % 3600) / 60);
                    $this->info("Service will be terminated in: {$hours}h {$minutes}m");
                    $this->info("Termination scheduled at: {$terminationTime['termination_at']}");
                } else {
                    $this->info("Service will be terminated immediately at next automation run");
                }

                // Log additional service details
                if ($service->billing_timestamp) {
                    $this->info("Next billing: {$service->billing_timestamp}");
                }

            } catch (\Exception $e) {
                $this->error("Error during service termination request: " . $e->getMessage());
                Log::error('Service termination request error: ' . $e->getMessage());
                $errorCount++;
            }
        }

        // Summary
        $this->info("=== Termination Summary ===");
        $this->info("Successfully requested termination: {$successCount}");
        $this->info("Failed requests: {$errorCount}");
        $this->info("Total attempted: {$count}");
    }
}
