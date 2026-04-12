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

use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use Illuminate\Support\Str;
use Modules\Product\puqProxmox\Models\PuqPmClusterGroup;
use Modules\Product\puqProxmox\Models\PuqPmLxcOsTemplate;

/**
 * Reads ProductOptionsSpec and syncs the declared option groups / options into the
 * generic product_option_groups + product_options tables.
 *
 * Two entry points:
 *   - getStatus() — diff spec vs. DB for the admin page. Read-only.
 *   - createMissing() — idempotent create; never updates or deletes existing rows.
 *
 * Design notes:
 *   - Lookups happen by `key` on both ProductOptionGroup and ProductOption.
 *   - For dynamic sources we derive expected options at call time from module tables,
 *     so adding a new cluster_group or OS template and clicking "Create Missing" will
 *     create the matching ProductOption row.
 *   - Nothing is deleted — if the admin removes a cluster_group, its location option
 *     is left behind for them to clean up manually. Keeps the flow predictable.
 */
class ProductOptionsSyncService
{
    /**
     * Return a full status report for the admin page.
     *
     * Tiered groups (CPU/RAM/disk) have an `absolute` tier value (e.g. 4 cores) and a
     * stored DB value equal to `absolute - product_base`. Base values are passed in so
     * the UI can show both what SHOULD be stored given the current base (expected_value)
     * and what IS stored (actual_value), and flag drift.
     *
     * Shape (per group):
     *   [
     *     'spec'           => <spec row>,
     *     'group_exists'   => bool,
     *     'group_uuid'     => string|null,
     *     'expected_count' => int,
     *     'present_count'  => int,
     *     'missing_count'  => int,
     *     'out_of_sync_count' => int,
     *     'options'        => [
     *         [
     *             'key'            => string,
     *             'absolute'       => int|null,    // tiered only
     *             'expected_value' => string,      // what createMissing would store now
     *             'actual_value'   => string|null, // what is in the DB (null if missing)
     *             'name'           => string,
     *             'status'         => 'present' | 'missing' | 'out_of_sync',
     *             'uuid'           => string|null,
     *         ],
     *         ...
     *     ],
     *   ]
     *
     * @param  array<string,int>  $baseValues  map of base_subject → base, e.g. ['cpu_cores' => 1, 'memory' => 1024, ...]
     * @return array<int,array<string,mixed>>
     */
    public function getStatus(array $baseValues = []): array
    {
        $baseValues = $this->normalizeBaseValues($baseValues);

        $rows = [];
        foreach (ProductOptionsSpec::groups() as $groupSpec) {
            $rows[] = $this->statusForGroup($groupSpec, $baseValues);
        }

        return $rows;
    }

    /**
     * Create any missing groups and options declared in the spec, using the supplied
     * base values to compute delta storage for tiered groups.
     *
     * Existing options are never updated — their DB value sticks even if it drifted
     * from the expected value. The UI flags drift via `out_of_sync` status so the admin
     * can delete + recreate manually.
     *
     * @param  array<string,int>  $baseValues
     * @return array{groups_created:int,options_created:int}
     */
    public function createMissing(array $baseValues = []): array
    {
        $baseValues = $this->normalizeBaseValues($baseValues);

        $groupsCreated = 0;
        $optionsCreated = 0;

        foreach (ProductOptionsSpec::groups() as $groupSpec) {
            $group = ProductOptionGroup::query()->where('key', $groupSpec['key'])->first();

            if (! $group) {
                $group = new ProductOptionGroup;
                $group->key = $groupSpec['key'];
                $group->type = 'default';
                $group->hidden = false;
                $group->save();

                $group->name = $groupSpec['name'];
                $group->short_description = $groupSpec['description'];
                $group->save();

                $groupsCreated++;
            }

            foreach ($this->expectedOptionsFor($groupSpec, $baseValues) as $optionSpec) {
                $exists = ProductOption::query()
                    ->where('product_option_group_uuid', $group->uuid)
                    ->where('key', $optionSpec['key'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $option = new ProductOption;
                $option->product_option_group_uuid = $group->uuid;
                $option->key = $optionSpec['key'];
                $option->value = $optionSpec['expected_value'];
                $option->order = $optionSpec['order'] ?? 0;
                $option->hidden = false;
                $option->save();

                $option->name = $optionSpec['name'];
                if (! empty($optionSpec['short_description'])) {
                    $option->short_description = $optionSpec['short_description'];
                }
                $option->save();

                $optionsCreated++;
            }
        }

        return [
            'groups_created' => $groupsCreated,
            'options_created' => $optionsCreated,
        ];
    }

    /**
     * Compute the status of a single group against the DB.
     *
     * @param  array<string,int>  $baseValues
     * @return array<string,mixed>
     */
    private function statusForGroup(array $groupSpec, array $baseValues): array
    {
        $group = ProductOptionGroup::query()->where('key', $groupSpec['key'])->first();

        $expected = $this->expectedOptionsFor($groupSpec, $baseValues);
        $expectedCount = count($expected);

        $present = [];
        if ($group) {
            $present = ProductOption::query()
                ->where('product_option_group_uuid', $group->uuid)
                ->get()
                ->keyBy('key');
        }

        $options = [];
        $presentCount = 0;
        $outOfSyncCount = 0;
        foreach ($expected as $optionSpec) {
            $row = $present[$optionSpec['key']] ?? null;

            $status = 'missing';
            $actualValue = null;
            $uuid = null;
            if ($row) {
                $presentCount++;
                $actualValue = (string) $row->value;
                $uuid = $row->uuid;
                $status = $actualValue === (string) $optionSpec['expected_value']
                    ? 'present'
                    : 'out_of_sync';
                if ($status === 'out_of_sync') {
                    $outOfSyncCount++;
                }
            }

            $options[] = [
                'key' => $optionSpec['key'],
                'absolute' => $optionSpec['absolute'] ?? null,
                'expected_value' => (string) $optionSpec['expected_value'],
                'actual_value' => $actualValue,
                'name' => $optionSpec['name'],
                'status' => $status,
                'uuid' => $uuid,
            ];
        }

        return [
            'spec' => [
                'key' => $groupSpec['key'],
                'name' => $groupSpec['name'],
                'description' => $groupSpec['description'],
                'source' => $groupSpec['source'],
                'dynamic_kind' => $groupSpec['dynamic_kind'] ?? null,
                'base_subject' => $groupSpec['base_subject'] ?? null,
                'unit' => $groupSpec['unit'] ?? null,
            ],
            'group_exists' => (bool) $group,
            'group_uuid' => $group?->uuid,
            'expected_count' => $expectedCount,
            'present_count' => $presentCount,
            'missing_count' => max(0, $expectedCount - $presentCount),
            'out_of_sync_count' => $outOfSyncCount,
            'options' => $options,
        ];
    }

    /**
     * Resolve the list of expected options for a group spec.
     *
     * Each returned row has a normalized shape:
     *   [
     *     'key' => string,
     *     'absolute' => int|null,
     *     'expected_value' => string,   // what should be stored as ProductOption.value
     *     'name' => string,
     *     'order' => int,
     *     'short_description' => string|null,
     *   ]
     *
     * For tiered groups the expected_value = max(0, absolute - base). Negative deltas
     * (tier below base) are clamped to 0 — the tier still exists as an option, it just
     * means "same as base".
     *
     * @param  array<string,int>  $baseValues
     * @return array<int,array<string,mixed>>
     */
    private function expectedOptionsFor(array $groupSpec, array $baseValues): array
    {
        $source = $groupSpec['source'];

        if ($source === ProductOptionsSpec::SOURCE_STATIC) {
            $rows = [];
            foreach ($groupSpec['options'] ?? [] as $opt) {
                $rows[] = [
                    'key' => $opt['key'],
                    'absolute' => null,
                    'expected_value' => (string) $opt['value'],
                    'name' => $opt['name'],
                    'order' => $opt['order'] ?? 0,
                    'short_description' => $opt['short_description'] ?? null,
                ];
            }

            return $rows;
        }

        if ($source === ProductOptionsSpec::SOURCE_TIERED) {
            $subject = $groupSpec['base_subject'] ?? null;
            $base = (int) ($baseValues[$subject] ?? 0);

            $rows = [];
            foreach ($groupSpec['options'] ?? [] as $opt) {
                $absolute = (int) $opt['absolute'];
                $delta = max(0, $absolute - $base);
                $rows[] = [
                    'key' => $opt['key'],
                    'absolute' => $absolute,
                    'expected_value' => (string) $delta,
                    'name' => $opt['name'],
                    'order' => $opt['order'] ?? 0,
                    'short_description' => null,
                ];
            }

            return $rows;
        }

        // Dynamic
        $dynamicRows = match ($groupSpec['dynamic_kind'] ?? null) {
            'location' => $this->locationOptions(),
            'os_template' => $this->osTemplateOptions(),
            default => [],
        };

        $rows = [];
        foreach ($dynamicRows as $opt) {
            $rows[] = [
                'key' => $opt['key'],
                'absolute' => null,
                'expected_value' => (string) $opt['value'],
                'name' => $opt['name'],
                'order' => $opt['order'] ?? 0,
                'short_description' => $opt['short_description'] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Merge caller-provided base values with the defaults from the spec.
     *
     * Non-numeric / missing subjects fall back to ProductOptionsSpec::DEFAULT_BASES.
     *
     * @param  array<string,mixed>  $baseValues
     * @return array<string,int>
     */
    private function normalizeBaseValues(array $baseValues): array
    {
        $out = [];
        foreach (ProductOptionsSpec::BASE_SUBJECTS as $subject) {
            $raw = $baseValues[$subject] ?? null;
            if (is_numeric($raw)) {
                $out[$subject] = max(0, (int) $raw);
            } else {
                $out[$subject] = ProductOptionsSpec::DEFAULT_BASES[$subject] ?? 0;
            }
        }

        return $out;
    }

    /**
     * Build location options from puq_pm_cluster_groups.
     *
     * Value format `{country_code}-{data_center}` (lowercase) is validated at order
     * time by PuqPmClusterGroup::getByLocation() which splits on `-`. A cluster_group
     * with no country or no data_center is skipped — it would never match anyway.
     *
     * @return array<int,array<string,mixed>>
     */
    private function locationOptions(): array
    {
        $rows = [];
        $order = 0;

        $clusterGroups = PuqPmClusterGroup::query()
            ->with('country')
            ->orderBy('name')
            ->get();

        foreach ($clusterGroups as $cg) {
            $countryCode = $cg->country?->code;
            $dataCenter = $cg->data_center;

            if (empty($countryCode) || empty($dataCenter)) {
                continue;
            }

            $value = mb_strtolower($countryCode.'-'.$dataCenter);
            $displayCountry = $cg->country?->name ?? $countryCode;

            $rows[] = [
                'key' => 'puq_pm_location_'.Str::slug($value, '_'),
                'value' => $value,
                'name' => $displayCountry.' — '.$cg->name,
                'short_description' => 'Data center: '.$dataCenter,
                'order' => $order++,
            ];
        }

        return $rows;
    }

    /**
     * Build OS template options from puq_pm_lxc_os_templates.
     *
     * Value = $template->key verbatim (case-sensitive). OrderController::GetImages()
     * matches ProductOption.value against PuqPmLxcOsTemplate.key, and the comparison
     * is `where('key', $value)` — any case mismatch silently drops the image from the
     * order picker.
     *
     * @return array<int,array<string,mixed>>
     */
    private function osTemplateOptions(): array
    {
        $rows = [];
        $order = 0;

        $templates = PuqPmLxcOsTemplate::query()
            ->orderBy('distribution')
            ->orderBy('version')
            ->get();

        foreach ($templates as $tpl) {
            if (empty($tpl->key)) {
                continue;
            }

            $rows[] = [
                'key' => 'puq_pm_os_'.Str::slug($tpl->key, '_'),
                'value' => $tpl->key,
                'name' => $tpl->name ?: $tpl->key,
                'short_description' => trim(($tpl->distribution ?? '').' '.($tpl->version ?? '')),
                'order' => $order++,
            ];
        }

        return $rows;
    }
}
