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
 * API Routes:
 *   GET    client/{uuid}/addresses                          -> getClientAddresses()    [admin.api.client.addresses.get]
 *   GET    client/{client_uuid}/address/{address_uuid}      -> getClientAddress()      [admin.api.client.address.get]
 *   PUT    client/{client_uuid}/address/{address_uuid}      -> putClientAddress()      [admin.api.client.address.put]
 *   POST   client/{uuid}/address                            -> postClientAddress()     [admin.api.client.address.post]
 *   DELETE client/{client_uuid}/address/{address_uuid}      -> deleteClientAddress()   [admin.api.client.address.delete]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminClientAddressesController extends Controller
{
    #[OA\Get(
        path: 'admin.api.client.addresses.get',
        description: 'Returns a list of addresses for a client with DataTables support.',
        summary: 'Get client addresses',
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
                description: 'Client addresses returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.client.address.get',
        description: 'Returns detailed information about a specific client address, including region and country data.',
        summary: 'Get client address by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'client_uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'address_uuid',
                description: 'UUID of the address',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client address data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Address not found'),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.client.address.put',
        description: 'Updates an existing client address including contact details, location, and type.',
        summary: 'Update client address by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name', 'contact_name', 'address_1', 'city', 'postcode', 'country_uuid', 'region_uuid'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'type', type: 'string'),
                        new OA\Property(property: 'contact_name', type: 'string'),
                        new OA\Property(property: 'contact_email', type: 'string', format: 'email'),
                        new OA\Property(property: 'address_1', type: 'string'),
                        new OA\Property(property: 'address_2', type: 'string'),
                        new OA\Property(property: 'city', type: 'string'),
                        new OA\Property(property: 'postcode', type: 'string'),
                        new OA\Property(property: 'country_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'region_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'country_code', type: 'string'),
                        new OA\Property(property: 'phone_number', type: 'string'),
                        new OA\Property(property: 'notes', type: 'string'),
                        new OA\Property(property: 'admin_notes', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'client_uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'address_uuid',
                description: 'UUID of the address',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Address updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Address not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Post(
        path: 'admin.api.client.address.post',
        description: 'Creates a new address for a client with contact details and location.',
        summary: 'Create a new client address',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name', 'contact_name', 'address_1', 'city', 'postcode', 'country_uuid', 'region_uuid'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'type', type: 'string'),
                        new OA\Property(property: 'contact_name', type: 'string'),
                        new OA\Property(property: 'contact_email', type: 'string', format: 'email'),
                        new OA\Property(property: 'address_1', type: 'string'),
                        new OA\Property(property: 'address_2', type: 'string'),
                        new OA\Property(property: 'city', type: 'string'),
                        new OA\Property(property: 'postcode', type: 'string'),
                        new OA\Property(property: 'country_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'region_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'country_code', type: 'string'),
                        new OA\Property(property: 'phone_number', type: 'string'),
                        new OA\Property(property: 'notes', type: 'string'),
                        new OA\Property(property: 'admin_notes', type: 'string'),
                    ]
                )
            )
        ),
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'client_uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Address created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Delete(
        path: 'admin.api.client.address.delete',
        description: 'Deletes a client address. Billing addresses cannot be deleted.',
        summary: 'Delete client address by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'client_uuid',
                description: 'UUID of the client',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'address_uuid',
                description: 'UUID of the address',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Address deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Address not found'),
            new OA\Response(response: 500, description: 'Deletion failed or billing address'),
        ]
    )]
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
}
