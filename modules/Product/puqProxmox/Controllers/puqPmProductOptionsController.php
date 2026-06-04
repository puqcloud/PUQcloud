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
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\puqProxmox\Services\ProductOptionsSpec;
use Modules\Product\puqProxmox\Services\ProductOptionsSyncService;
use OpenApi\Attributes as OA;

/**
 * "Product Options" admin page for the puqProxmox module.
 *
 * Shows the status of the product_option_groups and product_options that the module
 * expects under stable keys, and exposes a "Create Missing" button that idempotently
 * creates whatever is missing. Nothing is attached to any Product — the admin does
 * that from the Product edit page.
 *
 * All heavy lifting lives in ProductOptionsSyncService; this controller is just the
 * HTTP surface.
 */
class puqPmProductOptionsController extends Controller
{
    public function productOptions(Request $request): View
    {
        $title = __('main.Product Options');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.product_options.product_options',
            compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.Product.puqProxmox.product_options.get',
        description: 'Returns the status of all product option groups the puqProxmox module expects.',
        summary: 'Get puqProxmox product options status',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductOptions(Request $request): JsonResponse
    {
        $service = new ProductOptionsSyncService;
        $baseValues = $this->baseValuesFromRequest($request);

        return response()->json([
            'data' => $service->getStatus($baseValues),
            'meta' => [
                'base_values' => $baseValues,
                'defaults' => ProductOptionsSpec::DEFAULT_BASES,
            ],
        ]);
    }

    #[OA\Post(
        path: 'admin.api.Product.puqProxmox.product_options.create_missing.post',
        description: 'Creates any product option groups and options declared by the puqProxmox module spec but missing from the database. Idempotent.',
        summary: 'Create missing puqProxmox product options',
        security: [['apiKeyAuth' => []]],
        tags: ['puqProxmox'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sync completed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'groups_created', type: 'integer'),
                            new OA\Property(property: 'options_created', type: 'integer'),
                        ]),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postCreateMissing(Request $request): JsonResponse
    {
        $service = new ProductOptionsSyncService;
        $baseValues = $this->baseValuesFromRequest($request);
        $result = $service->createMissing($baseValues);

        return response()->json([
            'status' => 'success',
            'message' => __('main.Created :groups groups and :options options', [
                'groups' => $result['groups_created'],
                'options' => $result['options_created'],
            ]),
            'data' => $result,
        ]);
    }

    /**
     * Extract the base_subject → value map from a request. Missing / non-numeric keys
     * are dropped; the sync service will fall back to ProductOptionsSpec::DEFAULT_BASES
     * for anything not provided.
     *
     * @return array<string,int>
     */
    private function baseValuesFromRequest(Request $request): array
    {
        $out = [];
        foreach (ProductOptionsSpec::BASE_SUBJECTS as $subject) {
            $raw = $request->input($subject);
            if (is_numeric($raw)) {
                $out[$subject] = max(0, (int) $raw);
            }
        }

        return $out;
    }
}
