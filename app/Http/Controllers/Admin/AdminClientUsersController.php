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
 * API Routes:
 *   GET    client/{uuid}/users                              -> getClientUsers()             [admin.api.client.users.get]
 *   GET    client/{client_uuid}/user/{user_uuid}            -> getClientUser()              [admin.api.client.user.get]
 *   PUT    client/{client_uuid}/user/{user_uuid}            -> putClientUser()              [admin.api.client.user.put]
 *   POST   client/{uuid}/user/associate                     -> postClientUserAssociate()    [admin.api.client.user.associate.post]
 *   DELETE client/{client_uuid}/user/{user_uuid}            -> deleteClientUserAssociate()  [admin.api.client.user.delete]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Services\UserPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminClientUsersController extends Controller
{
    #[OA\Get(
        path: 'admin.api.client.users.get',
        description: 'Returns a list of users associated with a client with DataTables support.',
        summary: 'Get client users',
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
                description: 'Client users returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.client.user.get',
        description: 'Returns detailed information about a user associated with a client, including language, permissions, and pivot data.',
        summary: 'Get client user by UUID',
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
                name: 'user_uuid',
                description: 'UUID of the user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client user data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Client or user not found'),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.client.user.put',
        description: 'Updates a user associated with a client, including email, name, language, permissions, and owner status.',
        summary: 'Update client user by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['email', 'firstname', 'lastname', 'language'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'firstname', type: 'string'),
                        new OA\Property(property: 'lastname', type: 'string'),
                        new OA\Property(property: 'language', type: 'string'),
                        new OA\Property(property: 'country_code', type: 'string'),
                        new OA\Property(property: 'phone_number', type: 'string'),
                        new OA\Property(property: 'notes', type: 'string'),
                        new OA\Property(property: 'admin_notes', type: 'string'),
                        new OA\Property(property: 'permissions', type: 'object'),
                        new OA\Property(property: 'owner', type: 'string'),
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
                name: 'user_uuid',
                description: 'UUID of the user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client user updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Client or user not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Post(
        path: 'admin.api.client.user.associate.post',
        description: 'Associates an existing user with a client, optionally setting owner status and permissions.',
        summary: 'Associate user with client',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['user_uuid', 'permissions'],
                    properties: [
                        new OA\Property(property: 'user_uuid', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'owner', type: 'boolean'),
                        new OA\Property(property: 'permissions', type: 'object'),
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
                description: 'Association created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Client or user not found'),
            new OA\Response(response: 422, description: 'Association already exists'),
        ]
    )]
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

    #[OA\Delete(
        path: 'admin.api.client.user.delete',
        description: 'Removes the association between a user and a client. Owners cannot be removed.',
        summary: 'Delete client user association',
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
                name: 'user_uuid',
                description: 'UUID of the user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Association deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Client or user not found'),
            new OA\Response(response: 422, description: 'No existing association or cannot remove owner'),
        ]
    )]
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
}
