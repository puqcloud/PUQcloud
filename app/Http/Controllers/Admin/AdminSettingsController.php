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
use App\Models\Country;
use App\Models\Currency;
use App\Models\Region;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AdminSettingsController extends Controller
{
    public function generalSettings(): View
    {
        $title = __('main.General Settings');
        $settings = SettingService::getSettings();

        return view_admin('general_settings.general', compact('title', 'settings'));
    }

    public function getGeneralSettings(Request $request): JsonResponse
    {
        $general_settings = SettingService::getValuesByGroup($request->get('group'));

        return response()->json([
            'data' => $general_settings,
        ]);
    }

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
