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

namespace Database\Factories;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductAttributeFactory extends Factory
{
    protected $model = ProductAttribute::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'key' => 'attribute_'.Str::random(8),
            'product_attribute_group_uuid' => ProductAttributeGroup::factory(),
            'hidden' => $this->faker->boolean(5), // 5% chance to be hidden
            'order' => $this->faker->numberBetween(1, 100),
            'notes' => 'Generated by ProductAttributeFactory',
        ];
    }

    public function forGroup(ProductAttributeGroup $group): self
    {
        return $this->state(function (array $attributes) use ($group) {
            return [
                'product_attribute_group_uuid' => $group->uuid,
                'key' => $this->generateAttributeKeyForGroup($group->key),
            ];
        });
    }

    public function withTranslations(): self
    {
        return $this->afterCreating(function (ProductAttribute $attribute) {
            $attributeGroup = $attribute->productAttributeGroup;
            $groupKey = $attributeGroup ? $attributeGroup->key : 'general';

            $attributeData = $this->generateAttributeData($groupKey, $attribute->key);

            $attribute->name = $attributeData['name'];
            $attribute->value = $attributeData['value'];
            $attribute->short_description = $attributeData['short_description'];
            $attribute->description = $attributeData['description'];
            $attribute->save();
        });
    }

    private function generateAttributeKeyForGroup(string $groupKey): string
    {
        $attributeKeys = [
            'compute' => [
                'cpu_cores', 'cpu_threads', 'cpu_frequency', 'cpu_architecture', 'ram_size',
                'ram_type', 'cache_size', 'processing_power', 'virtualization_support',
            ],
            'storage' => [
                'disk_space', 'disk_type', 'iops', 'raid_level', 'backup_included',
                'snapshot_support', 'expandable_storage', 'storage_replication',
            ],
            'network' => [
                'bandwidth', 'transfer_limit', 'ipv4_addresses', 'ipv6_support', 'ddos_protection',
                'load_balancer', 'cdn_integration', 'private_network',
            ],
            'security' => [
                'ssl_certificate', 'firewall', 'intrusion_detection', 'malware_protection',
                'data_encryption', 'access_control', 'compliance_certification',
            ],
            'backup' => [
                'backup_frequency', 'retention_period', 'automated_backup', 'manual_backup',
                'restore_options', 'offsite_backup', 'incremental_backup',
            ],
            'software' => [
                'operating_system', 'control_panel', 'database_support', 'programming_languages',
                'web_server', 'mail_server', 'development_tools',
            ],
            'support' => [
                'support_level', 'response_time', 'availability', 'support_channels',
                'documentation', 'community_support', 'professional_services',
            ],
        ];

        $keys = $attributeKeys[$groupKey] ?? $attributeKeys['compute'];

        return $this->faker->randomElement($keys);
    }

    private function generateAttributeData(string $groupKey, string $attributeKey): array
    {
        $attributeTemplates = [
            'compute' => [
                'cpu_cores' => [
                    'name' => 'CPU Cores',
                    'value' => $this->faker->randomElement(['1 Core', '2 Cores', '4 Cores', '8 Cores', '16 Cores']),
                    'short_description' => 'Number of CPU cores',
                    'description' => 'The number of physical CPU cores allocated to your service for processing tasks.',
                ],
                'ram_size' => [
                    'name' => 'RAM Memory',
                    'value' => $this->faker->randomElement(['1 GB', '2 GB', '4 GB', '8 GB', '16 GB', '32 GB']),
                    'short_description' => 'Available system memory',
                    'description' => 'The amount of RAM memory available for your applications and operating system.',
                ],
                'cpu_frequency' => [
                    'name' => 'CPU Frequency',
                    'value' => $this->faker->randomElement(['2.4 GHz', '2.8 GHz', '3.2 GHz', '3.6 GHz', '4.0 GHz']),
                    'short_description' => 'Processor clock speed',
                    'description' => 'The clock speed of the CPU cores, indicating processing performance capability.',
                ],
            ],
            'storage' => [
                'disk_space' => [
                    'name' => 'Disk Space',
                    'value' => $this->faker->randomElement(['20 GB', '50 GB', '100 GB', '200 GB', '500 GB', '1 TB']),
                    'short_description' => 'Available storage space',
                    'description' => 'Total disk space available for your files, applications, and data storage.',
                ],
                'disk_type' => [
                    'name' => 'Storage Type',
                    'value' => $this->faker->randomElement(['SSD', 'NVMe SSD', 'HDD', 'Hybrid SSD/HDD']),
                    'short_description' => 'Type of storage drive',
                    'description' => 'The type of storage technology used, affecting performance and reliability.',
                ],
                'iops' => [
                    'name' => 'IOPS Performance',
                    'value' => $this->faker->randomElement(['1,000 IOPS', '3,000 IOPS', '5,000 IOPS', '10,000 IOPS']),
                    'short_description' => 'Input/Output operations per second',
                    'description' => 'Maximum number of read/write operations the storage can handle per second.',
                ],
            ],
            'network' => [
                'bandwidth' => [
                    'name' => 'Network Bandwidth',
                    'value' => $this->faker->randomElement(['100 Mbps', '1 Gbps', '10 Gbps', 'Unlimited']),
                    'short_description' => 'Network connection speed',
                    'description' => 'Maximum network bandwidth available for data transfer and connectivity.',
                ],
                'transfer_limit' => [
                    'name' => 'Data Transfer',
                    'value' => $this->faker->randomElement(['1 TB', '5 TB', '10 TB', 'Unlimited']),
                    'short_description' => 'Monthly data transfer allowance',
                    'description' => 'Amount of data you can transfer per month without additional charges.',
                ],
                'ddos_protection' => [
                    'name' => 'DDoS Protection',
                    'value' => $this->faker->randomElement(['Basic', 'Advanced', 'Enterprise', 'Custom']),
                    'short_description' => 'Distributed denial of service protection',
                    'description' => 'Protection against DDoS attacks to ensure service availability.',
                ],
            ],
            'security' => [
                'ssl_certificate' => [
                    'name' => 'SSL Certificate',
                    'value' => $this->faker->randomElement(['Let\'s Encrypt', 'Standard SSL', 'Wildcard SSL', 'EV SSL']),
                    'short_description' => 'SSL encryption certificate',
                    'description' => 'SSL certificate for secure encrypted connections to your service.',
                ],
                'firewall' => [
                    'name' => 'Firewall Protection',
                    'value' => $this->faker->randomElement(['Basic', 'Advanced', 'Web Application Firewall', 'Custom Rules']),
                    'short_description' => 'Network firewall protection',
                    'description' => 'Firewall protection to filter network traffic and prevent unauthorized access.',
                ],
            ],
            'backup' => [
                'backup_frequency' => [
                    'name' => 'Backup Frequency',
                    'value' => $this->faker->randomElement(['Daily', 'Weekly', 'Real-time', 'On-demand']),
                    'short_description' => 'How often backups are performed',
                    'description' => 'The frequency at which your data is automatically backed up for protection.',
                ],
                'retention_period' => [
                    'name' => 'Backup Retention',
                    'value' => $this->faker->randomElement(['7 days', '30 days', '90 days', '1 year']),
                    'short_description' => 'How long backups are kept',
                    'description' => 'The duration for which backup copies are stored and available for restoration.',
                ],
            ],
            'software' => [
                'operating_system' => [
                    'name' => 'Operating System',
                    'value' => $this->faker->randomElement(['Ubuntu 22.04', 'CentOS 8', 'Windows Server 2022', 'Debian 11']),
                    'short_description' => 'Server operating system',
                    'description' => 'The operating system installed and configured on your server.',
                ],
                'control_panel' => [
                    'name' => 'Control Panel',
                    'value' => $this->faker->randomElement(['cPanel', 'Plesk', 'DirectAdmin', 'Custom Dashboard']),
                    'short_description' => 'Management control panel',
                    'description' => 'Web-based control panel for easy server and service management.',
                ],
            ],
            'support' => [
                'support_level' => [
                    'name' => 'Support Level',
                    'value' => $this->faker->randomElement(['Basic', 'Priority', '24/7', 'Enterprise']),
                    'short_description' => 'Level of technical support',
                    'description' => 'The level of technical support and assistance included with your service.',
                ],
                'response_time' => [
                    'name' => 'Response Time',
                    'value' => $this->faker->randomElement(['4 hours', '2 hours', '1 hour', '15 minutes']),
                    'short_description' => 'Support ticket response time',
                    'description' => 'Maximum time to receive initial response to your support requests.',
                ],
            ],
        ];

        $groupTemplates = $attributeTemplates[$groupKey] ?? $attributeTemplates['compute'];
        $template = $groupTemplates[$attributeKey] ?? [
            'name' => ucwords(str_replace('_', ' ', $attributeKey)),
            'value' => $this->faker->word,
            'short_description' => 'Generated attribute',
            'description' => 'This is a generated attribute for demonstration purposes.',
        ];

        return $template;
    }
}
