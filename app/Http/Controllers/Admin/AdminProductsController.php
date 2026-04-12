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
 *   GET  products                                              -> products()                                [admin.web.products]
 *   GET  product/{uuid}/{tab}                                  -> productTab()                              [admin.web.product.tab]
 *
 * API Routes:
 *   GET    products                                            -> getProducts()                             [admin.api.products.get]
 *   POST   product                                             -> postProduct()                             [admin.api.product.post]
 *   GET    product/{uuid}                                      -> getProduct()                              [admin.api.product.get]
 *   PUT    product/{uuid}                                      -> putProduct()                              [admin.api.product.put]
 *   DELETE product/{uuid}                                      -> deleteProduct()                           [admin.api.product.delete]
 *   PUT    product/{uuid}/module                               -> putProductModule()                        [admin.api.product.module.put]
 *   GET    product/{uuid}/module                               -> getProductModule()                        [admin.api.product.module.get]
 *   GET    product/{uuid}/prices                               -> getProductPrices()                        [admin.api.product.prices.get]
 *   GET    product/{uuid}/price/{p_uuid}                       -> getProductPrice()                         [admin.api.product.price.get]
 *   PUT    product/{uuid}/price                                -> putProductPrice()                         [admin.api.product.price.put]
 *   POST   product/{uuid}/price                                -> postProductPrice()                        [admin.api.product.price.post]
 *   DELETE product/{uuid}/price/{p_uuid}                       -> deleteProductPrice()                      [admin.api.product.price.delete]
 *   GET    product/{uuid}/product_attributes                   -> getProductProductAttributes()             [admin.api.product.product_attributes.get]
 *   POST   product/{uuid}/product_attribute                    -> postProductProductAttribute()             [admin.api.product.product_attribute.post]
 *   GET    product/{uuid}/product_attributes/select            -> getProductProductAttributesSelect()       [admin.api.product.product_attributes.select.get]
 *   DELETE product/{uuid}/product_attribute/{pa_uuid}          -> deleteProductProductAttribute()           [admin.api.product.product_attribute.delete]
 *   GET    product/{uuid}/product_option_groups                -> getProductProductOptionGroups()           [admin.api.product.product_option_groups.get]
 *   POST   product/{uuid}/product_option_group                 -> postProductProductOptionGroup()           [admin.api.product.product_option_group.post]
 *   GET    product/{uuid}/product_option_groups/select         -> getProductProductOptionsGroupsSelect()    [admin.api.product.product_option_groups.select.get]
 *   DELETE product/{uuid}/product_option_group/{pog_uuid}      -> deleteProductProductOptionGroups()        [admin.api.product.product_option_group.delete]
 *   POST   product_group/{uuid}/product_option_group/update_order -> postProductProductOptionGroupsUpdateOrder() [admin.api.product.product_option_group.update_order.post]
 *   GET    product/{uuid}/modules/select                       -> getProductModulesSelect()                 [admin.api.product.module.select.get]
 *   GET    products/select                                     -> getProductsSelect()                       [admin.api.products.select.get]
 *   GET    product/prices/select                               -> getProductPricesSelect()                  [admin.api.product.prices.select.get]
 *   GET    product_option_groups/by_product                    -> getProductOptionGroupsByProduct()         [admin.api.product_option_groups.by_product.get]
 *   GET    price/periods/select                                -> getPricePeriods()                         [admin.api.price.periods.select.get]
 *
 * Other Product domains live in dedicated controllers:
 *   AdminProductGroupsController       — Product Groups + Templates selects
 *   AdminProductAttributesController   — Product Attribute Groups + Attributes
 *   AdminProductOptionsController      — Product Option Groups + Options + Prices
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Module;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductOptionGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminProductsController extends Controller
{
    public function products(): View
    {
        $title = __('main.Products');

        return view_admin('products.products', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.products.get',
        description: 'Returns a DataTable list of products.',
        summary: 'Get products list',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::query();

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
                ->addColumn('services_count', function ($product) {
                    return $product->services()->where('status', 'active')->count().'/'.$product->services()->count();
                })
                ->addColumn('images', function ($product) {
                    return $product->images;
                })
                ->addColumn('name', function ($product) {
                    return $product->name;
                })
                ->addColumn('urls', function ($product) {
                    $urls['edit'] = route('admin.web.product.tab', ['uuid' => $product->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.product.delete', $product->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.product.post',
        description: 'Creates a new product with the given key.',
        summary: 'Create a new product',
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
                description: 'Product created successfully',
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
    public function postProduct(Request $request): JsonResponse
    {
        $product = new Product;

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:products,key',
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }
        $product->key = $request->input('key');

        $product->save();
        $product->refresh();

        app(\App\Services\HookService::class)->callHooks('ProductCreated', ['product' => $product]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $product,
            'redirect' => route('admin.web.product.tab', ['uuid' => $product->uuid, 'tab' => 'general']),
        ]);
    }

    public function productTab(Request $request, $uuid, $tab): View|RedirectResponse
    {
        $validTabs = [
            'general', 'settings', 'images', 'attributes', 'pricing', 'options', 'module',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route('admin.web.product.tab', ['uuid' => $uuid, 'tab' => 'general']);
        }

        $title = __('main.Product');
        $locales = config('locale.client.locales');

        return view_admin('products.product_'.$tab, compact('title', 'uuid', 'tab', 'locales'));
    }

    #[OA\Get(
        path: 'admin.api.product.get',
        description: 'Returns detailed information about a product by UUID.',
        summary: 'Get product by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'locale', description: 'Locale for translations', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function getProduct(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if (!empty($request->input('locale'))) {
            $product->setLocale($request->input('locale'));
        }

        return response()->json([
            'data' => $product,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.product.put',
        description: 'Updates an existing product by UUID, including key, name, description, and various settings.',
        summary: 'Update product by UUID',
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
                        new OA\Property(property: 'cancellation_delay_hours', type: 'integer'),
                        new OA\Property(property: 'termination_delay_hours', type: 'integer'),
                        new OA\Property(property: 'hidden', type: 'string', enum: ['yes', 'no']),
                        new OA\Property(property: 'retired', type: 'string', enum: ['yes', 'no']),
                        new OA\Property(property: 'hourly_billing', type: 'string', enum: ['yes', 'no']),
                        new OA\Property(property: 'allow_idle', type: 'string', enum: ['yes', 'no']),
                        new OA\Property(property: 'convert_price', type: 'string', enum: ['yes', 'no']),
                        new OA\Property(property: 'stock_control', type: 'string', enum: ['yes', 'no']),
                        new OA\Property(property: 'quantity', type: 'integer'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putProduct(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        // Partial updates: `key` is validated only when actually submitted. This lets the
        // General tab save localized name/description/notes without re-sending the product key.
        $validator = Validator::make($request->all(), [
            'key' => 'sometimes|required|unique:products,key,'.$product->uuid.',uuid',
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
            $product->key = $request->input('key');
        }

        if (!empty($request->input('locale'))) {
            $product->setLocale($request->input('locale'));
        }

        if ($request->has('name')) {
            $product->name = $request->input('name');
        }
        if ($request->has('description')) {
            $product->description = $request->input('description');
        }
        if ($request->has('short_description')) {
            $product->short_description = $request->input('short_description');
        }
        if ($request->has('notes')) {
            $product->notes = $request->input('notes');
        }

        if ($request->has('cancellation_delay_hours')) {
            if ($request->input('cancellation_delay_hours') > 0) {
                $product->cancellation_delay_hours = $request->input('cancellation_delay_hours');
            }
        }

        if ($request->has('termination_delay_hours')) {
            if ($request->input('termination_delay_hours') > 0) {
                $product->termination_delay_hours = $request->input('termination_delay_hours');
            }
        }

        // Resolve the final billing mode + hourly_billing together so the compatibility check
        // uses the NEW values from the request, not stale DB state. Hourly billing only makes
        // sense for prepaid services — for invoice/both we silently disable it (the UI already
        // hides the toggle in those modes).
        $newBillingMode = $request->has('billing_mode')
            ? $request->input('billing_mode')
            : $product->billing_mode;

        if ($request->has('billing_mode') && ! in_array($newBillingMode, ['prepaid', 'invoice', 'both'], true)) {
            $newBillingMode = $product->billing_mode;
        }

        $hourlyRequested = $request->has('hourly_billing')
            ? ($request->input('hourly_billing') == 'yes')
            : (bool) $product->hourly_billing;

        // Only reject when the user EXPLICITLY sent hourly_billing=yes together with an
        // incompatible billing mode. Implicit stale DB values get auto-corrected below.
        if (
            $request->has('hourly_billing')
            && $request->input('hourly_billing') == 'yes'
            && in_array($newBillingMode, ['invoice', 'both'], true)
        ) {
            return response()->json([
                'status' => 'error',
                'errors' => ['Hourly billing is not compatible with invoice billing mode'],
            ], 422);
        }

        if (in_array($newBillingMode, ['invoice', 'both'], true)) {
            $hourlyRequested = false; // force-disable for incompatible modes
        }

        $product->billing_mode = $newBillingMode;
        $product->hourly_billing = $hourlyRequested;

        if ($request->has('hidden')) {
            $product->hidden = false;
            if ($request->input('hidden') == 'yes') {
                $product->hidden = true;
            }
        }
        if ($request->has('retired')) {
            $product->retired = false;
            if ($request->input('retired') == 'yes') {
                $product->retired = true;
            }
        }
        if ($request->has('allow_idle')) {
            $product->allow_idle = false;
            if ($request->input('allow_idle') == 'yes') {
                $product->allow_idle = true;
            }
        }
        if ($request->has('convert_price')) {
            $product->convert_price = false;
            if ($request->input('convert_price') == 'yes') {
                $product->convert_price = true;
            }
        }
        if ($request->has('stock_control')) {
            $product->stock_control = false;
            if ($request->input('stock_control') == 'yes') {
                $product->stock_control = true;
            }
        }
        if ($request->has('quantity')) {
            $value = $request->input('quantity');
            $product->quantity = is_numeric($value) && intval($value) == $value ? (int) $value : 0;
        }
        if ($request->has('allow_upgrade')) {
            $product->allow_upgrade = $request->input('allow_upgrade') == 'yes';
        }
        if ($request->has('allow_downgrade')) {
            $product->allow_downgrade = $request->input('allow_downgrade') == 'yes';
        }
        if ($request->has('allow_proration_alignment')) {
            $product->allow_proration_alignment = $request->input('allow_proration_alignment') == 'yes';
        }

        $product->save();
        $product->refresh();

        if (!empty($product->convert_price)) {
            $product->convertPrice();
        }

        app(\App\Services\HookService::class)->callHooks('ProductEdit', ['product' => $product]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $product,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.product.delete',
        description: 'Deletes a product by UUID if it has no associated services.',
        summary: 'Delete product',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Cannot delete product with active services'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 500, description: 'Deletion failed'),
        ]
    )]
    public function deleteProduct(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);
        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($product->services()->count() != 0) {
            return response()->json([
                'errors' => [__('error.Cannot delete product because it is associated with active services')],
            ], 400);
        }

        $productData = ['uuid' => $product->uuid, 'key' => $product->key];

        try {
            $deleted = $product->delete();
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

        app(\App\Services\HookService::class)->callHooks('ProductDeleted', $productData);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.price.periods.select.get',
        description: 'Returns a select list of price periods.',
        summary: 'Get price periods select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Price periods list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getPricePeriods(Request $request): JsonResponse
    {
        $categories = [];
        foreach (config('pricing.product') as $key => $value) {
            $categories[] = [
                'id' => $key,
                'text' => __('main.'.$key),
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredLanguages = array_filter($categories, function ($category) use ($searchTerm) {
            return empty($searchTerm) || stripos($category['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredLanguages),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    #[OA\Put(
        path: 'admin.api.product.price.put',
        description: 'Updates an existing price for a product.',
        summary: 'Update product price',
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
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
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
    public function putProductPrice(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $price = $product->prices()->find($request->input('price_uuid'));
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

        if (!empty($product->convert_price)) {
            $product->convertPrice();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Edited successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.product.price.post',
        description: 'Creates a new price entry for a product with currency, period, and price values.',
        summary: 'Create product price',
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
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
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
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error or price already exists'),
        ]
    )]
    public function postProductPrice(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);

        if (empty($product)) {
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

        $existingPrice = $product->prices()
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
        $product->prices()->attach($price->uuid);

        if (!empty($product->convert_price)) {
            $product->convertPrice();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Added successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product.prices.get',
        description: 'Returns a list of prices for a product with DataTables support.',
        summary: 'Get product prices',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product prices returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function getProductPrices(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);
        if (!$product) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $query = $product->prices();

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
                    $urls['edit'] = route('admin.api.product.price.get', ['uuid' => $uuid, 'p_uuid' => $price->uuid]);
                    $urls['delete'] = route('admin.api.product.price.delete',
                        ['uuid' => $uuid, 'p_uuid' => $price->uuid]);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.product.price.get',
        description: 'Returns a specific price for a product by price UUID.',
        summary: 'Get product price',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
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
    public function getProductPrice(Request $request, $uuid, $p_uuid): JsonResponse
    {
        $product = Product::find($uuid);
        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $price = $product->prices()->find($p_uuid);
        if (empty($price)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        $price->load('currency');

        return response()->json(['data' => $price], 200);
    }

    #[OA\Delete(
        path: 'admin.api.product.price.delete',
        description: 'Deletes a price from a product if it has no active service relationships.',
        summary: 'Delete product price',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
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
    public function deleteProductPrice(Request $request, $uuid, $p_uuid): JsonResponse
    {
        $product = Product::find($uuid);
        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $price = $product->prices()->find($p_uuid);
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

    #[OA\Get(
        path: 'admin.api.product.product_attributes.get',
        description: 'Returns a list of product attributes for a product with DataTables support.',
        summary: 'Get product attributes for a product',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
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
    public function getProductProductAttributes(Request $request, $uuid): JsonResponse
    {
        $query = Product::query()->find($uuid)->productAttributes();

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
                ->addColumn('name', function ($product_attribute) {
                    return $product_attribute->name;
                })
                ->addColumn('group_key', function ($product_attribute) {
                    return $product_attribute->productAttributeGroup->key;
                })
                ->addColumn('group_name', function ($product_attribute) {
                    return $product_attribute->productAttributeGroup->name;
                })
                ->addColumn('urls', function ($product_attribute) use ($uuid) {
                    $urls['edit'] = route('admin.web.product_attribute_group.tab',
                        ['uuid' => $uuid, 'tab' => 'attributes', 'edit' => $product_attribute->uuid]);
                    $urls['delete'] = route('admin.api.product.product_attribute.delete',
                        ['uuid' => $uuid, 'pa_uuid' => $product_attribute->uuid]);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.product.product_attributes.select.get',
        description: 'Returns a select list of product attributes for a product.',
        summary: 'Get product attributes select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product attributes select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductProductAttributesSelect(Request $request, $uuid): JsonResponse
    {
        $search = $request->input('q');

        $product = Product::find($uuid);

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Product not found')],
            ], 404);
        }

        $existingProductAttributeUuids = $product->productAttributes()
            ->pluck('uuid')
            ->toArray();

        $query = ProductAttribute::query();

        if (!empty($search)) {
            $query->where('uuid', 'like', '%'.$search.'%')
                ->orWhere('key', 'like', '%'.$search.'%');
        }

        $query->whereNotIn('uuid', $existingProductAttributeUuids);

        $product_attributes = $query->get();

        $results = [];
        foreach ($product_attributes as $product_attribute) {
            $results[] = [
                'id' => $product_attribute->uuid,
                'text' => $product_attribute->key.' ('.$product_attribute->productAttributeGroup->key.')',
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
        path: 'admin.api.product.product_attribute.post',
        description: 'Attaches an existing product attribute to a product.',
        summary: 'Add product attribute to product',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['product_attribute_uuid'],
                    properties: [
                        new OA\Property(property: 'product_attribute_uuid', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attribute attached successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Product already has this attribute'),
            new OA\Response(response: 404, description: 'Product or attribute not found'),
        ]
    )]
    public function postProductProductAttribute(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Product not found')],
            ], 404);
        }

        $attribute = ProductAttribute::find($request->input('product_attribute_uuid'));

        if (empty($attribute)) {
            return response()->json([
                'errors' => [__('error.Attribute not found')],
            ], 404);
        }

        $existingRelation = $product->productAttributes()->where('product_attribute_uuid', $attribute->uuid)->exists();

        if ($existingRelation) {
            return response()->json([
                'errors' => [__('error.Product already has this attribute')],
            ], 400);
        }

        $product->productAttributes()->attach($attribute);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.product.product_attribute.delete',
        description: 'Detaches a product attribute from a product.',
        summary: 'Remove product attribute from product',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'pa_uuid', description: 'UUID of the product attribute', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attribute detached successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product, attribute, or association not found'),
        ]
    )]
    public function deleteProductProductAttribute(Request $request, $uuid, $pa_uuid): JsonResponse
    {
        $product = Product::where('uuid', $uuid)->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product not found')],
            ], 404);
        }

        $product_attribute = ProductAttribute::where('uuid', $pa_uuid)->first();

        if (!$product_attribute) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Attribute not found')],
            ], 404);
        }

        $pivot = $product->productAttributes()->where('uuid', $product_attribute->uuid)->exists();

        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product Attribute is not associated with this product')],
            ], 404);
        }

        $product->productAttributes()->detach($product_attribute);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product.product_option_groups.get',
        description: 'Returns a list of product option groups for a product with DataTables support.',
        summary: 'Get product option groups for a product',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option groups returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductProductOptionGroups(Request $request, $uuid): JsonResponse
    {
        $query = Product::query()->find($uuid)->productOptionGroups();

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
                ->order(function ($query) use ($request) {
                    $direction = $request->get('dir', 'asc');
                    $query->orderBy('order', $direction);
                })
                ->addColumn('name', function ($product_option_group) {
                    return $product_option_group->name;
                })
                ->addColumn('options_count', function ($product_option_group) {
                    return $product_option_group->productOptions->count();
                })
                ->addColumn('urls', function ($product_option_group) use ($uuid) {
                    $urls['edit'] = route('admin.web.product_option_group.tab',
                        ['uuid' => $product_option_group->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.product.product_option_group.delete',
                        ['uuid' => $uuid, 'pog_uuid' => $product_option_group->uuid]);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.product.product_option_groups.select.get',
        description: 'Returns a select list of product option groups for a product.',
        summary: 'Get product option groups select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option groups select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductProductOptionsGroupsSelect(Request $request, $uuid): JsonResponse
    {
        $search = $request->input('q');

        $product = Product::find($uuid);

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Product not found')],
            ], 404);
        }

        $existingProductOptionGroupUuids = $product->productOptionGroups()
            ->pluck('uuid')
            ->toArray();

        $query = ProductOptionGroup::query();

        if (!empty($search)) {
            $query->where('uuid', 'like', '%'.$search.'%')
                ->orWhere('key', 'like', '%'.$search.'%');
        }

        $query->whereNotIn('uuid', $existingProductOptionGroupUuids);

        $product_option_groups = $query->get();

        $results = [];
        foreach ($product_option_groups as $product_option_group) {
            $results[] = [
                'id' => $product_option_group->uuid,
                'text' => $product_option_group->key,
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
        path: 'admin.api.product.product_option_group.post',
        description: 'Attaches an existing product option group to a product.',
        summary: 'Add product option group to product',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['product_option_group_uuid'],
                    properties: [
                        new OA\Property(property: 'product_option_group_uuid', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option group attached successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Product already has this product option group'),
            new OA\Response(response: 404, description: 'Product or product option group not found'),
        ]
    )]
    public function postProductProductOptionGroup(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Product not found')],
            ], 404);
        }

        $product_option_group = ProductOptionGroup::find($request->input('product_option_group_uuid'));

        if (empty($product_option_group)) {
            return response()->json([
                'errors' => [__('error.Product Option Group not found')],
            ], 404);
        }

        $existingRelation = $product->productOptionGroups()->where('uuid', $product_option_group->uuid)->exists();

        if ($existingRelation) {
            return response()->json([
                'errors' => [__('error.Product already has this Product Option Group')],
            ], 400);
        }

        $product->addProductOptionGroup($product_option_group);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.product.product_option_group.delete',
        description: 'Detaches a product option group from a product.',
        summary: 'Remove product option group from product',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'pog_uuid', description: 'UUID of the product option group', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option group detached successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product, option group, or association not found'),
        ]
    )]
    public function deleteProductProductOptionGroups(Request $request, $uuid, $pog_uuid): JsonResponse
    {
        $product = Product::where('uuid', $uuid)->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product not found')],
            ], 404);
        }

        $product_option_group = ProductOptionGroup::where('uuid', $pog_uuid)->first();

        if (empty($product_option_group)) {
            return response()->json([
                'errors' => [__('error.Product Option Group not found')],
            ], 404);
        }

        $pivot = $product->productOptionGroups()->where('uuid', $product_option_group->uuid)->exists();

        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product Option Group is not associated with this product')],
            ], 404);
        }

        $product->removeProductOptionGroup($product_option_group);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.product.product_option_group.update_order.post',
        description: 'Updates the display order of a product option group within a product.',
        summary: 'Update product option group order',
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
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
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
            new OA\Response(response: 404, description: 'Product or option group not found'),
            new OA\Response(response: 422, description: 'Validation error or cannot move'),
        ]
    )]
    public function postProductProductOptionGroupsUpdateOrder(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_uuid' => 'required|exists:product_x_product_option_group,product_option_group_uuid',
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

        $product = Product::where('uuid', $uuid)->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product not found')],
            ], 404);
        }

        $product_option_group = ProductOptionGroup::where('uuid', $request->input('product_uuid'))->first();

        if (!$product_option_group) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product Option Group not found')],
            ], 404);
        }

        $pivot = $product->productOptionGroups()->where('uuid', $product_option_group->uuid)->first();

        if (!$pivot) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product Option Group is not associated with this product')],
            ], 404);
        }

        $oldOrder = $pivot->pivot->order;
        $newOrder = $request->input('new_order');

        $minOrder = $product->productOptionGroups()->min('order');
        $maxOrder = $product->productOptionGroups()->max('order');

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
            DB::table('product_x_product_option_group')
                ->where('product_uuid', $uuid)
                ->where('order', '>', $oldOrder)
                ->where('order', '<=', $newOrder)
                ->decrement('order');
        } elseif ($newOrder < $oldOrder) {
            DB::table('product_x_product_option_group')
                ->where('product_uuid', $uuid)
                ->where('order', '<', $oldOrder)
                ->where('order', '>=', $newOrder)
                ->increment('order');
        }

        $product->productOptionGroups()->updateExistingPivot($product_option_group->uuid, ['order' => $newOrder]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }



    #[OA\Get(
        path: 'admin.api.products.select.get',
        description: 'Returns a select list of products.',
        summary: 'Get products select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');
        $page = (int) $request->input('page', 1);
        $perPage = 20;

        $query = Product::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $products = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $products->map(function ($product) {
            return [
                'id' => $product->uuid,
                'text' => $product->name ?: $product->key,
                'billing_mode' => $product->billing_mode ?? 'prepaid',
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

    #[OA\Get(
        path: 'admin.api.product.prices.select.get',
        description: 'Returns a select list of product prices.',
        summary: 'Get product prices select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product prices select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductPricesSelect(Request $request): JsonResponse
    {
        $client_uuid = $request->input('client_uuid');
        $client = Client::find($client_uuid);

        $product_uuid = $request->input('product_uuid');
        $product = Product::find($product_uuid);

        $prices = $product->prices()->where('currency_uuid', $client->currency_uuid)->get();

        $periodOrder = [
            'one-time',
            'hourly',
            'daily',
            'weekly',
            'bi-weekly',
            'monthly',
            'quarterly',
            'semi-annually',
            'annually',
            'biennially',
            'triennially',
        ];

        $prices = $prices->sort(function ($a, $b) use ($periodOrder) {
            return array_search($a->period, $periodOrder) <=> array_search($b->period, $periodOrder);
        });

        $results = [];
        foreach ($prices as $price) {
            $currency = $price->currency;
            $results[] = [
                'id' => $price->uuid,
                'text' => __('main.'.$price->period).' - '.$price->base.' '.$currency->code,
            ];
        }

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => ['more' => false],
            ],
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.product_option_groups.by_product.get',
        description: 'Returns option groups filtered by product.',
        summary: 'Get product option groups by product',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'product_uuid', description: 'UUID of the product', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'product_price_uuid', description: 'UUID of the product price', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product option groups returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductOptionGroupsByProduct(Request $request): JsonResponse
    {
        $product_uuid = $request->input('product_uuid');
        $product_price_uuid = $request->input('product_price_uuid');

        $product = Product::with('productOptionGroups.productOptions.prices')->find($product_uuid);
        $price = Price::find($product_price_uuid);

        if (empty($product) || empty($price)) {
            return response()->json([
                'data' => [],
            ]);
        }

        $groups = $product->getOptionGroupsWithPrices($price);

        return response()->json([
            'data' => $groups,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.product.module.select.get',
        description: 'Returns a select list of product modules.',
        summary: 'Get product modules select',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'q', description: 'Search query', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product modules select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProductModulesSelect(Request $request, $uuid): JsonResponse
    {
        $product = Product::findOrFail($uuid);
        $module_uuid = $product->module_uuid;

        $notificationModules = Module::all();
        $modules = [];

        foreach ($notificationModules as $module) {
            if ($module->type != 'Product') {
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
                'selected' => $module_uuid,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.product.module.get',
        description: 'Returns the module settings page data for a product.',
        summary: 'Get product module settings',
        security: [['apiKeyAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Module settings returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string', nullable: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function getProductModule(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => null,
            'data' => $product->getSettingsPage(),
        ]);
    }

    #[OA\Put(
        path: 'admin.api.product.module.put',
        description: 'Updates the module assignment or module data for a product.',
        summary: 'Update product module',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'module_uuid', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'uuid', description: 'UUID of the product', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Module updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function putProductModule(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($request->has('module_uuid')) {
            if ($product->module_uuid != $request->input('module_uuid')) {
                $product->module_uuid = $request->input('module_uuid');
                $product->save();

                return response()->json([
                    'status' => 'success',
                    'message' => __('message.Updated successfully'),
                    'data' => $product,
                ]);
            }
        }
        $save_module_data = $product->saveModuleData($request->all());

        if ($save_module_data['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $save_module_data['message'],
            ], $save_module_data['code']);
        }

        $product->save();
        $product->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $product,
        ]);
    }
}
