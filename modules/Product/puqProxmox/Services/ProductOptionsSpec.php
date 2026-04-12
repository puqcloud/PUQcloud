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

namespace Modules\Product\puqProxmox\Services;

/**
 * Declarative spec for the product option groups the puqProxmox module expects.
 *
 * Source of truth for the "Product Options" admin page + its "Create Missing" button.
 * Nothing here is attached to any specific Product — that is the admin's job from the
 * Product edit page. This spec only guarantees that the groups and their options exist
 * under the right keys and with the right values so the order/deploy flow can match them.
 *
 * Three source types:
 *   - `static`  : options are defined inline below with fixed values (backup count, bools).
 *   - `tiered`  : options are absolute tiers (e.g. 1, 2, 4, 8 cores) whose *stored* value
 *                 is a DELTA over the product's base (value = absolute - base). This matches
 *                 the convention used by existing puqPmLXC-cores-mini / puqPmApp-memory /
 *                 puqPmApp-disk groups in production: the Product itself ships with a base
 *                 CPU/RAM/disk and options add their delta on top at deploy time.
 *   - `dynamic` : options are generated at sync time from a module table
 *                 (locations ← puq_pm_cluster_groups, OS ← puq_pm_lxc_os_templates).
 *
 * Location value format is `{country.code}-{data_center}` in lowercase. This is enforced
 * by PuqPmClusterGroup::getByLocation() which splits the value on `-` and resolves each
 * half against Country.code and cluster_group.data_center. Any change in format here
 * will break the order flow — keep it in sync with that method.
 *
 * OS option value = PuqPmLxcOsTemplate.key exactly (case sensitive). The order controller
 * looks up the OS template by `where('key', $product_option->value)` — a mismatch there
 * silently drops the image from the order picker.
 */
class ProductOptionsSpec
{
    public const SOURCE_STATIC = 'static';
    public const SOURCE_TIERED = 'tiered';
    public const SOURCE_DYNAMIC = 'dynamic';

    /**
     * Keys accepted in the base-values map passed to the sync service.
     * Must match the form input names on the Product Options admin page and the
     * `base_subject` field on tiered spec rows.
     */
    public const BASE_SUBJECTS = ['cpu_cores', 'memory', 'rootfs_size', 'mp_size'];

    /**
     * Default base values used when the admin has not entered anything on the page yet.
     * Keep these conservative — they match the minimum product baseline puqProxmox has
     * used historically (1 core, 1 GB RAM, 10 GB root, no extra mp).
     */
    public const DEFAULT_BASES = [
        'cpu_cores' => 1,
        'memory' => 1024,
        'rootfs_size' => 10240,
        'mp_size' => 0,
    ];

    /**
     * Full list of option groups the module expects. Each entry:
     *
     *   key          — stable ProductOptionGroup.key; used to find-or-create the group.
     *   name         — default display name (English; translatable later via admin UI).
     *   description  — short helper text shown in the admin page.
     *   source       — 'static' or 'dynamic'.
     *   dynamic_kind — (dynamic only) which module table drives it: 'location' | 'os_template'.
     *   options      — (static only) array of ['key','value','name','order'] rows.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'puq_pm_location',
                'name' => 'Proxmox Location',
                'description' => 'Datacenter locations. Generated from puq_pm_cluster_groups. Value format: {country_code}-{data_center}.',
                'source' => self::SOURCE_DYNAMIC,
                'dynamic_kind' => 'location',
            ],
            [
                'key' => 'puq_pm_os_template',
                'name' => 'Proxmox OS Template',
                'description' => 'LXC OS images. Generated from puq_pm_lxc_os_templates. Value = template key.',
                'source' => self::SOURCE_DYNAMIC,
                'dynamic_kind' => 'os_template',
            ],
            [
                'key' => 'puq_pm_cpu_cores',
                'name' => 'Proxmox CPU Cores',
                'description' => 'Number of CPU cores allocated to the LXC container. Stored as delta over the product base.',
                'source' => self::SOURCE_TIERED,
                'base_subject' => 'cpu_cores',
                'unit' => 'cores',
                'options' => self::tieredLadder('puq_pm_cpu_cores', [
                    [1, '1 Core'],
                    [2, '2 Cores'],
                    [4, '4 Cores'],
                    [8, '8 Cores'],
                    [16, '16 Cores'],
                ]),
            ],
            [
                'key' => 'puq_pm_memory',
                'name' => 'Proxmox Memory (RAM)',
                'description' => 'RAM allocated to the LXC container, in megabytes. Stored as delta over the product base.',
                'source' => self::SOURCE_TIERED,
                'base_subject' => 'memory',
                'unit' => 'MB',
                'options' => self::tieredLadder('puq_pm_memory', [
                    [512, '512 MB'],
                    [1024, '1 GB'],
                    [2048, '2 GB'],
                    [4096, '4 GB'],
                    [8192, '8 GB'],
                    [16384, '16 GB'],
                ]),
            ],
            [
                'key' => 'puq_pm_rootfs_size',
                'name' => 'Proxmox Root Disk Size',
                'description' => 'Root filesystem disk size, in megabytes. Stored as delta over the product base.',
                'source' => self::SOURCE_TIERED,
                'base_subject' => 'rootfs_size',
                'unit' => 'MB',
                'options' => self::tieredLadder('puq_pm_rootfs_size', [
                    [10240, '10 GB'],
                    [20480, '20 GB'],
                    [40960, '40 GB'],
                    [81920, '80 GB'],
                    [163840, '160 GB'],
                ]),
            ],
            [
                'key' => 'puq_pm_mp_size',
                'name' => 'Proxmox Additional Disk Size',
                'description' => 'Additional mountpoint disk size, in megabytes. Stored as delta over the product base.',
                'source' => self::SOURCE_TIERED,
                'base_subject' => 'mp_size',
                'unit' => 'MB',
                'options' => self::tieredLadder('puq_pm_mp_size', [
                    [0, 'None'],
                    [10240, '10 GB'],
                    [20480, '20 GB'],
                    [40960, '40 GB'],
                    [81920, '80 GB'],
                ]),
            ],
            [
                'key' => 'puq_pm_backup_count',
                'name' => 'Proxmox Backup Count',
                'description' => 'Number of scheduled backups to retain. 0 disables backups.',
                'source' => self::SOURCE_STATIC,
                'options' => self::numericLadder('puq_pm_backup_count', [
                    [0, 'No Backups'],
                    [1, '1 Backup'],
                    [3, '3 Backups'],
                    [7, '7 Backups'],
                ]),
            ],
            [
                'key' => 'puq_pm_ipv4',
                'name' => 'Proxmox Public IPv4',
                'description' => 'Allocate a public IPv4 address to the container.',
                'source' => self::SOURCE_STATIC,
                'options' => self::booleanPair('puq_pm_ipv4'),
            ],
            [
                'key' => 'puq_pm_ipv6',
                'name' => 'Proxmox Public IPv6',
                'description' => 'Allocate a public IPv6 address to the container.',
                'source' => self::SOURCE_STATIC,
                'options' => self::booleanPair('puq_pm_ipv6'),
            ],
            [
                'key' => 'puq_pm_local_private_network',
                'name' => 'Proxmox Local Private Network',
                'description' => 'Attach the container to the client-scoped local private network.',
                'source' => self::SOURCE_STATIC,
                'options' => self::booleanPair('puq_pm_local_private_network'),
            ],
            [
                'key' => 'puq_pm_global_private_network',
                'name' => 'Proxmox Global Private Network',
                'description' => 'Attach the container to the client-scoped global private network.',
                'source' => self::SOURCE_STATIC,
                'options' => self::booleanPair('puq_pm_global_private_network'),
            ],
        ];
    }

    /**
     * Build a numeric ladder where the number is an absolute value the client-facing
     * label should show. The sync service subtracts the current product base from this
     * absolute to produce the stored ProductOption.value (delta).
     *
     * @param  array<int,array{0:int,1:string}>  $rows
     * @return array<int,array<string,mixed>>
     */
    private static function tieredLadder(string $groupKey, array $rows): array
    {
        $out = [];
        foreach ($rows as $order => [$absolute, $label]) {
            $out[] = [
                'key' => $groupKey.'_'.$absolute,
                'absolute' => (int) $absolute,
                'name' => $label,
                'order' => $order,
            ];
        }

        return $out;
    }

    /**
     * Build a simple static ladder where the stored value IS the absolute number,
     * with no base subtraction. Used for backup_count and similar flat counters.
     *
     * @param  array<int,array{0:int,1:string}>  $rows
     * @return array<int,array<string,mixed>>
     */
    private static function numericLadder(string $groupKey, array $rows): array
    {
        $out = [];
        foreach ($rows as $order => [$value, $label]) {
            $out[] = [
                'key' => $groupKey.'_'.$value,
                'value' => (string) $value,
                'name' => $label,
                'order' => $order,
            ];
        }

        return $out;
    }

    /**
     * Two-row boolean group: no (value=0) / yes (value=1).
     *
     * @return array<int,array<string,mixed>>
     */
    private static function booleanPair(string $groupKey): array
    {
        return [
            [
                'key' => $groupKey.'_no',
                'value' => '0',
                'name' => 'No',
                'order' => 0,
            ],
            [
                'key' => $groupKey.'_yes',
                'value' => '1',
                'name' => 'Yes',
                'order' => 1,
            ],
        ];
    }
}
