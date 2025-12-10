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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\puqProxmox\Models\PuqPmLxcInstance;
use Modules\Product\puqProxmox\Models\PuqPmScriptLog;

class puqPmLxcInstanceController extends Controller
{

    public function getDeployStatus(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $model->getDeployStatus(),
        ]);
    }

    public function putDnsRecords(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $pur_dns_records = $model->createDnsRecords();
        if ($pur_dns_records['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $pur_dns_records['errors'],
            ], $pur_dns_records['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.The task was sent successfully'),
        ]);
    }

    public function putReboot(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $reboot = $model->reboot();
        if ($reboot['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $reboot['errors'],
            ], $reboot['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.The task was sent successfully'),
        ]);
    }

    public function getScriptLog(Request $request, $uuid, $log_uuid): JsonResponse
    {
        $model = PuqPmLxcInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $log = PuqPmScriptLog::query()->where('uuid', $log_uuid)->first();
        if (empty($log)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Log Not Found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $log,
        ]);
    }

    public function putRetryDeploy(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcInstance::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if ($model->deploy_status != 'failed') {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deploy Status must be Failed')],
            ], 404);
        }


        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.The task was sent successfully'),
        ]);
    }

}
