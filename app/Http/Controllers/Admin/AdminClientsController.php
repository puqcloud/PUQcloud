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
 *   GET  clients                                    -> clients()            [admin.web.clients]
 *   GET  client/create                              -> clientCreate()       [admin.web.client.create]
 *   GET  client/{uuid}/login_as_client_owner        -> loginAsClientOwner() [admin.web.client.login_as_client_owner]
 *   GET  client/{uuid}/return_to_admin              -> returnToAdmin()      [admin.web.client.return_to_admin]
 *   GET  client/{uuid}/{tab}                        -> clientTabs()         [admin.web.client.tab]
 *
 * API Routes:
 *   GET    clients                                  -> getClients()         [admin.api.clients.get]
 *   GET    client/{uuid}                            -> getClient()          [admin.api.client.get]
 *   POST   client                                   -> postClient()         [admin.api.client.post]
 *   PUT    client/{uuid}                            -> putClient()          [admin.api.client.put]
 *   GET    clients/select                           -> getClientsSelect()   [admin.api.clients.select.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Region;
use App\Models\User;
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
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminClientsController extends Controller
{
    public function clients(): View
    {
        $title = __('main.Clients');

        return view_admin('clients.clients', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.clients.get',
        description: 'Returns a DataTable list of clients.',
        summary: 'Get clients list',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Clients list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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
                        $urls['edit'] = route('admin.web.client.tab', [$client->uuid, 'summary']);
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

    #[OA\Post(
        path: 'admin.api.client.post',
        description: 'Creates a new client with user account, billing address, and associated data.',
        summary: 'Create a new client',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['email', 'password', 'firstname', 'lastname', 'language', 'currency_uuid', 'status', 'address_1', 'city', 'postcode', 'country_uuid', 'region_uuid'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'password', type: 'string'),
                        new OA\Property(property: 'firstname', type: 'string'),
                        new OA\Property(property: 'lastname', type: 'string'),
                        new OA\Property(property: 'language', type: 'string'),
                        new OA\Property(property: 'currency_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'status', type: 'string', enum: ['new', 'active', 'inactive', 'closed', 'fraud']),
                        new OA\Property(property: 'address_1', type: 'string'),
                        new OA\Property(property: 'address_2', type: 'string'),
                        new OA\Property(property: 'city', type: 'string'),
                        new OA\Property(property: 'postcode', type: 'string'),
                        new OA\Property(property: 'country_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'region_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'company_name', type: 'string'),
                        new OA\Property(property: 'tax_id', type: 'string'),
                        new OA\Property(property: 'country_code', type: 'string'),
                        new OA\Property(property: 'phone_number', type: 'string'),
                        new OA\Property(property: 'notes', type: 'string'),
                        new OA\Property(property: 'admin_notes', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Clients'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client created successfully',
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
            new OA\Response(response: 500, description: 'Creation failed'),
        ]
    )]
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

            app(\App\Services\HookService::class)->callHooks('ClientCreated', ['client' => $client]);

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
            'summary', 'profile', 'users', 'services', 'domains', 'invoices', 'transactions', 'tickets', 'notifications', 'activity-log', 'session-log',
        ];

        if (! in_array($tab, $validTabs)) {
            return redirect()->route('admin.web.client.tab', ['uuid' => $uuid, 'tab' => 'summary']);
        }

        $title = __('main.'.ucfirst(str_replace('-', '_', $tab)));

        return view_admin('clients.client_'.$tab, compact('title', 'uuid', 'tab'));
    }

    #[OA\Get(
        path: 'admin.api.client.get',
        description: 'Returns detailed information about a client, including language, currency, and balance.',
        summary: 'Get client by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Client not found'),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.client.put',
        description: 'Updates an existing client profile including name, status, language, currency, and notes.',
        summary: 'Update client by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['firstname', 'lastname', 'language', 'currency_uuid', 'status'],
                    properties: [
                        new OA\Property(property: 'firstname', type: 'string'),
                        new OA\Property(property: 'lastname', type: 'string'),
                        new OA\Property(property: 'language', type: 'string'),
                        new OA\Property(property: 'currency_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'status', type: 'string', enum: ['new', 'active', 'inactive', 'closed', 'fraud']),
                        new OA\Property(property: 'company_name', type: 'string'),
                        new OA\Property(property: 'tax_id', type: 'string'),
                        new OA\Property(property: 'notes', type: 'string'),
                        new OA\Property(property: 'admin_notes', type: 'string'),
                        new OA\Property(property: 'credit_limit', type: 'number'),
                    ]
                )
            )
        ),
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Client not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

        app(\App\Services\HookService::class)->callHooks('ClientEdit', ['client' => $client]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => [],
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

    #[OA\Get(
        path: 'admin.api.clients.select.get',
        description: 'Returns a paginated list of clients for select2 dropdown.',
        summary: 'Get clients select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
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
                description: 'Clients select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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
                'text' => ($owner ? $owner->email : 'No owner')." - {$client->firstname} {$client->lastname}".($client->company_name ? " ({$client->company_name})" : ''),
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
}

