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
 *   GET  admin_api_keys                           -> adminApiKeys()             [admin.web.admin_api_keys]
 *
 * API Routes:
 *   GET     admin_api_keys                         -> getAdminApiKeys()          [admin.api.admin_api_keys.get]
 *   GET     admin_api_key/{uuid}                   -> getAdminApiKey()           [admin.api.admin_api_key.get]
 *   POST    admin_api_key                          -> postAdminApiKey()          [admin.api.admin_api_key.post]
 *   PUT     admin_api_key/{uuid}                   -> putAdminApiKey()           [admin.api.admin_api_key.put]
 *   DELETE  admin_api_key/{uuid}                   -> deleteAdminApiKey()        [admin.api.admin_api_key.delete]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminApiKey;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminAdminApiKeysController extends Controller
{
    public function adminApiKeys(): View
    {
        $title = __('main.Administrators API Keys');

        return view_admin('admin_api_keys.admin_api_keys', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.admin_api_keys.get',
        description: 'Returns a DataTable list of admin API keys.',
        summary: 'Get admin API keys list',
        security: [['apiKeyAuth' => []]],
        tags: ['Admin API Keys'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin API keys list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getAdminApiKeys(Request $request): JsonResponse
    {
        $query = AdminApiKey::with('admin');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('ips', 'like', "%{$search}%");

                            $q->orWhereHas('admin', function ($qAdmin) use ($search) {
                                $qAdmin->where('uuid', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhere('firstname', 'like', "%{$search}%")
                                    ->orWhere('lastname', 'like', "%{$search}%")
                                    ->orWhere('admin_notes', 'like', "%{$search}%");
                            });
                        });
                    }
                })
                ->addColumn('urls', function ($admin_api_key) {
                    $admin_online = app('admin');
                    $urls = [];

                    if ($admin_online->hasPermission('admin-api-keys-view')) {
                        $urls['get'] = route('admin.api.admin_api_key.get', $admin_api_key->uuid);
                    }

                    if ($admin_online->hasPermission('admin-api-keys-edit')) {
                        $urls['post'] = route('admin.api.admin_api_key.post');
                    }

                    if ($admin_online->hasPermission('admin-api-keys-create')) {
                        $urls['put'] = route('admin.api.admin_api_key.put', $admin_api_key->uuid);
                    }

                    if ($admin_online->hasPermission('admin-api-keys-delete')) {
                        $urls['delete'] = route('admin.api.admin_api_key.delete', $admin_api_key->uuid);
                    }

                    return $urls;
                })
                ->addColumn('admin', function ($admin_api_key) {
                    $admin = $admin_api_key->admin;
                    if (!$admin) {
                        return '-';
                    }

                    return $admin->firstname.' '.$admin->lastname.' ('.$admin->email.')';
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.admin_api_key.get',
        description: 'Returns detailed information about an admin API key.',
        summary: 'Get admin API key by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Admin API Keys'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the admin API key',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin API key data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'admin_uuid', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'name', type: 'string', example: 'My API Key'),
                                new OA\Property(property: 'description', type: 'string', example: 'Key description', nullable: true),
                                new OA\Property(property: 'hash', type: 'string'),
                                new OA\Property(property: 'ips', type: 'array', items: new OA\Items(type: 'string'), example: ['127.0.0.1']),
                                new OA\Property(property: 'last_used_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getAdminApiKey(Request $request, $uuid): JsonResponse
    {
        $admin_api_key = AdminApiKey::find($uuid);

        if (empty($admin_api_key)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'data' => $admin_api_key,
        ]);
    }

    #[OA\Post(
        path: 'admin.api.admin_api_key.post',
        description: 'Creates a new admin API key with specified name, admin, description, and IP restrictions.',
        summary: 'Create a new admin API key',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name', 'admin_uuid'],
                    properties: [
                        new OA\Property(property: 'name', description: 'API key name', type: 'string', example: 'My API Key'),
                        new OA\Property(property: 'admin_uuid', description: 'UUID of the admin', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'description', description: 'API key description', type: 'string', nullable: true),
                        new OA\Property(property: 'ips', description: 'Comma-separated list of allowed IPs', type: 'string', example: '127.0.0.1,192.168.1.1', nullable: true),
                    ]
                )
            )
        ),
        tags: ['Admin API Keys'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin API key created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', description: 'Raw API key value', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postAdminApiKey(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'admin_uuid' => 'required|uuid|exists:admins,uuid',
            'description' => 'nullable|string',
            'ips' => 'nullable|string',
        ], [
            'name.required' => __('error.The name field is required'),
            'name.string' => __('error.The name must be a valid string'),
            'admin_uuid.required' => __('error.Admin is required'),
            'admin_uuid.uuid' => __('error.Invalid admin UUID'),
            'admin_uuid.exists' => __('error.Admin not found'),
            'description.string' => __('error.Description must be a string'),
            'ips.string' => __('error.IPs must be a string'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $admin = Admin::where('uuid', $request->admin_uuid)->first();

        $ipsInput = $request->input('ips', '');
        $ipsArray = [];

        if (!empty($ipsInput)) {
            $ipsRaw = array_map('trim', explode(',', $ipsInput));
            foreach ($ipsRaw as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ipsArray[] = $ip;
                } else {
                    return response()->json([
                        'message' => __('error.Invalid IP address: :ip', ['ip' => $ip]),
                    ], 422);
                }
            }
        }

        do {
            $rawKey = Str::uuid();
            $hash = hash('sha256', $rawKey);
        } while (AdminApiKey::where('hash', $hash)->exists());

        $apiKey = AdminApiKey::create([
            'admin_uuid' => $admin->uuid,
            'name' => $request->name,
            'description' => $request->description,
            'hash' => $hash,
            'ips' => $ipsArray,
            'last_used_at' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $rawKey,
        ], 200);
    }

    #[OA\Put(
        path: 'admin.api.admin_api_key.put',
        description: 'Updates an existing admin API key name, description, and IP restrictions.',
        summary: 'Update admin API key by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'name', description: 'API key name', type: 'string', example: 'My API Key'),
                        new OA\Property(property: 'description', description: 'API key description', type: 'string', nullable: true),
                        new OA\Property(property: 'ips', description: 'Comma-separated list of allowed IPs', type: 'string', example: '127.0.0.1,192.168.1.1', nullable: true),
                    ]
                )
            )
        ),
        tags: ['Admin API Keys'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the admin API key to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin API key updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putAdminApiKey(Request $request, string $uuid): JsonResponse
    {
        $adminApiKey = AdminApiKey::where('uuid', $uuid)->first();

        if (!$adminApiKey) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ips' => 'nullable|string',
        ], [
            'name.required' => __('error.The name field is required'),
            'name.string' => __('error.The name must be a valid string'),
            'description.string' => __('error.Description must be a string'),
            'ips.string' => __('error.IPs must be a string'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $ipsArray = [];
        $ipsInput = $request->input('ips');

        if (!empty($ipsInput)) {
            $ipsRaw = array_map('trim', explode(',', $ipsInput));

            foreach ($ipsRaw as $ip) {
                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    return response()->json([
                        'message' => ['ips' => [__('error.Invalid IP address: :ip', ['ip' => $ip])]],
                    ], 422);
                }
                $ipsArray[] = $ip;
            }
        }

        $adminApiKey->name = $request->name;
        $adminApiKey->description = $request->description;
        $adminApiKey->ips = $ipsArray;

        $adminApiKey->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.admin_api_key.delete',
        description: 'Deletes an admin API key by UUID.',
        summary: 'Delete admin API key by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Admin API Keys'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the admin API key to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin API key deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteAdminApiKey(Request $request, $uuid): JsonResponse
    {

        $admin_api_key = AdminApiKey::find($uuid);

        if (empty($admin_api_key)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $admin_api_key->delete();
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
}
