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

namespace Modules\Product\puqProxmox\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\PuqPmLxcPreset;
use Modules\Product\puqProxmox\Models\PuqPmLxcPresetClusterGroup;
use Yajra\DataTables\DataTables;

class puqPmLxcPresetController extends Controller
{
    public function lxcPresets(Request $request): View
    {
        $title = __('Product.puqProxmox.LXC Presets');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.lxc_presets.lxc_presets', compact('title'));
    }

    public function getLxcPresets(Request $request): JsonResponse
    {
        $query = PuqPmLxcPreset::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.web.Product.puqProxmox.lxc_preset.tab',
                        ['uuid' => $model->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.lxc_preset.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function lxcPresetTab(Request $request, $uuid, $tab): View|RedirectResponse
    {

        $lxcPreset = PuqPmLxcPreset::findOrFail($uuid);

        $validTabs = [
            'general',
            'cluster_groups',
            'os_templates',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route(
                'admin.web.Product.puqProxmox.lxc_preset.tab',
                ['uuid' => $lxcPreset->uuid, 'tab' => 'general']
            );
        }

        $title = $lxcPreset->name;

        return view_admin_module(
            'Product',
            'puqProxmox',
            'admin_area.lxc_presets.lxc_preset_'.$tab,
            compact('title', 'uuid', 'tab', 'lxcPreset')
        );
    }

    public function lxcPreset(Request $request): View
    {
        $title = __('Product.puqProxmox.LXC Preset');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.lxc_presets.lxc_preset', compact('title'));
    }

    public function postLxcPreset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_pm_lxc_presets,name',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmLxcPreset;

        $model->name = $request->input('name');
        $model->puq_pm_dns_zone_uuid = $request->input('puq_pm_dns_zone_uuid');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function getLxcPreset(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();
        $dns_zone = $model->puqPmDnsZone;
        $data['puq_pm_dns_zone_data'] = [
            'id' => $dns_zone->uuid,
            'text' => $dns_zone->name,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function putLxcPreset(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_pm_lxc_presets,name,'.$uuid.',uuid',
            'description' => 'nullable|string|max:255',

            'hostname' => 'required',
            'puq_pm_dns_zone_uuid' => 'required|uuid|exists:puq_pm_dns_zones,uuid',
            'onboot' => 'nullable|in:yes,no',

            'arch' => 'required|in:amd64,i386,arm64,armhf,riscv32,riscv64',
            'cores' => 'required|integer|min:1',
            'cpulimit' => 'nullable|integer|min:0',
            'cpuunits' => 'nullable|integer|min:8',
            'memory' => 'required|integer|min:16',
            'swap' => 'required|integer|min:0',

            'rootfs_size' => 'required|integer|min:0',
            'rootfs_mountoptions' => 'nullable|string|max:255',

            'mp' => 'nullable|string|max:255',
            'mp_size' => 'nullable|integer|min:0',
            'mp_mountoptions' => 'nullable|string|max:255',
            'mp_backup' => 'nullable|in:yes,no',

            'vzdump_mode' => 'nullable|in:snapshot,suspend,stop',
            'vzdump_compress' => 'nullable|in:0,gzip,lzo,zstd',
            'vzdump_bwlimit' => 'nullable|integer|min:0',
            'backup_count' => 'integer|min:0',

            'pn_name' => 'nullable|string|max:255',
            'pn_rate' => 'nullable|integer|min:0',
            'pn_mtu' => 'nullable|integer|min:0',
            'pn_firewall' => 'nullable|in:yes,no',

            'lpn_name' => 'nullable|string|max:255',
            'lpn_rate' => 'nullable|integer|min:0',
            'lpn_mtu' => 'nullable|integer|min:0',
            'lpn_firewall' => 'nullable|in:yes,no',

            'gpn_name' => 'nullable|string|max:255',
            'gpn_rate' => 'nullable|integer|min:0',
            'gpn_mtu' => 'nullable|integer|min:0',
            'gpn_firewall' => 'nullable|in:yes,no',

            'firewall_log_level_in' => 'nullable|in:nolog,emerg,alert,crit,err,warning,notice,info,debug',
            'firewall_log_level_out' => 'nullable|in:nolog,emerg,alert,crit,err,warning,notice,info,debug',
            'firewall_policy_in' => 'nullable|in:ACCEPT,REJECT,DROP',
            'firewall_policy_out' => 'nullable|in:ACCEPT,REJECT,DROP',

        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'cores.required' => __('Product.puqProxmox.Cores field is required'),
            'cores.integer' => __('Product.puqProxmox.Cores must be a number'),
            'cores.min' => __('Product.puqProxmox.Cores must be at least 1'),
            'memory.required' => __('Product.puqProxmox.RAM field is required'),
            'memory.integer' => __('Product.puqProxmox.RAM must be a number'),
            'memory.min' => __('Product.puqProxmox.RAM must be at least 16'),
            'swap.required' => __('Product.puqProxmox.Swap field is required'),
            'swap.integer' => __('Product.puqProxmox.Swap must be a number'),
            'swap.min' => __('Product.puqProxmox.Swap must be at least 0'),
            'rootfs_size.required' => __('Product.puqProxmox.Disk field is required'),
            'rootfs_size.integer' => __('Product.puqProxmox.Disk must be a number'),
            'rootfs_size.min' => __('Product.puqProxmox.Disk must be at least 0'),
            'cpulimit.integer' => __('Product.puqProxmox.CPU Limit must be a number'),
            'cpulimit.min' => __('Product.puqProxmox.CPU Limit must be at least 0'),
            'cpuunits.integer' => __('Product.puqProxmox.CPU Weight must be a number'),
            'cpuunits.min' => __('Product.puqProxmox.CPU Weight must be at least 8'),
            'puq_pm_dns_zone_uuid.required' => __('Product.puqProxmox.DNS Zone is required'),
            'puq_pm_dns_zone_uuid.uuid' => __('Product.puqProxmox.Invalid DNS Zone format'),
            'puq_pm_dns_zone_uuid.exists' => __('Product.puqProxmox.Selected DNS Zone does not exist'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = PuqPmLxcPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $toBool = fn(?string $val) => $val === 'yes';

        $model->name = $request->input('name');
        $model->description = $request->input('description');

        $model->hostname = $request->input('hostname');
        $model->puq_pm_dns_zone_uuid = $request->input('puq_pm_dns_zone_uuid');
        $model->onboot = $toBool($request->input('onboot'));

        $model->arch = $request->input('arch');
        $model->cores = $request->input('cores');
        $model->cpulimit = $request->input('cpulimit');
        $model->cpuunits = $request->input('cpuunits');
        $model->memory = $request->input('memory');
        $model->swap = $request->input('swap');

        $model->rootfs_size = $request->input('rootfs_size');
        $model->rootfs_mountoptions = $request->input('rootfs_mountoptions');

        $model->mp = $request->input('mp');
        $model->mp_size = $request->input('mp_size');
        $model->mp_mountoptions = $request->input('mp_mountoptions');
        $model->mp_backup = $toBool($request->input('mp_backup'));

        $model->vzdump_mode = $request->input('vzdump_mode');
        $model->vzdump_compress = $request->input('vzdump_compress');
        $model->vzdump_bwlimit = $request->input('vzdump_bwlimit');
        $model->backup_count = $request->input('backup_count');

        $model->pn_name = $request->input('pn_name');
        $model->pn_rate = $request->input('pn_rate');
        $model->pn_mtu = $request->input('pn_mtu');
        $model->pn_firewall = $toBool($request->input('pn_firewall'));

        $model->lpn_name = $request->input('lpn_name');
        $model->lpn_rate = $request->input('lpn_rate');
        $model->lpn_mtu = $request->input('lpn_mtu');
        $model->lpn_firewall = $toBool($request->input('lpn_firewall'));

        $model->gpn_name = $request->input('gpn_name');
        $model->gpn_rate = $request->input('gpn_rate');
        $model->gpn_mtu = $request->input('gpn_mtu');
        $model->gpn_firewall = $toBool($request->input('gpn_firewall'));

        $model->firewall_enable = $toBool($request->input('firewall_enable'));
        $model->firewall_dhcp = $toBool($request->input('firewall_dhcp'));
        $model->firewall_ipfilter = $toBool($request->input('firewall_ipfilter'));
        $model->firewall_macfilter = $toBool($request->input('firewall_macfilter'));
        $model->firewall_ndp = $toBool($request->input('firewall_ndp'));
        $model->firewall_radv = $toBool($request->input('firewall_radv'));

        $model->firewall_log_level_in = $request->input('firewall_log_level_in');
        $model->firewall_log_level_out = $request->input('firewall_log_level_out');
        $model->firewall_policy_in = $request->input('firewall_policy_in');
        $model->firewall_policy_out = $request->input('firewall_policy_out');

        $model->ha_managed = $toBool($request->input('ha_managed'));
        $model->unprivileged = $toBool($request->input('unprivileged'));
        $model->nesting = $toBool($request->input('nesting'));
        $model->fuse = $toBool($request->input('fuse'));
        $model->mknod = $toBool($request->input('mknod'));

        if ($model->unprivileged) {
            $model->keyctl = $toBool($request->input('keyctl'));
        } else {
            $model->keyctl = false;
        }

        if (!$model->unprivileged) {
            $model->mount_nfs = $toBool($request->input('mount_nfs'));
            $model->mount_cifs = $toBool($request->input('mount_cifs'));
        } else {
            $model->mount_nfs = false;
            $model->mount_cifs = false;
        }

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteLxcPreset(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    public function getLxcPresetLxcPresetClusterGroups(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $model->puqPmLxcPresetClusterGroups()
            ->join('puq_pm_cluster_groups', 'puq_pm_cluster_groups.uuid',
                'puq_pm_lxc_preset_cluster_groups.puq_pm_cluster_group_uuid')
            ->select('puq_pm_cluster_groups.name as name', 'puq_pm_lxc_preset_cluster_groups.*');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('puq_pm_cluster_groups.name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('model', function ($model) {
                    return class_basename(get_class($model));
                })
                ->addColumn('node_tags', function ($model) {
                    $tags = $model->getTagsByType('node')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('rootfs_storage_tags', function ($model) {
                    $tags = $model->getTagsByType('rootfs_storage')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('additional_storage_tags', function ($model) {
                    $tags = $model->getTagsByType('additional_storage')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('backup_storage_tags', function ($model) {
                    $tags = $model->getTagsByType('backup_storage')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('public_network_tags', function ($model) {
                    $tags = $model->getTagsByType('public_network')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('local_private_network_tags', function ($model) {
                    $tags = $model->getTagsByType('local_private_network')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('global_private_network_tags', function ($model) {
                    $tags = $model->getTagsByType('global_private_network')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['delete'] = route('admin.api.Product.puqProxmox.lxc_preset_cluster_group.delete',
                        $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getLxcPresetClusterGroups(Request $request): JsonResponse
    {
        $query = PuqPmLxcPresetClusterGroup::query()
            ->join('puq_pm_cluster_groups', 'puq_pm_cluster_groups.uuid',
                'puq_pm_lxc_preset_cluster_groups.puq_pm_cluster_group_uuid')
            ->select('puq_pm_cluster_groups.name as name', 'puq_pm_lxc_preset_cluster_groups.*');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('puq_pm_cluster_groups.name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('model', function ($model) {
                    return class_basename(get_class($model));
                })
                ->addColumn('node_tags', function ($model) {
                    $tags = $model->getTagsByType('node')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('rootfs_storage_tags', function ($model) {
                    $tags = $model->getTagsByType('rootfs_storage')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('additional_storage_tags', function ($model) {
                    $tags = $model->getTagsByType('additional_storage')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('backup_storage_tags', function ($model) {
                    $tags = $model->getTagsByType('backup_storage')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('public_network_tags', function ($model) {
                    $tags = $model->getTagsByType('public_network')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('local_private_network_tags', function ($model) {
                    $tags = $model->getTagsByType('local_private_network')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('global_private_network_tags', function ($model) {
                    $tags = $model->getTagsByType('global_private_network')->pluck('name')->toArray();

                    return $tags;
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['delete'] = route('admin.api.Product.puqProxmox.lxc_preset_cluster_group.delete',
                        $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postLxcPresetClusterGroups(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'puq_pm_lxc_preset_uuid' => 'required|exists:puq_pm_lxc_presets,uuid',
            'puq_pm_cluster_group_uuid' => 'required|exists:puq_pm_cluster_groups,uuid',
        ], [
            'puq_pm_lxc_preset_uuid.required' => __('Product.puqProxmox.The Preset field is required'),
            'puq_pm_lxc_preset_uuid.exists' => __('Product.puqProxmox.Preset not found'),
            'puq_pm_cluster_group_uuid.required' => __('Product.puqProxmox.The Cluster Group field is required'),
            'puq_pm_cluster_group_uuid.exists' => __('Product.puqProxmox.Cluster Group not found'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $exists = PuqPmLxcPresetClusterGroup::where('puq_pm_lxc_preset_uuid', $request->puq_pm_lxc_preset_uuid)
            ->where('puq_pm_cluster_group_uuid', $request->puq_pm_cluster_group_uuid)
            ->exists();

        if ($exists) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.This relation already exists')],
            ], 409); // Conflict
        }

        $model = new PuqPmLxcPresetClusterGroup;
        $model->puq_pm_lxc_preset_uuid = $request->puq_pm_lxc_preset_uuid;
        $model->puq_pm_cluster_group_uuid = $request->puq_pm_cluster_group_uuid;
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function deleteLxcPresetClusterGroup(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcPresetClusterGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    public function getLxcPresetLxcPresetLxcOsTemplates(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $model->puqPmLxcOsTemplates()
            ->join('puq_pm_lxc_templates', 'puq_pm_lxc_os_templates.puq_pm_lxc_template_uuid', '=',
                'puq_pm_lxc_templates.uuid')
            ->select('puq_pm_lxc_os_templates.*', 'puq_pm_lxc_templates.name as template_name');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request, $uuid) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('puq_pm_lxc_os_templates.key', 'like', "%{$search}%")
                                ->orWhere('puq_pm_lxc_os_templates.distribution', 'like', "%{$search}%")
                                ->orWhere('puq_pm_lxc_os_templates.version', 'like', "%{$search}%")
                                ->orWhere('puq_pm_lxc_os_templates.distribution', 'like', "%{$search}%")
                                ->orWhere('puq_pm_lxc_templates.name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) use ($uuid) {
                    $urls = [];
                    $urls['delete'] = route('admin.api.Product.puqProxmox.lxc_preset.lxc_os_template.delete',
                        [
                            'uuid' => $uuid,
                            'lxc_os_template_uuid' => $model->uuid,
                        ]
                    );

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postLxcPresetLxcPresetLxcOsTemplate(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'puq_pm_lxc_os_template_uuid' => 'required|exists:puq_pm_lxc_os_templates,uuid',
        ], [
            'puq_pm_lxc_os_template_uuid.required' => __('Product.puqProxmox.LXC OS Template is required'),
            'puq_pm_lxc_os_template_uuid.exists' => __('Product.puqProxmox.LXC OS Template does not exist'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = PuqPmLxcPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if (!$model->puqPmLxcOsTemplates()->where('puq_pm_lxc_os_template_uuid',
            $request->puq_pm_lxc_os_template_uuid)->exists()) {
            $model->puqPmLxcOsTemplates()->attach($request->puq_pm_lxc_os_template_uuid);
        }
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteLxcPresetLxcPresetLxcOsTemplate(Request $request, $uuid, $lxc_os_template_uuid): JsonResponse
    {
        $model = PuqPmLxcPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if ($model->puqPmLxcOsTemplates()->where('puq_pm_lxc_os_template_uuid', $lxc_os_template_uuid)->exists()) {
            $model->puqPmLxcOsTemplates()->detach($lxc_os_template_uuid);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Unlinked successfully'),
        ]);
    }

    public function getLxcPresetsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (!empty($search)) {
            $models = PuqPmLxcPreset::query()->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $models = PuqPmLxcPreset::query()->get();
        }

        $results = [];
        foreach ($models->toArray() ?? [] as $model) {
            $results[] = [
                'id' => $model['uuid'],
                'text' => $model['name'],
            ];
        }

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }
}
