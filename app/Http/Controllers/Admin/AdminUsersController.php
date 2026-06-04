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
 *   GET  users                                                 -> users()                       [admin.web.users]
 *
 * API Routes:
 *   GET    users                                               -> getUsers()                    [admin.api.users.get]
 *   GET    user/{uuid}                                         -> getUser()                     [admin.api.user.get]
 *   GET    user/{uuid}/clients                                 -> getUserClients()              [admin.api.user.clients.get]
 *   POST   user                                                -> postUser()                    [admin.api.user.post]
 *   PUT    user/{uuid}                                         -> putUser()                     [admin.api.user.put]
 *   DELETE user/{uuid}                                         -> deleteUser()                  [admin.api.user.delete]
 *   GET    users/select                                        -> getUsersSelect()              [admin.api.users.select.get]
 *   GET    user/permissions/select                             -> getUserPermissionsSelect()    [admin.api.user.permissions.select.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserPermissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminUsersController extends Controller
{
    public function users(): View
    {
        $title = __('main.Users');

        return view_admin('users.users', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.users.get',
        description: 'Returns a DataTable list of users.',
        summary: 'Get users list',
        security: [['apiKeyAuth' => []]],
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Users list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getUsers(Request $request): JsonResponse
    {
        $query = User::query();

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
                ->addColumn('urls', function ($user) {
                    $urls = ['gravatar' => get_gravatar($user->email, 100)];
                    $admin_online = app('admin');

                    if ($admin_online->hasPermission('users-view')) {
                        $urls['get'] = route('admin.api.user.get', $user->uuid);
                        $urls['get_clients'] = route('admin.api.user.clients.get', $user->uuid);
                    }

                    if ($admin_online->hasPermission('users-edit')) {
                        $urls['put'] = route('admin.api.user.put', $user->uuid);
                    }

                    if ($admin_online->hasPermission('users-delete')) {
                        $urls['delete'] = route('admin.api.user.delete', $user->uuid);
                    }

                    return $urls;
                })
                ->addColumn('clients', function ($user) {
                    return $user->clients()->count();
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.user.post',
        description: 'Creates a new user with specified details.',
        summary: 'Create a new user',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['email', 'password', 'firstname', 'lastname', 'language'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'password', type: 'string', format: 'password'),
                        new OA\Property(property: 'firstname', type: 'string'),
                        new OA\Property(property: 'lastname', type: 'string'),
                        new OA\Property(property: 'language', type: 'string', example: 'en'),
                        new OA\Property(property: 'phone_number', type: 'string', nullable: true),
                        new OA\Property(property: 'country_code', type: 'string', nullable: true),
                        new OA\Property(property: 'notes', type: 'string', nullable: true),
                        new OA\Property(property: 'admin_notes', type: 'string', nullable: true),
                        new OA\Property(property: 'two_factor', type: 'string', enum: ['yes', 'no'], nullable: true),
                    ]
                )
            )
        ),
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postUser(Request $request): JsonResponse
    {

        $locales = array_keys(config('locale.admin.locales'));

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'language' => 'required|in:'.implode(',', $locales),
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

        if (! empty($phone_number) and User::query()->where('phone_number', $phone_number)->exists()) {
            return response()->json([
                'message' => ['phone_number' => [__('error.This phone number is already taken')]],
            ], 422);
        }

        if (empty($phone_number)) {
            $phone_number = null;
        }

        $user = new User;
        $user->fill([
            'email' => $request->input('email'),
            'phone_number' => $phone_number,
            'password' => Hash::make($request->input('password')),
            'status' => 'New',
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'language' => $request->input('language'),
            'notes' => $request->input('notes'),
            'admin_notes' => $request->input('admin_notes'),
            'two_factor' => $request->input('two_factor') == 'yes',
        ]);
        $user->save();
        $user->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    #[OA\Get(
        path: 'admin.api.user.get',
        description: 'Returns detailed information about a user, including language data.',
        summary: 'Get user details by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'firstname', type: 'string'),
                                new OA\Property(property: 'lastname', type: 'string'),
                                new OA\Property(property: 'language', type: 'string'),
                                new OA\Property(property: 'phone_number', type: 'string', nullable: true),
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(
                                    property: 'language_data',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string'),
                                        new OA\Property(property: 'text', type: 'string'),
                                    ],
                                    type: 'object'
                                ),
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
    public function getUser(Request $request, $uuid): JsonResponse
    {
        $user = User::find($uuid);

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

        return response()->json([
            'data' => $responseData,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.user.clients.get',
        description: 'Returns user details with associated clients list.',
        summary: 'Get user clients by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User clients data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getUserClients(Request $request, $uuid): JsonResponse
    {
        $user = User::find($uuid);

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

        foreach ($user->clients as $key => $client) {
            $user->clients[$key]['web_url'] = route('admin.web.client.tab', ['uuid' => $client['uuid'], 'tab' => 'summary']);
        }

        $responseData = $user->toArray();

        $responseData['language_data'] = $language;

        return response()->json([
            'data' => $responseData,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.user.put',
        description: 'Updates an existing user details.',
        summary: 'Update user by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['email', 'firstname', 'lastname', 'language'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true),
                        new OA\Property(property: 'firstname', type: 'string'),
                        new OA\Property(property: 'lastname', type: 'string'),
                        new OA\Property(property: 'language', type: 'string'),
                        new OA\Property(property: 'phone_number', type: 'string', nullable: true),
                        new OA\Property(property: 'country_code', type: 'string', nullable: true),
                        new OA\Property(property: 'notes', type: 'string', nullable: true),
                        new OA\Property(property: 'admin_notes', type: 'string', nullable: true),
                        new OA\Property(property: 'two_factor', type: 'string', enum: ['yes', 'no'], nullable: true),
                        new OA\Property(property: 'email_verified', type: 'string', enum: ['yes', 'no'], nullable: true),
                        new OA\Property(property: 'phone_verified', type: 'string', enum: ['yes', 'no'], nullable: true),
                    ]
                )
            )
        ),
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the user to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putUser(Request $request, $uuid): JsonResponse
    {

        $user = User::find($uuid);

        if (empty($user)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $locales = array_keys(config('locale.admin.locales'));

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,'.$user->uuid.',uuid',
            'password' => 'nullable|min:6',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'language' => 'required|in:'.implode(',', $locales),
        ], [
            'email.required' => __('error.The email field is required'),
            'email.email' => __('error.The email must be a valid email address'),
            'email.unique' => __('error.This email is already taken'),
            'password.min' => __('error.The password must be at least 6 characters'),
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
            if (User::query()->where('phone_number', $phone_number)->where('uuid', '<>', $uuid)->exists()) {
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

        if ($request->has('two_factor')) {
            $user->two_factor = $request->input('two_factor') == 'yes';
        }

        if ($request->has('email_verified')) {
            $user->email_verified = $request->input('email_verified') == 'yes';
        }

        if ($request->has('phone_verified')) {
            $user->phone_verified = $request->input('phone_verified') == 'yes';
        }

        if (! empty($request->input('password'))) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => [],
        ]);
    }

    #[OA\Get(
        path: 'admin.api.users.select.get',
        description: 'Returns a paginated list of users for select2 dropdown.',
        summary: 'Get users select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Users'],
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
                description: 'Users select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getUsersSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');
        $page = (int) $request->input('page', 1);
        $perPage = 20;

        $query = User::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('native_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $users = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $users->map(function ($user) {
            return [
                'id' => $user->uuid,
                'text' => "{$user->email} ({$user->firstname} {$user->lastname})",
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
        path: 'admin.api.user.permissions.select.get',
        description: 'Returns a list of user permissions for select2 dropdown.',
        summary: 'Get user permissions select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Users'],
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
                description: 'User permissions select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getUserPermissionsSelect(Request $request): JsonResponse
    {

        $permissions = [];
        foreach (UserPermissionService::all() as $value) {
            $permissions[] = [
                'id' => $value['key'],
                'text' => $value['name'],
            ];
        }

        $searchTerm = $request->input('q');

        $filteredLanguages = array_filter($permissions, function ($permission) use ($searchTerm) {
            return empty($searchTerm) || stripos($permission['text'], $searchTerm) !== false;
        });

        return response()->json(['data' => [
            'results' => array_values($filteredLanguages),
            'pagination' => [
                'more' => false,
            ],
        ]], 200);

    }

    #[OA\Delete(
        path: 'admin.api.user.delete',
        description: 'Deletes a user by UUID. Cannot delete if user is associated with clients.',
        summary: 'Delete user by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the user to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User deleted successfully',
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
    public function deleteUser(Request $request, $uuid): JsonResponse
    {
        $user = User::find($uuid);

        if (empty($user)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($user->clients()->count() > 0) {
            return response()->json([
                'errors' => [__('error.User is associated with the client, cannot be deleted')],
            ], 404);
        }

        try {
            $deleted = $user->delete();
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
