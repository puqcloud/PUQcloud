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

use App\Models\ProductAttribute;
use App\Models\ProductAttributeGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DemoDataSeederProductAttributeGroups extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributeGroups = [
            'processor' => [
                'name' => 'Processor',
                'short_description' => 'CPU specifications and performance characteristics',
                'description' => 'Detailed information about the processor, including frequency, cores, cache, and other performance metrics',
                'attributes' => [
                    ['name' => 'Frequency', 'value' => '2.4 GHz', 'description' => 'Processor clock speed'],
                    ['name' => 'Cores', 'value' => '4', 'description' => 'Number of physical CPU cores'],
                    ['name' => 'Threads', 'value' => '8', 'description' => 'Number of logical processors'],
                    ['name' => 'Cache', 'value' => '8 MB', 'description' => 'CPU cache size'],
                    ['name' => 'Architecture', 'value' => 'x86_64', 'description' => 'CPU architecture'],
                    ['name' => 'Manufacturer', 'value' => 'Intel', 'description' => 'CPU manufacturer'],
                ],
            ],
            'memory' => [
                'name' => 'Memory',
                'short_description' => 'RAM specifications and characteristics',
                'description' => 'Detailed information about the system memory, including capacity, type, and performance metrics',
                'attributes' => [
                    ['name' => 'Capacity', 'value' => '8 GB', 'description' => 'Total RAM capacity'],
                    ['name' => 'Type', 'value' => 'DDR4', 'description' => 'RAM type'],
                    ['name' => 'Frequency', 'value' => '3200 MHz', 'description' => 'RAM frequency'],
                    ['name' => 'Channels', 'value' => 'Dual', 'description' => 'Memory channel configuration'],
                    ['name' => 'ECC', 'value' => 'No', 'description' => 'Error-correcting code support'],
                ],
            ],
            'storage' => [
                'name' => 'Storage',
                'short_description' => 'Storage device specifications and performance',
                'description' => 'Detailed information about storage devices, including type, capacity, and performance metrics',
                'attributes' => [
                    ['name' => 'Type', 'value' => 'SSD', 'description' => 'Storage device type'],
                    ['name' => 'Capacity', 'value' => '256 GB', 'description' => 'Total storage capacity'],
                    ['name' => 'Read Speed', 'value' => '550 MB/s', 'description' => 'Maximum read speed'],
                    ['name' => 'Write Speed', 'value' => '520 MB/s', 'description' => 'Maximum write speed'],
                    ['name' => 'Interface', 'value' => 'SATA III', 'description' => 'Storage interface type'],
                    ['name' => 'Form Factor', 'value' => '2.5"', 'description' => 'Physical form factor'],
                ],
            ],
            'network' => [
                'name' => 'Network',
                'short_description' => 'Network connectivity and bandwidth specifications',
                'description' => 'Detailed information about network capabilities, including bandwidth, traffic, and IP configuration',
                'attributes' => [
                    ['name' => 'Bandwidth', 'value' => '1 Gbps', 'description' => 'Network bandwidth'],
                    ['name' => 'Traffic', 'value' => 'Unlimited', 'description' => 'Monthly traffic allowance'],
                    ['name' => 'IP Addresses', 'value' => '1 IPv4 + 1 IPv6', 'description' => 'Number of IP addresses'],
                    ['name' => 'Port Speed', 'value' => '1 Gbps', 'description' => 'Network port speed'],
                    ['name' => 'DDoS Protection', 'value' => '10 Gbps', 'description' => 'DDoS protection capacity'],
                ],
            ],
            'security' => [
                'name' => 'Security',
                'short_description' => 'Security features and protection measures',
                'description' => 'Detailed information about security features, including DDoS protection, firewall, and antivirus',
                'attributes' => [
                    ['name' => 'DDoS Protection', 'value' => 'Enabled', 'description' => 'DDoS protection status'],
                    ['name' => 'Firewall', 'value' => 'Enabled', 'description' => 'Firewall status'],
                    ['name' => 'Antivirus', 'value' => 'Enabled', 'description' => 'Antivirus protection status'],
                    ['name' => 'SSL Certificate', 'value' => 'Included', 'description' => 'SSL certificate availability'],
                    ['name' => 'Backup Encryption', 'value' => 'AES-256', 'description' => 'Backup encryption method'],
                ],
            ],
            'backup' => [
                'name' => 'Backup',
                'short_description' => 'Backup and recovery options',
                'description' => 'Detailed information about backup capabilities, including frequency, retention, and type',
                'attributes' => [
                    ['name' => 'Frequency', 'value' => 'Daily', 'description' => 'Backup frequency'],
                    ['name' => 'Retention', 'value' => '30 days', 'description' => 'Backup retention period'],
                    ['name' => 'Type', 'value' => 'Full', 'description' => 'Backup type'],
                    ['name' => 'Storage', 'value' => '100 GB', 'description' => 'Backup storage space'],
                    ['name' => 'Recovery Time', 'value' => '< 4 hours', 'description' => 'Maximum recovery time'],
                ],
            ],
            'support' => [
                'name' => 'Support',
                'short_description' => 'Technical support and service level',
                'description' => 'Detailed information about support services, including availability, channels, and response time',
                'attributes' => [
                    ['name' => 'Level', 'value' => '24/7', 'description' => 'Support availability'],
                    ['name' => 'Channels', 'value' => 'Email, Chat, Phone', 'description' => 'Support channels'],
                    ['name' => 'Response Time', 'value' => '1 hour', 'description' => 'Maximum response time'],
                    ['name' => 'Language', 'value' => 'English', 'description' => 'Support language'],
                    ['name' => 'SLA', 'value' => '99.9%', 'description' => 'Service level agreement'],
                ],
            ],
            'software' => [
                'name' => 'Software',
                'short_description' => 'Included software and applications',
                'description' => 'Detailed information about included software, including operating system, control panel, and databases',
                'attributes' => [
                    ['name' => 'Operating System', 'value' => 'Ubuntu 22.04', 'description' => 'Base operating system'],
                    ['name' => 'Control Panel', 'value' => 'cPanel', 'description' => 'Control panel software'],
                    ['name' => 'Database', 'value' => 'MySQL 8.0', 'description' => 'Database software'],
                    ['name' => 'Web Server', 'value' => 'Apache 2.4', 'description' => 'Web server software'],
                    ['name' => 'PHP Version', 'value' => '8.1', 'description' => 'PHP version'],
                ],
            ],
        ];

        foreach ($attributeGroups as $key => $groupData) {
            try {
                // Create or update attribute group
                $group = ProductAttributeGroup::updateOrCreate(
                    ['key' => $key],
                    [
                        'name' => $groupData['name'],
                        'short_description' => $groupData['short_description'],
                        'description' => $groupData['description'],
                        'hidden' => false,
                        'notes' => 'Automatically created attribute group',
                    ]
                );

                // Создаем атрибуты для группы
                foreach ($groupData['attributes'] as $index => $attribute) {
                    ProductAttribute::updateOrCreate(
                        [
                            'product_attribute_group_uuid' => $group->uuid,
                            'key' => strtolower(str_replace(' ', '_', $attribute['name'])),
                        ],
                        [
                            'name' => $attribute['name'],
                            'value' => $attribute['value'],
                            'short_description' => $attribute['description'],
                            'description' => $attribute['description'],
                            'hidden' => false,
                            'order' => $index + 1,
                            'notes' => 'Automatically created attribute',
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error("Error creating attribute group {$key}: ".$e->getMessage());

                continue;
            }
        }
    }
}
