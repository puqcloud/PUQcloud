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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\PuqPmSshPublicKey;
use Yajra\DataTables\DataTables;

class puqPmSshPublicKeyController extends Controller
{

    public function sshPublicKeys(Request $request): View
    {
        $title = __('Product.puqProxmox.SSH Public Keys');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.ssh_public_keys.ssh_public_keys',
            compact('title'));
    }

    public function getSshPublicKeys(Request $request): JsonResponse
    {
        $query = PuqPmSshPublicKey::query()
            ->join('clients', 'clients.uuid', '=', 'puq_pm_ssh_public_keys.client_uuid')
            ->select(
                'puq_pm_ssh_public_keys.name',
                'puq_pm_ssh_public_keys.client_uuid',
                'puq_pm_ssh_public_keys.uuid',
                'puq_pm_ssh_public_keys.public_key',
                'clients.firstname as client_firstname',
                'clients.lastname as client_lastname',
                'clients.company_name as client_company_name'
            );

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('puq_pm_ssh_public_keys.name', 'like', "%{$search}%")
                                ->orWhere('puq_pm_ssh_public_keys.client_uuid', 'like', "%{$search}%")
                                ->orWhere('puq_pm_ssh_public_keys.uuid', 'like', "%{$search}%")
                                ->orWhere('clients.firstname', 'like', "%{$search}%")
                                ->orWhere('clients.lastname', 'like', "%{$search}%")
                                ->orWhere('clients.company_name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('info', function ($model) {
                    try {
                        return $model->getInfo();
                    } catch (\Exception $e) {
                        return [
                            'type' => 'unknown',
                            'fingerprint' => null,
                            'comment' => null,
                        ];
                    }
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['delete'] = route('admin.api.Product.puqProxmox.ssh_public_key.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postSshPublicKey(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
            'client_uuid' => [
                'required',
                'uuid',
            ],
            'public_key' => [
                'required',
                'regex:/^(ssh-(rsa|ed25519|dss|ecdsa)|ecdsa-sha2-nistp(256|384|521))\s+[A-Za-z0-9+\/=]+(\s.*)?$/',
            ],
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.regex' => __('Product.puqProxmox.Only uppercase letters, digits, dashes and underscores are allowed'),

            'client_uuid.required' => __('Product.puqProxmox.Client is required'),
            'client_uuid.uuid' => __('Product.puqProxmox.Invalid Client UUID'),

            'public_key.required' => __('Product.puqProxmox.SSH Public Key is required'),
            'public_key.regex' => __('Product.puqProxmox.Invalid SSH Public Key format'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmSshPublicKey;
        $model->name = $request->input('name');
        $model->client_uuid = $request->input('client_uuid');
        $model->public_key = $request->input('public_key');
        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function deleteSshPublicKey(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmSshPublicKey::find($uuid);

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

}
