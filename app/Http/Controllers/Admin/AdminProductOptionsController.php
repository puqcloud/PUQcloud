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

/*
 * WEB Routes:
 *   GET  product_option_groups                                 -> productOptionGroups()                     [admin.web.product_option_groups]
 *   GET  product_option_group/{uuid}/{tab}                     -> productOptionGroupTab()                   [admin.web.product_option_group.tab]
 *
 * API Routes:
 *   GET    product_option_groups                                -> getProductOptionGroups()                  [admin.api.product_option_groups.get]
 *   POST   product_option_group                                 -> postProductOptionGroup()                  [admin.api.product_option_group.post]
 *   GET    product_option_group/{uuid}                          -> getProductOptionGroup()                   [admin.api.product_option_group.get]
 *   PUT    product_option_group/{uuid}                          -> putProductOptionGroup()                   [admin.api.product_option_group.put]
 *   DELETE product_option_group/{uuid}                          -> deleteProductOptionGroup()                [admin.api.product_option_group.delete]
 *   GET    product_option_group/{uuid}/product_options          -> getProductOptionGroupProductOptions()     [admin.api.product_option_group.product_options.get]
 *   POST   product_option_group/{uuid}/product_option           -> postProductOptionGroupProductOption()     [admin.api.product_option_group.product_option.post]
 *   GET    product_option/{uuid}                                -> getProductOption()                        [admin.api.product_option.get]
 *   PUT    product_option/{uuid}                                -> putProductOption()                        [admin.api.product_option.put]
 *   DELETE product_option/{uuid}                                -> deleteProductOption()                     [admin.api.product_option.delete]
 *   GET    product_option/{uuid}/price                          -> getProductOptionPrices()                  [admin.api.product_option.prices.get]
 *   GET    product_option/{uuid}/price/{p_uuid}                 -> getProductOptionPrice()                   [admin.api.product_option.price.get]
 *   POST   product_option/{uuid}/price                          -> postProductOptionPrice()                  [admin.api.product_option.price.post]
 *   PUT    product_option/{uuid}/price                          -> putProductOptionPrice()                   [admin.api.product_option.price.put]
 *   DELETE product_option/{uuid}/price/{p_uuid}                 -> deleteProductOptionPrice()                [admin.api.product_option.price.delete]
 *   POST   product_options/update_order                         -> postProductOptionsUpdateOrder()           [admin.api.product_options.update_order.post]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminProductOptionsController extends Controller
{
    public function productOptionGroups(): View
    {
        $title = __('main.Product Option Groups');

        return view_admin('product_option_groups.product_option_groups', compact('title'));
    }

    public function productOptionGroupTab(Request $request, $uuid, $tab): View|RedirectResponse
    {
        $validTabs = [
            'general', 'images', 'options',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route('admin.web.product_option_group.tab', ['uuid' => $uuid, 'tab' => 'general']);
        }

        $title = __('main.Product Option Group');
        $locales = config('locale.client.locales');

        return view_admin('product_option_groups.product_option_group_'.$tab,
            compact('title', 'uuid', 'tab', 'locales'));
    }

    #[OA\Get(
        path: 'admin.api.product_option_groups.get',
        description: 'Returns a DataTable list of product option groups.',
        summary: 'Get product option groups list',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option groups list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductOptionGroups(Request $request): JsonResponse
    {
        $query = ProductOptionGroup::withCount(['productOptions', 'products']);

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('uuid', 'like', "%{$search}%")
                                ->orWhere('key', 'like', "%{$search}%")
                                ->orWhere('notes', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('images', function ($query) {
                    return $query->images;
                })
                ->addColumn('name', function ($query) {
                    return $query->name;
                })
                ->addColumn('options_count', fn($query) => $query->product_options_count)
                ->addColumn('products_count', fn($query) => $query->products_count)
                ->addColumn('urls', function ($query) {
                    return [
                        'edit' => route('admin.web.product_option_group.tab',
                            ['uuid' => $query->uuid, 'tab' => 'general']),
                        'delete' => route('admin.api.product_option_group.delete', $query->uuid),
                    ];
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.product_option_group.post',
        description: 'Creates a new product option group with the given key.',
        summary: 'Create a new product option group',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['key'],
                    properties: [
                        new OA\Property(property: 'key', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option group created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'redirect', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postProductOptionGroup(Request $request): JsonResponse
    {
        $product_option_group = new ProductOptionGroup;

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:product_option_groups,key',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }
        $product_option_group->key = $request->input('key');
        $product_option_group->save();
        $product_option_group->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $product_option_group,
            'redirect' => route('admin.web.product_option_group.tab',
                ['uuid' => $product_option_group->uuid, 'tab' => 'general']),
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.product_option_group.delete',
        description: 'Deletes a product option group by UUID if it has no associated options or products.',
        summary: 'Delete product option group',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option group deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Cannot delete with associated options or products'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 500, description: 'Deletion failed'),
        ]
    )]
    public function deleteProductOptionGroup(Request $request, $uuid): JsonResponse
    {
        $product_option_group = ProductOptionGroup::find($uuid);
        if (empty($product_option_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($product_option_group->productOptions()->count() != 0) {
            return response()->json([
                'errors' => [__('error.Cannot delete Product Option Group with associated Options')],
            ], 400);
        }

        if ($product_option_group->products()->count() != 0) {
            return response()->json([
                'errors' => [__('error.Cannot delete Product Option Group because it is associated with active product')],
            ], 400);
        }

        try {
            $deleted = $product_option_group->delete();
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
        path: 'admin.api.product_option_group.get',
        description: 'Returns detailed information about a product option group by UUID.',
        summary: 'Get product option group by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'locale', description: 'Locale for translations', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option group data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getProductOptionGroup(Request $request, $uuid): JsonResponse
    {
        $product_option_group = ProductOptionGroup::find($uuid);

        if (empty($product_option_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        // $product_option_group->loadImagesField();

        if (!empty($request->input('locale'))) {
            $product_option_group->setLocale($request->input('locale'));
        }

        return response()->json([
            'data' => $product_option_group,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.product_option_group.put',
        description: 'Updates an existing product option group by UUID.',
        summary: 'Update product option group by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['key'],
                    properties: [
                        new OA\Property(property: 'key', type: 'string'),
                        new OA\Property(property: 'locale', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'short_description', type: 'string'),
                        new OA\Property(property: 'notes', type: 'string'),
                        new OA\Property(property: 'hidden', type: 'string', enum: ['yes', 'no']),
                        new OA\Property(property: 'convert_price', type: 'string', enum: ['yes', 'no']),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option group updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putProductOptionGroup(Request $request, $uuid): JsonResponse
    {
        $product_option_group = ProductOptionGroup::find($uuid);

        if (empty($product_option_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:product_groups,key,'.$product_option_group->uuid.',uuid',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $product_option_group->key = $request->input('key');

        if (!empty($request->input('locale'))) {
            $product_option_group->setLocale($request->input('locale'));
        }
        if ($request->has('name')) {
            $product_option_group->name = $request->input('name');
        }
        if ($request->has('description')) {
            $product_option_group->description = $request->input('description');
        }
        if ($request->has('short_description')) {
            $product_option_group->short_description = $request->input('short_description');
        }
        if ($request->has('notes')) {
            $product_option_group->notes = $request->input('notes');
        }
        if ($request->has('hidden')) {
            $product_option_group->hidden = false;
            if ($request->input('hidden') == 'yes') {
                $product_option_group->hidden = true;
            }
        }
        if ($request->has('convert_price')) {
            $product_option_group->convert_price = false;
            if ($request->input('convert_price') == 'yes') {
                $product_option_group->convert_price = true;
            }
        }

        $product_option_group->save();
        $product_option_group->refresh();

        if (!empty($product_option_group->convert_price)) {
            $product_option_group->convertPrice();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $product_option_group,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product_option_group.product_options.get',
        description: 'Returns a list of product options in a product option group with DataTables support.',
        summary: 'Get product options in option group',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product options returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductOptionGroupProductOptions(Request $request, $uuid): JsonResponse
    {
        $query = ProductOptionGroup::query()->find($uuid)->productOptions();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('uuid', 'like', "%{$search}%")
                                ->orWhere('key', 'like', "%{$search}%")
                                ->orWhere('notes', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('images', function ($query) {
                    return $query->images;
                })
                ->addColumn('name', function ($query) {
                    return $query->name;
                })
                ->order(function ($query) use ($request) {
                    $direction = $request->get('dir', 'asc');
                    $query->orderBy('order', $direction);
                })
                ->addColumn('urls', function ($product_option) {
                    $urls['edit'] = route('admin.web.product_option_group.tab', [
                        'uuid' => $product_option->product_option_group_uuid, 'tab' => 'options',
                        'edit' => $product_option->uuid,
                    ]);
                    $urls['delete'] = route('admin.api.product_option.delete', $product_option->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.product_option_group.product_option.post',
        description: 'Creates a new product option within a product option group.',
        summary: 'Create product option in option group',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['key'],
                    properties: [
                        new OA\Property(property: 'key', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'redirect', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product option group not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postProductOptionGroupProductOption(Request $request, $uuid): JsonResponse
    {
        $product_option_group = ProductOptionGroup::find($uuid);

        if (empty($product_option_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $product_option = new ProductOption;

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:product_options,key',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }
        $product_option->key = $request->input('key');
        $product_option->value = '';
        $product_option->product_option_group_uuid = $uuid;
        $product_option->save();
        $product_option->refresh();
        ProductOption::reorder($uuid);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $product_option,
            'redirect' => route('admin.web.product_option_group.tab',
                ['uuid' => $product_option_group->uuid, 'tab' => 'options', 'edit' => $product_option->uuid]),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product_option.get',
        description: 'Returns detailed information about a product option by UUID.',
        summary: 'Get product option by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'locale', description: 'Locale for translations', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getProductOption(Request $request, $uuid): JsonResponse
    {
        $product_option = ProductOption::find($uuid);

        if (empty($product_option)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if (!empty($request->input('locale'))) {
            $product_option->setLocale($request->input('locale'));
        }

        return response()->json([
            'data' => $product_option,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.product_option.put',
        description: 'Updates an existing product option by UUID.',
        summary: 'Update product option by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['key'],
                    properties: [
                        new OA\Property(property: 'key', type: 'string'),
                        new OA\Property(property: 'locale', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'value', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'short_description', type: 'string'),
                        new OA\Property(property: 'notes', type: 'string'),
                        new OA\Property(property: 'hidden', type: 'string', enum: ['yes', 'no']),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putProductOption(Request $request, $uuid): JsonResponse
    {
        $product_option = ProductOption::find($uuid);

        if (empty($product_option)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:product_groups,key,'.$product_option->uuid.',uuid',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $product_option->key = $request->input('key');

        if (!empty($request->input('locale'))) {
            $product_option->setLocale($request->input('locale'));
        }
        if ($request->has('name')) {
            $product_option->name = $request->input('name');
        }
        if ($request->has('value')) {
            $product_option->value = $request->input('value');
        }
        if ($request->has('description')) {
            $product_option->description = $request->input('description');
        }
        if ($request->has('short_description')) {
            $product_option->short_description = $request->input('short_description');
        }
        if ($request->has('notes')) {
            $product_option->notes = $request->input('notes');
        }
        if ($request->has('hidden')) {
            $product_option->hidden = false;
            if ($request->input('hidden') == 'yes') {
                $product_option->hidden = true;
            }
        }

        $product_option->save();
        $product_option->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $product_option,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.product_option.delete',
        description: 'Deletes a product option by UUID.',
        summary: 'Delete product option',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 500, description: 'Deletion failed'),
        ]
    )]
    public function deleteProductOption(Request $request, $uuid): JsonResponse
    {
        $product_option = ProductOption::find($uuid);
        if (empty($product_option)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        $product_option_group_uuid = $product_option->product_option_group_uuid;
        try {
            $deleted = $product_option->delete();
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

        ProductOption::reorder($product_option_group_uuid);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product_option.prices.get',
        description: 'Returns a list of prices for a product option with DataTables support.',
        summary: 'Get product option prices',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option prices returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getProductOptionPrices(Request $request, $uuid): JsonResponse
    {
        $product_option = ProductOption::find($uuid);

        if (!$product_option) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $query = $product_option->prices();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('period', 'like', "%{$search}%")
                                ->orWhere('setup', 'like', "%{$search}%")
                                ->orWhere('base', 'like', "%{$search}%")
                                ->orWhere('idle', 'like', "%{$search}%")
                                ->orWhere('switch_down', 'like', "%{$search}%")
                                ->orWhere('switch_up', 'like', "%{$search}%")
                                ->orWhere('uninstall', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('currency', function ($price) {
                    return $price->currency->code ?? '';
                })
                ->addColumn('default', function ($price) {
                    return $price->currency->default ?? false;
                })
                ->addColumn('urls', function ($price) use ($uuid) {
                    $urls['edit'] = route('admin.api.product_option.price.get',
                        ['uuid' => $uuid, 'p_uuid' => $price->uuid]);
                    $urls['delete'] = route('admin.api.product_option.price.delete',
                        ['uuid' => $uuid, 'p_uuid' => $price->uuid]);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Put(
        path: 'admin.api.product_option.price.put',
        description: 'Updates an existing price for a product option.',
        summary: 'Update product option price',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['price_uuid'],
                    properties: [
                        new OA\Property(property: 'price_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'setup', type: 'number'),
                        new OA\Property(property: 'base', type: 'number'),
                        new OA\Property(property: 'idle', type: 'number'),
                        new OA\Property(property: 'switch_down', type: 'number'),
                        new OA\Property(property: 'switch_up', type: 'number'),
                        new OA\Property(property: 'uninstall', type: 'number'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Price updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'At least one price value must be provided'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function putProductOptionPrice(Request $request, $uuid): JsonResponse
    {
        $product_option = ProductOption::find($uuid);

        if (empty($product_option)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $price = $product_option->prices()->find($request->input('price_uuid'));
        if (empty($price)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $currency = $price->currency;
        if (empty($currency)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $setup = $request->input('setup');
        $base = $request->input('base');
        $idle = $request->input('idle');
        $switch_down = $request->input('switch_down');
        $switch_up = $request->input('switch_up');
        $uninstall = $request->input('uninstall');

        if (empty($setup) && empty($base) && empty($idle) && empty($switch_down) && empty($switch_up) && empty($uninstall)) {
            return response()->json([
                'errors' => [__('error.At least one price value must be provided')],
            ], 400);
        }

        $price->setup = $setup ?? null;
        $price->base = $base ?? null;
        $price->idle = $idle ?? null;
        $price->switch_down = $switch_down ?? null;
        $price->switch_up = $switch_up ?? null;
        $price->uninstall = $uninstall ?? null;

        $price->save();
        $price->refresh();

        $product_option_group = $product_option->productOptionGroup;
        if (!empty($product_option_group->convert_price)) {
            $product_option_group->convertPrice();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Edited successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.product_option.price.post',
        description: 'Creates a new price entry for a product option with currency, period, and price values.',
        summary: 'Create product option price',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['currency_uuid', 'period'],
                    properties: [
                        new OA\Property(property: 'currency_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'period', type: 'string', enum: ['one-time', 'hourly', 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'semi-annually', 'annually', 'biennially', 'triennially']),
                        new OA\Property(property: 'setup', type: 'number'),
                        new OA\Property(property: 'base', type: 'number'),
                        new OA\Property(property: 'idle', type: 'number'),
                        new OA\Property(property: 'switch_down', type: 'number'),
                        new OA\Property(property: 'switch_up', type: 'number'),
                        new OA\Property(property: 'uninstall', type: 'number'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Price added successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'At least one price value must be provided'),
            new OA\Response(response: 404, description: 'Product option not found'),
            new OA\Response(response: 422, description: 'Validation error or price already exists'),
        ]
    )]
    public function postProductOptionPrice(Request $request, $uuid): JsonResponse
    {
        $product_option = ProductOption::find($uuid);

        if (empty($product_option)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'currency_uuid' => 'required|exists:currencies,uuid',
            'period' => 'required|string|in:one-time,hourly,daily,weekly,bi-weekly,monthly,quarterly,semi-annually,annually,biennially,triennially',
        ], [
            'currency_uuid.required' => __('error.Currency is required'),
            'currency_uuid.exists' => __('error.Currency not found in the system'),
            'period.required' => __('error.Period is required'),
            'period.in' => __('error.Invalid period selected. Please choose a valid option'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $setup = $request->input('setup');
        $base = $request->input('base');
        $idle = $request->input('idle');
        $switch_down = $request->input('switch_down');
        $switch_up = $request->input('switch_up');
        $uninstall = $request->input('uninstall');

        if (empty($setup) && empty($base) && empty($idle) && empty($switch_down) && empty($switch_up) && empty($uninstall)) {
            return response()->json([
                'errors' => [__('error.At least one price value must be provided')],
            ], 400);
        }

        $existingPrice = $product_option->prices()
            ->where('currency_uuid', $request->input('currency_uuid'))
            ->where('period', $request->input('period'))
            ->first();

        if ($existingPrice) {
            return response()->json([
                'errors' => [__('error.Price already exists for this type, period, and currency')],
            ], 422);
        }

        $price = new Price;
        $price->currency_uuid = $request->input('currency_uuid');
        $price->period = $request->input('period');
        $price->setup = $setup ?? null;
        $price->base = $base ?? null;
        $price->idle = $idle ?? null;
        $price->switch_down = $switch_down ?? null;
        $price->switch_up = $switch_up ?? null;
        $price->uninstall = $uninstall ?? null;

        $price->save();
        $price->refresh();
        $product_option->prices()->attach($price->uuid);

        $product_option_group = $product_option->productOptionGroup;
        if (!empty($product_option_group->convert_price)) {
            $product_option_group->convertPrice();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Added successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product_option.price.get',
        description: 'Returns a specific price for a product option by price UUID.',
        summary: 'Get product option price',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'p_uuid', description: 'UUID of the price', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Price data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getProductOptionPrice(Request $request, $uuid, $p_uuid): JsonResponse
    {
        $product_option = ProductOption::find($uuid);
        if (empty($product_option)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $price = $product_option->prices()->find($p_uuid);
        if (empty($price)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        $price->load('currency');

        return response()->json(['data' => $price], 200);
    }

    #[OA\Delete(
        path: 'admin.api.product_option.price.delete',
        description: 'Deletes a price from a product option if it has no active service relationships.',
        summary: 'Delete product option price',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product option', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'p_uuid', description: 'UUID of the price', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Price deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Cannot delete price with active relationships'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function deleteProductOptionPrice(Request $request, $uuid, $p_uuid): JsonResponse
    {
        $product_option = ProductOption::find($uuid);
        if (empty($product_option)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $price = $product_option->prices()->find($p_uuid);
        if (empty($price)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($price->services()->exists()) {
            return response()->json([
                'errors' => [__('error.Cannot delete price, there are active relationships')],
            ], 400);
        }

        $price->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.product_options.update_order.post',
        description: 'Updates the display order of a product option.',
        summary: 'Update product option order',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['uuid', 'new_order'],
                    properties: [
                        new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'new_order', type: 'integer'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product option not found'),
            new OA\Response(response: 422, description: 'Validation error or cannot move'),
        ]
    )]
    public function postProductOptionsUpdateOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:product_options,uuid',
            'new_order' => 'required|integer',
        ], [
            'uuid.required' => __('error.The uuid field is required'),
            'uuid.exists' => __('error.The uuid does not exist in the product_options table'),
            'new_order.required' => __('error.The new_order field is required'),
            'new_order.integer' => __('error.The new_order must be an integer'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $productOption = ProductOption::where('uuid', $request->input('uuid'))->first();

        if (!$productOption) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product group not found')],
            ], 404);
        }

        $productOptions = ProductOption::orderBy('order')
            ->where('product_option_group_uuid', $productOption->product_option_group_uuid)
            ->get();

        $minOrder = $productOptions->first()->order;
        $maxOrder = $productOptions->last()->order;

        if ($productOption->order == $minOrder && $request->input('new_order') < $minOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The first item cannot be moved up')],
            ], 422);
        }

        if ($productOption->order == $maxOrder && $request->input('new_order') > $maxOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The last item cannot be moved down')],
            ], 422);
        }

        $currentOrder = $productOption->order;
        $newOrder = $request->input('new_order');

        if ($newOrder > $currentOrder) {
            ProductOption::where('order', '>', $currentOrder)
                ->where('product_option_group_uuid', $productOption->product_option_group_uuid)
                ->where('order', '<=', $newOrder)
                ->decrement('order');
        } else {
            ProductOption::where('order', '<', $currentOrder)
                ->where('product_option_group_uuid', $productOption->product_option_group_uuid)
                ->where('order', '>=', $newOrder)
                ->increment('order');
        }

        $productOption->order = $newOrder;
        $productOption->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }
}
