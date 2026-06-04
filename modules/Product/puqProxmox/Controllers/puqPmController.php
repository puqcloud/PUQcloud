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

namespace Modules\Product\puqProxmox\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CertificateAuthority;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\PuqPmLxcInstance;
use Modules\Product\puqProxmox\Models\PuqPmSshPublicKey;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class puqPmController extends Controller
{

    public function settings(Request $request): View
    {
        $title = __('Product.puqProxmox.Settings');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.settings.settings', compact('title'));
    }


    #[OA\Get(path: 'admin.api.Product.puqProxmox.settings.get', description: 'Returns module settings.', summary: 'Get settings', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], responses: [new OA\Response(response: 200, description: 'Settings returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
    public function getSettings(Request $request): JsonResponse
    {
        $settings = [
            'global_private_network' => SettingService::get('Product.puqProxmox.global_private_network'),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $settings,
        ]);
    }


    #[OA\Put(path: 'admin.api.Product.puqProxmox.settings.put', description: 'Updates module settings.', summary: 'Update settings', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['global_private_network'], properties: [new OA\Property(property: 'global_private_network', type: 'string')], type: 'object')), responses: [new OA\Response(response: 200, description: 'Settings updated successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string')], type: 'object'))])]
    public function putSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'global_private_network' => [
                'required',
                'regex:/^((25[0-5]|2[0-4][0-9]|1?[0-9]{1,2})\.){3}(25[0-5]|2[0-4][0-9]|1?[0-9]{1,2})\/([0-9]|[1-2][0-9]|3[0-2])$/',
            ],
        ], [
            'global_private_network.required' => __('Product.puqProxmox.The Global Private Network field is required'),
            'global_private_network.regex' => __('Product.puqProxmox.The Global Private Network must be a valid CIDR, e.g., 192.168.0.0/24'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        SettingService::set('Product.puqProxmox.global_private_network', $request->get('global_private_network'));

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Saved successfully'),
        ]);
    }


    #[OA\Get(path: 'admin.api.Product.puqProxmox.certificate_authorities.select.get', description: 'Returns certificate authorities for select dropdown.', summary: 'Get certificate authorities select', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], responses: [new OA\Response(response: 200, description: 'Certificate authorities select returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
    public function getCertificateAuthoritiesSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');
        $page = (int) $request->input('page', 1);
        $perPage = 20;

        $query = CertificateAuthority::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $ca = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $ca->map(function ($model) {
            return [
                'id' => $model->uuid,
                'text' => $model->name,
            ];
        });

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => ($page * $perPage) < $total,
                ],
            ],
        ]);
    }



    public function lxcInstances(Request $request): View
    {
        $title = __('Product.puqProxmox.LXC Instances');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.other.lxc_instances', compact('title'));
    }

    #[OA\Get(path: 'admin.api.Product.puqProxmox.lxc_instances.get', description: 'Returns a list of LXC instances.', summary: 'Get LXC instances list', security: [['apiKeyAuth' => []]], tags: ['puqProxmox'], responses: [new OA\Response(response: 200, description: 'LXC instances list returned successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')], type: 'object'))])]
    public function getLxcInstances(Request $request): JsonResponse
    {
        $query = PuqPmLxcInstance::query()->orderByDesc('created_at');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('uuid', 'like', "%{$search}%")
                                ->orWhere('hostname', 'like', "%{$search}%")
                                ->orWhere('vmid', 'like', "%{$search}%")
                                ->orWhereHas('service', function($q2) use ($search) {
                                    $q2->where('client_label', 'like', "%{$search}%")
                                        ->orWhereHas('product', function($q3) use ($search) {
                                            $q3->where('key', 'like', "%{$search}%");
                                        });
                                })
                                ->orWhereHas('status', function($q4) use ($search) {
                                    $q4->where('status', 'like', "%{$search}%");
                                });
                        });
                    }
                })

                ->addColumn('service', function ($model) {
                    return $model->service?->toArray();
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    return $urls;
                })
                ->make(true),
        ], 200);

    }


    public function appInstances(Request $request): View
    {
        $title = __('Product.puqProxmox.APP Instances');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.other.app_instances', compact('title'));
    }




}
