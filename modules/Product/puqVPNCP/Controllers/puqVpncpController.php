<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

namespace Modules\Product\puqVPNCP\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HomeCompany;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Product\puqVPNCP\Models\PuqVpncpServer;
use Modules\Product\puqVPNCP\Models\PuqVpncpServerGroup;
use OpenApi\Attributes as OA;
use puqVpncpClient;
use Yajra\DataTables\DataTables;

class puqVpncpController extends Controller
{
    // ─── Web Views ───────────────────────────────────────────────

    public function servers(Request $request): View
    {
        $title = __('Product.puqVPNCP.Servers');

        return view_admin_module('Product', 'puqVPNCP', 'admin_area.servers', compact('title'));
    }

    public function serverGroups(Request $request): View
    {
        $title = __('Product.puqVPNCP.Server Groups');

        return view_admin_module('Product', 'puqVPNCP', 'admin_area.server_groups', compact('title'));
    }

    public function serverGroupTab(Request $request, $uuid, $tab): View|RedirectResponse
    {
        $server_group = PuqVpncpServerGroup::findOrFail($uuid);

        $validTabs = [
            'general',
        ];

        if (! in_array($tab, $validTabs)) {
            return redirect()->route(
                'admin.web.Product.puqVPNCP.server_group.tab',
                ['uuid' => $server_group->uuid, 'tab' => 'general']
            );
        }

        $title = $server_group->name;

        return view_admin_module(
            'Product',
            'puqVPNCP',
            'admin_area.server_groups.server_group_'.$tab,
            compact('title', 'uuid', 'tab', 'server_group')
        );
    }

    public function serverTab(Request $request, $uuid, $tab): View|RedirectResponse
    {
        $server = PuqVpncpServer::findOrFail($uuid);

        $validTabs = [
            'general',
        ];

        if (! in_array($tab, $validTabs)) {
            return redirect()->route(
                'admin.web.Product.puqVPNCP.server.tab',
                ['uuid' => $server->uuid, 'tab' => 'general']
            );
        }

        $title = $server->name;

        return view_admin_module(
            'Product',
            'puqVPNCP',
            'admin_area.servers.server_'.$tab,
            compact('title', 'uuid', 'tab', 'server')
        );
    }

    // ─── Servers API ─────────────────────────────────────────────

    #[OA\Get(
        path: 'admin.api.Product.puqVPNCP.servers.get',
        description: 'Returns a list of all VPNCP servers with filtering and pagination support.',
        summary: 'Get servers list',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Servers list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getServers(Request $request): JsonResponse
    {
        $query = PuqVpncpServer::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('api_url', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('use_accounts', function ($model) {
                    return $model->getUseAccounts();
                })
                ->addColumn('free_slots', function ($model) {
                    return $model->getFreeSlots();
                })
                ->addColumn('total_accounts', function ($model) {
                    return $model->getTotalAccounts();
                })
                ->addColumn('license_accounts', function ($model) {
                    return $model->getLicenseAccounts();
                })
                ->addColumn('active_clients', function ($model) {
                    return $model->getActiveClientsCount();
                })
                ->addColumn('cached_status', function ($model) {
                    return $model->description ?? [];
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.web.Product.puqVPNCP.server.tab', ['uuid' => $model->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.Product.puqVPNCP.server.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.Product.puqVPNCP.server.post',
        description: 'Creates a new VPNCP server entry.',
        summary: 'Create server',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Server created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postServer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_vpncp_servers,name',
        ], [
            'name.required' => __('Product.puqVPNCP.The Name field is required'),
            'name.unique' => __('Product.puqVPNCP.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqVpncpServer;
        $model->name = $request->input('name');
        $model->api_url = '';
        $model->api_token = Crypt::encryptString('');
        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqVPNCP.Created successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.Product.puqVPNCP.server.delete',
        description: 'Deletes a VPNCP server by UUID.',
        summary: 'Delete server',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Server UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Server deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteServer(Request $request, $uuid): JsonResponse
    {
        $model = PuqVpncpServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Not found')],
            ], 404);
        }

        // Check if server can be deleted
        $errors = $model->canDelete();
        if (! empty($errors)) {
            return response()->json([
                'errors' => $errors,
            ], 422);
        }

        try {
            $deleted = $model->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('Product.puqVPNCP.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqVPNCP.Deleted successfully'),
            'redirect' => route('admin.web.Product.puqVPNCP.servers'),
        ]);
    }

    // ─── Server Groups API ───────────────────────────────────────

    #[OA\Get(
        path: 'admin.api.Product.puqVPNCP.server_groups.get',
        description: 'Returns a list of all VPNCP server groups with filtering and pagination support.',
        summary: 'Get server groups list',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Server groups list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getServerGroups(Request $request): JsonResponse
    {
        $query = PuqVpncpServerGroup::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('fill_type', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('servers_count', function ($model) {
                    return DB::table('puq_vpncp_server_group_server')
                        ->where('server_group_uuid', $model->uuid)
                        ->count();
                })
                ->addColumn('servers', function ($model) {
                    // Single JOIN instead of N+1 pivot→find loop
                    $rows = DB::table('puq_vpncp_server_group_server as p')
                        ->join('puq_vpncp_servers as s', 's.uuid', '=', 'p.server_uuid')
                        ->where('p.server_group_uuid', $model->uuid)
                        ->select('s.uuid', 's.name')
                        ->get();

                    return $rows->map(fn ($r) => [
                        'name' => $r->name,
                        'url' => route('admin.web.Product.puqVPNCP.server.tab', ['uuid' => $r->uuid, 'tab' => 'general']),
                    ])->all();
                })
                ->addColumn('country', function ($model) {
                    return $model->country->name ?? '';
                })
                ->addColumn('region', function ($model) {
                    return $model->region->name ?? '';
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.web.Product.puqVPNCP.server_group.tab',
                        ['uuid' => $model->uuid, 'tab' => 'general']);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqVPNCP.server_groups.select.get',
        description: 'Returns server groups formatted for select dropdown with optional search.',
        summary: 'Get server groups for select',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Server groups select data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getServerGroupsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (! empty($search)) {
            $models = PuqVpncpServerGroup::query()->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $models = PuqVpncpServerGroup::query()->get();
        }

        $results = [];
        foreach ($models->toArray() ?? [] as $model) {
            $results[] = [
                'id' => $model['uuid'],
                'text' => $model['name'],
            ];
        }

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqVPNCP.servers.select.get',
        description: 'Returns servers formatted for select dropdown with optional search.',
        summary: 'Get servers for select',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Servers select data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getServersSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        $query = PuqVpncpServer::query();
        if (! empty($search)) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        // Optionally exclude servers already in a specific group
        $excludeGroupUuid = $request->input('exclude_group_uuid');
        if ($excludeGroupUuid) {
            $existingServerUuids = DB::table('puq_vpncp_server_group_server')
                ->where('server_group_uuid', $excludeGroupUuid)
                ->pluck('server_uuid')
                ->toArray();
            if (! empty($existingServerUuids)) {
                $query->whereNotIn('uuid', $existingServerUuids);
            }
        }

        $models = $query->get();

        $results = [];
        foreach ($models as $model) {
            $results[] = [
                'id' => $model->uuid,
                'text' => $model->name,
            ];
        }

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    #[OA\Post(
        path: 'admin.api.Product.puqVPNCP.server_group.post',
        description: 'Creates a new VPNCP server group.',
        summary: 'Create server group',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Server group created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postServerGroup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_vpncp_server_groups,name',
            'fill_type' => 'required|in:default,least_used,round_robin',
        ], [
            'name.required' => __('Product.puqVPNCP.The Name field is required'),
            'name.unique' => __('Product.puqVPNCP.This Name is already taken'),
            'fill_type.required' => __('Product.puqVPNCP.The Fill Type field is required'),
            'fill_type.in' => __('Product.puqVPNCP.The Fill Type is invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqVpncpServerGroup;

        $model->name = $request->input('name');
        $model->fill_type = $request->input('fill_type', 'default');

        $home_company = HomeCompany::query()->where('default', true)->first();
        if ($home_company) {
            $model->country_uuid = $home_company->country_uuid;
            $model->region_uuid = $home_company->region_uuid;
        }

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqVPNCP.Created successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqVPNCP.server_group.get',
        description: 'Returns a single VPNCP server group by UUID.',
        summary: 'Get server group',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Server group UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Server group returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getServerGroup(Request $request, $uuid): JsonResponse
    {
        $model = PuqVpncpServerGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Not found')],
            ], 404);
        }

        $data = $model->toArray();

        if ($model->country) {
            $data['country_data'] = ['id' => $model->country->uuid ?? null, 'text' => $model->country->name ?? null];
        }

        if ($model->region) {
            $data['region_data'] = ['id' => $model->region->uuid ?? null, 'text' => $model->region->name ?? null];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.Product.puqVPNCP.server_group.put',
        description: 'Updates an existing VPNCP server group by UUID.',
        summary: 'Update server group',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Server group UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Server group updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putServerGroup(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_vpncp_server_groups,name,'.$uuid.',uuid',
            'fill_type' => 'required|in:default,least_used,round_robin',
        ], [
            'name.required' => __('Product.puqVPNCP.The Name field is required'),
            'name.unique' => __('Product.puqVPNCP.This Name is already taken'),
            'fill_type.required' => __('Product.puqVPNCP.The Fill Type field is required'),
            'fill_type.in' => __('Product.puqVPNCP.The Fill Type is invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = PuqVpncpServerGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Not found')],
            ], 404);
        }

        $model->name = $request->input('name');
        $model->fill_type = $request->input('fill_type');
        $model->data_center = $request->input('data_center');
        $model->description = $request->input('description');
        $model->country_uuid = $request->input('country_uuid');
        $model->region_uuid = $request->input('region_uuid');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqVPNCP.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.Product.puqVPNCP.server_group.delete',
        description: 'Deletes a VPNCP server group by UUID.',
        summary: 'Delete server group',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Server group UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Server group deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteServerGroup(Request $request, $uuid): JsonResponse
    {
        $model = PuqVpncpServerGroup::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Not found')],
            ], 404);
        }

        if ($model->hasServers()) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Group has servers')],
            ], 422);
        }

        if (PuqVpncpServerGroup::query()->count() == 1) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.There must be at least one group left')],
            ], 422);
        }

        try {
            $deleted = $model->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('Product.puqVPNCP.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqVPNCP.Deleted successfully'),
            'redirect' => route('admin.web.Product.puqVPNCP.server_groups'),
        ]);
    }

    // ─── Server Detail API ───────────────────────────────────────

    #[OA\Get(
        path: 'admin.api.Product.puqVPNCP.server.get',
        description: 'Returns a single VPNCP server by UUID.',
        summary: 'Get server',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Server UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Server returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getServer(Request $request, $uuid): JsonResponse
    {
        $model = PuqVpncpServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Not found')],
            ], 404);
        }

        $data = $model->toArray();
        $data['api_token'] = '**********';
        $data['use_accounts'] = $model->getUseAccounts();
        $data['total_accounts'] = $model->getTotalAccounts();
        $data['free_slots'] = $model->getFreeSlots();
        $data['active_clients'] = $model->getActiveClientsCount();
        $data['license_accounts'] = $model->getLicenseAccounts();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.Product.puqVPNCP.server.put',
        description: 'Updates an existing VPNCP server by UUID.',
        summary: 'Update server',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Server UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Server updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putServer(Request $request, $uuid): JsonResponse
    {
        $model = PuqVpncpServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_vpncp_servers,name,'.$model->uuid.',uuid',
            'api_url' => 'required|url',
        ], [
            'name.required' => __('Product.puqVPNCP.The Name field is required'),
            'name.unique' => __('Product.puqVPNCP.This Name is already taken'),
            'api_url.required' => __('Product.puqVPNCP.The API URL field is required'),
            'api_url.url' => __('Product.puqVPNCP.The API URL must be a valid URL'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->api_url = $request->input('api_url');
        if ($request->input('api_token') != '**********') {
            $model->api_token = Crypt::encryptString($request->input('api_token'));
        }
        $model->verify_ssl = $request->boolean('verify_ssl', true);

        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqVPNCP.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Post(
        path: 'admin.api.Product.puqVPNCP.server.test_connection.post',
        description: 'Tests the connection to a VPNCP server by UUID using provided credentials.',
        summary: 'Test server connection',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Server UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connection test completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postServerTestConnection(Request $request, $uuid): JsonResponse
    {
        $model = PuqVpncpServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Not found')],
            ], 404);
        }

        $apiUrl = $request->input('api_url', $model->api_url);
        $apiToken = $request->input('api_token');
        if ($apiToken == '**********' || empty($apiToken)) {
            $apiToken = $model->api_token;
        } else {
            $apiToken = Crypt::encryptString($apiToken);
        }

        $vpncp = new puqVpncpClient([
            'api_url' => $apiUrl,
            'api_token' => $apiToken,
            'verify_ssl' => $request->boolean('verify_ssl', $model->verify_ssl ?? true),
        ]);

        $response = $vpncp->apiTestConnection();

        if ($response['status'] !== 'success') {
            return response()->json([
                'status' => 'error',
                'errors' => [$response['error'] ?? ''],
            ], 422);
        }

        // Also sync server data on successful test connection
        $systemStatus = $vpncp->getSystemStatus();
        $license = $vpncp->getLicense();
        $networks = $vpncp->getNetworks();

        $licenseData = $license['data'] ?? [];
        unset($licenseData['key']);

        $networksData = $networks['data'] ?? [];

        // Aggregate protocol availability from networks
        $protocols = ['wireguard' => false, 'openvpn' => false, 'ikev2' => false];
        foreach ($networksData as $net) {
            if (is_array($net) && isset($net['protocols'])) {
                foreach ($protocols as $proto => $val) {
                    if (! empty($net['protocols'][$proto])) {
                        $protocols[$proto] = true;
                    }
                }
            }
        }

        $model->description = [
            'system' => $systemStatus['data'] ?? [],
            'license' => $licenseData,
            'networks' => $networksData,
            'protocols' => $protocols,
            'synced_at' => now()->toIso8601String(),
        ];
        $model->save();

        $responseData = $response['data'];
        if (isset($responseData['license']['key'])) {
            unset($responseData['license']['key']);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqVPNCP.Successfully'),
            'data' => $responseData,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.Product.puqVPNCP.server.test_connection.get',
        description: 'Tests the connection to a VPNCP server using the server UUID from query parameter.',
        summary: 'Test server connection (GET)',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connection test completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getServerTestConnection(Request $request): JsonResponse
    {
        $uuid = $request->get('uuid');
        $model = PuqVpncpServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Not found')],
            ], 404);
        }

        $vpncp = $model->getApiClient();
        $response = $vpncp->apiTestConnection();

        if ($response['status'] !== 'success') {
            return response()->json([
                'status' => 'error',
                'errors' => [$response['error'] ?? ''],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => $response['data'],
        ]);
    }

    // ─── Server Networks ─────────────────────────────────────────

    #[OA\Get(
        path: 'admin.api.Product.puqVPNCP.server.networks.get',
        description: 'Returns available networks from a VPNCP server.',
        summary: 'Get server networks',
        security: [['apiKeyAuth' => []]],
        tags: ['puqVPNCP'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'Server UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Networks returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getServerNetworks(Request $request, $uuid): JsonResponse
    {
        $model = PuqVpncpServer::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Not found')],
            ], 404);
        }

        // First try cached networks from description
        $cached = $model->description['networks'] ?? null;
        if ($cached) {
            return response()->json([
                'status' => 'success',
                'data' => $cached,
            ]);
        }

        // Fallback to live API
        $vpncp = $model->getApiClient();
        $response = $vpncp->getNetworks();

        if ($response['status'] !== 'success') {
            return response()->json([
                'status' => 'error',
                'errors' => [$response['error'] ?? ''],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => $response['data'],
        ]);
    }

    // ─── Server Group Servers Management ─────────────────────────

    public function getServerGroupServers(Request $request, $uuid): JsonResponse
    {
        $group = PuqVpncpServerGroup::find($uuid);
        if (empty($group)) {
            return response()->json(['errors' => [__('Product.puqVPNCP.Not found')]], 404);
        }

        $pivots = DB::table('puq_vpncp_server_group_server')
            ->where('server_group_uuid', $uuid)
            ->get();

        // Batch-fetch servers to avoid N+1 (previously called PuqVpncpServer::find in loop)
        $serverUuids = $pivots->pluck('server_uuid')->filter()->unique()->values()->all();
        $servers = PuqVpncpServer::whereIn('uuid', $serverUuids)->get()->keyBy('uuid');

        $result = [];
        foreach ($pivots as $pivot) {
            $server = $servers->get($pivot->server_uuid);
            if (! $server) {
                continue;
            }

            $selectedNetworks = json_decode($pivot->selected_networks, true) ?? [];
            $cachedNetworks = $server->description['networks'] ?? [];
            $license = $server->description['license'] ?? [];

            // Build network details with client info
            $networkDetails = [];
            foreach ($cachedNetworks as $network) {
                if (! is_array($network)) {
                    continue;
                }
                $name = $network['name'] ?? $network['interface'] ?? '';
                $networkDetails[] = [
                    'name' => $name,
                    'selected' => in_array($name, $selectedNetworks),
                    'address' => $network['address'] ?? '',
                    'client_count' => $network['client_count'] ?? 0,
                ];
            }

            $result[] = [
                'server_uuid' => $server->uuid,
                'server_name' => $server->name,
                'server_url' => route('admin.web.Product.puqVPNCP.server.tab', ['uuid' => $server->uuid, 'tab' => 'general']),
                'selected_networks' => $selectedNetworks,
                'networks' => $networkDetails,
                'license' => $license,
                'total_accounts' => $server->getTotalAccounts(),
                'license_accounts' => $server->getLicenseAccounts(),
                'active_clients' => $server->getActiveClientsCount(),
                'free_slots' => $server->getFreeSlots(),
                'use_accounts' => $server->getUseAccounts(),
                'synced_at' => $server->description['synced_at'] ?? null,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function postServerGroupServer(Request $request, $uuid): JsonResponse
    {
        $group = PuqVpncpServerGroup::find($uuid);
        if (empty($group)) {
            return response()->json(['errors' => [__('Product.puqVPNCP.Not found')]], 404);
        }

        $validator = Validator::make($request->all(), [
            'server_uuid' => 'required|exists:puq_vpncp_servers,uuid',
            'selected_networks' => 'array',
            'selected_networks.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $serverUuid = $request->input('server_uuid');

        // Validate selected networks against the server's cached network list
        $server = PuqVpncpServer::find($serverUuid);
        $requestedNetworks = array_values(array_filter(
            array_map('strval', $request->input('selected_networks', [])),
            fn ($n) => $n !== ''
        ));

        if (! empty($requestedNetworks)) {
            $allowedNetworks = self::extractNetworkNames($server?->description['networks'] ?? []);
            $unknown = array_diff($requestedNetworks, $allowedNetworks);
            if (! empty($unknown)) {
                return response()->json([
                    'errors' => [__('Product.puqVPNCP.Unknown networks:').' '.implode(', ', $unknown)],
                ], 422);
            }
        }

        // Atomic insert: unique index group_server_unique(server_group_uuid, server_uuid) prevents duplicates.
        // insertOrIgnore returns number of rows inserted (0 means already exists).
        $inserted = DB::table('puq_vpncp_server_group_server')->insertOrIgnore([
            'uuid' => (string) Str::uuid(),
            'server_group_uuid' => $uuid,
            'server_uuid' => $serverUuid,
            'selected_networks' => json_encode($requestedNetworks),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted === 0) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Server already in this group')],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqVPNCP.Server added to group'),
        ]);
    }

    public function putServerGroupServer(Request $request, $group_uuid, $server_uuid): JsonResponse
    {
        $exists = DB::table('puq_vpncp_server_group_server')
            ->where('server_group_uuid', $group_uuid)
            ->where('server_uuid', $server_uuid)
            ->exists();

        if (! $exists) {
            return response()->json(['errors' => [__('Product.puqVPNCP.Not found')]], 404);
        }

        $validator = Validator::make($request->all(), [
            'selected_networks' => 'array',
            'selected_networks.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        // Validate selected networks against the server's cached network list
        $server = PuqVpncpServer::find($server_uuid);
        $requestedNetworks = array_values(array_filter(
            array_map('strval', $request->input('selected_networks', [])),
            fn ($n) => $n !== ''
        ));

        if (! empty($requestedNetworks)) {
            $allowedNetworks = self::extractNetworkNames($server?->description['networks'] ?? []);
            $unknown = array_diff($requestedNetworks, $allowedNetworks);
            if (! empty($unknown)) {
                return response()->json([
                    'errors' => [__('Product.puqVPNCP.Unknown networks:').' '.implode(', ', $unknown)],
                ], 422);
            }
        }

        DB::table('puq_vpncp_server_group_server')
            ->where('server_group_uuid', $group_uuid)
            ->where('server_uuid', $server_uuid)
            ->update([
                'selected_networks' => json_encode($requestedNetworks),
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqVPNCP.Updated successfully'),
        ]);
    }

    /**
     * Extract network name strings from a cached networks array (each item is an associative
     * array from /network API; the name is under 'name' or 'interface').
     *
     * @param  array<int, mixed>  $networks
     * @return array<int, string>
     */
    private static function extractNetworkNames(array $networks): array
    {
        $names = [];
        foreach ($networks as $network) {
            if (! is_array($network)) {
                continue;
            }
            $name = $network['name'] ?? $network['interface'] ?? null;
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    // ─── Service Client Info (live data from VPN server) ────────

    public function getServiceClientInfo(Request $request): JsonResponse
    {
        $serverUuid = $request->input('server_uuid');
        $clientName = $request->input('client_name');

        if (empty($serverUuid) || empty($clientName)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Missing server_uuid or client_name')],
            ], 422);
        }

        $server = PuqVpncpServer::find($serverUuid);
        if (empty($server)) {
            return response()->json([
                'errors' => [__('Product.puqVPNCP.Server not found')],
            ], 404);
        }

        $vpncp = $server->getApiClient();

        // OTL-only mode: just generate a new one-time link
        if ($request->input('otl_only')) {
            $otl = $vpncp->getOTL($clientName);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'otl' => ($otl['status'] === 'success') ? ($otl['data'] ?? '') : '',
                    'server' => [
                        'api_url' => $server->api_url,
                    ],
                ],
            ]);
        }

        // Fetch client info
        $client = $vpncp->getClient($clientName);
        if ($client['status'] !== 'success') {
            return response()->json([
                'status' => 'error',
                'errors' => [$client['error'] ?? __('Product.puqVPNCP.Failed to get client info')],
            ], 422);
        }

        // Fetch traffic for current month
        $traffic = $vpncp->getClientTraffic($clientName, date('Y'), date('m'));

        // Fetch available traffic periods
        $trafficAvailable = $vpncp->getClientTrafficAvailable($clientName);

        // Fetch protocol profiles only if protocol is available
        $clientData = $client['data'] ?? [];
        $protocols = $clientData['protocols'] ?? [];

        $wgData = ['config_text' => '', 'config_qr' => ''];
        if (! empty($protocols['wireguard'])) {
            $configText = $vpncp->getConfigText($clientName);
            $configQr = $vpncp->getConfigQR($clientName);
            $wgData['config_text'] = ($configText['status'] === 'success' && ! empty($configText['data'])) ? $configText['data'] : '';
            $wgData['config_qr'] = ($configQr['status'] === 'success' && ! empty($configQr['data'])) ? $configQr['data'] : '';
        }

        $ovpnData = ['available' => ! empty($protocols['openvpn']), 'profile' => ''];
        if (! empty($protocols['openvpn'])) {
            $openvpnProfile = $vpncp->getOpenVPNProfile($clientName);
            $ovpnData['profile'] = ($openvpnProfile['status'] === 'success') ? ($openvpnProfile['data'] ?? '') : '';
        }

        $ikev2Data = ['available' => ! empty($protocols['ikev2']), 'profile' => ''];
        if (! empty($protocols['ikev2'])) {
            $ikev2Profile = $vpncp->getIKEv2Profile($clientName);
            $ikev2Data['profile'] = ($ikev2Profile['status'] === 'success') ? ($ikev2Profile['data'] ?? '') : '';
        }

        $otl = $vpncp->getOTL($clientName);

        // Server cached data for context
        $serverDesc = $server->description ?? [];

        return response()->json([
            'status' => 'success',
            'data' => [
                'client' => $clientData,
                'traffic' => $traffic['status'] === 'success' ? ($traffic['data'] ?? []) : [],
                'traffic_available' => $trafficAvailable['status'] === 'success' ? ($trafficAvailable['data'] ?? []) : [],
                'wireguard' => $wgData,
                'openvpn' => $ovpnData,
                'ikev2' => $ikev2Data,
                'otl' => ($otl['status'] === 'success') ? ($otl['data'] ?? '') : '',
                'server' => [
                    'name' => $server->name,
                    'api_url' => $server->api_url,
                    'synced_at' => $serverDesc['synced_at'] ?? null,
                ],
            ],
        ]);
    }

    public function deleteServerGroupServer(Request $request, $group_uuid, $server_uuid): JsonResponse
    {
        $exists = DB::table('puq_vpncp_server_group_server')
            ->where('server_group_uuid', $group_uuid)
            ->where('server_uuid', $server_uuid)
            ->exists();

        if (! $exists) {
            return response()->json(['errors' => [__('Product.puqVPNCP.Not found')]], 404);
        }

        DB::table('puq_vpncp_server_group_server')
            ->where('server_group_uuid', $group_uuid)
            ->where('server_uuid', $server_uuid)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqVPNCP.Server removed from group'),
        ]);
    }
}
