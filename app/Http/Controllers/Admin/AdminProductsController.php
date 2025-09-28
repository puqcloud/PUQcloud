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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Module;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeGroup;
use App\Models\ProductGroup;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AdminProductsController extends Controller
{
    public function products(): View
    {
        $title = __('main.Products');

        return view_admin('products.products', compact('title'));
    }

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
                    $urls['web_edit'] = route('admin.web.product.tab', ['uuid' => $product->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.product.delete', $product->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

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
            'general', 'images', 'attributes', 'pricing', 'options', 'module',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route('admin.web.product.tab', ['uuid' => $uuid, 'tab' => 'general']);
        }

        $title = __('main.Product');
        $locales = config('locale.client.locales');

        return view_admin('products.product_'.$tab, compact('title', 'uuid', 'tab', 'locales'));
    }

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

    public function putProduct(Request $request, $uuid): JsonResponse
    {
        $product = Product::find($uuid);

        if (empty($product)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:products,key,'.$product->uuid.',uuid',
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
        if ($request->has('hourly_billing')) {
            $product->hourly_billing = false;
            if ($request->input('hourly_billing') == 'yes') {
                $product->hourly_billing = true;
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

        $product->save();
        $product->refresh();

        if (!empty($product->convert_price)) {
            $product->convertPrice();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $product,
        ]);
    }

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

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

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
                    return $price->currency->code;
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
                    $urls['web_edit'] = route('admin.web.product_attribute_group.tab',
                        ['uuid' => $uuid, 'tab' => 'attributes', 'edit' => $product_attribute->uuid]);
                    $urls['delete'] = route('admin.api.product.product_attribute.delete',
                        ['uuid' => $uuid, 'pa_uuid' => $product_attribute->uuid]);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

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
                    $urls['web_edit'] = route('admin.web.product_option_group.tab',
                        ['uuid' => $product_option_group->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.product.product_option_group.delete',
                        ['uuid' => $uuid, 'pog_uuid' => $product_option_group->uuid]);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

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

    public function productGroups(): View
    {
        $title = __('main.Product Groups');

        return view_admin('product_groups.product_groups', compact('title'));
    }

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
                    $urls['web_edit'] = route('admin.web.product_group.tab',
                        ['uuid' => $product_group->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.product_group.delete', $product_group->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

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
            'general', 'images', 'products',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route('admin.web.product_group.tab', ['uuid' => $uuid, 'tab' => 'general']);
        }

        $title = __('main.Product Group');
        $locales = config('locale.client.locales');

        return view_admin('product_groups.product_group_'.$tab, compact('title', 'uuid', 'tab', 'locales'));
    }

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

    public function putProductGroup(Request $request, $uuid): JsonResponse
    {
        $product_group = ProductGroup::find($uuid);

        if (empty($product_group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:product_groups,key,'.$product_group->uuid.',uuid',
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
                    $urls['web_edit'] = route('admin.web.product.tab', ['uuid' => $product->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.product_group_product.delete',
                        ['uuid' => $uuid, 'p_uuid' => $product->uuid]);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

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

    public function getListTemplatesSelect(Request $request): JsonResponse
    {

        $servicesPath = base_path('templates/client/'.env('TEMPLATE_CLIENT', 'puqcloud').'/views/service_views/list');

        $folders = [];
        if (is_dir($servicesPath)) {
            $allItems = scandir($servicesPath);
            foreach ($allItems as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $fullPath = $servicesPath.DIRECTORY_SEPARATOR.$item;
                if (is_dir($fullPath)) {
                    $folders[] = [
                        'id' => $item,
                        'text' => $item,
                    ];
                }
            }
        }

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

    public function getOrderTemplatesSelect(Request $request): JsonResponse
    {

        $servicesPath = base_path('templates/client/'.env('TEMPLATE_CLIENT', 'puqcloud').'/views/service_views/order');

        $folders = [];
        if (is_dir($servicesPath)) {
            $allItems = scandir($servicesPath);
            foreach ($allItems as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $fullPath = $servicesPath.DIRECTORY_SEPARATOR.$item;
                if (is_dir($fullPath)) {
                    $folders[] = [
                        'id' => $item,
                        'text' => $item,
                    ];
                }
            }
        }

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

    public function getManageTemplatesSelect(Request $request): JsonResponse
    {

        $servicesPath = base_path('templates/client/'.env('TEMPLATE_CLIENT', 'puqcloud').'/views/service_views/manage');

        $folders = [];
        if (is_dir($servicesPath)) {
            $allItems = scandir($servicesPath);
            foreach ($allItems as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $fullPath = $servicesPath.DIRECTORY_SEPARATOR.$item;
                if (is_dir($fullPath)) {
                    $folders[] = [
                        'id' => $item,
                        'text' => $item,
                    ];
                }
            }
        }

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
                    $urls['web_edit'] = route('admin.web.product_attribute_group.tab',
                        ['uuid' => $product_attribute_group->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.product_attribute_group.delete', $product_attribute_group->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

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
                    $urls['web_edit'] = route('admin.web.product_attribute_group.tab', [
                        'uuid' => $product_attribute->product_attribute_group_uuid, 'tab' => 'attributes',
                        'edit' => $product_attribute->uuid,
                    ]);
                    $urls['delete'] = route('admin.api.product_attribute.delete', $product_attribute->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

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
                        'web_edit' => route('admin.web.product_option_group.tab',
                            ['uuid' => $query->uuid, 'tab' => 'general']),
                        'delete' => route('admin.api.product_option_group.delete', $query->uuid),
                    ];
                })
                ->make(true),
        ], 200);
    }

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
                    $urls['web_edit'] = route('admin.web.product_option_group.tab', [
                        'uuid' => $product_option->product_option_group_uuid, 'tab' => 'options',
                        'edit' => $product_option->uuid,
                    ]);
                    $urls['delete'] = route('admin.api.product_option.delete', $product_option->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

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
