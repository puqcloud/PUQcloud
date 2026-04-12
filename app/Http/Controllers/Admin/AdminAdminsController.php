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
 *   GET  admins                                   -> admins()                   [admin.web.admins]
 *   GET  admin/{uuid}                             -> admin()                    [admin.web.admin]
 *
 * API Routes:
 *   GET     admins                                 -> getAdmins()                [admin.api.admins.get]
 *   GET     admins/select                          -> getAdminsSelect()          [admin.api.admins.select.get]
 *   GET     admin/{uuid}                           -> getAdmin()                 [admin.api.admin.get]
 *   POST    admin                                  -> postAdmin()                [admin.api.admin.post]
 *   PUT     admin/{uuid}                           -> putAdmin()                 [admin.api.admin.put]
 *   DELETE  admin/{uuid}                           -> deleteAdmin()              [admin.api.admin.delete]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Group;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminAdminsController extends Controller
{
    public function admins(): View
    {
        $title = __('main.Administrators');

        return view_admin('admins.admins', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.admins.get',
        description: 'Returns a DataTable list of admins.',
        summary: 'Get admins list',
        security: [['apiKeyAuth' => []]],
        tags: ['Admins'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admins list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getAdmins(Request $request): JsonResponse
    {
        $query = Admin::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('email', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($admin) {
                    $admin_online = app('admin');
                    $urls = ['gravatar' => get_gravatar($admin->email, 100)];

                    if ($admin_online->hasPermission('admins-view')) {
                        $urls['edit'] = route('admin.web.admin', $admin->uuid);
                        $urls['get'] = route('admin.api.admin.get', $admin->uuid);
                    }

                    if ($admin_online->hasPermission('admins-edit')) {
                        $urls['post'] = route('admin.api.admin.post', $admin->uuid);
                    }

                    if ($admin_online->hasPermission('admins-create')) {
                        $urls['put'] = route('admin.api.admin.put', $admin->uuid);
                    }

                    if ($admin_online->hasPermission('admins-delete')) {
                        $urls['delete'] = route('admin.api.admin.delete', $admin->uuid);
                    }

                    return $urls;

                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.admins.select.get',
        description: 'Returns a list of admins for select2 dropdown.',
        summary: 'Get admins select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Admins'],
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
                description: 'Admins select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getAdminsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (! empty($search)) {
            $admins = Admin::query()
                ->where('uuid', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('firstname', 'like', '%'.$search.'%')
                ->orWhere('lastname', 'like', '%'.$search.'%')
                ->get();
        } else {
            $admins = Admin::all();
        }

        $formattedGroups = $admins->map(function ($admin) {
            return [
                'id' => $admin->uuid,
                'text' => $admin->email,
            ];
        });

        return response()->json([
            'data' => [
                'results' => $formattedGroups,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    public function admin(Request $request, $uuid): View
    {
        $title = __('main.Edit Administrator');

        return view_admin('admins.admin', compact('title', 'uuid'));
    }

    #[OA\Get(
        path: 'admin.api.admin.get',
        description: 'Returns detailed information about an admin, including language data, groups, and associated IPs.',
        summary: 'Get admin details by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Admins'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the admin',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: null, nullable: true),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'),
                            example: null, nullable: true),
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', format: 'uuid',
                                    example: 'a5e49d8f-dd43-457b-8c0e-01acbb000ae6'),
                                new OA\Property(property: 'email', type: 'string', example: 'it@puq.pl'),
                                new OA\Property(property: 'phone_number', type: 'string', example: '123'),
                                new OA\Property(property: 'two_factor', type: 'integer', example: 0),
                                new OA\Property(property: 'disable', type: 'boolean', example: false),
                                new OA\Property(property: 'email_verified', type: 'integer', example: 0),
                                new OA\Property(property: 'phone_verified', type: 'integer', example: 0),
                                new OA\Property(property: 'firstname', type: 'string', example: 'PUQ'),
                                new OA\Property(property: 'lastname', type: 'string', example: 'Cloud'),
                                new OA\Property(property: 'admin_notes', type: 'string', example: null, nullable: true),
                                new OA\Property(property: 'dashboard', type: 'string',
                                    example: '[{"key":"PUQCloudInfo","width":4,"height":46,"x":0,"y":0,"autoRefresh":false}]'),
                                new OA\Property(property: 'client_summary_dashboard', type: 'string',
                                    example: '[{"key":"ClientFinance","width":3,"height":45,"x":0,"y":0,"autoRefresh":false}]'),
                                new OA\Property(property: 'old_permissions', type: 'string',
                                    example: '["Notification-puqPHPmail-test-connection","Payment-puqPayPal-test-connection"]'),
                                new OA\Property(property: 'language', type: 'string', example: 'en'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time',
                                    example: '2025-05-26 10:52:11'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time',
                                    example: '2026-01-10 14:07:51'),
                                new OA\Property(
                                    property: 'language_data',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', example: 'en'),
                                        new OA\Property(property: 'text', type: 'string', example: 'English (English)'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'groups', type: 'string', example: ''),
                                new OA\Property(
                                    property: 'groups_data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string',
                                                example: '5cfb8c43-85c0-4fb8-81b3-658f0d397348'),
                                            new OA\Property(property: 'text', type: 'string', example: 'Super Admin'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'ips',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'uuid', type: 'string', format: 'uuid',
                                                example: '1a8a59b4-a208-4ee1-83b6-7824acc7814a'),
                                            new OA\Property(property: 'admin_uuid', type: 'string', format: 'uuid',
                                                example: 'a5e49d8f-dd43-457b-8c0e-01acbb000ae6'),
                                            new OA\Property(property: 'ip_address', type: 'string',
                                                example: '77.87.125.4'),
                                            new OA\Property(property: 'start_use', type: 'string', format: 'date-time',
                                                example: '2025-12-13 14:49:40'),
                                            new OA\Property(property: 'stop_use', type: 'string', format: 'date-time',
                                                example: '2026-01-11 02:23:28'),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time',
                                                example: '2025-12-13 14:49:40'),
                                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time',
                                                example: '2026-01-11 02:23:28'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getAdmin(Request $request, $uuid): JsonResponse
    {
        $admin = Admin::find($uuid);

        if (empty($admin)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $responseData = $admin->toArray();
        $responseData['language_data'] = $admin->getLanguageData();
        $responseData['groups'] = '';
        $responseData['groups_data'] = $admin->getGroupsData();
        $responseData['ips'] = $admin->ips()->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'data' => $responseData,
        ]);
    }

    #[OA\Post(
        path: 'admin.api.admin.post',
        description: 'Creates a new administrator with specified details, groups, and permissions.',
        summary: 'Create a new admin',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: [
                        'email', 'password', 'password_confirmation', 'firstname', 'lastname', 'language', 'groups',
                    ],
                    properties: [
                        new OA\Property(
                            property: 'email',
                            description: 'Admin email address (must be unique)',
                            type: 'string',
                            format: 'email',
                            example: 'admin@example.com'
                        ),
                        new OA\Property(
                            property: 'password',
                            description: 'Admin password (minimum 6 characters)',
                            type: 'string',
                            format: 'password',
                            example: 'password123'
                        ),
                        new OA\Property(
                            property: 'password_confirmation',
                            description: 'Password confirmation (must match password)',
                            type: 'string',
                            format: 'password',
                            example: 'password123'
                        ),
                        new OA\Property(
                            property: 'firstname',
                            description: 'Admin first name',
                            type: 'string',
                            example: 'John'
                        ),
                        new OA\Property(
                            property: 'lastname',
                            description: 'Admin last name',
                            type: 'string',
                            example: 'Doe'
                        ),
                        new OA\Property(
                            property: 'language',
                            description: 'Admin interface language (must be one of available locales)',
                            type: 'string',
                            example: 'en'
                        ),
                        new OA\Property(
                            property: 'groups',
                            description: 'Array of group UUIDs to assign to the admin',
                            type: 'array',
                            items: new OA\Items(
                                type: 'string',
                                format: 'uuid'
                            ),
                            example: ['24fe5c7f-0f53-4c1d-bd99-77062af57afa']
                        ),
                        new OA\Property(
                            property: 'phone_number',
                            description: 'Phone number (optional)',
                            type: 'string',
                            example: '1234567890',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'country_code',
                            description: 'Country code for phone number (optional)',
                            type: 'string',
                            example: '+1',
                            nullable: true
                        ),
                    ]
                )
            )
        ),
        tags: ['Admins'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', format: 'uuid',
                                    example: 'a5e49d8f-dd43-457b-8c0e-01acbb000ae6'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                                new OA\Property(property: 'firstname', type: 'string', example: 'John'),
                                new OA\Property(property: 'lastname', type: 'string', example: 'Doe'),
                                new OA\Property(property: 'language', type: 'string', example: 'en'),
                                new OA\Property(property: 'phone_number', type: 'string', example: '+11234567890',
                                    nullable: true),
                                new OA\Property(property: 'disable', type: 'boolean', example: false),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time',
                                    example: '2026-01-11 10:00:00'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time',
                                    example: '2026-01-11 10:00:00'),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'redirect', type: 'string',
                            example: 'https://example.com/admin/a5e49d8f-dd43-457b-8c0e-01acbb000ae6'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postAdmin(Request $request): JsonResponse
    {

        $admin = new Admin;

        $locales = array_keys(config('locale.admin.locales'));

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'language' => 'required|in:'.implode(',', $locales),
            'groups' => 'required|array',
            'groups.*' => 'exists:groups,uuid',
        ], [
            'email.required' => __('error.The email field is required'),
            'email.email' => __('error.The email must be a valid email address'),
            'email.unique' => __('error.This email is already taken'),
            'password.required' => __('error.The password field is required'),
            'password.min' => __('error.The password must be at least 6 characters'),
            'password.confirmed' => __('error.The password confirmation does not match'),
            'password_confirmation.required' => __('error.The password field is required'),
            'password_confirmation.min' => __('error.The password must be at least 6 characters'),
            'firstname.required' => __('error.The firstname field is required'),
            'firstname.string' => __('error.The firstname must be a valid string'),
            'lastname.required' => __('error.The lastname field is required'),
            'lastname.string' => __('error.The lastname must be a valid string'),
            'language.required' => __('error.The language field is required'),
            'language.in' => __('error.The selected language is invalid'),
            'groups.required' => __('error.Groups field is required'),
            'groups.array' => __('error.Groups must be an array'),
            'groups.*.exists' => __('error.One or more selected groups are invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('email') && ! empty($request->input('email'))) {
            $admin->email = $request->input('email');
        }

        if ($request->filled('password')) {
            $admin->password = bcrypt($request->input('password'));
        }

        if (! empty($request->input('firstname'))) {
            $admin->firstname = $request->input('firstname');
        }

        if (! empty($request->input('lastname'))) {
            $admin->lastname = $request->input('lastname');
        }

        if (! empty($request->input('language'))) {
            $admin->language = $request->input('language');
        }

        if ($request->has('phone_number') and $request->has('country_code')) {
            $admin->phone_number = $request->input('country_code').$request->input('phone_number');
        }

        $admin->disable = false;
        $admin->save();

        if ($request->has('groups')) {
            $groupUuids = $request->input('groups');
            $existingGroups = Group::whereIn('uuid', $groupUuids)->pluck('uuid')->toArray();
            $validGroups = array_intersect($groupUuids, $existingGroups);
            $admin->groups()->detach();
            foreach ($validGroups as $groupUuid) {
                $admin->addGroup($groupUuid);
            }
        }
        $admin->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $admin,
            'redirect' => route('admin.web.admin', $admin->uuid),
        ]);
    }

    #[OA\Put(
        path: 'admin.api.admin.put',
        description: 'Updates an existing administrator information including email, password, personal details, groups, and status.',
        summary: 'Update admin details by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['groups'],
                    properties: [
                        new OA\Property(
                            property: 'email',
                            description: 'Admin email address (must be unique)',
                            type: 'string',
                            format: 'email',
                            example: 'admin@example.com',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'password',
                            description: 'New password (minimum 6 characters, optional)',
                            type: 'string',
                            format: 'password',
                            example: 'newpassword123',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'password_confirmation',
                            description: 'Password confirmation (required if password is set)',
                            type: 'string',
                            format: 'password',
                            example: 'newpassword123',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'firstname',
                            description: 'Admin first name',
                            type: 'string',
                            example: 'John',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'lastname',
                            description: 'Admin last name',
                            type: 'string',
                            example: 'Doe',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'language',
                            description: 'Admin interface language (must be one of available locales)',
                            type: 'string',
                            example: 'en',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'groups',
                            description: 'Array of group UUIDs to assign to the admin (required)',
                            type: 'array',
                            items: new OA\Items(
                                type: 'string',
                                format: 'uuid'
                            ),
                            example: ['5cfb8c43-85c0-4fb8-81b3-658f0d397348']
                        ),
                        new OA\Property(
                            property: 'phone_number',
                            description: 'Phone number (optional)',
                            type: 'string',
                            example: '1234567890',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'country_code',
                            description: 'Country code for phone number (optional)',
                            type: 'string',
                            example: '+1',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'disable',
                            description: 'Enable or disable admin account ("yes" to disable, "no" to enable)',
                            type: 'string',
                            enum: ['yes', 'no'],
                            example: 'no',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'admin_notes',
                            description: 'Internal notes about the admin',
                            type: 'string',
                            example: 'Important admin notes here',
                            nullable: true
                        ),
                    ]
                )
            )
        ),
        tags: ['Admins'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the admin to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', format: 'uuid',
                                    example: 'a5e49d8f-dd43-457b-8c0e-01acbb000ae6'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                                new OA\Property(property: 'firstname', type: 'string', example: 'John'),
                                new OA\Property(property: 'lastname', type: 'string', example: 'Doe'),
                                new OA\Property(property: 'language', type: 'string', example: 'en'),
                                new OA\Property(property: 'phone_number', type: 'string', example: '+11234567890',
                                    nullable: true),
                                new OA\Property(property: 'disable', type: 'boolean', example: false),
                                new OA\Property(property: 'admin_notes', type: 'string',
                                    example: 'Important admin notes here',
                                    nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time',
                                    example: '2025-05-26 10:52:11'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time',
                                    example: '2026-01-11 10:00:00'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putAdmin(Request $request, $uuid): JsonResponse
    {
        $login_admin = app('admin');

        $admin = Admin::find($uuid);

        if (empty($admin)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $locales = array_keys(config('locale.admin.locales'));

        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|unique:admins,email,'.$admin->uuid.',uuid',
            'password' => 'min:6|confirmed',
            'firstname' => 'nullable|string',
            'lastname' => 'nullable|string',
            'language' => 'nullable|in:'.implode(',', $locales),
            'groups' => 'array',
            'groups.*' => 'exists:groups,uuid',
        ], [
            'email.email' => __('error.The email must be a valid email address'),
            'email.unique' => __('error.This email is already taken'),
            'password.min' => __('error.The password must be at least 6 characters'),
            'password.confirmed' => __('error.The password confirmation does not match'),
            'firstname.string' => __('error.The firstname must be a valid string'),
            'lastname.string' => __('error.The lastname must be a valid string'),
            'language.in' => __('error.The selected language is invalid'),
            'groups.required' => __('error.Groups field is required'),
            'groups.array' => __('error.Groups must be an array'),
            'groups.*.exists' => __('error.One or more selected groups are invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('email') && ! empty($request->input('email'))) {
            $admin->email = $request->input('email');
        }

        if ($request->filled('password')) {
            $admin->password = bcrypt($request->input('password'));
        }

        if (! empty($request->input('firstname'))) {
            $admin->firstname = $request->input('firstname');
        }

        if (! empty($request->input('lastname'))) {
            $admin->lastname = $request->input('lastname');
        }

        if (! empty($request->input('language'))) {
            $admin->language = $request->input('language');
        }

        if ($request->has('phone_number') and $request->has('country_code')) {
            $tel = str_replace(' ', '', $request->input('country_code').$request->input('phone_number'));
            if (! empty($tel)) {
                $admin->phone_number = $tel;
                if (Admin::where('phone_number', $admin->phone_number)
                    ->where('uuid', '!=', $admin->uuid)
                    ->exists()) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => [__('error.This phone number is already taken')],
                    ], 422);
                }
            }
        }

        if ($request->has('disable') and $request->input('disable') == 'yes') {
            if ($login_admin->uuid == $admin->uuid) {
                return response()->json([
                    'errors' => [__('error.You can not disable yourself')],
                ], 404);
            }
            $admin->disable = true;
        }

        if ($request->has('disable') and $request->input('disable') == 'no') {
            $admin->disable = false;
        }

        if ($request->has('admin_notes')) {
            $admin->admin_notes = $request->input('admin_notes');
        }

        $admin->save();

        if ($request->has('groups')) {
            $groupUuids = $request->input('groups');
            $existingGroups = Group::whereIn('uuid', $groupUuids)->pluck('uuid')->toArray();
            $validGroups = array_intersect($groupUuids, $existingGroups);
            $admin->groups()->detach();
            foreach ($validGroups as $groupUuid) {
                $admin->addGroup($groupUuid);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.admin.delete',
        description: 'Deletes an administrator by UUID. Cannot delete yourself.',
        summary: 'Delete admin by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Admins'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the admin to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin deleted successfully',
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
    public function deleteAdmin(Request $request, $uuid): JsonResponse
    {
        $login_admin = app('admin');
        $admin = Admin::find($uuid);

        if (empty($admin)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($login_admin->uuid == $admin->uuid) {
            return response()->json([
                'errors' => [__('error.You can not remove yourself')],
            ], 404);
        }

        try {
            $deleted = $admin->delete();
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
