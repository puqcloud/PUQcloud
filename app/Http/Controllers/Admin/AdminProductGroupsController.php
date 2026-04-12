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
 *   GET  product_groups                                        -> productGroups()                           [admin.web.product_groups]
 *   GET  product_group/{uuid}/{tab}                            -> productGroupTab()                         [admin.web.product_group.tab]
 *
 * API Routes:
 *   GET    product_groups                                      -> getProductGroups()                        [admin.api.product_groups.get]
 *   POST   product_groups/update_order                         -> postProductGroupsUpdateOrder()            [admin.api.product_groups.update_order.post]
 *   POST   product_group                                       -> postProductGroup()                        [admin.api.product_group.post]
 *   GET    product_group/{uuid}                                -> getProductGroup()                         [admin.api.product_group.get]
 *   PUT    product_group/{uuid}                                -> putProductGroup()                         [admin.api.product_group.put]
 *   DELETE product_group/{uuid}                                -> deleteProductGroup()                      [admin.api.product_group.delete]
 *   GET    product_group/{uuid}/products                       -> getProductGroupProducts()                 [admin.api.product_group_products.get]
 *   GET    product_group/{uuid}/products/select                -> getProductGroupProductsSelect()           [admin.api.product_group_products.select.get]
 *   POST   product_group/{uuid}/product                        -> postProductGroupProduct()                 [admin.api.product_group_product.post]
 *   POST   product_group/{uuid}/products/update_order          -> postProductGroupProductsUpdateOrder()     [admin.api.product_group_products.update_order.post]
 *   DELETE product_group/{uuid}/product/{p_uuid}               -> deleteProductGroupProduct()               [admin.api.product_group_product.delete]
 *   GET    product_group/list_templates/select                 -> getListTemplatesSelect()                  [admin.api.product_group.list_templates.select.get]
 *   GET    product_group/order_templates/select                -> getOrderTemplatesSelect()                 [admin.api.product_group.order_templates.select.get]
 *   GET    product_group/manage_templates/select               -> getManageTemplatesSelect()                [admin.api.product_group.manage_templates.select.get]
 *   GET    product_group/upgrade_templates/select              -> getUpgradeTemplatesSelect()               [admin.api.product_group.upgrade_templates.select.get]
 *   GET    product_group/proration_templates/select            -> getProrationTemplatesSelect()             [admin.api.product_group.proration_templates.select.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminProductGroupsController extends Controller
{
    public function productGroups(): View
    {
        $title = __('main.Product Groups');

        return view_admin('product_groups.product_groups', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.product_groups.get',
        description: 'Returns a DataTable list of product groups.',
        summary: 'Get product groups list',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product groups list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductGroups(Request $request): JsonResponse
    {
        $query = ProductGroup::query();

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
                ->addColumn('name', function ($product_group) {
                    return $product_group->name;
                })
                ->addColumn('images', function ($product_group) {
                    return $product_group->images;
                })
                ->order(function ($product_group) use ($request) {
                    $direction = $request->get('dir', 'asc');
                    $product_group->orderBy('order', $direction);
                })
                ->addColumn('products_count', function ($product_group) {
                    return $product_group->products->count();
                })
                ->addColumn('urls', function ($product_group) {
                    $urls['edit'] = route('admin.web.product_group.tab',
                        ['uuid' => $product_group->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.product_group.delete', $product_group->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.product_group.post',
        description: 'Creates a new product group with the given key.',
        summary: 'Create a new product group',
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
                description: 'Product group created successfully',
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
    public function postProductGroup(Request $request): JsonResponse
    {
        $product_group = new ProductGroup;

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:product_groups,key',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }
        $product_group->key = $request->input('key');
        $product_group->save();
        $product_group->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $product_group,
            'redirect' => route('admin.web.product_group.tab', ['uuid' => $product_group->uuid, 'tab' => 'general']),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.product_groups.update_order.post',
        description: 'Updates the display order of a product group.',
        summary: 'Update product group order',
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
            new OA\Response(response: 404, description: 'Product group not found'),
            new OA\Response(response: 422, description: 'Validation error or cannot move'),
        ]
    )]
    public function postProductGroupsUpdateOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:product_groups,uuid',
            'new_order' => 'required|integer',
        ], [
            'uuid.required' => __('error.The uuid field is required'),
            'uuid.exists' => __('error.The uuid does not exist in the product_groups table'),
            'new_order.required' => __('error.The new_order field is required'),
            'new_order.integer' => __('error.The new_order must be an integer'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $productGroup = ProductGroup::where('uuid', $request->input('uuid'))->first();

        if (!$productGroup) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product group not found')],
            ], 404);
        }

        $productGroups = ProductGroup::orderBy('order')->get();

        $minOrder = $productGroups->first()->order;
        $maxOrder = $productGroups->last()->order;

        if ($productGroup->order == $minOrder && $request->input('new_order') < $minOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The first item cannot be moved up')],
            ], 422);
        }

        if ($productGroup->order == $maxOrder && $request->input('new_order') > $maxOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The last item cannot be moved down')],
            ], 422);
        }

        $currentOrder = $productGroup->order;
        $newOrder = $request->input('new_order');

        if ($newOrder > $currentOrder) {
            ProductGroup::where('order', '>', $currentOrder)
                ->where('order', '<=', $newOrder)
                ->decrement('order');
        } else {
            ProductGroup::where('order', '<', $currentOrder)
                ->where('order', '>=', $newOrder)
                ->increment('order');
        }

        $productGroup->order = $newOrder;
        $productGroup->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    public function productGroupTab(Request $request, $uuid, $tab): View|RedirectResponse
    {
        $validTabs = [
            'general', 'settings', 'images', 'products',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route('admin.web.product_group.tab', ['uuid' => $uuid, 'tab' => 'general']);
        }

        $title = __('main.Product Group');
        $locales = config('locale.client.locales');

        return view_admin('product_groups.product_group_'.$tab, compact('title', 'uuid', 'tab', 'locales'));
    }

    #[OA\Get(
        path: 'admin.api.product_group.get',
        description: 'Returns detailed information about a product group by UUID.',
        summary: 'Get product group by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'locale', description: 'Locale for translations', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product group data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product group not found'),
        ]
    )]
    public function getProductGroup(Request $request, $uuid): JsonResponse
    {
        $product_group = ProductGroup::find($uuid);

        if (empty($product_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        // $product_group->loadImagesField();

        if (!empty($request->input('locale'))) {
            $product_group->setLocale($request->input('locale'));
        }

        return response()->json([
            'data' => $product_group,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.product_group.put',
        description: 'Updates an existing product group by UUID, including key, name, description, templates, and settings.',
        summary: 'Update product group by UUID',
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
                        new OA\Property(property: 'icon', type: 'string'),
                        new OA\Property(property: 'list_template', type: 'string'),
                        new OA\Property(property: 'order_template', type: 'string'),
                        new OA\Property(property: 'manage_template', type: 'string'),
                        new OA\Property(property: 'hidden', type: 'string', enum: ['yes', 'no']),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product group updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product group not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putProductGroup(Request $request, $uuid): JsonResponse
    {
        $product_group = ProductGroup::find($uuid);

        if (empty($product_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        // Partial updates: `key` is validated only when actually submitted. This lets the
        // General tab save localized name/description/notes without re-sending the group key.
        $validator = Validator::make($request->all(), [
            'key' => 'sometimes|required|unique:product_groups,key,'.$product_group->uuid.',uuid',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('key')) {
            $product_group->key = $request->input('key');
        }

        if (!empty($request->input('locale'))) {
            $product_group->setLocale($request->input('locale'));
        }

        if ($request->has('name')) {
            $product_group->name = $request->input('name');
        }
        if ($request->has('description')) {
            $product_group->description = $request->input('description');
        }
        if ($request->has('short_description')) {
            $product_group->short_description = $request->input('short_description');
        }
        if ($request->has('notes')) {
            $product_group->notes = $request->input('notes');
        }

        if ($request->has('icon')) {
            $product_group->icon = $request->input('icon');
        }

        if ($request->has('list_template')) {
            $product_group->list_template = $request->input('list_template');
        }

        if ($request->has('order_template')) {
            $product_group->order_template = $request->input('order_template');
        }

        if ($request->has('manage_template')) {
            $product_group->manage_template = $request->input('manage_template');
        }

        if ($request->has('upgrade_template')) {
            $product_group->upgrade_template = $request->input('upgrade_template');
        }

        if ($request->has('proration_template')) {
            $product_group->proration_template = $request->input('proration_template');
        }

        if ($request->has('hidden')) {
            $product_group->hidden = false;
            if ($request->input('hidden') == 'yes') {
                $product_group->hidden = true;
            }
        }

        $product_group->save();
        $product_group->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $product_group,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.product_group.delete',
        description: 'Deletes a product group by UUID if it has no associated products.',
        summary: 'Delete product group',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product group deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Cannot delete product group with associated products'),
            new OA\Response(response: 404, description: 'Product group not found'),
            new OA\Response(response: 500, description: 'Deletion failed'),
        ]
    )]
    public function deleteProductGroup(Request $request, $uuid): JsonResponse
    {
        $product_group = ProductGroup::find($uuid);
        if (empty($product_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($product_group->products()->count() != 0) {
            return response()->json([
                'errors' => [__('error.Cannot delete Product Group with associated products')],
            ], 400);
        }

        try {
            $deleted = $product_group->delete();
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

        ProductGroup::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product_group_products.get',
        description: 'Returns a list of products in a product group with DataTables support.',
        summary: 'Get products in product group',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductGroupProducts(Request $request, $uuid): JsonResponse
    {
        $query = Product::query()
            ->whereHas('productGroups', function ($q) use ($uuid) {
                $q->where('product_groups.uuid', $uuid);
            })
            ->join('product_x_product_group', 'products.uuid', '=', 'product_x_product_group.product_uuid')
            ->where('product_x_product_group.product_group_uuid', $uuid)
            ->select('products.*', 'product_x_product_group.order as pivot_order');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('products.uuid', 'like', "%{$search}%")
                                ->orWhere('products.notes', 'like', "%{$search}%");
                        });
                    }
                })
                ->order(function ($query) use ($request) {
                    $direction = $request->get('dir', 'asc');
                    $query->orderBy('pivot_order', $direction);
                })
                ->addColumn('name', function ($product) {
                    return $product->name;
                })
                ->addColumn('order', function ($product) {
                    return $product->pivot_order;
                })
                ->addColumn('urls', function ($product) use ($uuid) {
                    $urls['edit'] = route('admin.web.product.tab', ['uuid' => $product->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.product_group_product.delete',
                        ['uuid' => $uuid, 'p_uuid' => $product->uuid]);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.product_group_products.select.get',
        description: 'Returns a select list of products for a product group.',
        summary: 'Get product group products select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product group products select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductGroupProductsSelect(Request $request, $uuid): JsonResponse
    {
        $search = $request->input('q');

        $existingProductUuids = DB::table('product_x_product_group')
            ->where('product_group_uuid', $uuid)
            ->pluck('product_uuid')
            ->toArray();

        $query = Product::query();

        if (!empty($search)) {
            $query->where('uuid', 'like', '%'.$search.'%')
                ->orWhere('key', 'like', '%'.$search.'%');
        }

        $query->whereNotIn('uuid', $existingProductUuids);

        $products = $query->get();

        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->uuid,
                'text' => $product->key,
            ];
        }

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.product_group_product.post',
        description: 'Adds a product to a product group.',
        summary: 'Add product to product group',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['product_uuid'],
                    properties: [
                        new OA\Property(property: 'product_uuid', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product added to group successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Product already exists in this group'),
            new OA\Response(response: 404, description: 'Product group or product not found'),
        ]
    )]
    public function postProductGroupProduct(Request $request, $uuid): JsonResponse
    {
        $product_group = ProductGroup::find($uuid);

        if (empty($product_group)) {
            return response()->json([
                'errors' => [__('error.Product group not found')],
            ], 404);
        }

        $product = Product::find($request->input('product_uuid'));

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Product not found')],
            ], 404);
        }

        $existingRelation = $product_group->products()->where('product_uuid', $product->uuid)->exists();

        if ($existingRelation) {
            return response()->json([
                'errors' => [__('error.Product already exists in this group')],
            ], 400);
        }
        $product_group->addProduct($product);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.product_group_products.update_order.post',
        description: 'Updates the display order of a product within a product group.',
        summary: 'Update product order in product group',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['product_uuid', 'new_order'],
                    properties: [
                        new OA\Property(property: 'product_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'new_order', type: 'integer'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
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
            new OA\Response(response: 404, description: 'Product group or product not found'),
            new OA\Response(response: 422, description: 'Validation error or cannot move'),
        ]
    )]
    public function postProductGroupProductsUpdateOrder(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_uuid' => 'required|exists:products,uuid',
            'new_order' => 'required|integer',
        ], [
            'product_uuid.required' => __('error.Product uuid is required'),
            'product_uuid.exists' => __('error.Product not found'),
            'new_order.required' => __('error.New order is required'),
            'new_order.integer' => __('error.New order must be an integer'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $productGroup = ProductGroup::where('uuid', $uuid)->first();

        if (!$productGroup) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product group not found')],
            ], 404);
        }

        $product = Product::where('uuid', $request->input('product_uuid'))->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product not found')],
            ], 404);
        }

        $pivot = $productGroup->products()->where('product_uuid', $product->uuid)->first();

        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product is not associated with this group')],
            ], 404);
        }

        $oldOrder = $pivot->pivot->order;
        $newOrder = $request->input('new_order');

        $minOrder = $productGroup->products()->min('product_x_product_group.order');
        $maxOrder = $productGroup->products()->max('product_x_product_group.order');

        if ($oldOrder == $minOrder && $newOrder < $minOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The first item cannot be moved up')],
            ], 422);
        }

        if ($oldOrder == $maxOrder && $newOrder > $maxOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The last item cannot be moved down')],
            ], 422);
        }

        if ($newOrder > $oldOrder) {
            DB::table('product_x_product_group')
                ->where('product_group_uuid', $uuid)
                ->where('order', '>', $oldOrder)
                ->where('order', '<=', $newOrder)
                ->decrement('order');
        } elseif ($newOrder < $oldOrder) {
            DB::table('product_x_product_group')
                ->where('product_group_uuid', $uuid)
                ->where('order', '<', $oldOrder)
                ->where('order', '>=', $newOrder)
                ->increment('order');
        }

        $productGroup->products()->updateExistingPivot($product->uuid, ['order' => $newOrder]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.product_group_product.delete',
        description: 'Removes a product from a product group.',
        summary: 'Remove product from product group',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'p_uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product removed from group successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product group, product, or association not found'),
        ]
    )]
    public function deleteProductGroupProduct(Request $request, $uuid, $p_uuid): JsonResponse
    {
        $productGroup = ProductGroup::where('uuid', $uuid)->first();

        if (!$productGroup) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product group not found')],
            ], 404);
        }

        $product = Product::where('uuid', $p_uuid)->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product not found')],
            ], 404);
        }

        $pivot = $productGroup->products()->where('uuid', $product->uuid)->first();

        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product is not associated with this group')],
            ], 404);
        }

        $productGroup->removeProduct($product);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product_group.list_templates.select.get',
        description: 'Returns a select list of list templates.',
        summary: 'Get list templates select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List templates select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getListTemplatesSelect(Request $request): JsonResponse
    {
        $folders = getServiceViewTemplatesList('list');

        $searchTerm = $request->get('term', '');
        $filteredFolders = array_filter($folders, function ($folder) use ($searchTerm) {
            return empty($searchTerm) || stripos($folder['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredFolders),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.product_group.order_templates.select.get',
        description: 'Returns a select list of order templates.',
        summary: 'Get order templates select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order templates select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getOrderTemplatesSelect(Request $request): JsonResponse
    {
        $folders = getServiceViewTemplatesList('order');

        $searchTerm = $request->get('term', '');
        $filteredFolders = array_filter($folders, function ($folder) use ($searchTerm) {
            return empty($searchTerm) || stripos($folder['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredFolders),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.product_group.manage_templates.select.get',
        description: 'Returns a select list of manage templates.',
        summary: 'Get manage templates select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Manage templates select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getManageTemplatesSelect(Request $request): JsonResponse
    {
        $folders = getServiceViewTemplatesList('manage');

        $searchTerm = $request->get('term', '');
        $filteredFolders = array_filter($folders, function ($folder) use ($searchTerm) {
            return empty($searchTerm) || stripos($folder['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredFolders),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.product_group.upgrade_templates.select.get',
        description: 'Returns a select list of upgrade templates.',
        summary: 'Get upgrade templates select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Upgrade templates select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getUpgradeTemplatesSelect(Request $request): JsonResponse
    {
        $folders = getServiceViewTemplatesList('upgrade');

        $searchTerm = $request->get('term', '');
        $filteredFolders = array_filter($folders, function ($folder) use ($searchTerm) {
            return empty($searchTerm) || stripos($folder['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredFolders),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.product_group.proration_templates.select.get',
        description: 'Returns a select list of proration templates.',
        summary: 'Get proration templates select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proration templates select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProrationTemplatesSelect(Request $request): JsonResponse
    {
        $folders = getServiceViewTemplatesList('proration');

        $searchTerm = $request->get('term', '');
        $filteredFolders = array_filter($folders, function ($folder) use ($searchTerm) {
            return empty($searchTerm) || stripos($folder['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredFolders),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }
}
