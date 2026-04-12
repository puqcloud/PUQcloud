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
 *   GET  tax_rules                                          -> taxRules()            [admin.web.tax_rules]
 *
 * API Routes:
 *   GET  tax_rules                                          -> getTaxRules()                         [admin.api.tax_rules.get]
 *   GET  tax_rule/{uuid}                                    -> getTaxRule()                          [admin.api.tax_rule.get]
 *   POST tax_rule                                           -> postTaxRule()                         [admin.api.tax_rule.post]
 *   PUT  tax_rule/{uuid}                                    -> putTaxRule()                          [admin.api.tax_rule.put]
 *   DELETE tax_rule/{uuid}                                  -> deleteTaxRule()                       [admin.api.tax_rule.delete]
 *   POST tax_rules/update_order                             -> postTaxRulesUpdateOrder()             [admin.api.tax_rules.update_order.post]
 *   POST tax_rule/eu                                        -> postTaxEuRules()                     [admin.api.tax_rule.eu.post]
 *   POST tax_rule/canadian                                  -> postTaxCanadianRules()                [admin.api.tax_rule.canadian.post]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\HomeCompany;
use App\Models\Region;
use App\Models\TaxRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminTaxRulesController extends Controller
{
    public function taxRules(): View
    {
        $title = __('main.Tax Rules');

        return view_admin('finance.tax_rules.tax_rules', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.tax_rules.get',
        description: 'Returns a DataTable list of tax rules.',
        summary: 'Get tax rules list',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tax rules list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getTaxRules(Request $request): JsonResponse
    {
        $query = TaxRule::with('country', 'region', 'homeCompany')
            ->select('tax_rules.*')
            ->leftJoin('countries', 'tax_rules.country_uuid', '=', 'countries.uuid')
            ->leftJoin('regions', 'tax_rules.region_uuid', '=', 'regions.uuid')
            ->leftJoin('home_companies', 'tax_rules.home_company_uuid', '=', 'home_companies.uuid')
            ->orderBy('tax_rules.order', 'asc');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('tax_rules.code', 'like', "%{$search}%")
                                ->orWhere('countries.name', 'like', "%{$search}%")
                                ->orWhere('regions.name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($tax_rule) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('finance-view')) {
                        $urls['get'] = route('admin.api.tax_rule.get', $tax_rule->uuid);
                    }
                    if ($admin_online->hasPermission('finance-edit')) {
                        $urls['put'] = route('admin.api.tax_rule.put', $tax_rule->uuid);
                    }
                    if ($admin_online->hasPermission('finance-delete')) {
                        $urls['delete'] = route('admin.api.tax_rule.delete', $tax_rule->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.tax_rule.get',
        description: 'Returns detailed information about a tax rule, including country, region, and home company data.',
        summary: 'Get tax rule by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the tax rule',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tax rule data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Tax rule not found'),
        ]
    )]
    public function getTaxRule(Request $request, $uuid): JsonResponse
    {
        $tax_rule = TaxRule::find($uuid);

        if (empty($tax_rule)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($tax_rule->country) {
            $tax_rule->country_data = ['id' => $tax_rule->country->uuid ?? null, 'text' => $tax_rule->country->name ?? null];
        } else {
            $tax_rule->country_data = ['id' => '0', 'text' => __('main.No choice')];
        }

        if ($tax_rule->region) {
            $tax_rule->region_data = ['id' => $tax_rule->region->uuid ?? null, 'text' => $tax_rule->region->name ?? null];
        } else {
            $tax_rule->region_data = ['id' => '0', 'text' => __('main.No choice')];
        }

        $tax_rule->home_company_data = ['id' => $tax_rule->homeCompany->uuid, 'text' => $tax_rule->homeCompany->company_name];

        return response()->json([
            'data' => $tax_rule,
        ]);
    }

    #[OA\Post(
        path: 'admin.api.tax_rule.post',
        description: 'Creates a new tax rule with home company, country, region, tax rates, and client type settings.',
        summary: 'Create a new tax rule',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['home_company_uuid'],
                    properties: [
                        new OA\Property(property: 'home_company_uuid', description: 'UUID of the home company', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'country_uuid', description: 'UUID of the country (0 for none)', type: 'string'),
                        new OA\Property(property: 'region_uuid', description: 'UUID of the region (0 for none)', type: 'string'),
                        new OA\Property(property: 'tax_1', description: 'Tax rate 1', type: 'number', example: 23),
                        new OA\Property(property: 'tax_1_name', description: 'Tax 1 name', type: 'string', example: 'VAT'),
                        new OA\Property(property: 'tax_2', description: 'Tax rate 2', type: 'number'),
                        new OA\Property(property: 'tax_2_name', description: 'Tax 2 name', type: 'string'),
                        new OA\Property(property: 'tax_3', description: 'Tax rate 3', type: 'number'),
                        new OA\Property(property: 'tax_3_name', description: 'Tax 3 name', type: 'string'),
                        new OA\Property(property: 'private_client', description: 'Applies to private clients (yes/no)', type: 'string', example: 'yes'),
                        new OA\Property(property: 'company_without_tax_id', description: 'Applies to companies without tax ID (yes/no)', type: 'string', example: 'yes'),
                        new OA\Property(property: 'company_with_tax_id', description: 'Applies to companies with tax ID (yes/no)', type: 'string', example: 'yes'),
                        new OA\Property(property: 'individual_tax_rate', description: 'Individual tax rate (yes/no)', type: 'string', example: 'yes'),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tax rule created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items()),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTaxRule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'home_company_uuid' => 'required|exists:home_companies,uuid',
        ], [
            'home_company_uuid.required' => __('error.The Home Company field is required'),
            'home_company_uuid.exists' => __('error.Invalid Home Company UUID'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $tax_rule = new TaxRule;

        $tax_rule->home_company_uuid = $request->get('home_company_uuid');

        if ($request->has('country_uuid')) {
            $tax_rule->country_uuid = null;
            if ($request->get('country_uuid') !== '0') {
                $tax_rule->country_uuid = $request->get('country_uuid');
            }
        }

        if ($request->has('region_uuid')) {
            $tax_rule->region_uuid = null;
            if ($request->get('region_uuid') !== '0') {
                $tax_rule->region_uuid = $request->get('region_uuid');
            }
        }

        if ($request->has('tax_3_name')) {
            $tax_rule->tax_3_name = $request->get('tax_3_name');
        }

        if ($request->has('private_client')) {
            $tax_rule->private_client = false;
            if ($request->get('private_client') === 'yes') {
                $tax_rule->private_client = true;
            }
        }

        if ($request->has('company_without_tax_id')) {
            $tax_rule->company_without_tax_id = false;
            if ($request->get('company_without_tax_id') === 'yes') {
                $tax_rule->company_without_tax_id = true;
            }
        }

        if ($request->has('company_with_tax_id')) {
            $tax_rule->company_with_tax_id = false;
            if ($request->get('company_with_tax_id') === 'yes') {
                $tax_rule->company_with_tax_id = true;
            }
        }

        if ($request->has('individual_tax_rate')) {
            $tax_rule->individual_tax_rate = false;
            if ($request->get('individual_tax_rate') === 'yes') {
                $tax_rule->individual_tax_rate = true;
            }
        }

        if ($request->has('tax_1')) {
            $tax_rule->tax_1 = $request->get('tax_1');
        }
        if ($request->has('tax_1_name')) {
            $tax_rule->tax_1_name = $request->get('tax_1_name');
        }

        if ($request->has('tax_2')) {
            $tax_rule->tax_2 = $request->get('tax_2');
        }
        if ($request->has('tax_2_name')) {
            $tax_rule->tax_2_name = $request->get('tax_2_name');
        }

        if ($request->has('tax_3')) {
            $tax_rule->tax_3 = $request->get('tax_3');
        }
        if ($request->has('tax_3_name')) {
            $tax_rule->tax_3_name = $request->get('tax_3_name');
        }

        $tax_rule->save();
        TaxRule::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    #[OA\Put(
        path: 'admin.api.tax_rule.put',
        description: 'Updates a tax rule by UUID, including home company, country, region, tax rates, and client type settings.',
        summary: 'Update tax rule by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['home_company_uuid'],
                    properties: [
                        new OA\Property(property: 'home_company_uuid', description: 'UUID of the home company', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'country_uuid', description: 'UUID of the country (0 for none)', type: 'string'),
                        new OA\Property(property: 'region_uuid', description: 'UUID of the region (0 for none)', type: 'string'),
                        new OA\Property(property: 'tax_1', description: 'Tax rate 1', type: 'number', example: 23),
                        new OA\Property(property: 'tax_1_name', description: 'Tax 1 name', type: 'string', example: 'VAT'),
                        new OA\Property(property: 'tax_2', description: 'Tax rate 2', type: 'number'),
                        new OA\Property(property: 'tax_2_name', description: 'Tax 2 name', type: 'string'),
                        new OA\Property(property: 'tax_3', description: 'Tax rate 3', type: 'number'),
                        new OA\Property(property: 'tax_3_name', description: 'Tax 3 name', type: 'string'),
                        new OA\Property(property: 'private_client', description: 'Applies to private clients (yes/no)', type: 'string', example: 'yes'),
                        new OA\Property(property: 'company_without_tax_id', description: 'Applies to companies without tax ID (yes/no)', type: 'string', example: 'yes'),
                        new OA\Property(property: 'company_with_tax_id', description: 'Applies to companies with tax ID (yes/no)', type: 'string', example: 'yes'),
                        new OA\Property(property: 'individual_tax_rate', description: 'Individual tax rate (yes/no)', type: 'string', example: 'yes'),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the tax rule',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tax rule updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items()),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Tax rule not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putTaxRule(Request $request, $uuid): JsonResponse
    {
        $tax_rule = TaxRule::find($uuid);

        if (empty($tax_rule)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'home_company_uuid' => 'required|exists:home_companies,uuid',
        ], [
            'home_company_uuid.required' => __('error.The Home Company field is required'),
            'home_company_uuid.exists' => __('error.Invalid Home Company UUID'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $tax_rule->home_company_uuid = $request->get('home_company_uuid');

        if ($request->has('country_uuid')) {
            $tax_rule->country_uuid = null;
            if ($request->get('country_uuid') !== '0') {
                $tax_rule->country_uuid = $request->get('country_uuid');
            }
        }

        if ($request->has('region_uuid')) {
            $tax_rule->region_uuid = null;
            if ($request->get('region_uuid') !== '0') {
                $tax_rule->region_uuid = $request->get('region_uuid');
            }
        }

        if ($request->has('tax_3_name')) {
            $tax_rule->tax_3_name = $request->get('tax_3_name');
        }

        if ($request->has('private_client')) {
            $tax_rule->private_client = false;
            if ($request->get('private_client') === 'yes') {
                $tax_rule->private_client = true;
            }
        }

        if ($request->has('company_without_tax_id')) {
            $tax_rule->company_without_tax_id = false;
            if ($request->get('company_without_tax_id') === 'yes') {
                $tax_rule->company_without_tax_id = true;
            }
        }

        if ($request->has('company_with_tax_id')) {
            $tax_rule->company_with_tax_id = false;
            if ($request->get('company_with_tax_id') === 'yes') {
                $tax_rule->company_with_tax_id = true;
            }
        }

        if ($request->has('individual_tax_rate')) {
            $tax_rule->individual_tax_rate = false;
            if ($request->get('individual_tax_rate') === 'yes') {
                $tax_rule->individual_tax_rate = true;
            }
        }

        if ($request->has('tax_1')) {
            $tax_rule->tax_1 = $request->get('tax_1');
        }
        if ($request->has('tax_1_name')) {
            $tax_rule->tax_1_name = $request->get('tax_1_name');
        }

        if ($request->has('tax_2')) {
            $tax_rule->tax_2 = $request->get('tax_2');
        }
        if ($request->has('tax_2_name')) {
            $tax_rule->tax_2_name = $request->get('tax_2_name');
        }

        if ($request->has('tax_3')) {
            $tax_rule->tax_3 = $request->get('tax_3');
        }
        if ($request->has('tax_3_name')) {
            $tax_rule->tax_3_name = $request->get('tax_3_name');
        }

        $tax_rule->save();
        TaxRule::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);

    }

    #[OA\Delete(
        path: 'admin.api.tax_rule.delete',
        description: 'Deletes a tax rule by UUID and reorders remaining tax rules.',
        summary: 'Delete tax rule by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the tax rule to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tax rule deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Tax rule not found'),
        ]
    )]
    public function deleteTaxRule(Request $request, $uuid): JsonResponse
    {
        $tax_rule = TaxRule::find($uuid);

        if (empty($tax_rule)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $tax_rule->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('error.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('error.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        TaxRule::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.tax_rules.update_order.post',
        description: 'Updates the display order of a tax rule by moving it to a new position.',
        summary: 'Update tax rule order',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['uuid', 'new_order'],
                    properties: [
                        new OA\Property(property: 'uuid', description: 'UUID of the tax rule to reorder', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'new_order', description: 'New order position', type: 'integer', example: 2),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tax rule order updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Tax rule not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTaxRulesUpdateOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:tax_rules,uuid',
            'new_order' => 'required|integer',
        ], [
            'uuid.required' => __('error.The uuid field is required'),
            'uuid.exists' => __('error.The uuid does not exist in the tax_rules table'),
            'new_order.required' => __('error.The new_order field is required'),
            'new_order.integer' => __('error.The new_order must be an integer'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $taxRule = TaxRule::where('uuid', $request->input('uuid'))->first();

        if (! $taxRule) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product group not found')],
            ], 404);
        }

        $taxRules = TaxRule::orderBy('order')->get();

        $minOrder = $taxRules->first()->order;
        $maxOrder = $taxRules->last()->order;

        if ($taxRule->order == $minOrder && $request->input('new_order') < $minOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The first item cannot be moved up')],
            ], 422);
        }

        if ($taxRule->order == $maxOrder && $request->input('new_order') > $maxOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The last item cannot be moved down')],
            ], 422);
        }

        $currentOrder = $taxRule->order;
        $newOrder = $request->input('new_order');

        if ($newOrder > $currentOrder) {
            TaxRule::where('order', '>', $currentOrder)
                ->where('order', '<=', $newOrder)
                ->decrement('order');
        } else {
            TaxRule::where('order', '<', $currentOrder)
                ->where('order', '>=', $newOrder)
                ->increment('order');
        }

        $taxRule->order = $newOrder;
        $taxRule->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Post(
        path: 'admin.api.tax_rule.eu.post',
        description: 'Creates EU tax rules for all EU countries based on predefined tax configuration for a given home company.',
        summary: 'Create EU tax rules',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['home_company_uuid'],
                    properties: [
                        new OA\Property(property: 'home_company_uuid', description: 'UUID of the home company', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'EU tax rules created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items()),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTaxEuRules(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'home_company_uuid' => 'required|exists:home_companies,uuid',
        ], [
            'home_company_uuid.required' => __('error.The Home Company field is required'),
            'home_company_uuid.exists' => __('error.Invalid Home Company UUID'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $taxes = config('taxes.EU');

        foreach ($taxes as $country_code => $tax_data) {
            $tax_rule = new TaxRule;
            $tax_rule->home_company_uuid = $request->get('home_company_uuid');
            $country = Country::where('code', $country_code)->first();
            $tax_rule->country_uuid = $country->uuid;
            $tax_rule->region_uuid = null;
            $tax_rule->tax_1 = $tax_data['rate'];
            $tax_rule->tax_1_name = $tax_data['name'];
            $tax_rule->individual_tax_rate = true;
            $tax_rule->private_client = true;
            $tax_rule->company_without_tax_id = true;
            $tax_rule->company_with_tax_id = true;
            $tax_rule->save();
        }
        TaxRule::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    #[OA\Post(
        path: 'admin.api.tax_rule.canadian.post',
        description: 'Creates Canadian tax rules for all provinces based on predefined tax configuration for a given home company.',
        summary: 'Create Canadian tax rules',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['home_company_uuid'],
                    properties: [
                        new OA\Property(property: 'home_company_uuid', description: 'UUID of the home company', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Canadian tax rules created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items()),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postTaxCanadianRules(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'home_company_uuid' => 'required|exists:home_companies,uuid',
        ], [
            'home_company_uuid.required' => __('error.The Home Company field is required'),
            'home_company_uuid.exists' => __('error.Invalid Home Company UUID'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $taxes = config('taxes.Canada');
        $country = Country::where('code', 'CA')->first();
        foreach ($taxes as $province => $tax_data) {
            $tax_rule = new TaxRule;
            $tax_rule->home_company_uuid = $request->get('home_company_uuid');
            $tax_rule->country_uuid = $country->uuid;
            $province_record = Region::where('name', $province)->first();
            $tax_rule->region_uuid = $province_record->uuid;
            foreach ($tax_data as $index => $tax) {
                $tax_field = 'tax_'.($index + 1);
                $tax_name_field = 'tax_'.($index + 1).'_name';
                $tax_rule->$tax_field = $tax['rate'];
                $tax_rule->$tax_name_field = $tax['name'];
            }
            $tax_rule->individual_tax_rate = true;
            $tax_rule->private_client = true;
            $tax_rule->company_without_tax_id = true;
            $tax_rule->company_with_tax_id = true;
            $tax_rule->save();
        }
        TaxRule::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }
}
