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
use App\Models\ClientAddress;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Region;
use App\Models\Transaction;
use App\Models\User;
use App\Services\UserPermissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AdminClientsController extends Controller
{
    public function clients(): View
    {
        $title = __('main.Clients');

        return view_admin('clients.clients', compact('title'));
    }

    public function getClients(Request $request): JsonResponse
    {
        $query = Client::query()
            ->with('currency')
            ->leftJoin('client_balances', 'clients.uuid', '=', 'client_balances.client_uuid')
            ->join('client_x_user', function ($join) {
                $join->on('clients.uuid', '=', 'client_x_user.client_uuid')
                    ->where('client_x_user.owner', true);
            })
            ->join('users', 'client_x_user.user_uuid', '=', 'users.uuid')
            ->select(
                'clients.*',
                'client_balances.balance as balance',
                'users.uuid as owner_uuid',
                'users.email as owner_email',
                'users.firstname as owner_firstname',
                'users.lastname as owner_lastname',
            );

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('clients.company_name', 'like', "%{$search}%")
                                ->orWhere('clients.uuid', 'like', "%{$search}%")
                                ->orWhere('clients.firstname', 'like', "%{$search}%")
                                ->orWhere('clients.lastname', 'like', "%{$search}%")
                                ->orWhere('clients.tax_id', 'like', "%{$search}%")
                                ->orWhere('users.email', 'like', "%{$search}%")
                                ->orWhere('users.uuid', 'like', "%{$search}%")
                                ->orWhere('client_balances.balance', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('billing_address', function ($client) {
                    $billingAddress = $client->billingAddress();

                    return [
                        'country' => $billingAddress->country,
                        'city' => $billingAddress->city,
                    ];
                })
                ->addColumn('balance', function ($client) {
                    return number_format_custom($client->balance ?? 0, 2, $client->currency->format);
                })
                ->addColumn('credit_limit', function ($client) {
                    return number_format_custom($client->credit_limit ?? 0, 2, $client->currency->format);
                })
                ->addColumn('urls', function ($client) {
                    $admin_online = app('admin');
                    $urls = ['gravatar' => get_gravatar($client->owner_email, 100)];
                    if ($admin_online->hasPermission('clients-view')) {
                        $urls['web_edit'] = route('admin.web.client.tab', [$client->uuid, 'summary']);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function clientCreate(): View
    {
        $title = __('main.Create a New Client');

        return view_admin('clients.client_create', compact('title'));
    }

    public function postClient(Request $request): JsonResponse
    {

        $locales = array_keys(config('locale.admin.locales'));
        $currencies = Currency::query()->get()->pluck('uuid')->toArray();

        $countries = Country::query()->get()->pluck('uuid')->toArray();
        $regions = Region::query()->where('country_uuid', $request->input('country_uuid'))->get()->pluck('uuid')->toArray();

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'language' => 'required|in:'.implode(',', $locales),
            'currency_uuid' => 'required|string|in:'.implode(',', $currencies),
            'status' => 'required|string|in:'.implode(',', ['new', 'active', 'inactive', 'closed', 'fraud']),

            'address_1' => 'required',
            'address_2' => 'nullable',

            'city' => 'required',
            'postcode' => 'required',

            'country_uuid' => 'required|string|in:'.implode(',', $countries),
            'region_uuid' => 'required|string|in:'.implode(',', $regions),

            'company_name' => 'nullable|unique:clients,company_name',
            'tax_id' => 'nullable|unique:clients,tax_id',

        ], [
            'email.required' => __('error.The email field is required'),
            'email.email' => __('error.The email must be a valid email address'),
            'email.unique' => __('error.This email is already taken'),
            'password.required' => __('error.The password field is required'),
            'password.min' => __('error.The password must be at least 6 characters'),
            'firstname.required' => __('error.The firstname field is required'),
            'firstname.string' => __('error.The firstname must be a valid string'),
            'lastname.required' => __('error.The lastname field is required'),
            'lastname.string' => __('error.The lastname must be a valid string'),
            'language.required' => __('error.The language field is required'),
            'language.in' => __('error.The selected language is invalid'),
            'currency_uuid.string' => __('error.The currency must be a valid string'),
            'currency_uuid.required' => __('error.The currency field is required'),
            'currency_uuid.in' => __('error.The selected currency is invalid'),
            'status.string' => __('error.The status must be a valid string'),
            'status.required' => __('error.The status field is required'),
            'status.in' => __('error.The selected status is invalid'),

            'address_1.required' => __('error.The address 1 field is required'),
            'city.required' => __('error.The city field is required'),
            'postcode.required' => __('error.The postcode field is required'),

            'country_uuid.string' => __('error.The country must be a valid string'),
            'country_uuid.required' => __('error.The country field is required'),
            'country_uuid.in' => __('error.The selected country is invalid'),
            'region_uuid.string' => __('error.The region must be a valid string'),
            'region_uuid.required' => __('error.The region field is required'),
            'region_uuid.in' => __('error.The selected region is invalid'),

            'company_name.unique' => __('error.This company name is already taken'),
            'tax_id.unique' => __('error.This tax id is already taken'),

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $phone_number = '';
        if (! empty($request->input('country_code')) and ! empty($request->input('phone_number'))) {
            $phone_number = $request->input('country_code').$request->input('phone_number');
        }

        if ($phone_number != '') {
            if (User::query()->where('phone_number', $phone_number)->exists()) {
                return response()->json([
                    'message' => ['phone_number' => [__('error.This phone number is already taken')]],
                ], 422);
            }
        } else {
            $phone_number = null;
        }

        DB::beginTransaction();
        try {

            $user = new User;
            $user->fill([
                'email' => $request->input('email'),
                'phone_number' => $phone_number,
                'password' => Hash::make($request->input('password')),
                'status' => $request->input('status'),
                'firstname' => $request->input('firstname'),
                'lastname' => $request->input('lastname'),
                'language' => $request->input('language'),
                'notes' => $request->input('notes'),
                'admin_notes' => $request->input('admin_notes'),
            ]);
            $user->save();
            $user->refresh();

            $client = new Client;
            $client->fill([
                'firstname' => $request->input('firstname'),
                'lastname' => $request->input('lastname'),
                'company_name' => $request->input('company_name'),
                'tax_id' => $request->input('tax_id'),
                'status' => $request->input('status'),
                'language' => $request->input('language'),
                'currency_uuid' => $request->input('currency_uuid'),
                'notes' => $request->input('notes'),
                'admin_notes' => $request->input('admin_notes'),
            ]);
            $client->save();
            $client->refresh();

            $user->clients()->attach($client->uuid, ['owner' => true]);

            $address = new ClientAddress;
            $address->fill([
                'name' => 'Default',
                'client_uuid' => $client->uuid,
                'type' => 'billing',
                'contact_name' => $request->input('firstname').' '.$request->input('lastname'),
                'contact_phone' => $phone_number,
                'contact_email' => $request->input('email'),
                'address_1' => $request->input('address_1'),
                'address_2' => $request->input('address_2'),
                'city' => $request->input('city'),
                'postcode' => $request->input('postcode'),
                'region_uuid' => $request->input('region_uuid'),
                'country_uuid' => $request->input('country_uuid'),
                'notes' => $request->input('notes'),
                'admin_notes' => $request->input('admin_notes'),
            ]);
            $address->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('message.Created successfully'),
                'data' => [],
                'redirect' => route('admin.web.client.tab', ['uuid' => $client->uuid, 'tab' => 'summary']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => __('message.Creation failed'),
                'errors' => [$e->getMessage()],
            ], 500);
        }

    }

    public function clientTabs(Request $request, $uuid, $tab): View|RedirectResponse
    {
        $validTabs = [
            'summary', 'profile', 'users', 'services', 'domains', 'invoices', 'transactions', 'tickets', 'notifications', 'session-log',
        ];

        if (! in_array($tab, $validTabs)) {
            return redirect()->route('admin.web.client.tab', ['uuid' => $uuid, 'tab' => 'summary']);
        }

        $title = __('main.'.ucfirst(str_replace('-', '_', $tab)));

        return view_admin('clients.client_'.$tab, compact('title', 'uuid', 'tab'));
    }

    public function getClient(Request $request, $uuid): JsonResponse
    {

        $client = Client::find($uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $language = [];
        foreach (config('locale.admin.locales') as $key => $value) {
            if ($key == $client->language) {
                $language = [
                    'id' => $key,
                    'text' => $value['name'].' ('.$value['native'].')',
                ];
            }
        }

        $currency = Currency::find($client->currency_uuid);

        if (! empty($currency)) {
            $currency = [
                'id' => $currency->uuid,
                'text' => $currency['code'],
            ];
        }

        $responseData = $client->toArray();
        $responseData['language_data'] = $language;
        $responseData['currency_data'] = $currency;
        $responseData['balance'] = $client->balance->balance ?? 0.00;

        return response()->json([
            'data' => $responseData,
        ]);
    }

    public function putClient(Request $request, $uuid): JsonResponse
    {

        $client = Client::find($uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $locales = array_keys(config('locale.admin.locales'));
        $currencies = Currency::query()->get()->pluck('uuid')->toArray();

        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'language' => 'required|in:'.implode(',', $locales),
            'currency_uuid' => 'required|string|in:'.implode(',', $currencies),
            'status' => 'required|string|in:'.implode(',', ['new', 'active', 'inactive', 'closed', 'fraud']),
            'company_name' => 'nullable|unique:clients,company_name,'.$client->uuid.',uuid',
            'tax_id' => 'nullable|unique:clients,tax_id,'.$client->uuid.',uuid',

        ], [
            'firstname.required' => __('error.The firstname field is required'),
            'firstname.string' => __('error.The firstname must be a valid string'),
            'lastname.required' => __('error.The lastname field is required'),
            'lastname.string' => __('error.The lastname must be a valid string'),
            'language.required' => __('error.The language field is required'),
            'language.in' => __('error.The selected language is invalid'),
            'currency_uuid.string' => __('error.The currency must be a valid string'),
            'currency_uuid.required' => __('error.The currency field is required'),
            'currency_uuid.in' => __('error.The selected currency is invalid'),
            'status.string' => __('error.The status must be a valid string'),
            'status.required' => __('error.The status field is required'),
            'status.in' => __('error.The selected status is invalid'),
            'company_name.unique' => __('error.This company name is already taken'),
            'tax_id.unique' => __('error.This tax id is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $client->firstname = $request->input('firstname');
        $client->lastname = $request->input('lastname');
        $client->company_name = $request->input('company_name');
        $client->tax_id = $request->input('tax_id');
        $client->status = $request->input('status');
        $client->language = $request->input('language');
        $client->currency_uuid = $request->input('currency_uuid');
        $client->notes = $request->input('notes');
        $client->admin_notes = $request->input('admin_notes');
        $client->credit_limit = $request->input('credit_limit') ?? 0.00;
        if ($client->credit_limit < 0) {
            $client->credit_limit = 0.00;
        }

        $client->save();
        $client->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => [],
        ]);
    }

    public function getClientAddresses(Request $request, $uuid): JsonResponse
    {
        $query = ClientAddress::query()->where('client_uuid', $uuid);

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('clients.uuid', 'like', "%{$search}%")
                                ->orWhere('type', 'like', "%{$search}%")
                                ->orWhere('contact_name', 'like', "%{$search}%")
                                ->orWhere('contact_phone', 'like', "%{$search}%")
                                ->orWhere('address_1', 'like', "%{$search}%")
                                ->orWhere('address_2', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%")
                                ->orWhere('postcode', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($address) {
                    $admin_online = app('admin');

                    if ($admin_online->hasPermission('clients-edit')) {
                        $urls['get'] = route('admin.api.client.address.get', [$address->client_uuid, $address->uuid]);
                        $urls['put'] = route('admin.api.client.address.put', [$address->client_uuid, $address->uuid]);
                        $urls['delete'] = route('admin.api.client.address.delete', [$address->client_uuid, $address->uuid]);
                    }

                    return $urls;
                })
                ->addColumn('country', function ($address) {
                    return $address->country->toArray();
                })
                ->addColumn('region', function ($address) {
                    return $address->region->toArray();
                })
                ->make(true),
        ], 200);
    }

    public function getClientAddress(Request $request, $client_uuid, $address_uuid): JsonResponse
    {

        $address = ClientAddress::query()->where('client_uuid', $client_uuid)->where('uuid', $address_uuid)->first();

        if (empty($address)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $region = Region::find($address->region_uuid);

        if (! empty($region)) {
            $region = [
                'id' => $region->uuid,
                'text' => $region['name'],
            ];
        }

        $country = Country::find($address->country_uuid);

        if (! empty($country)) {
            $country = [
                'id' => $country->uuid,
                'text' => $country['name'],
            ];
        }

        $responseData = $address->toArray();
        $responseData['region_data'] = $region;
        $responseData['country_data'] = $country;

        return response()->json([
            'data' => $responseData,
        ]);
    }

    public function putClientAddress(Request $request, $client_uuid, $address_uuid): JsonResponse
    {

        $address = ClientAddress::query()->where('client_uuid', $client_uuid)->where('uuid', $address_uuid)->first();

        if (empty($address)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $countries = Country::query()->get()->pluck('uuid')->toArray();
        $regions = Region::query()->where('country_uuid', $request->input('country_uuid'))->get()->pluck('uuid')->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'contact_name' => 'required|string',
            'contact_email' => 'email',
            'address_1' => 'required',
            'address_2' => 'nullable',
            'city' => 'required',
            'postcode' => 'required',
            'country_uuid' => 'required|string|in:'.implode(',', $countries),
            'region_uuid' => 'required|string|in:'.implode(',', $regions),
        ], [
            'name.required' => __('error.The name field is required'),
            'name.string' => __('error.The name must be a valid string'),
            'contact_name.required' => __('error.The contact name field is required'),
            'contact_name.string' => __('error.The contact name must be a valid string'),
            'contact_email.email' => __('error.The email must be a valid email address'),
            'address_1.required' => __('error.The address 1 field is required'),
            'city.required' => __('error.The city field is required'),
            'postcode.required' => __('error.The postcode field is required'),
            'country_uuid.string' => __('error.The country must be a valid string'),
            'country_uuid.required' => __('error.The country field is required'),
            'country_uuid.in' => __('error.The selected country is invalid'),
            'region_uuid.string' => __('error.The region must be a valid string'),
            'region_uuid.required' => __('error.The region field is required'),
            'region_uuid.in' => __('error.The selected region is invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($address->type == 'billing' and $request->get('type') != 'billing') {
            return response()->json([
                'message' => ['type' => [__('error.You cannot change the type of the billing address')]],
            ], 422);
        }

        if ($address->type != 'billing' and $request->get('type') == 'billing') {
            return response()->json([
                'message' => ['type' => [__('error.You cannot create the billing address')]],
            ], 422);
        }

        $contact_phone = null;
        if (! empty($request->input('country_code')) and ! empty($request->input('phone_number'))) {
            $contact_phone = str_replace(' ', '', $request->input('country_code').$request->input('phone_number'));
        }

        $address->name = $request->input('name');
        $address->type = $request->input('type');
        $address->contact_name = $request->input('contact_name');
        $address->contact_email = $request->input('contact_email');
        $address->address_1 = $request->input('address_1');
        $address->address_2 = $request->input('address_2');
        $address->city = $request->input('city');
        $address->postcode = $request->input('postcode');
        $address->region_uuid = $request->input('region_uuid');
        $address->country_uuid = $request->input('country_uuid');
        $address->contact_phone = $contact_phone;
        $address->notes = $request->input('notes');
        $address->admin_notes = $request->input('admin_notes');

        $address->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => [],
        ]);
    }

    public function postClientAddress(Request $request, $uuid): JsonResponse
    {

        $address = new ClientAddress;

        $countries = Country::query()->get()->pluck('uuid')->toArray();
        $regions = Region::query()->where('country_uuid', $request->input('country_uuid'))->get()->pluck('uuid')->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'contact_name' => 'required|string',
            'contact_email' => 'email',
            'address_1' => 'required',
            'address_2' => 'nullable',
            'city' => 'required',
            'postcode' => 'required',
            'country_uuid' => 'required|string|in:'.implode(',', $countries),
            'region_uuid' => 'required|string|in:'.implode(',', $regions),
        ], [
            'name.required' => __('error.The name field is required'),
            'name.string' => __('error.The name must be a valid string'),
            'contact_name.required' => __('error.The contact name field is required'),
            'contact_name.string' => __('error.The contact name must be a valid string'),
            'contact_email.email' => __('error.The email must be a valid email address'),
            'address_1.required' => __('error.The address 1 field is required'),
            'city.required' => __('error.The city field is required'),
            'postcode.required' => __('error.The postcode field is required'),
            'country_uuid.string' => __('error.The country must be a valid string'),
            'country_uuid.required' => __('error.The country field is required'),
            'country_uuid.in' => __('error.The selected country is invalid'),
            'region_uuid.string' => __('error.The region must be a valid string'),
            'region_uuid.required' => __('error.The region field is required'),
            'region_uuid.in' => __('error.The selected region is invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->get('type') == 'billing') {
            return response()->json([
                'message' => ['type' => [__('error.You cannot create the billing address')]],
            ], 422);
        }

        $contact_phone = null;
        if (! empty($request->input('country_code')) and ! empty($request->input('phone_number'))) {
            $contact_phone = str_replace(' ', '', $request->input('country_code').$request->input('phone_number'));
        }

        $address->client_uuid = $uuid;
        $address->name = $request->input('name');
        $address->type = $request->input('type');
        $address->contact_name = $request->input('contact_name');
        $address->contact_phone = $request->input('contact_phone');
        $address->contact_email = $request->input('contact_email');
        $address->address_1 = $request->input('address_1');
        $address->address_2 = $request->input('address_2');
        $address->city = $request->input('city');
        $address->postcode = $request->input('postcode');
        $address->region_uuid = $request->input('region_uuid');
        $address->country_uuid = $request->input('country_uuid');
        $address->contact_phone = $contact_phone;
        $address->notes = $request->input('notes');
        $address->admin_notes = $request->input('admin_notes');

        $address->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Crated successfully'),
            'data' => [],
        ]);
    }

    public function deleteClientAddress(Request $request, $client_uuid, $address_uuid): JsonResponse
    {
        $address = ClientAddress::query()->where('client_uuid', $client_uuid)->where('uuid', $address_uuid)->first();

        if (empty($address)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($address->type == 'billing') {
            return response()->json([
                'errors' => [__('error.You cannot remove billing address')],
            ], 500);
        }

        try {
            $deleted = $address->delete();
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

    public function getClientUsers(Request $request, $uuid): JsonResponse
    {
        $client = Client::query()->where('uuid', $uuid)->first();
        $query = $client->users();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('email', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%")
                                ->orWhere('status', 'like', "%{$search}%")
                                ->orWhere('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($user) use ($client) {
                    $urls = ['gravatar' => get_gravatar($user->email, 100)];
                    $admin_online = app('admin');

                    if ($admin_online->hasPermission('clients-view')) {
                        $urls['get'] = route('admin.api.client.user.get', ['client_uuid' => $client->uuid, 'user_uuid' => $user->uuid]);
                    }

                    if ($admin_online->hasPermission('clients-edit')) {
                        $urls['put'] = route('admin.api.client.user.put', ['client_uuid' => $client->uuid, 'user_uuid' => $user->uuid]);
                    }

                    if ($admin_online->hasPermission('clients-edit')) {
                        $urls['delete'] = route('admin.api.client.user.delete', ['client_uuid' => $client->uuid, 'user_uuid' => $user->uuid]);
                    }

                    return $urls;
                })
                ->addColumn('clients', function ($user) {
                    return $user->clients()->count();
                })
                ->make(true),
        ], 200);
    }

    public function getClientUser(Request $request, $client_uuid, $user_uuid): JsonResponse
    {
        $client = Client::find($client_uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $user = $client->users()->where('uuid', $user_uuid)->first();

        if (empty($user)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $language = [];
        foreach (config('locale.admin.locales') as $key => $value) {
            if ($key == $user->language) {
                $language = [
                    'id' => $key,
                    'text' => $value['name'].' ('.$value['native'].')',
                ];
            }
        }
        $responseData = $user->toArray();
        $responseData['language_data'] = $language;

        $responseData['permissions_data'] = UserPermissionService::all();
        $responseData['pivot']['permissions'] = json_decode($responseData['pivot']['permissions']) ?? [];

        return response()->json([
            'data' => $responseData,
        ]);
    }

    public function putClientUser(Request $request, $client_uuid, $user_uuid): JsonResponse
    {

        $client = Client::find($client_uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $user = $client->users()->where('uuid', $user_uuid)->first();

        if (empty($user)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $locales = array_keys(config('locale.admin.locales'));

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,'.$user->uuid.',uuid',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'language' => 'required|in:'.implode(',', $locales),
        ], [
            'email.required' => __('error.The email field is required'),
            'email.email' => __('error.The email must be a valid email address'),
            'email.unique' => __('error.This email is already taken'),
            'firstname.required' => __('error.The firstname field is required'),
            'firstname.string' => __('error.The firstname must be a valid string'),
            'lastname.required' => __('error.The lastname field is required'),
            'lastname.string' => __('error.The lastname must be a valid string'),
            'language.required' => __('error.The language field is required'),
            'language.in' => __('error.The selected language is invalid'),
            'status.string' => __('error.The status must be a valid string'),
            'status.required' => __('error.The status field is required'),
            'status.in' => __('error.The selected status is invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $phone_number = '';
        if (! empty($request->input('country_code')) and ! empty($request->input('phone_number'))) {
            $phone_number = $request->input('country_code').$request->input('phone_number');
        }
        if ($phone_number != '') {
            if (User::query()->where('phone_number', $phone_number)->where('uuid', '<>', $user_uuid)->exists()) {
                return response()->json([
                    'message' => ['phone_number' => [__('error.This phone number is already taken')]],
                ], 422);
            }
        } else {
            $phone_number = null;
        }

        $user->email = $request->input('email');
        $user->phone_number = $phone_number;
        $user->firstname = $request->input('firstname');
        $user->lastname = $request->input('lastname');
        $user->language = $request->input('language');
        $user->notes = $request->input('notes');
        $user->admin_notes = $request->input('admin_notes');
        $user->save();

        $permissions = [];
        foreach ($request->input('permissions') as $key => $value) {
            if ($value == 'yes') {
                $permissions[] = $key;
            }
        }

        $client->users()->updateExistingPivot($user->uuid, [
            'permissions' => json_encode($permissions),
        ]);

        if (! empty($request->input('owner')) && $request->input('owner') == 'yes') {
            $client->updateOwner($user->uuid);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => [],
        ]);
    }

    public function postClientUserAssociate(Request $request, $uuid): JsonResponse
    {
        $client = Client::find($uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Client not found')],
            ], 404);
        }

        $userUuid = $request->input('user_uuid');
        $user = User::query()->where('uuid', $userUuid)->first();

        if (empty($user)) {
            return response()->json([
                'errors' => [__('error.User not found')],
            ], 404);
        }

        $existingRelation = $client->users()->where('user_uuid', $userUuid)->exists();

        if ($existingRelation) {
            return response()->json([
                'errors' => [__('error.Association already exists')],
            ], 422);
        }

        $permissions = [];
        foreach ($request->input('permissions') as $key => $value) {
            if ($value == 'yes') {
                $permissions[] = $key;
            }
        }

        $client->users()->attach($userUuid, [
            'owner' => $request->input('owner', false),
            'permissions' => json_encode($permissions),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Association created successfully'),
        ]);
    }

    public function deleteClientUserAssociate(Request $request, $client_uuid, $user_uuid): JsonResponse
    {
        $client = Client::find($client_uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Client not found')],
            ], 404);
        }

        $user = User::find($user_uuid);

        if (empty($user)) {
            return response()->json([
                'errors' => [__('error.User not found')],
            ], 404);
        }

        $existingRelation = $client->users()->wherePivot('user_uuid', $user_uuid)->exists();

        if (! $existingRelation) {
            return response()->json([
                'errors' => [__('error.No existing association')],
            ], 422);
        }

        $pivotData = $client->users()->where('user_uuid', $user_uuid)->first()->pivot;

        if ($pivotData && $pivotData->owner) {
            return response()->json([
                'errors' => [__('error.Cannot remove owner')],
            ], 422);
        }

        $client->users()->detach($user_uuid);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Association deleted successfully'),
        ]);
    }

    public function getClientServices(Request $request, $uuid): JsonResponse
    {
        $client = Client::query()->where('uuid', $uuid)->first();
        $query = $client->services()->with('product');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('status', 'like', "%{$search}%")
                                ->orWhere('admin_label', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhereHas('product', function ($q2) use ($search) {
                                    $q2->where('key', 'like', "%{$search}%");
                                });
                        });
                    }
                })
                ->addColumn('product_key', function ($service) {
                    return $service->product->key ?? '';
                })
                ->addColumn('product_name', function ($service) {
                    return $service->product->name;
                })
                ->addColumn('termination_time', function ($service) {
                    return $service->getTerminationTime();
                })
                ->addColumn('cancellation_time', function ($service) {
                    return $service->getCancellationTime();
                })
                ->addColumn('product_group_keys', function ($service) {
                    $product = $service->product;

                    return $product->productGroups()->pluck('key')->toArray();
                })
                ->addColumn('price', function ($service) {
                    $price = $service->price;
                    $currency = $price->currency;
                    $price_total = $service->getPriceTotal();

                    return [
                        'code' => $currency->code ?? '',
                        'amount' => number_format((float) $price_total['base'], 2),
                        'period' => $price->period,
                    ];
                })
                ->addColumn('urls', function ($service) use ($client) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('clients-view')) {
                        $urls['edit'] = route('admin.web.client.tab', ['uuid' => $client->uuid, 'tab' => 'services', 'edit' => $service->uuid]);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getClientTransactions(Request $request, $uuid): JsonResponse
    {
        $client = Client::query()->where('uuid', $uuid)->first();

        if (! $client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        $query = $client->transactions();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('transaction_date', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('transaction_id', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%")
                                ->orWhere('type', 'like', "%{$search}%")
                                ->orWhere('amount_net', 'like', "%{$search}%")
                                ->orWhere('amount_gross', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('currency', function ($transaction) use ($client) {
                    return $client->currency->toArray();
                })
                ->addColumn('payment_gateway', function ($transaction) {
                    $payment_gateway = $transaction->paymentGateway;
                    if (! empty($payment_gateway)) {
                        return ['uuid' => $payment_gateway->uuid, 'name' => $payment_gateway->name];
                    }

                    return [];
                })
                ->addColumn('urls', function ($transaction) use ($client) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online && $admin_online->hasPermission('finance-view')) {
                        $urls['view'] = route('admin.api.client.transaction.get', ['uuid' => $client->uuid, 't_uuid' => $transaction->uuid]);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postClientTransaction(Request $request, $uuid): JsonResponse
    {
        $client = Client::find($uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ((float) $value === 0.0) {
                        $fail(__('error.Amount cannot be zero'));
                    }
                },
            ],
            'transaction_id' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
        ], [
            'amount.required' => __('error.The amount field is required'),
            'amount.numeric' => __('error.The amount must be a number'),
            'description.required' => __('error.The description field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $transaction = new Transaction;

        $transaction->client_uuid = $client->uuid;

        $admin = app('admin');
        if (! empty($admin->uuid)) {
            $transaction->admin_uuid = $admin->uuid;
        }
        $transaction->type = 'adjustment';
        $transaction->amount_net = $request->get('amount');
        $transaction->amount_gross = $request->get('amount');
        $transaction->description = $request->get('description');
        $transaction->transaction_id = $request->get('transaction_id');
        $transaction->transaction_date = now();
        $transaction->period_start = now();
        $transaction->period_stop = now();
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    public function getClientInvoices(Request $request, $uuid): JsonResponse
    {
        $client = Client::query()->where('uuid', $uuid)->first();

        if (! $client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        $query = $client->invoices()->with('homeCompany');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('type', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('status', 'like', "%{$search}%")
                                ->orWhere('issue_date', 'like', "%{$search}%")
                                ->orWhere('due_date', 'like', "%{$search}%")
                                ->orWhere('paid_date', 'like', "%{$search}%")
                                ->orWhere('refunded_date', 'like', "%{$search}%")
                                ->orWhere('canceled_date', 'like', "%{$search}%")
                                ->orWhere('total', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('currency', function ($invoice) use ($client) {
                    return $client->currency->toArray();
                })
                ->addColumn('urls', function ($invoice) use ($client) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online && $admin_online->hasPermission('finance-view')) {
                        $urls['edit'] = route('admin.web.client.tab', ['uuid' => $client->uuid, 'tab' => 'invoices', 'edit' => $invoice->uuid]);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postClientInvoiceProformaAddFunds(Request $request, $uuid): JsonResponse
    {
        $client = Client::find($uuid);

        if (empty($client)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ((float) $value === 0.0) {
                        $fail(__('error.Amount cannot be zero'));
                    }
                },
            ],
        ], [
            'amount.required' => __('error.The amount field is required'),
            'amount.numeric' => __('error.The amount must be a number'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $client->createInvoiceProformaAddFunds($request->get('amount'));

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    public function getClientsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');
        $page = (int) $request->input('page', 1);
        $perPage = 20;

        $query = Client::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%")
                    ->orWhere('admin_notes', 'like', "%{$search}%");
            })->orWhereHas('users', function ($q) use ($search) {
                $q->where('client_x_user.owner', true)
                    ->where('email', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $clients = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $clients->map(function ($client) {
            $owner = $client->users()->wherePivot('owner', true)->first();

            return [
                'id' => $client->uuid,
                'text' => ($owner ? $owner->email : 'No owner')." - {$client->firstname} {$client->lastname}",
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

    public function getServicesSelect(Request $request, $uuid): JsonResponse
    {
        $search = $request->input('q');

        $query = Client::find($uuid)->services();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->where('admin_label', 'like', "%{$search}%");
            });
        }

        $services = $query->orderBy('created_at', 'desc')->get();

        $results = $services->map(function ($service) {
            return [
                'id' => $service->uuid,
                'text' => $service->product->key.' - '.$service->admin_label,
            ];
        });

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ]);
    }

    public function loginAsClientOwner(Request $request, $uuid): Application|JsonResponse|Redirector|RedirectResponse
    {
        $client = Client::query()->where('uuid', $uuid)->first();
        Session::put('login_as_client_owner', $uuid);

        if (! $client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        Auth::guard('client')->login($client->owner());
        Session::put('client_uuid', $uuid);

        return redirect()->route('client.web.panel.dashboard');
    }

    public function returnToAdmin(Request $request, $uuid): RedirectResponse
    {
        Session::forget('login_as_client_owner');
        Auth::guard('client')->logout();

        return redirect()->route('admin.web.client.tab', ['uuid' => $uuid, 'tab' => 'summary']);
    }
}
