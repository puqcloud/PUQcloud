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
use App\Models\Service;
use App\Models\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\PuqPmClusterGroup;
use Modules\Product\puqProxmox\Models\PuqPmDnsZone;
use Modules\Product\puqProxmox\Models\PuqPmLoadBalancer;
use Modules\Product\puqProxmox\Models\PuqPmWebProxy;
use Yajra\DataTables\DataTables;

class puqPmLoadBalancerController extends Controller
{
    public function loadBalancers(Request $request): View
    {
        $title = __('Product.puqProxmox.Load Balancers');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.load_balancers.load_balancers', compact('title'));
    }

    public function getLoadBalancers(Request $request): JsonResponse
    {
        $query = PuqPmLoadBalancer::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('cluster_group', function ($model) {
                    $cluster_group = $model->puqPmClusterGroup;

                    return $cluster_group->name;
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route(
                        'admin.web.Product.puqProxmox.load_balancer.tab',
                        ['uuid' => $model->uuid, 'tab' => 'general']
                    );

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function loadBalancerTab(Request $request, $uuid, $tab): View|RedirectResponse
    {

        $load_balancer = PuqPmLoadBalancer::findOrFail($uuid);

        $validTabs = [
            'general',
            'web_proxies',
            'nginx-conf_script',
            'service-conf_script',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route(
                'admin.web.Product.puqProxmox.load_balancer.tab',
                ['uuid' => $load_balancer->uuid, 'tab' => 'general']
            );
        }

        $title = $load_balancer->name;

        return view_admin_module(
            'Product',
            'puqProxmox',
            'admin_area.load_balancers.load_balancer_'.$tab,
            compact('title', 'uuid', 'tab', 'load_balancer')
        );
    }

    public function postLoadBalancer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_pm_load_balancers,name',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmLoadBalancer();
        $model->name = $request->input('name');

        $cluster_group = PuqPmClusterGroup::find($request->input('puq_pm_cluster_group_uuid'));

        if (empty($cluster_group)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Cluster Group not found')],
            ], 404);
        }

        $model->puq_pm_cluster_group_uuid = $cluster_group->uuid;

        $dns_zone = PuqPmDnsZone::find($request->input('puq_pm_dns_zone_uuid'));

        if (empty($dns_zone)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.DNS Zone not found')],
            ], 404);
        }

        $model->puq_pm_dns_zone_uuid = $dns_zone->uuid;

        $model->save();
        $model->refresh();
        $model->loadAllDefaultScripts();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function getLoadBalancer(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLoadBalancer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();
        $cluster_group = $model->puqPmClusterGroup;

        $data['puq_pm_cluster_group_data'] = [
            'id' => $cluster_group->uuid,
            'text' => $cluster_group->name,
        ];

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

    public function putLoadBalancer(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLoadBalancer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_pm_load_balancers,name,'.$uuid.',uuid',
            'subdomain' => [
                'required',
                'regex:/^[a-z0-9-]+$/i',
                'max:63',
            ],
            'puq_pm_cluster_group_uuid' => 'required|exists:puq_pm_cluster_groups,uuid',
            'puq_pm_dns_zone_uuid' => 'required|exists:puq_pm_dns_zones,uuid',
            'dns_record_ttl' => 'required|integer|min:30',
            'default_thresholds' => 'required|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $thresholds = json_decode($request->input('default_thresholds'), true);
        $adjusted = [];

        $defaults = [
            'cpu_used_load1' => ['enabled' => false, 'value' => 90],
            'cpu_used_load5' => ['enabled' => false, 'value' => 90],
            'cpu_used_load15' => ['enabled' => false, 'value' => 90],
            'memory_free_megabyte' => ['enabled' => false, 'value' => 100],
            'memory_free_percent' => ['enabled' => false, 'value' => 10],
            'uptime' => ['enabled' => false, 'value' => 600],
        ];

        foreach ($defaults as $key => $default) {
            $enabled = $thresholds[$key]['enabled'] ?? $default['enabled'];
            $val = floatval($thresholds[$key]['value'] ?? $default['value']);

            $adjusted[$key] = [
                'enabled' => $enabled,
                'value' => $val,
            ];
        }

        $model->name = $request->input('name');
        $model->subdomain = $request->input('subdomain');
        $model->puq_pm_cluster_group_uuid = $request->input('puq_pm_cluster_group_uuid');
        $model->puq_pm_dns_zone_uuid = $request->input('puq_pm_dns_zone_uuid');
        $model->dns_record_ttl = $request->input('dns_record_ttl');
        $model->default_thresholds = $adjusted;

        $model->save();
        $model->refresh();

        $rebalance = $model->rebalance();
        if ($rebalance['status'] == 'error') {
            return response()->json([
                'errors' => $rebalance['errors'] ?? ['unknown error'],
            ], $rebalance['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteLoadBalancer(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLoadBalancer::find($uuid);

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
            'redirect' => route('admin.web.Product.puqProxmox.loadBalancers'),
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    public function getLoadBalancerStatusWebProxies(Request $request, $uuid): JsonResponse
    {
        $puq_pm_load_balancer = PuqPmLoadBalancer::find($uuid);

        if (empty($puq_pm_load_balancer)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $puq_pm_load_balancer->puqPmWebProxies();

        return response()->json([
            'data' => DataTables::of($query)
                ->editColumn('cpu_used_load1', function ($model) {
                    return $model->getThresholdStatus('cpu_used_load1');
                })
                ->editColumn('cpu_used_load5', function ($model) {
                    return $model->getThresholdStatus('cpu_used_load5');
                })
                ->editColumn('cpu_used_load15', function ($model) {
                    return $model->getThresholdStatus('cpu_used_load15');
                })
                ->editColumn('memory_free_megabyte', function ($model) {
                    return $model->getThresholdStatus('memory_free_megabyte');
                })
                ->editColumn('memory_free_percent', function ($model) {
                    return $model->getThresholdStatus('memory_free_percent');
                })
                ->editColumn('uptime', function ($model) {
                    return $model->getThresholdStatus('uptime');
                })
                ->editColumn('ip_dns_records', function ($model) {
                    return $model->getIpDnsRecords();
                })
                ->addColumn('urls', function ($model) use ($uuid) {
                    return [
                        'edit' => route('admin.web.Product.puqProxmox.load_balancer.tab', [
                            'uuid' => $uuid,
                            'tab' => 'web_proxies',
                            'edit' => $model->uuid,
                        ]),
                    ];
                })
                ->make(true),
        ], 200);
    }

    public function putLoadBalancerRebalance(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLoadBalancer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $rebalance = $model->rebalance();
        if ($rebalance['status'] == 'error') {
            return response()->json([
                'errors' => $rebalance['errors'] ?? ['unknown error'],
            ], $rebalance['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $rebalance['data'] ?? null,
        ]);
    }

    public function getLoadBalancerScript(Request $request, $uuid, $type): JsonResponse
    {
        $model = PuqPmLoadBalancer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->puqPmScripts()->where('type', $type)->first();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function putLoadBalancerScript(Request $request, $uuid, $type): JsonResponse
    {
        $model = PuqPmLoadBalancer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $scriptModel = $model->puqPmScripts()->where('type', $type)->first();

        if ($scriptModel) {
            $scriptModel->script = $request->input('script');
            $scriptModel->save();
        } else {
            $scriptModel = $model->puqPmScripts()->create([
                'type' => $type,
                'script' => $request->input('script'),
                'model' => PuqPmLoadBalancer::class,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $scriptModel,
        ]);
    }

    public function putLoadBalancerDefaultScript(Request $request, $uuid, $type): JsonResponse
    {
        $model = PuqPmLoadBalancer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $load = $model->loadDefaultScript($type);

        if ($load['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $load['errors'],
            ], $load['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
        ]);
    }

    public function putLoadBalancerDeployConfig(Request $request, $uuid, $type): JsonResponse
    {
        $model = PuqPmLoadBalancer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if ($type == 'nginx-conf') {
            $deploy = $model->deployMainConfig(true);
        }
        if ($type == 'service-conf') {
            $deploy = $model->deployServiceConfig(true);
        }

        if ($deploy['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $deploy['errors'] ?? [],
            ], $deploy['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deploy Successfully'),
        ]);
    }

    public function putLoadBalancerDeployAll(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLoadBalancer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = [
            'module' => $model,
            'method' => 'deployAll',        // The method name that should be executed inside the job
            'callback' => 'deployAllCallback',
            // Optional. The method name in the module that will be executed after the main method is finished.
            // Receives the result and jobId as parameters.
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 3600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'deployAll'
        ];

        Task::add('ModuleJob', 'puqProxmox-LoadBalancer', $data, $tags);

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deploy Successfully'),
        ]);
    }


    // WEB Proxies ------------------------------------------------------------------------------
    public function getLoadBalancerWebProxies(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLoadBalancer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $model->puqPmWebProxies();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('uuid', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) use ($uuid) {
                    $urls = [];

                    $urls['edit'] = route('admin.web.Product.puqProxmox.load_balancer.tab',
                        ['uuid' => $uuid, 'tab' => 'web_proxies', 'edit' => $model->uuid]);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.web_proxy.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getWebProxy(Request $request, $uuid): JsonResponse
    {

        $model = PuqPmWebProxy::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $model,
        ]);
    }

    public function postWebProxy(Request $request): JsonResponse
    {
        $model = new PuqPmWebProxy;

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:puq_pm_web_proxies,name',
            'puq_pm_load_balancer_uuid' => 'required|exists:puq_pm_load_balancers,uuid',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'puq_pm_load_balancer_uuid.required' => __('Product.puqProxmox.Load Balancer is required'),
            'puq_pm_load_balancer_uuid.exists' => __('Product.puqProxmox.Load Balancer not found'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->puq_pm_load_balancer_uuid = $request->input('puq_pm_load_balancer_uuid');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function putWebProxy(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmWebProxy::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:puq_pm_web_proxies,name,'.$uuid.',uuid',
            'disable' => 'nullable|in:yes,no',
            'api_url' => 'nullable|url|max:255',
            'api_key' => 'nullable|string|max:255',
            'frontend_ips' => 'nullable|string',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'api_url.url' => __('Product.puqProxmox.Invalid API URL format'),
            'disable.in' => __('Product.puqProxmox.Disable must be yes or no'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->disable = $request->input('disable') === 'yes';
        $model->api_url = $request->input('api_url');
        $model->api_key = $request->input('api_key');

        $frontendIps = $request->input('frontend_ips');
        $validIps = [];

        if (!empty($frontendIps)) {
            $decoded = json_decode($frontendIps, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return response()->json([
                    'message' => ['frontend_ips' => [__('Product.puqProxmox.Invalid JSON format in frontend_ips')]],
                ], 422);
            }

            foreach ($decoded as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $validIps[compressIpv6($ip)] = compressIpv6($ip);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $validIps[$ip] = $ip;
                }
            }
        }

        $model->frontend_ips = array_values($validIps);
        $model->save();
        $model->refresh();

        $rebalance = $model->puqPmLoadBalancer->rebalance();
        if ($rebalance['status'] == 'error') {
            return response()->json([
                'errors' => $rebalance['errors'] ?? ['unknown error'],
            ], $rebalance['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteWebProxy(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmWebProxy::find($uuid);

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

    public function getWebProxySystemStatus(Request $request, $uuid): JsonResponse
    {

        $model = PuqPmWebProxy::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $system_status = $model->getSystemStatus();
        if ($system_status['status'] == 'error') {
            return response()->json([
                'errors' => $system_status['errors'] ?? ['unknown error'],
            ], $system_status['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $system_status['data'] ?? [],
        ]);
    }

}
