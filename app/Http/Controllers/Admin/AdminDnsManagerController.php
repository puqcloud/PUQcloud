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

/*
 * WEB Routes:
 *   GET  dns_server_groups                                     -> dnsServerGroups()                 [admin.web.dns_server_groups]
 *   GET  dns_server_group/{uuid}                               -> dnsServerGroup()                  [admin.web.dns_server_group]
 *   GET  dns_servers                                           -> dnsServers()                      [admin.web.dns_servers]
 *   GET  dns_server/{uuid}                                     -> dnsServer()                       [admin.web.dns_server]
 *   GET  dns_server/{uuid}/import                              -> dnsZoneImport()                   [admin.web.dns_server.import]
 *   GET  dns_zones                                             -> dnsZones()                        [admin.web.dns_zones]
 *   GET  dns_zone/{uuid}                                       -> dnsZone()                         [admin.web.dns_zone]
 *   GET  dns_zone_templates                                    -> dnsZoneTemplates()                [admin.web.dns_zone_templates]
 *   GET  dns_zone_template/{uuid}                              -> dnsZoneTemplate()                 [admin.web.dns_zone_template]
 *
 * API Routes:
 *   GET    dns_server_groups                                   -> getDnsServerGroups()              [admin.api.dns_server_groups.get]
 *   POST   dns_server_group                                    -> postDnsServerGroup()              [admin.api.dns_server_group.post]
 *   GET    dns_server_group/{uuid}                             -> getDnsServerGroup()               [admin.api.dns_server_group.get]
 *   PUT    dns_server_group/{uuid}                             -> putDnsServerGroup()               [admin.api.dns_server_group.put]
 *   DELETE dns_server_group/{uuid}                             -> deleteDnsServerGroup()            [admin.api.dns_server_group.delete]
 *   GET    dns_server_group/{uuid}/reload_all_zones            -> getDnsServerGroupReloadAllZones() [admin.api.dns_server_group.reload_all_zones.get]
 *   GET    dns_servers                                         -> getDnsServers()                   [admin.api.dns_servers.get]
 *   POST   dns_server                                          -> postDnsServer()                   [admin.api.dns_server.post]
 *   GET    dns_server/{uuid}                                   -> getDnsServer()                    [admin.api.dns_server.get]
 *   PUT    dns_server/{uuid}                                   -> putDnsServer()                    [admin.api.dns_server.put]
 *   DELETE dns_server/{uuid}                                   -> deleteDnsServer()                 [admin.api.dns_server.delete]
 *   GET    dns_server/{uuid}/dns_zones                         -> getDnsServerDnsZones()            [admin.api.dns_server.dns_zones.get]
 *   POST   dns_server/{uuid}/import_zones                      -> postDnsServerImportZones()        [admin.api.dns_server.import_zones.post]
 *   GET    dns_server/{uuid}/test_connection                   -> getDnsServerTestConnection()      [admin.api.dns_server.test_connection.get]
 *   GET    dns_zones                                           -> getDnsZones()                     [admin.api.dns_zones.get]
 *   POST   dns_zone                                            -> postDnsZone()                     [admin.api.dns_zone.post]
 *   GET    dns_zone/{uuid}                                     -> getDnsZone()                      [admin.api.dns_zone.get]
 *   PUT    dns_zone/{uuid}                                     -> putDnsZone()                      [admin.api.dns_zone.put]
 *   DELETE dns_zone/{uuid}                                     -> deleteDnsZone()                   [admin.api.dns_zone.delete]
 *   PUT    dns_zone/{uuid}/move_to                             -> putDnsZoneMoveTo()                [admin.api.dns_zone.move_to.put]
 *   GET    dns_zone/{uuid}/reload                              -> getDnsZoneReload()                [admin.api.dns_zone.reload.get]
 *   GET    dns_zone/{uuid}/export/bind                         -> getDnsZoneExportBind()            [admin.api.dns_zone.export.bind.get]
 *   GET    dns_zone/{uuid}/export/json                         -> getDnsZoneExportJson()            [admin.api.dns_zone.export.json.get]
 *   POST   dns_zone/{uuid}/import/bind                         -> postDnsZoneImportBind()           [admin.api.dns_zone.import.bind.post]
 *   POST   dns_zone/{uuid}/import/json                         -> postDnsZoneImportJson()           [admin.api.dns_zone.import.json.post]
 *   DELETE dns_zone/{uuid}/dns_records/bulk                    -> deleteDnsZoneDnsRecordsBulk()     [admin.api.dns_zone.dns_records.bulk_delete]
 *   GET    dns_zone/{uuid}/dns_records                         -> getDnsZoneDnsRecords()            [admin.api.dns_zone.dns_records.get]
 *   POST   dns_zone/{uuid}/dns_record                          -> postDnsZoneDnsRecord()            [admin.api.dns_zone.dns_record.post]
 *   GET    dns_zone/{uuid}/dns_record/{r_uuid}                 -> getDnsZoneDnsRecord()             [admin.api.dns_zone.dns_record.get]
 *   PUT    dns_zone/{uuid}/dns_record/{r_uuid}                 -> putDnsZoneDnsRecord()             [admin.api.dns_zone.dns_record.put]
 *   DELETE dns_zone/{uuid}/dns_record/{r_uuid}                 -> deleteDnsZoneDnsRecord()          [admin.api.dns_zone.dns_record.delete]
 *   GET    dns_server_modules/select                           -> getDnsServerModulesSelect()       [admin.api.dns_server_modules.select.get]
 *   GET    dns_servers/select                                  -> getDnsServersSelect()             [admin.api.dns_servers.select.get]
 *   GET    dns_server_groups/select                            -> getDnsServerGroupsSelect()        [admin.api.dns_server_groups.select.get]
 *   GET    dns_zones/select                                    -> getDnsZonesSelect()               [admin.api.dns_zones.select.get]
 *   POST   dns_zone/{uuid}/clone                               -> postDnsZoneClone()                [admin.api.dns_zone.clone.post]
 *   POST   dns_zone/{uuid}/save_as_template                    -> postDnsZoneTemplateFromZone()     [admin.api.dns_zone.save_as_template.post]
 *   GET    dns_zone_templates                                  -> getDnsZoneTemplates()             [admin.api.dns_zone_templates.get]
 *   POST   dns_zone_template                                   -> postDnsZoneTemplate()             [admin.api.dns_zone_template.post]
 *   GET    dns_zone_template/{uuid}                            -> getDnsZoneTemplate()              [admin.api.dns_zone_template.get]
 *   PUT    dns_zone_template/{uuid}                            -> putDnsZoneTemplate()              [admin.api.dns_zone_template.put]
 *   DELETE dns_zone_template/{uuid}                            -> deleteDnsZoneTemplate()           [admin.api.dns_zone_template.delete]
 *   GET    dns_zone_templates/select                           -> getDnsZoneTemplatesSelect()       [admin.api.dns_zone_templates.select.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DnsRecord;
use App\Models\DnsServer;
use App\Models\DnsServerGroup;
use App\Models\DnsZone;
use App\Models\DnsZoneTemplate;
use App\Models\Module;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
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

    #[OA\Get(
        path: 'admin.api.dns_server_groups.get',
        description: 'Returns a DataTable list of DNS server groups.',
        summary: 'Get DNS server groups list',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Server Groups'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'DNS server groups list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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
                        $urls['edit'] = route('admin.web.dns_server_group', $model->uuid);
                        $urls['delete'] = route('admin.api.dns_server_group.delete', $model->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.dns_server_group.get',
        description: 'Returns detailed information about a DNS server group including associated DNS servers.',
        summary: 'Get DNS server group by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS server group', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS server group data returned successfully',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object')),
            new OA\Response(response: 404, description: 'DNS server group not found'),
        ]
    )]
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

    #[OA\Post(
        path: 'admin.api.dns_server_group.post',
        description: 'Creates a new DNS server group with specified name, description, and NS domains.',
        summary: 'Create a new DNS server group',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'name', description: 'Group name (must be unique)', type: 'string'),
                        new OA\Property(property: 'description', description: 'Group description', type: 'string', nullable: true),
                        new OA\Property(property: 'ns_domains', description: 'NS domains (one per line)', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['DNS Manager'],
        responses: [
            new OA\Response(response: 200, description: 'DNS server group created successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                    new OA\Property(property: 'redirect', type: 'string'),
                ], type: 'object')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Delete(
        path: 'admin.api.dns_server_group.delete',
        description: 'Deletes a DNS server group by UUID.',
        summary: 'Delete DNS server group by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS server group to delete', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS server group deleted successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS server group not found'),
            new OA\Response(response: 500, description: 'Deletion failed'),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.dns_server_group.put',
        description: 'Updates a DNS server group by UUID with name, description, NS domains, and associated DNS servers.',
        summary: 'Update DNS server group by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS server group', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'name', description: 'Group name (must be unique)', type: 'string'),
                        new OA\Property(property: 'description', description: 'Group description', type: 'string', nullable: true),
                        new OA\Property(property: 'ns_domains', description: 'NS domains (one per line)', type: 'string'),
                        new OA\Property(property: 'ns_ttl', description: 'NS TTL value', type: 'integer', example: 3600),
                        new OA\Property(property: 'dns_server_uuids', description: 'Array of DNS server UUIDs to associate', type: 'array',
                            items: new OA\Items(type: 'string', format: 'uuid')),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'DNS server group updated successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS server group not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_server_group.reload_all_zones.get',
        description: 'Reloads all DNS zones associated with a DNS server group.',
        summary: 'Reload all zones for DNS server group',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS server group', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'All zones reloaded successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS server group not found'),
        ]
    )]
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


    #[OA\Get(
        path: 'admin.api.dns_servers.get',
        description: 'Returns a DataTable list of DNS servers.',
        summary: 'Get DNS servers list',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Servers'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'DNS servers list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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
                        $urls['edit'] = route('admin.web.dns_server', $model->uuid);
                        $urls['delete'] = route('admin.api.dns_server.delete', $model->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.dns_server.get',
        description: 'Returns detailed information about a DNS server including module settings page.',
        summary: 'Get DNS server by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS server', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS server data returned successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS server not found'),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_server.dns_zones.get',
        description: 'Returns all DNS zones from a DNS server.',
        summary: 'Get DNS zones for DNS server',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS server', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS zones returned successfully',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object')),
            new OA\Response(response: 404, description: 'DNS server not found'),
            new OA\Response(response: 500, description: 'Error fetching DNS zones'),
        ]
    )]
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

    #[OA\Post(
        path: 'admin.api.dns_server.import_zones.post',
        description: 'Imports DNS zones from a DNS server into a DNS server group.',
        summary: 'Import DNS zones from DNS server',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS server', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['zones', 'import_mode', 'dns_server_group_uuid'],
                    properties: [
                        new OA\Property(property: 'zones', description: 'Array of zone names to import', type: 'array',
                            items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'import_mode', description: 'Import mode', type: 'string', enum: ['add', 'replace']),
                        new OA\Property(property: 'dns_server_group_uuid', description: 'UUID of the target DNS server group', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Zones imported successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS server not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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


    #[OA\Post(
        path: 'admin.api.dns_server.post',
        description: 'Creates a new DNS server with specified name and module.',
        summary: 'Create a new DNS server',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name', 'module'],
                    properties: [
                        new OA\Property(property: 'name', description: 'Server name (must be unique)', type: 'string'),
                        new OA\Property(property: 'module', description: 'UUID of the DNS server module', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'description', description: 'Server description', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        tags: ['DNS Manager'],
        responses: [
            new OA\Response(response: 200, description: 'DNS server created successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                    new OA\Property(property: 'redirect', type: 'string'),
                ], type: 'object')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.dns_server.put',
        description: 'Updates a DNS server by UUID with name, description, and module-specific data.',
        summary: 'Update DNS server by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS server', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', description: 'Server name (must be unique)', type: 'string'),
                        new OA\Property(property: 'description', description: 'Server description', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'DNS server updated successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS server not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Delete(
        path: 'admin.api.dns_server.delete',
        description: 'Deletes a DNS server by UUID.',
        summary: 'Delete DNS server by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS server to delete', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS server deleted successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS server not found'),
            new OA\Response(response: 500, description: 'Deletion failed'),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_server_modules.select.get',
        description: 'Returns a select list of DNS server modules.',
        summary: 'Get DNS server modules select list',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Servers'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'DNS server modules select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_server.test_connection.get',
        description: 'Tests the connection to a DNS server.',
        summary: 'Test DNS server connection',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS server', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Connection test result returned successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS server not found'),
            new OA\Response(response: 500, description: 'Connection test failed'),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_servers.select.get',
        description: 'Returns a select list of DNS servers.',
        summary: 'Get DNS servers select list',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Servers'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'DNS servers select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_zones.select.get',
        description: 'Returns a select list of DNS zones.',
        summary: 'Get DNS zones select list',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Zones'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'DNS zones select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    public function dnsZoneTemplates(): View
    {
        $title = __('main.DNS Zone Templates');

        return view_admin('dns_zones.dns_zone_templates', compact('title'));
    }

    public function dnsZoneTemplate(Request $request, $uuid): View
    {
        $title = __('main.DNS Zone Template');

        return view_admin('dns_zones.dns_zone_template', compact('title', 'uuid'));
    }

    #[OA\Get(
        path: 'admin.api.dns_zones.get',
        description: 'Returns a DataTable list of DNS zones.',
        summary: 'Get DNS zones list',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Zones'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'DNS zones list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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
                        $urls['edit'] = route('admin.web.dns_zone', $model->uuid);
                        $urls['delete'] = route('admin.api.dns_zone.delete', $model->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.dns_zone.post',
        description: 'Creates a new DNS zone.',
        summary: 'Create a new DNS zone',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', description: 'Zone name (domain)', type: 'string'),
                        new OA\Property(property: 'dns_server_group_uuid', description: 'UUID of the DNS server group', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['DNS Manager'],
        responses: [
            new OA\Response(response: 200, description: 'DNS zone created successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                    new OA\Property(property: 'redirect', type: 'string'),
                ], type: 'object')),
            new OA\Response(response: 412, description: 'Zone creation failed'),
        ]
    )]
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

        // Apply template if specified
        if ($request->has('template_uuid') && ! empty($request->get('template_uuid'))) {
            $template = DnsZoneTemplate::find($request->get('template_uuid'));
            if ($template) {
                $variables = array_merge(
                    ['DOMAIN' => $model->name],
                    $request->get('template_variables', [])
                );
                $bindContent = $template->render($variables);
                $model->importBind($bindContent, 'add');
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $model,
            'redirect' => route('admin.web.dns_zone', $model->uuid),
        ]);
    }

    #[OA\Put(
        path: 'admin.api.dns_zone.put',
        description: 'Updates SOA settings for a DNS zone by UUID.',
        summary: 'Update DNS zone by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['soa_admin_email', 'soa_ttl', 'soa_refresh', 'soa_retry', 'soa_expire', 'soa_minimum'],
                    properties: [
                        new OA\Property(property: 'soa_admin_email', description: 'SOA admin email', type: 'string', format: 'email'),
                        new OA\Property(property: 'soa_ttl', description: 'SOA TTL value', type: 'integer', example: 3600),
                        new OA\Property(property: 'soa_refresh', description: 'SOA refresh interval', type: 'integer', example: 3600),
                        new OA\Property(property: 'soa_retry', description: 'SOA retry interval', type: 'integer', example: 900),
                        new OA\Property(property: 'soa_expire', description: 'SOA expire time', type: 'integer', example: 604800),
                        new OA\Property(property: 'soa_minimum', description: 'SOA minimum TTL', type: 'integer', example: 86400),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'DNS zone updated successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.dns_zone.move_to.put',
        description: 'Moves a DNS zone to a different DNS server group.',
        summary: 'Move DNS zone to another server group',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['dns_server_group_uuid'],
                    properties: [
                        new OA\Property(property: 'dns_server_group_uuid', description: 'UUID of the target DNS server group', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'DNS zone moved successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone not found'),
        ]
    )]
    public function putDnsZoneMoveTo(Request $request, $uuid): JsonResponse
    {

        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }


        $move_to = $model->moveTo($request->get('dns_server_group_uuid'));

        if ($move_to['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $move_to['error'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.dns_zone.get',
        description: 'Returns detailed information about a DNS zone including SOA data and server group.',
        summary: 'Get DNS zone by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS zone data returned successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone not found'),
        ]
    )]
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

    #[OA\Delete(
        path: 'admin.api.dns_zone.delete',
        description: 'Deletes a DNS zone by UUID.',
        summary: 'Delete DNS zone by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone to delete', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS zone deleted successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone not found'),
            new OA\Response(response: 500, description: 'Deletion failed'),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_zone.reload.get',
        description: 'Reloads a DNS zone on all associated DNS servers.',
        summary: 'Reload DNS zone',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS zone reloaded successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone not found'),
            new OA\Response(response: 500, description: 'Reload failed'),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_zone.export.bind.get',
        description: 'Exports a DNS zone in BIND format as a base64-encoded file.',
        summary: 'Export DNS zone in BIND format',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'BIND zone file exported successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'file_name', type: 'string'),
                        new OA\Property(property: 'file_content', description: 'Base64-encoded zone file content', type: 'string'),
                    ], type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone not found'),
            new OA\Response(response: 500, description: 'Export failed'),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_zone.export.json.get',
        description: 'Exports a DNS zone in JSON format.',
        summary: 'Export DNS zone in JSON format',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'JSON zone data exported successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone not found'),
            new OA\Response(response: 500, description: 'Export failed'),
        ]
    )]
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

    public function postDnsZoneImportBind(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'file_content' => 'required|string',
            'import_mode' => 'required|in:add,replace',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $content = base64_decode($request->get('file_content'));
        if ($content === false) {
            return response()->json([
                'errors' => [__('error.Invalid file content')],
            ], 422);
        }

        $result = $model->importBind($content, $request->get('import_mode'));

        logActivity(
            'info',
            'DNS Zone: '.$model->name.' BIND import. Success: '.$result['data']['success'].'. Errors: '.$result['data']['errors'],
            'DNS zone import',
        );

        return response()->json([
            'status' => 'success',
            'message' => __('message.Successfully').'. '.__('main.Imported').': '.$result['data']['success'].'. '.__('main.Errors').': '.$result['data']['errors'],
            'data' => $result['data'],
        ]);
    }

    public function postDnsZoneImportJson(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'file_content' => 'required|string',
            'import_mode' => 'required|in:add,replace',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $content = base64_decode($request->get('file_content'));
        if ($content === false) {
            return response()->json([
                'errors' => [__('error.Invalid file content')],
            ], 422);
        }

        $jsonData = json_decode($content, true);
        if (! is_array($jsonData)) {
            return response()->json([
                'errors' => [__('error.Invalid JSON format')],
            ], 422);
        }

        $result = $model->importJson($jsonData, $request->get('import_mode'));

        logActivity(
            'info',
            'DNS Zone: '.$model->name.' JSON import. Success: '.$result['data']['success'].'. Errors: '.$result['data']['errors'],
            'DNS zone import',
        );

        return response()->json([
            'status' => 'success',
            'message' => __('message.Successfully').'. '.__('main.Imported').': '.$result['data']['success'].'. '.__('main.Errors').': '.$result['data']['errors'],
            'data' => $result['data'],
        ]);
    }

    public function deleteDnsZoneDnsRecordsBulk(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'uuids' => 'required|array|min:1',
            'uuids.*' => 'required|string|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $deleted = 0;
        $errors = 0;

        foreach ($request->get('uuids') as $recordUuid) {
            $result = $model->deleteRecord($recordUuid);
            if ($result['status'] === 'error') {
                $errors++;
            } else {
                $deleted++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully').'. '.$deleted.' / '.($deleted + $errors),
        ]);
    }

    // Zone Templates ---------------------------------------------------------------------------------------------------

    public function getDnsZoneTemplates(Request $request): JsonResponse
    {
        $query = DnsZoneTemplate::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->addColumn('urls', function ($template) {
                    return [
                        'edit' => route('admin.web.dns_zone_template', $template->uuid),
                        'delete' => route('admin.api.dns_zone_template.delete', $template->uuid),
                    ];
                })
                ->make(true),
        ], 200);
    }

    public function getDnsZoneTemplate(Request $request, $uuid): JsonResponse
    {
        $model = DnsZoneTemplate::find($uuid);

        if (empty($model)) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        return response()->json(['data' => $model]);
    }

    public function postDnsZoneTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:dns_zone_templates,name',
            'description' => 'nullable|string|max:1000',
            'content' => 'required|string',
        ], [
            'name.required' => __('error.The name field is required'),
            'name.unique' => __('error.A template with this name already exists'),
            'content.required' => __('error.The content field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $template = DnsZoneTemplate::create($request->only(['name', 'description', 'content']));

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $template,
            'redirect' => route('admin.web.dns_zone_template', $template->uuid),
        ]);
    }

    public function putDnsZoneTemplate(Request $request, $uuid): JsonResponse
    {
        $model = DnsZoneTemplate::find($uuid);

        if (empty($model)) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:dns_zone_templates,name,'.$uuid.',uuid',
            'description' => 'nullable|string|max:1000',
            'content' => 'required|string',
        ], [
            'name.required' => __('error.The name field is required'),
            'name.unique' => __('error.A template with this name already exists'),
            'content.required' => __('error.The content field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $model->update($request->only(['name', 'description', 'content']));

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteDnsZoneTemplate(Request $request, $uuid): JsonResponse
    {
        $model = DnsZoneTemplate::find($uuid);

        if (empty($model)) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $model->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    public function getDnsZoneTemplatesSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');
        $query = DnsZoneTemplate::query();

        if (! empty($search)) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $results = $query->orderBy('name')->get()->map(function ($t) {
            return ['id' => $t->uuid, 'text' => $t->name];
        });

        return response()->json(['data' => ['results' => $results, 'pagination' => ['more' => false]]]);
    }

    public function postDnsZoneTemplateFromZone(Request $request, $uuid): JsonResponse
    {
        $zone = DnsZone::find($uuid);

        if (empty($zone)) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:dns_zone_templates,name',
        ], [
            'name.required' => __('error.The name field is required'),
            'name.unique' => __('error.A template with this name already exists'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $export = $zone->exportBind();
        $content = $export['data'] ?? '';

        // Replace zone-specific values with variables
        $content = str_replace($zone->name, '{{DOMAIN}}', $content);

        $template = DnsZoneTemplate::create([
            'name' => $request->get('name'),
            'description' => 'Created from zone: '.$zone->name,
            'content' => $content,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $template,
        ]);
    }

    // Clone Zone -------------------------------------------------------------------------------------------------------

    public function postDnsZoneClone(Request $request, $uuid): JsonResponse
    {
        $sourceZone = DnsZone::find($uuid);

        if (empty($sourceZone)) {
            return response()->json(['errors' => [__('error.Not found')]], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:dns_zones,name',
        ], [
            'name.required' => __('error.The name field is required'),
            'name.unique' => __('error.A DNS zone with this name already exists'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $result = DnsZone::createZone([
            'name' => $request->get('name'),
            'soa_ttl' => $sourceZone->soa_ttl,
            'soa_admin_email' => str_replace($sourceZone->name, $request->get('name'), $sourceZone->soa_admin_email),
            'soa_refresh' => $sourceZone->soa_refresh,
            'soa_retry' => $sourceZone->soa_retry,
            'soa_expire' => $sourceZone->soa_expire,
            'soa_minimum' => $sourceZone->soa_minimum,
            'dns_server_group_uuid' => $sourceZone->dns_server_group_uuid,
        ]);

        if ($result['status'] === 'error') {
            return response()->json(['message' => $result['message'] ?? []], 412);
        }

        $newZone = $result['data'];
        $success = 0;
        $errors = 0;

        foreach ($sourceZone->dnsRecords as $record) {
            $res = $newZone->createUpdateRecord([
                'type' => $record->type,
                'name' => $record->name,
                'ttl' => $record->ttl,
                'content' => $record->content,
            ], null, true, true);
            $res['status'] === 'error' ? $errors++ : $success++;
        }

        $newZone->reloadZone();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully').'. Records: '.$success.($errors > 0 ? ', Errors: '.$errors : ''),
            'redirect' => route('admin.web.dns_zone', $newZone->uuid),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.dns_server_groups.select.get',
        description: 'Returns a select list of DNS server groups.',
        summary: 'Get DNS server groups select list',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Server Groups'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'DNS server groups select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_zone.dns_records.get',
        description: 'Returns a paginated list of DNS records for a DNS zone with search and filtering.',
        summary: 'List DNS records for DNS zone',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS records list returned successfully',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone not found'),
        ]
    )]
    public function getDnsZoneDnsRecords(Request $request, $uuid): JsonResponse
    {
        $model = DnsZone::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $query = $model->dnsRecords()->orderByRaw("CAST(name AS UNSIGNED) ASC");

        if ($request->has('type_filter') && ! empty($request->get('type_filter'))) {
            $query->where('type', $request->get('type_filter'));
        }

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

    #[OA\Post(
        path: 'admin.api.dns_zone.dns_record.post',
        description: 'Creates a new DNS record in a DNS zone.',
        summary: 'Create a new DNS record',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', description: 'Record name', type: 'string'),
                        new OA\Property(property: 'type', description: 'Record type (A, AAAA, CNAME, MX, TXT, etc.)', type: 'string'),
                        new OA\Property(property: 'content', description: 'Record content', type: 'string'),
                        new OA\Property(property: 'ttl', description: 'TTL value', type: 'integer'),
                        new OA\Property(property: 'priority', description: 'Record priority (for MX, SRV)', type: 'integer', nullable: true),
                        new OA\Property(property: 'description', description: 'Record description', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'DNS record created successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone not found'),
            new OA\Response(response: 412, description: 'Record creation failed'),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.dns_zone.dns_record.get',
        description: 'Returns detailed information about a DNS record in a DNS zone.',
        summary: 'Get DNS record by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'r_uuid', description: 'UUID of the DNS record', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS record data returned successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone or record not found'),
            new OA\Response(response: 412, description: 'Error retrieving record'),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.dns_zone.dns_record.put',
        description: 'Updates a DNS record in a DNS zone.',
        summary: 'Update DNS record by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'r_uuid', description: 'UUID of the DNS record', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', description: 'Record name', type: 'string'),
                        new OA\Property(property: 'type', description: 'Record type (A, AAAA, CNAME, MX, TXT, etc.)', type: 'string'),
                        new OA\Property(property: 'content', description: 'Record content', type: 'string'),
                        new OA\Property(property: 'ttl', description: 'TTL value', type: 'integer'),
                        new OA\Property(property: 'priority', description: 'Record priority (for MX, SRV)', type: 'integer', nullable: true),
                        new OA\Property(property: 'description', description: 'Record description', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'DNS record updated successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone or record not found'),
            new OA\Response(response: 412, description: 'Record update failed'),
        ]
    )]
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

    #[OA\Delete(
        path: 'admin.api.dns_zone.dns_record.delete',
        description: 'Deletes a DNS record from a DNS zone.',
        summary: 'Delete DNS record by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['DNS Manager'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the DNS zone', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'r_uuid', description: 'UUID of the DNS record to delete', in: 'path', required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'DNS record deleted successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'DNS zone or record not found'),
            new OA\Response(response: 500, description: 'Deletion failed'),
        ]
    )]
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
