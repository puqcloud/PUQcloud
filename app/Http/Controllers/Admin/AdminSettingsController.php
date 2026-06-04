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
 *   GET  general_settings                                      -> generalSettings()         [admin.web.general_settings]
 *   GET  countries                                             -> countries()               [admin.web.countries]
 *   GET  currencies                                            -> currencies()              [admin.web.currencies]
 *
 * API Routes:
 *   GET    general_settings                                    -> getGeneralSettings()       [admin.api.general_settings.get]
 *   PUT    general_settings                                    -> putGeneralSettings()       [admin.api.general_settings.put]
 *   GET    countries                                           -> getCountries()             [admin.api.countries.get]
 *   GET    country/{uuid}/regions                              -> getCountryRegions()        [admin.api.country_regions.get]
 *   GET    currencies                                          -> getCurrencies()            [admin.api.currencies.get]
 *   POST   currency                                            -> postCurrency()             [admin.api.currency.post]
 *   GET    currency/{uuid}                                     -> getCurrency()              [admin.api.currency.get]
 *   PUT    currency/{uuid}                                     -> putCurrency()              [admin.api.currency.put]
 *   DELETE currency/{uuid}                                     -> deleteCurrency()           [admin.api.currency.delete]
 *   GET    currencies/select                                   -> getCurrenciesSelect()      [admin.api.currencies.select.get]
 *   GET    countries/select                                    -> getCountriesSelect()       [admin.api.countries.select.get]
 *   GET    regions/select                                      -> getRegionsSelect()         [admin.api.regions.select.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Region;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminSettingsController extends Controller
{
    public function generalSettings(): View
    {
        $title = __('main.General Settings');
        $settings = SettingService::getSettings();

        return view_admin('general_settings.general', compact('title', 'settings'));
    }

    #[OA\Get(
        path: 'admin.api.general_settings.get',
        description: 'Returns general settings values by group.',
        summary: 'Get general settings',
        security: [['apiKeyAuth' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'group',
                description: 'Settings group name',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Settings returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getGeneralSettings(Request $request): JsonResponse
    {
        $general_settings = SettingService::getValuesByGroup($request->get('group'));

        return response()->json([
            'data' => $general_settings,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.general_settings.put',
        description: 'Updates general settings values for a specific group.',
        summary: 'Update general settings',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'group', description: 'Settings group name', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Settings updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putGeneralSettings(Request $request): JsonResponse
    {
        $group = $request->get('group');
        $settings = config('settings.'.$group);

        $rules = [];
        foreach ($settings as $name => $setting) {
            if ($request->has($name)) {
                $rules[$name] = $setting['validation'] ?? 'nullable';
            }
        }

        $validator = Validator::make($request->only(array_keys($rules)), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        foreach ($settings as $name => $setting) {
            if ($request->has($name)) {
                SettingService::set($group.'.'.$name, $request->get($name));
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    public function countries(): View
    {
        $title = __('main.Countries');

        return view_admin('countries.countries', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.countries.get',
        description: 'Returns a DataTable list of countries.',
        summary: 'Get countries list',
        security: [['apiKeyAuth' => []]],
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Countries list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getCountries(Request $request): JsonResponse
    {
        $query = Country::query()->withCount('regions');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('code', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('calling_code', 'like', "%{$search}%")
                                ->orWhere('native_name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('regions_count', function ($country) {
                    return $country->regions_count;
                })
                ->addColumn('urls', function ($country) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('general-settings-management')) {
                        $urls['regions'] = route('admin.api.country_regions.get', $country->uuid);
                    }

                    return $urls;

                })
                ->make(true),
        ], 200);

    }

    #[OA\Get(
        path: 'admin.api.country_regions.get',
        description: 'Returns regions for a specific country.',
        summary: 'Get country regions by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the country',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Country regions returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'code', type: 'string'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'native_name', type: 'string'),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getCountryRegions(Request $request, $uuid): JsonResponse
    {
        $country = Country::find($uuid);

        if (empty($country)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'data' => $country->regions,
        ]);
    }

    public function currencies(): View
    {
        $title = __('main.Currencies');

        return view_admin('currencies.currencies', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.currencies.get',
        description: 'Returns a DataTable list of currencies.',
        summary: 'Get currencies list',
        security: [['apiKeyAuth' => []]],
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Currencies list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getCurrencies(Request $request): JsonResponse
    {
        $query = Currency::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('code', 'like', "%{$search}%")
                                ->orWhere('prefix', 'like', "%{$search}%")
                                ->orWhere('suffix', 'like', "%{$search}%")
                                ->orWhere('exchange_rate', 'like', "%{$search}%")
                                ->orWhere('format', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($country) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('currencies-management')) {
                        $urls['get'] = route('admin.api.currency.get', $country->uuid);
                        $urls['put'] = route('admin.api.currency.put', $country->uuid);
                        $urls['delete'] = route('admin.api.currency.delete', $country->uuid);
                    }

                    return $urls;

                })
                ->make(true),
        ], 200);

    }

    #[OA\Post(
        path: 'admin.api.currency.post',
        description: 'Creates a new currency with specified code, format, and exchange rate.',
        summary: 'Create a new currency',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['code', 'format', 'exchange_rate'],
                    properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'USD'),
                        new OA\Property(property: 'prefix', type: 'string', example: '$', nullable: true),
                        new OA\Property(property: 'suffix', type: 'string', nullable: true),
                        new OA\Property(property: 'exchange_rate', type: 'number', example: 1.00),
                        new OA\Property(property: 'format', type: 'string', example: '1,234.56'),
                        new OA\Property(property: 'default', type: 'string', enum: ['yes', 'no'], nullable: true),
                    ]
                )
            )
        ),
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Currency created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postCurrency(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:currencies,code',
            'format' => [
                'required',
                function ($attribute, $value, $fail) {
                    $validFormats = ['1234.56', '1,234.56', '1.234,56'];
                    if (! in_array($value, $validFormats, true)) {
                        $fail(__('error.The selected format is invalid'));
                    }
                },
            ],
            'exchange_rate' => 'required|numeric|regex:/^\d+(\.\d+)?$/',

        ], [
            'code.required' => __('error.The Code field is required'),
            'code.unique' => __('error.This Code is already taken'),
            'format.required' => __('error.The Format field is required'),
            'exchange_rate.required' => __('error.The Exchange Rate field is required'),
            'exchange_rate.numeric' => __('error.The Exchange Rate must be a number'),
            'exchange_rate.regex' => __('error.The Exchange Rate must be a valid number (e.g., 123 or 123.45)'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $currency = Currency::where('code', $request->input('code'))->first();
        if (! empty($currency)) {
            return response()->json(['errors' => [__('error.The currency already exists')]], 400);
        }

        $currency = new Currency;
        $currency->default = false;

        if ($request->has('default') && $request->input('default') == 'yes') {
            $currency->default = true;
            Currency::where('default', true)->update(['default' => false]);
        }

        $currencies_count = Currency::query()->count();
        if ($currencies_count == 0) {
            $currency->default = true;
        }

        if (! empty($request->input('code'))) {
            $currency->code = $request->input('code');
        }

        if (! empty($request->input('prefix'))) {
            $currency->prefix = $request->input('prefix');
        }

        if (! empty($request->input('suffix'))) {
            $currency->suffix = $request->input('suffix');
        }

        if (! empty($request->input('exchange_rate'))) {
            $currency->exchange_rate = $request->input('exchange_rate');
        }

        if (! empty($request->input('format'))) {
            $currency->format = $request->input('format');
        }

        $currency->save();
        $currency->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $currency,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.currency.get',
        description: 'Returns detailed information about a currency.',
        summary: 'Get currency by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the currency',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Currency data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'code', type: 'string', example: 'USD'),
                                new OA\Property(property: 'prefix', type: 'string', example: '$'),
                                new OA\Property(property: 'suffix', type: 'string', nullable: true),
                                new OA\Property(property: 'exchange_rate', type: 'number', example: 1.00),
                                new OA\Property(property: 'format', type: 'string', example: '1,234.56'),
                                new OA\Property(property: 'default', type: 'boolean'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getCurrency(Request $request, $uuid): JsonResponse
    {
        $currency = Currency::find($uuid);

        if (empty($currency)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'data' => $currency,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.currency.put',
        description: 'Updates an existing currency details.',
        summary: 'Update currency by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['code', 'format', 'exchange_rate'],
                    properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'USD'),
                        new OA\Property(property: 'prefix', type: 'string', nullable: true),
                        new OA\Property(property: 'suffix', type: 'string', nullable: true),
                        new OA\Property(property: 'exchange_rate', type: 'number'),
                        new OA\Property(property: 'format', type: 'string'),
                        new OA\Property(property: 'default', type: 'string', enum: ['yes', 'no'], nullable: true),
                    ]
                )
            )
        ),
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the currency to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Currency updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putCurrency(Request $request, $uuid): JsonResponse
    {

        $currency = Currency::query()->find($uuid);
        if (empty($currency)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:currencies,code,'.$uuid.',uuid',
            'format' => [
                'required',
                function ($attribute, $value, $fail) {
                    $validFormats = ['1234.56', '1,234.56', '1.234,56'];
                    if (! in_array($value, $validFormats, true)) {
                        $fail(__('error.The selected format is invalid'));
                    }
                },
            ],
            'exchange_rate' => 'required|numeric|regex:/^\d+(\.\d+)?$/',

        ], [
            'code.required' => __('error.The Code field is required'),
            'code.unique' => __('error.This Code is already taken'),
            'format.required' => __('error.The Format field is required'),
            'exchange_rate.required' => __('error.The Exchange Rate field is required'),
            'exchange_rate.numeric' => __('error.The Exchange Rate must be a number'),
            'exchange_rate.regex' => __('error.The Exchange Rate must be a valid number (e.g., 123 or 123.45)'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $currency->default = false;

        if ($request->has('default') && $request->input('default') == 'yes') {
            $currency->default = true;
            Currency::where('default', true)->update(['default' => false]);
        }

        if ($currency->default && $request->input('default') != 'yes') {
            $defaultCurrencyCount = Currency::where('default', true)->count();
            if ($defaultCurrencyCount == 1) {
                return response()->json([
                    'errors' => [__('error.You cannot unset the default flag without setting another currency as default.')],
                ], 400);
            }
            $currency->default = false;
        }

        $currencies_count = Currency::query()->count();
        if ($currencies_count == 0) {
            $currency->default = true;
        }

        $currencies_count = Currency::query()->where('default', true)->count();
        if ($currencies_count == 0) {
            $currency->default = true;
        }

        if (! empty($request->input('code'))) {
            $currency->code = $request->input('code');
        }

        if (! empty($request->input('prefix'))) {
            $currency->prefix = $request->input('prefix');
        }

        if (! empty($request->input('suffix'))) {
            $currency->suffix = $request->input('suffix');
        }

        if (! empty($request->input('exchange_rate'))) {
            $currency->exchange_rate = $request->input('exchange_rate');
        }

        if (! empty($request->input('format'))) {
            $currency->format = $request->input('format');
        }

        $currency->save();
        $currency->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $currency,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.currency.delete',
        description: 'Deletes a currency by UUID. Cannot delete the default or last currency.',
        summary: 'Delete currency by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the currency to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Currency deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteCurrency(Request $request, $uuid): JsonResponse
    {
        $currency = Currency::find($uuid);

        if (empty($currency)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($currency->default) {
            return response()->json([
                'errors' => [__('error.Cannot delete default currency')],
            ], 400);
        }

        $currencyCount = Currency::count();
        if ($currencyCount <= 1) {
            return response()->json([
                'errors' => [__('error.Cannot delete the last currency')],
            ], 400);
        }

        try {
            $deleted = $currency->delete();
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

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    #[OA\Get(
        path: 'admin.api.currencies.select.get',
        description: 'Returns a list of currencies for select2 dropdown.',
        summary: 'Get currencies select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Currencies select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getCurrenciesSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (! empty($search)) {
            $currencies = Currency::orderBy('default', 'desc')->where('code', 'like', '%'.$search.'%')->get();
        } else {
            $currencies = Currency::orderBy('default', 'desc')->get();
        }

        $results = [];
        foreach ($currencies->toArray() as $currency) {
            $results[] = [
                'id' => $currency['uuid'],
                'text' => $currency['code'].($currency['default'] ? '*' : ''),
            ];
        }

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    #[OA\Get(
        path: 'admin.api.countries.select.get',
        description: 'Returns a paginated list of countries for select2 dropdown.',
        summary: 'Get countries select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Countries select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getCountriesSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');
        $page = (int) $request->input('page', 1);
        $perPage = 20;

        $query = Country::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('native_name', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $countries = $query->orderBy('name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $countries->map(function ($country) {
            return [
                'id' => $country->uuid,
                'text' => "{$country->code} - {$country->name} ({$country->native_name})",
            ];
        });

        if ($request->has('empty')) {
            $results->prepend([
                'id' => '0',
                'text' => __('main.No choice'),
            ]);
        }

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
        path: 'admin.api.regions.select.get',
        description: 'Returns a list of regions for select2 dropdown, filtered by country.',
        summary: 'Get regions select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'selected_country_uuid',
                description: 'UUID of the country to filter regions',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Regions select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getRegionsSelect(Request $request): JsonResponse
    {
        $key = $request->input('selected_country_uuid');

        $search = $request->input('q');

        if (! empty($search)) {
            $regions = Region::where(function ($query) use ($search) {
                $query->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('native_name', 'like', '%'.$search.'%');
            })->where('country_uuid', $key)
                ->orderBy('name')
                ->get();
        } else {
            $regions = Region::query()
                ->where('country_uuid', '=', $key)
                ->orderBy('name')
                ->get();
        }

        $results = [];
        foreach ($regions->toArray() as $region) {
            $results[] = [
                'id' => $region['uuid'],
                'text' => $region['code'].' - '.$region['name'].' ('.$region['native_name'].')',
            ];
        }

        if ($request->has('empty')) {
            array_unshift($results, [
                'id' => '0',
                'text' => __('main.No choice'),
            ]);
        }

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }
}
