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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DnsServer;
use App\Models\DnsServerGroup;
use App\Models\DnsZone;
use App\Models\Module;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AdminDnsManagerController extends Controller
{
    public function dnsServerGroups(): View
    {
        $title = __('main.DNS Server Groups');

        return view_admin('dns_server_groups.dns_server_groups', compact('title'));
    }

    public function dnsServerGroup(Request $request, $uuid): View
    {
        $title = __('main.DNS Server Group');

        return view_admin('dns_server_groups.dns_server_group', compact('title', 'uuid'));
    }

    public function getDnsServerGroups(Request $request): JsonResponse
    {
        $query = DnsServerGroup::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%")
                                ->orWhere('ns_domains', 'like', "%{$search}%");

                        });
                    }
                })
                ->addColumn('urls', function ($model) {
                    $admin = app('admin');
                    $urls = [];

                    if ($admin->hasPermission('dns-manager-dns-server-groups')) {
                        $urls['web_edit'] = route('admin.web.dns_server_group', $model->uuid);
                        $urls['delete'] = route('admin.api.dns_server_group.delete', $model->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getDnsServerGroup(Request $request, $uuid): JsonResponse
    {
        $model = DnsServerGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $responseData = $model->toArray();
        $responseData['dns_servers'] = [];
        foreach ($model->DnsServers as $DnsServer) {

            $responseData['dns_servers'][] = [
                'id' => $DnsServer->uuid,
                'text' => $DnsServer->name,
            ];
        }

        return response()->json([
            'data' => $responseData,
        ]);
    }

    public function postDnsServerGroup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:dns_server_groups,name',
            'description' => 'nullable|string|max:1000',
            'ns_domains' => [
                'string', function ($attribute, $value, $fail) {
                    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value)));
                    if (empty($lines)) {
                        return $fail(__('error.At least one NS domain is required'));
                    }
                    foreach ($lines as $line) {
                        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $line)) {
                            return $fail(__('error.Invalid NS domain: :domain', ['domain' => $line]));
                        }
                    }
                },
            ],
        ], [
            'name.unique' => __('error.The name is already taken'),
            'name.required' => __('error.The name field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new DnsServerGroup;

        $model->name = $request->input('name');
        $model->description = $request->input('description') ?? '';

        $nsDomainsRaw = $request->input('ns_domains') ?? '';
        $nsDomains = [];
        if (!empty($nsDomainsRaw)) {
            $nsDomains = array_values(array_unique(array_filter(array_map('trim',
                preg_split('/\r\n|\r|\n/', $nsDomainsRaw)))));
        }

        $model->ns_domains = $nsDomains;

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $model,
            'redirect' => route('admin.web.dns_server_group', $model->uuid),
        ]);
    }

    public function deleteDnsServerGroup(Request $request, $uuid): JsonResponse
    {
        $model = DnsServerGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('error.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('error.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);

    }

    public function putDnsServerGroup(Request $request, $uuid): JsonResponse
    {
        $model = DnsServerGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:dns_server_groups,name,'.$model->uuid.',uuid',
            'description' => 'nullable|string|max:1000',
            'ns_domains' => [
                'string', function ($attribute, $value, $fail) {
                    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value)));
                    if (empty($lines)) {
                        return $fail(__('error.At least one NS domain is required'));
                    }
                    foreach ($lines as $line) {
                        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $line)) {
                            return $fail(__('error.Invalid NS domain: :domain', ['domain' => $line]));
                        }
                    }
                },
            ],
        ], [
            'name.unique' => __('error.The name is already taken'),
            'name.required' => __('error.The name field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->description = $request->input('description') ?? '';

        $model->ns_ttl = $request->input('ns_ttl') ?? 3600;

        $nsDomainsRaw = $request->input('ns_domains') ?? '';
        $nsDomains = [];
        if (!empty($nsDomainsRaw)) {
            $nsDomains = array_values(array_unique(array_filter(array_map('trim',
                preg_split('/\r\n|\r|\n/', $nsDomainsRaw)))));
        }

        $model->ns_domains = $nsDomains;

        $model->save();
        $model->refresh();

        $model->DnsServers()->sync($request->input('dns_server_uuids', []));

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function getDnsServerGroupReloadAllZones(Request $request, $uuid): JsonResponse
    {
        $model = DnsServerGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $dns_zones = $model->dnsZones;

        foreach ($dns_zones as $zone_zone) {
            $reload = $zone_zone->reloadZone();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Reload successfully'),
            'data' => $model,
        ]);
    }


    public function dnsServers(): View
    {
        $title = __('main.DNS Servers');

        return view_admin('dns_servers.dns_servers', compact('title'));
    }

    public function dnsServer(Request $request, $uuid): View
    {
        $title = __('main.DNS Server');

        return view_admin('dns_servers.dns_server', compact('title', 'uuid'));
    }

    public function dnsZoneImport(Request $request, $uuid): View
    {
        $title = __('main.DNS Server Import Zones');

        return view_admin('dns_servers.dns_server_import', compact('title', 'uuid'));
    }


    public function getDnsServers(Request $request): JsonResponse
    {
        $query = DnsServer::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");

                        });
                    }
                })
                ->addColumn('dns_server_groups', function ($model) {
                    return $model->dnsServerGroups;
                })
                ->addColumn('module_data', function ($model) {
                    return $model->getModuleConfig();
                })
                ->addColumn('urls', function ($model) {
                    $admin = app('admin');
                    $urls = [];

                    if ($admin->hasPermission('dns-manager-dns-servers')) {
                        $urls['web_edit'] = route('admin.web.dns_server', $model->uuid);
                        $urls['delete'] = route('admin.api.dns_server.delete', $model->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getDnsServer(Request $request, $uuid): JsonResponse
    {
        $model = DnsServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $module_html = $model->getSettingsPage();
        $responseData = $model->toArray();
        $responseData['module_html'] = $module_html;

        return response()->json([
            'status' => 'success',
            'data' => $responseData,
        ], 200);
    }

    public function getDnsServerDnsZones(Request $request, $uuid): JsonResponse
    {
        $model = DnsServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $dns_zones = $model->getDnsZones();

        if ($dns_zones['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $dns_zones['errors'],
            ], $dns_zones['code'] ?? 500);
        }

        return response()->json([
            'data' => [
                'original' => [
                    'data' => $dns_zones['data'],
                ],
            ],
        ]);
    }

    public function postDnsServerImportZones(Request $request, $uuid): JsonResponse
    {
        $model = DnsServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'zones' => 'required|array|min:1',
            'zones.*' => 'string',
            'import_mode' => 'required|in:add,replace',
            'dns_server_group_uuid' => 'required|exists:dns_server_groups,uuid',
        ], [
            'zones.required' => __('error.Zones list is required'),
            'zones.array' => __('error.Zones must be an array'),
            'zones.min' => __('error.At least one zone must be selected'),
            'import_mode.required' => __('error.Mode is required'),
            'import_mode.in' => __('error.Invalid mode selected'),
            'dns_server_group_uuid.required' => __('error.DNS server group is required'),
            'dns_server_group_uuid.exists' => __('error.DNS server group not found'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $import_status = [];
        foreach ($request->zones as $zone) {
            $status = $model->importZone($zone, $request->import_mode, $request->dns_server_group_uuid);
            if ($status['status'] == 'error') {
                $import_status[] = $zone.': '.__('error.Import failed');
            } else {
                $import_status[] = $zone.': Success: '.$status['data']['success'].'. Error: '.$status['data']['errors'];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => implode('<br>', $import_status),
        ]);
    }


    public function postDnsServer(Request $request): JsonResponse
    {
        $model = new DnsServer;
        $modules = Module::query()->where('type', 'DnsServer')->get()->pluck('uuid')->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:dns_servers,name',
            'module' => 'required|in:'.implode(',', $modules),
        ], [
            'name.unique' => __('error.The name is already in taken'),
            'name.required' => __('error.The name field is required'),
            'module.in' => __('error.The selected Module is invalid'),
            'module.required' => __('error.The Module field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name') && !empty($request->input('name'))) {
            $model->name = $request->input('name');
        }
        if ($request->has('module') && !empty($request->input('module'))) {
            $model->module_uuid = $request->input('module');
        }

        $model->configuration = json_encode([]);
        $model->description = $request->input('description') ?? '';

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $model,
            'redirect' => route('admin.web.dns_server', $model->uuid),
        ]);
    }

    public function putDnsServer(Request $request, $uuid): JsonResponse
    {

        $model = DnsServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'unique:dns_servers,name,'.$model->uuid.',uuid',
        ], [
            'name.unique' => __('error.The name is already in taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->description = $request->input('description') ?? '';

        $save_module_data = $model->saveModuleData($request->all());

        if ($save_module_data['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $save_module_data['message'],
            ], $save_module_data['code']);
        }

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteDnsServer(Request $request, $uuid): JsonResponse
    {
        $model = DnsServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('error.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('error.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);

    }

    public function getDnsServerModulesSelect(Request $request): JsonResponse
    {
        $Module_models = Module::all();
        $modules = [];
        foreach ($Module_models as $module) {
            if ($module->type != 'DnsServer') {
                continue;
            }
            $module_name = !empty($module->module_data['name']) ? $module->module_data['name'] : $module->name;
            $modules[] = [
                'id' => $module->uuid,
                'text' => $module_name.' ('.$module->status.')',
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredModules = array_filter($modules, function ($module) use ($searchTerm) {
            return empty($searchTerm) || stripos($module['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredModules),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    public function getDnsServerTestConnection(Request $request, $uuid): JsonResponse
    {
        $model = DnsServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $test_connection = $model->testConnection();

        if ($test_connection['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $test_connection['errors'],
            ], $test_connection['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $test_connection['data'] ?? '',
        ], 200);
    }

    public function getDnsServersSelect(Request $request): JsonResponse
    {
        $models = DnsServer::all();
        $results = [];
        foreach ($models as $model) {
            $results[] = [
                'id' => $model->uuid,
                'text' => $model->name,
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredModules = array_filter($results, function ($result) use ($searchTerm) {
            return empty($searchTerm) || stripos($result['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredModules),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    public function getDnsZonesSelect(Request $request): JsonResponse
    {
        $models = DnsZone::all();
        $results = [];
        foreach ($models as $model) {
            $results[] = [
                'id' => $model->uuid,
                'text' => $model->name,
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredModules = array_filter($results, function ($result) use ($searchTerm) {
            return empty($searchTerm) || stripos($result['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredModules),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    public function dnsZones(): View
    {
        $title = __('main.DNS Zones');

        return view_admin('dns_zones.dns_zones', compact('title'));
    }

    public function dnsZone(Request $request, $uuid): View
    {
        $title = __('main.DNS Zone');

        return view_admin('dns_zones.dns_zone', compact('title', 'uuid'));
    }

    public function getDnsZones(Request $request): JsonResponse
    {
        $query = DnsZone::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('dns_server_group', function ($model) {
                    return $model->dnsServerGroup->toArray();
                })
                ->addColumn('soa_primary_ns', function ($model) {
                    return $model->getSoaPrimaryNs();
                })
                ->addColumn('dns_record_count', function ($model) {
                    return $model->getDnsRecordCount();
                })
                ->addColumn('urls', function ($model) {
                    $admin = app('admin');
                    $urls = [];

                    if ($admin->hasPermission('dns-manager-dns-zones')) {
                        $urls['web_edit'] = route('admin.web.dns_zone', $model->uuid);
                        $urls['delete'] = route('admin.api.dns_zone.delete', $model->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postDnsZone(Request $request): JsonResponse
    {
        $result = DnsZone::createZone($request->all());

        if ($result['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? [],
            ], 412);
        }

        $model = $result['data'];

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $model,
            'redirect' => route('admin.web.dns_zone', $model->uuid),
        ]);
    }

    public function putDnsZone(Request $request, $uuid): JsonResponse
    {

        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'soa_admin_email' => 'required|email|max:255',
            'soa_ttl' => 'required|integer|min:30',
            'soa_refresh' => 'required|integer|min:30',
            'soa_retry' => 'required|integer|min:30',
            'soa_expire' => 'required|integer|min:30',
            'soa_minimum' => 'required|integer|min:30',
        ], [
            'soa_admin_email.required' => __('error.The SOA admin email field is required'),
            'soa_admin_email.email' => __('error.The SOA admin email must be a valid email address'),
            'soa_admin_email.max' => __('error.The SOA admin email may not be greater than 255 characters'),

            'soa_ttl.required' => __('error.The SOA TTL field is required'),
            'soa_ttl.integer' => __('error.The SOA TTL must be an integer'),
            'soa_ttl.min' => __('error.The SOA TTL must be at least 10'),

            'soa_refresh.required' => __('error.The SOA refresh field is required'),
            'soa_refresh.integer' => __('error.The SOA refresh must be an integer'),
            'soa_refresh.min' => __('error.The SOA refresh must be at least 30'),

            'soa_retry.required' => __('error.The SOA retry field is required'),
            'soa_retry.integer' => __('error.The SOA retry must be an integer'),
            'soa_retry.min' => __('error.The SOA retry must be at least 30'),

            'soa_expire.required' => __('error.The SOA expire field is required'),
            'soa_expire.integer' => __('error.The SOA expire must be an integer'),
            'soa_expire.min' => __('error.The SOA expire must be at least 30'),

            'soa_minimum.required' => __('error.The SOA minimum field is required'),
            'soa_minimum.integer' => __('error.The SOA minimum must be an integer'),
            'soa_minimum.min' => __('error.The SOA minimum must be at least 30'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->soa_admin_email = $request->input('soa_admin_email');
        $model->soa_ttl = $request->input('soa_ttl');
        $model->soa_refresh = $request->input('soa_refresh');
        $model->soa_retry = $request->input('soa_retry');
        $model->soa_expire = $request->input('soa_expire');
        $model->soa_minimum = $request->input('soa_minimum');

        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function getDnsZone(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $responseData = $model->toArray();
        $responseData['soa_primary_ns'] = $model->getSoaPrimaryNs();
        $dns_server_group = $model->dnsServerGroup;
        $responseData['dns_server_group_name'] = $dns_server_group->name;
        $responseData['ns_domains'] = $dns_server_group->ns_domains;

        return response()->json([
            'status' => 'success',
            'data' => $responseData,
        ], 200);
    }

    public function deleteDnsZone(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('error.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('error.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);

    }

    public function getDnsZoneReload(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $reload = $model->reloadZone();
        if ($reload['status'] == 'error') {
            return response()->json([
                'errors' => $reload['errors'],
            ], $reload['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Reload successfully'),
        ]);
    }

    public function getDnsZoneExportBind(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $file_data = $model->exportBind();

        if ($file_data['status'] === 'error') {
            return response()->json([
                'errors' => $file_data['errors'],
            ], $file_data['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'file_name' => $model->name.'.zone',
                'file_content' => base64_encode($file_data['data']),
            ],
        ]);
    }

    public function getDnsZoneExportJson(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $json_data = $model->exportJson();

        if ($json_data['status'] === 'error') {
            return response()->json([
                'errors' => $json_data['errors'],
            ], $json_data['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $json_data['data'],
        ]);
    }


    public function getDnsServerGroupsSelect(Request $request): JsonResponse
    {
        $models = DnsServerGroup::all();
        $results = [];
        foreach ($models as $model) {
            $results[] = [
                'id' => $model->uuid,
                'text' => $model->name,
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredModules = array_filter($results, function ($result) use ($searchTerm) {
            return empty($searchTerm) || stripos($result['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredModules),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    public function getDnsZoneDnsRecords(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $query = $model->dnsRecords()->orderByRaw("CAST(name AS UNSIGNED) ASC");

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('type', 'like', "%{$search}%")
                                ->orWhere('content', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) {
                    $admin = app('admin');
                    $urls = [];

                    if ($admin->hasPermission('dns-manager-dns-records')) {
                        $urls['delete'] = route('admin.api.dns_zone.dns_record.delete',
                            ['uuid' => $model->dns_zone_uuid, 'r_uuid' => $model->uuid]);
                        $urls['get'] = route('admin.api.dns_zone.dns_record.get',
                            ['uuid' => $model->dns_zone_uuid, 'r_uuid' => $model->uuid]);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postDnsZoneDnsRecord(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $result = $model->createUpdateRecord($request->all());

        if ($result['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $result['errors'] ?? [],
                'message' => $result['message'] ?? [],
            ], $result['code'] ?? 412);
        }

        $model = $result['data'];

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $model,
        ]);
    }

    public function getDnsZoneDnsRecord(Request $request, $uuid, $r_uuid): JsonResponse
    {
        $dns_zone = DnsZone::find($uuid);

        if (empty($dns_zone)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $result = $dns_zone->getRecord($r_uuid);

        if ($result['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $result['errors'] ?? [],
            ], $result['code'] ?? 412);
        }

        return response()->json([
            'status' => 'success',
            'data' => $result['data'],
        ]);
    }

    public function putDnsZoneDnsRecord(Request $request, $uuid, $r_uuid): JsonResponse
    {
        $dns_zone = DnsZone::find($uuid);

        if (empty($dns_zone)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $result = $dns_zone->createUpdateRecord($request->all(), $r_uuid);

        if ($result['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $result['errors'] ?? [],
                'message' => $result['message'] ?? [],
            ], $result['code'] ?? 412);
        }

        $model = $result['data'];

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteDnsZoneDnsRecord(Request $request, $uuid, $r_uuid): JsonResponse
    {

        $dns_zone = DnsZone::find($uuid);

        if (empty($dns_zone)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $result = $dns_zone->deleteRecord($r_uuid);

        if ($result['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $result['errors'] ?? [],
            ], $result['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);

    }
}
