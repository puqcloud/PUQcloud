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

namespace Template\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\PaymentGateway;
use App\Models\Region;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function dashboard(Request $request): View|Factory|Application
    {
        $title = __('main.Dashboard');

        return view_client('dashboard.dashboard', compact('title'));
    }

    public function getLanguagesSelect(Request $request): JsonResponse
    {
        $languages = [];
        foreach (config('locale.client.locales') as $key => $value) {
            $languages[] = [
                'id' => $key,
                'text' => $value['name'].' ('.$value['native'].')',
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredLanguages = array_filter($languages, function ($language) use ($searchTerm) {
            return empty($searchTerm) || stripos($language['text'], $searchTerm) !== false;
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

    public function getCountriesSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');
        $page = (int) $request->input('page', 1);
        $perPage = 20;

        $query = Country::query();

        if (!empty($search)) {
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

        if (!empty($search)) {
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

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    public function getVerification(Request $request): JsonResponse
    {
        $user = app('user');
        $verification = $user->verifications()->where('default', true)->where('verified', true)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.The default verification method is not verified')],
            ], 404);
        }

        $generate_code = $verification->confirmationSendVerificationCode();

        return response()->json([
            'status' => 'success',
            'data' => $generate_code,
        ]);
    }

    public function moduleClientWeb(Request $request, $type, $name, $method, $uuid = null): View|Factory|Application
    {
        $client = app('client');
        if (!$client) {
            abort(404);
        }

        $data = [];

        if ($type == 'Payment') {
            if ($uuid == null) {
                abort(404);
            }
            $home_company = $client->getHomeCompany();

            $payment_gateway = $home_company->paymentGateways()->where('uuid', $uuid)->first();
            if (!$payment_gateway) {
                abort(404);
            }

            if (!$payment_gateway->module) {
                abort(404);
            }

            $module_data = $payment_gateway->module->moduleExecute('controllerClientWeb_'.$method,
                ['request' => $request]);

            if ($module_data['status'] == 'error' or empty($module_data['data']['blade'])) {
                abort(404);
            }

            $bladePath = base_path('modules/'.$type.'/'.$name.'/views/'.$module_data['data']['blade'].'.blade.php');
            if (!file_exists($bladePath)) {
                abort(404);
            }

            $data['blade'] = $bladePath;
            $data['variables'] = $module_data['data']['variables'] ?? [];
        }

        return view_client('module.module', compact('data'));
    }

    public function moduleClientApi(Request $request, $type, $name, $method, $uuid = null): JsonResponse
    {
        $client = app('client');
        if (!$client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($type == 'Payment') {
            if ($uuid == null) {
                return response()->json([
                    'errors' => [__('error.Not found')],
                ], 404);
            }
            $home_company = $client->getHomeCompany();

            $payment_gateway = $home_company->paymentGateways()->where('uuid', $uuid)->first();
            if (!$payment_gateway) {
                return response()->json([
                    'errors' => [__('error.Not found')],
                ], 404);
            }

            if (!$payment_gateway->module) {
                return response()->json([
                    'errors' => [__('error.Not found')],
                ], 404);
            }

            $result = $payment_gateway->module->moduleExecute('controllerClientApi_'.$method, ['request' => $request]);

            if ($result instanceof JsonResponse) {
                return $result;
            }

            if ($result['status'] === 'success') {
                return response()->json([
                    'errors' => $result['errors'],
                ], $result['code'] ?? 500);
            }

        }

        return response()->json([], 500);
    }

    public function moduleClientStatic(Request $request, $type, $name, $method, $uuid = null): JsonResponse
    {

        if ($type == 'Payment') {
            if ($uuid == null) {
                return response()->json([
                    'errors' => [__('error.Not found')],
                ], 404);
            }

            $payment_gateway = PaymentGateway::query()->where('uuid', $uuid)->first();

            if (!$payment_gateway) {
                return response()->json([
                    'errors' => [__('error.Not found')],
                ], 404);
            }

            if (!$payment_gateway->module) {
                return response()->json([
                    'errors' => [__('error.Not found')],
                ], 404);
            }

            $result = $payment_gateway->module->moduleExecute('controllerClientStatic_'.$method,
                ['request' => $request]);
            if ($result['status'] === 'success') {
                return $result['data'];
            }

        }

        return response()->json($result ?? [], 500);
    }

    public function dashboardServicesApi(): JsonResponse
    {
        $client = app('client');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $client->services()->whereIn('status', ['active', 'suspended', 'pending'])->count(),
                'active' => $client->services()->where('status', 'active')->count(),
                'suspended' => $client->services()->where('status', 'suspended')->count(),
                'termination_request' => $client->services()->whereIn('status',
                    ['active', 'suspended', 'pending'])->where('termination_request', true)->count(),
            ],
        ]);
    }

    public function dashboardCalculateRecurringPaymentsBreakdownApi(): JsonResponse
    {
        $client = app('client');
        $currency = $client->currency;
        $data = [];
        foreach ($client->calculateRecurringPaymentsBreakdown() as $key => $value) {

            if ($key == 'currency') {
                $data[$key] = $value;
            } else {
                $data[$key] = number_format_custom($value, 4, $currency->format);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
