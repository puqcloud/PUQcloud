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
 *   GET  product_attribute_groups                              -> productAttributeGroups()                  [admin.web.product_attribute_groups]
 *   GET  product_attribute_group/{uuid}/{tab}                  -> productAttributeGroupTab()                [admin.web.product_attribute_group.tab]
 *
 * API Routes:
 *   GET    product_attribute_groups                             -> getProductAttributeGroups()               [admin.api.product_attribute_groups.get]
 *   POST   product_attribute_group                              -> postProductAttributeGroup()               [admin.api.product_attribute_group.post]
 *   GET    product_attribute_group/{uuid}                       -> getProductAttributeGroup()                [admin.api.product_attribute_group.get]
 *   PUT    product_attribute_group/{uuid}                       -> putProductAttributeGroup()                [admin.api.product_attribute_group.put]
 *   DELETE product_attribute_group/{uuid}                       -> deleteProductAttributeGroup()             [admin.api.product_attribute_group.delete]
 *   GET    product_attribute_group/{uuid}/product_attributes    -> getProductAttributeGroupProductAttributes() [admin.api.product_attribute_group.product_attributes.get]
 *   POST   product_attribute_group/{uuid}/product_attribute     -> postProductAttributeGroupProductAttribute() [admin.api.product_attribute_group.product_attribute.post]
 *   GET    product_attribute/{uuid}                             -> getProductAttribute()                     [admin.api.product_attribute.get]
 *   PUT    product_attribute/{uuid}                             -> putProductAttribute()                     [admin.api.product_attribute.put]
 *   DELETE product_attribute/{uuid}                             -> deleteProductAttribute()                  [admin.api.product_attribute.delete]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminProductAttributesController extends Controller
{
    public function productAttributeGroups(): View
    {
        $title = __('main.Product Attribute Groups');

        return view_admin('product_attribute_groups.product_attribute_groups', compact('title'));
    }

    public function productAttributeGroupTab(Request $request, $uuid, $tab): View|RedirectResponse
    {
        $validTabs = [
            'general', 'images', 'attributes',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route('admin.web.product_attribute_group.tab', ['uuid' => $uuid, 'tab' => 'general']);
        }

        $title = __('main.Product Attribute Group');
        $locales = config('locale.client.locales');

        return view_admin('product_attribute_groups.product_attribute_group_'.$tab,
            compact('title', 'uuid', 'tab', 'locales'));
    }

    #[OA\Get(
        path: 'admin.api.product_attribute_groups.get',
        description: 'Returns a DataTable list of product attribute groups.',
        summary: 'Get product attribute groups list',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product attribute groups list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductAttributeGroups(Request $request): JsonResponse
    {
        $query = ProductAttributeGroup::query();

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
                ->addColumn('name', function ($product_attribute_group) {
                    return $product_attribute_group->name;
                })
                ->addColumn('images', function ($product_attribute_group) {
                    return $product_attribute_group->images;
                })
                ->addColumn('attributes_count', function ($product_attribute_group) {
                    return $product_attribute_group->productAttributes->count();
                })
                ->addColumn('urls', function ($product_attribute_group) {
                    $urls['edit'] = route('admin.web.product_attribute_group.tab',
                        ['uuid' => $product_attribute_group->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.product_attribute_group.delete', $product_attribute_group->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.product_attribute_group.post',
        description: 'Creates a new product attribute group with the given key.',
        summary: 'Create a new product attribute group',
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
                description: 'Product attribute group created successfully',
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
    public function postProductAttributeGroup(Request $request): JsonResponse
    {
        $product_attribute_group = new ProductAttributeGroup;

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:product_attribute_groups,key',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }
        $product_attribute_group->key = $request->input('key');
        $product_attribute_group->save();
        $product_attribute_group->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $product_attribute_group,
            'redirect' => route('admin.web.product_attribute_group.tab',
                ['uuid' => $product_attribute_group->uuid, 'tab' => 'general']),
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.product_attribute_group.delete',
        description: 'Deletes a product attribute group by UUID if it has no associated attributes.',
        summary: 'Delete product attribute group',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product attribute group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product attribute group deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Cannot delete with associated attributes'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 500, description: 'Deletion failed'),
        ]
    )]
    public function deleteProductAttributeGroup(Request $request, $uuid): JsonResponse
    {
        $product_attribute_group = ProductAttributeGroup::find($uuid);
        if (empty($product_attribute_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($product_attribute_group->productAttributes()->count() != 0) {
            return response()->json([
                'errors' => [__('error.Cannot delete Product Attribute Group with associated Attributes')],
            ], 400);
        }

        try {
            $deleted = $product_attribute_group->delete();
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
        path: 'admin.api.product_attribute_group.get',
        description: 'Returns detailed information about a product attribute group by UUID.',
        summary: 'Get product attribute group by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product attribute group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'locale', description: 'Locale for translations', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product attribute group data returned successfully',
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
    public function getProductAttributeGroup(Request $request, $uuid): JsonResponse
    {
        $product_attribute_group = ProductAttributeGroup::find($uuid);

        if (empty($product_attribute_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        // $product_attribute_group->loadImagesField();

        if (!empty($request->input('locale'))) {
            $product_attribute_group->setLocale($request->input('locale'));
        }

        return response()->json([
            'data' => $product_attribute_group,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.product_attribute_group.put',
        description: 'Updates an existing product attribute group by UUID.',
        summary: 'Update product attribute group by UUID',
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
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product attribute group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product attribute group updated successfully',
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
    public function putProductAttributeGroup(Request $request, $uuid): JsonResponse
    {
        $product_attribute_group = ProductAttributeGroup::find($uuid);

        if (empty($product_attribute_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:product_groups,key,'.$product_attribute_group->uuid.',uuid',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $product_attribute_group->key = $request->input('key');

        if (!empty($request->input('locale'))) {
            $product_attribute_group->setLocale($request->input('locale'));
        }
        if ($request->has('name')) {
            $product_attribute_group->name = $request->input('name');
        }
        if ($request->has('description')) {
            $product_attribute_group->description = $request->input('description');
        }
        if ($request->has('short_description')) {
            $product_attribute_group->short_description = $request->input('short_description');
        }
        if ($request->has('notes')) {
            $product_attribute_group->notes = $request->input('notes');
        }
        if ($request->has('hidden')) {
            $product_attribute_group->hidden = false;
            if ($request->input('hidden') == 'yes') {
                $product_attribute_group->hidden = true;
            }
        }

        $product_attribute_group->save();
        $product_attribute_group->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $product_attribute_group,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product_attribute_group.product_attributes.get',
        description: 'Returns a list of product attributes in a product attribute group with DataTables support.',
        summary: 'Get product attributes in attribute group',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product attribute group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product attributes returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductAttributeGroupProductAttributes(Request $request, $uuid): JsonResponse
    {
        $query = ProductAttributeGroup::query()->find($uuid)->productAttributes();

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
                ->addColumn('name', function ($query) {
                    return $query->name;
                })
                ->addColumn('images', function ($query) {
                    return $query->images;
                })
                ->addColumn('urls', function ($product_attribute) {
                    $urls['edit'] = route('admin.web.product_attribute_group.tab', [
                        'uuid' => $product_attribute->product_attribute_group_uuid, 'tab' => 'attributes',
                        'edit' => $product_attribute->uuid,
                    ]);
                    $urls['delete'] = route('admin.api.product_attribute.delete', $product_attribute->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.product_attribute_group.product_attribute.post',
        description: 'Creates a new product attribute within a product attribute group.',
        summary: 'Create product attribute in attribute group',
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
            new OA\Parameter(name: 'uuid', description: 'UUID of the product attribute group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product attribute created successfully',
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
            new OA\Response(response: 404, description: 'Product attribute group not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postProductAttributeGroupProductAttribute(Request $request, $uuid): JsonResponse
    {
        $product_attribute_group = ProductAttributeGroup::find($uuid);

        if (empty($product_attribute_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $product_attribute = new ProductAttribute;

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:product_attributes,key',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }
        $product_attribute->key = $request->input('key');
        $product_attribute->product_attribute_group_uuid = $uuid;
        $product_attribute->save();
        $product_attribute->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $product_attribute,
            'redirect' => route('admin.web.product_attribute_group.tab',
                ['uuid' => $product_attribute_group->uuid, 'tab' => 'attributes', 'edit' => $product_attribute->uuid]),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product_attribute.get',
        description: 'Returns detailed information about a product attribute by UUID.',
        summary: 'Get product attribute by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product attribute', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'locale', description: 'Locale for translations', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product attribute data returned successfully',
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
    public function getProductAttribute(Request $request, $uuid): JsonResponse
    {
        $product_attribute = ProductAttribute::find($uuid);

        if (empty($product_attribute)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if (!empty($request->input('locale'))) {
            $product_attribute->setLocale($request->input('locale'));
        }

        return response()->json([
            'data' => $product_attribute,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.product_attribute.put',
        description: 'Updates an existing product attribute by UUID.',
        summary: 'Update product attribute by UUID',
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
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product attribute', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product attribute updated successfully',
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
    public function putProductAttribute(Request $request, $uuid): JsonResponse
    {
        $product_attribute = ProductAttribute::find($uuid);

        if (empty($product_attribute)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:product_groups,key,'.$product_attribute->uuid.',uuid',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $product_attribute->key = $request->input('key');

        if (!empty($request->input('locale'))) {
            $product_attribute->setLocale($request->input('locale'));
        }
        if ($request->has('name')) {
            $product_attribute->name = $request->input('name');
        }
        if ($request->has('description')) {
            $product_attribute->description = $request->input('description');
        }
        if ($request->has('short_description')) {
            $product_attribute->short_description = $request->input('short_description');
        }
        if ($request->has('notes')) {
            $product_attribute->notes = $request->input('notes');
        }
        if ($request->has('hidden')) {
            $product_attribute->hidden = false;
            if ($request->input('hidden') == 'yes') {
                $product_attribute->hidden = true;
            }
        }

        $product_attribute->save();
        $product_attribute->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $product_attribute,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.product_attribute.delete',
        description: 'Deletes a product attribute by UUID.',
        summary: 'Delete product attribute',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product attribute', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product attribute deleted successfully',
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
    public function deleteProductAttribute(Request $request, $uuid): JsonResponse
    {
        $product_attribute = ProductAttribute::find($uuid);
        if (empty($product_attribute)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $product_attribute->delete();
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
