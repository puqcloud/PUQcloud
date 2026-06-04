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

namespace Template\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use OpenApi\Attributes as OA;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    public function profile(): View|Factory|Application
    {
        $title = __('main.My Account');

        return view_client('user.profile', compact('title'));
    }

    #[OA\Get(
        path: 'client.api.user.profile.get',
        description: 'Returns the current authenticated user profile information including personal details and language preferences.',
        summary: 'Get user profile',
        security: [['apiKeyAuth' => []]],
        tags: ['User Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User profile returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'email',
                                    description: 'User email address',
                                    type: 'string',
                                    format: 'email',
                                    example: 'puqcloud@gmail.com'
                                ),
                                new OA\Property(
                                    property: 'phone_number',
                                    description: 'User phone number',
                                    type: 'string',
                                    example: '1234567890',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'firstname',
                                    description: 'User first name',
                                    type: 'string',
                                    example: 'Ruslan'
                                ),
                                new OA\Property(
                                    property: 'lastname',
                                    description: 'User last name',
                                    type: 'string',
                                    example: 'Polovyi'
                                ),
                                new OA\Property(
                                    property: 'email_verified',
                                    description: 'Email verification status (0 = not verified, 1 = verified)',
                                    type: 'integer',
                                    example: 1
                                ),
                                new OA\Property(
                                    property: 'language',
                                    description: 'User preferred language code',
                                    type: 'string',
                                    example: 'en'
                                ),
                                new OA\Property(
                                    property: 'language_data',
                                    description: 'Detailed language information',
                                    properties: [
                                        new OA\Property(
                                            property: 'id',
                                            description: 'Language code',
                                            type: 'string',
                                            example: 'en'
                                        ),
                                        new OA\Property(
                                            property: 'text',
                                            description: 'Language display name',
                                            type: 'string',
                                            example: 'English (English)'
                                        ),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getProfile(): JsonResponse
    {
        $user = app('user');

        $allowedKeys = [
            'firstname',
            'lastname',
            'language',
            'email',
            'phone_number',
            'email_verified',
        ];

        $filteredUserData = collect($user->toArray())
            ->only($allowedKeys)
            ->toArray();

        $language = [];

        foreach (config('locale.client.locales') as $key => $value) {
            if ($key == $user->language) {
                $language = [
                    'id' => $key,
                    'text' => $value['name'].' ('.$value['native'].')',
                ];
            }
        }

        $filteredUserData['language_data'] = $language;

        return response()->json([
            'data' => $filteredUserData,
        ], 200);
    }

    #[OA\Put(
        path: 'client.api.user.profile.put',
        description: 'Updates the current authenticated user profile information. Requires existing password confirmation for security. All fields are optional except existing password.',
        summary: 'Update user profile',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['existingpw'],
                    properties: [
                        new OA\Property(
                            property: 'email',
                            description: 'User email address (must be unique)',
                            type: 'string',
                            format: 'email',
                            example: 'newemail@example.com',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'firstname',
                            description: 'User first name',
                            type: 'string',
                            example: 'John',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'lastname',
                            description: 'User last name',
                            type: 'string',
                            example: 'Doe',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'language',
                            description: 'User preferred language (must be one of available locales)',
                            type: 'string',
                            example: 'en',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'phone_number',
                            description: 'Phone number (optional, requires country_code)',
                            type: 'string',
                            example: '1234567890',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'country_code',
                            description: 'Country code for phone number (optional, requires phone_number)',
                            type: 'string',
                            example: '+1',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'existingpw',
                            description: 'Current password for verification (required)',
                            type: 'string',
                            format: 'password',
                            example: 'currentpassword123'
                        ),
                    ]
                )
            )
        ),
        tags: ['User Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully',
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
    public function putProfile(Request $request): JsonResponse
    {
        $user = app('user');
        $locales = array_keys(config('locale.client.locales'));

        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|unique:users,email,'.$user->uuid.',uuid',
            'firstname' => 'nullable|string',
            'lastname' => 'nullable|string',
            'language' => 'nullable|in:'.implode(',', $locales),
        ], [
            'email.email' => __('error.The email must be a valid email address'),
            'email.unique' => __('error.This email is already taken'),
            'firstname.string' => __('error.The firstname must be a valid string'),
            'lastname.string' => __('error.The lastname must be a valid string'),
            'language.in' => __('error.The selected language is invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('email') && ! empty($request->input('email'))) {
            $user->email = $request->input('email');
        }

        if (! empty($request->input('firstname'))) {
            $user->firstname = $request->input('firstname');
        }

        if (! empty($request->input('lastname'))) {
            $user->lastname = $request->input('lastname');
        }

        if (! empty($request->input('language'))) {
            $user->language = $request->input('language');
        }

        if ($request->has('phone_number') and $request->has('country_code')) {
            $tel = str_replace(' ', '', $request->input('country_code').$request->input('phone_number'));
            if (! empty($tel)) {
                $user->phone_number = $tel;
                if (User::where('phone_number', $user->phone_number)
                    ->where('uuid', '!=', $user->uuid)
                    ->exists()) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => [__('error.This phone number is already taken')],
                    ], 422);
                }
            }
        }

        if (! Hash::check($request->existingpw, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => ['existingpw' => [__('error.Existing password is incorrect')]],
            ],
                422);
        }

        if ($user->status === 'new') {
            $user->status = 'active';
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    public function changePassword(): View|Factory|Application
    {
        $title = __('main.Change Password');

        return view_client('user.change_password', compact('title'));
    }

    #[OA\Put(
        path: 'client.api.user.change_password.put',
        description: 'Changes the current authenticated user password. Requires existing password confirmation and validates new password strength (minimum 6 characters, must include a number and a special character).',
        summary: 'Change user password',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['existingpw', 'newpw', 'confirmpw'],
                    properties: [
                        new OA\Property(
                            property: 'existingpw',
                            description: 'Current password for verification',
                            type: 'string',
                            format: 'password',
                            example: 'oldPassword123!'
                        ),
                        new OA\Property(
                            property: 'newpw',
                            description: 'New password (minimum 6 characters, must include a number and a special character)',
                            type: 'string',
                            format: 'password',
                            example: 'newPassword123!'
                        ),
                        new OA\Property(
                            property: 'confirmpw',
                            description: 'Confirm new password (must match newpw)',
                            type: 'string',
                            format: 'password',
                            example: 'newPassword123!'
                        ),
                    ]
                )
            )
        ),
        tags: ['User Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password changed successfully',
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
    public function putChangePassword(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'existingpw' => 'required',
            'newpw' => ['required', 'string', 'min:6', 'regex:/[0-9]/', 'regex:/[!@#$%^&*(),.?":{}|<>]/'],
            'confirmpw' => 'required|same:newpw',
        ], [
            'existingpw.required' => __('error.Existing password is required'),
            'newpw.required' => __('error.New password is required'),
            'newpw.min' => __('error.Password must be at least 6 characters'),
            'newpw.regex' => __('error.Password must include a number and a special character'),
            'confirmpw.required' => __('error.Confirm password is required'),
            'confirmpw.same' => __('error.Passwords must match'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $user = app('user');

        if (! Hash::check($request->existingpw, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => ['existingpw' => [__('error.Existing password is incorrect')]],
            ],
                422);
        }

        $user->password = Hash::make($request->newpw);
        $user->save();

        app(\App\Services\HookService::class)->callHooks('ClientChangePassword', ['user' => $user]);

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);

    }

    public function verificationCenter(): View|Factory|Application
    {
        $title = __('main.Verification Center');

        return view_client('user.verification_center', compact('title'));
    }

    #[OA\Get(
        path: 'client.api.user.verifications.get',
        description: 'Returns a list of user verification methods (email, phone, etc.) with DataTables support for pagination, search, and filtering.',
        summary: 'Get user verifications',
        security: [['apiKeyAuth' => []]],
        tags: ['User Profile'],
        parameters: [
            new OA\Parameter(
                name: 'search[value]',
                description: 'Search term to filter verifications by type, value, or device name',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'email'
            ),
            new OA\Parameter(
                name: 'start',
                description: 'DataTables start position for pagination',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 0
            ),
            new OA\Parameter(
                name: 'length',
                description: 'DataTables number of records per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 10
            ),
            new OA\Parameter(
                name: 'draw',
                description: 'DataTables draw counter',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verifications list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'headers',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: []
                                ),
                                new OA\Property(
                                    property: 'original',
                                    properties: [
                                        new OA\Property(
                                            property: 'draw',
                                            description: 'DataTables draw counter',
                                            type: 'integer',
                                            example: 1
                                        ),
                                        new OA\Property(
                                            property: 'recordsTotal',
                                            description: 'Total number of records',
                                            type: 'integer',
                                            example: 2
                                        ),
                                        new OA\Property(
                                            property: 'recordsFiltered',
                                            description: 'Number of records after filtering',
                                            type: 'integer',
                                            example: 1
                                        ),
                                        new OA\Property(
                                            property: 'data',
                                            description: 'Array of verification records',
                                            type: 'array',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(
                                                        property: 'uuid',
                                                        type: 'string',
                                                        format: 'uuid',
                                                        example: '0119a655-cf9c-49fd-99b2-37bd4553734a'
                                                    ),
                                                    new OA\Property(
                                                        property: 'type',
                                                        description: 'Verification type (email, phone, etc.)',
                                                        type: 'string',
                                                        example: 'email'
                                                    ),
                                                    new OA\Property(
                                                        property: 'device_name',
                                                        description: 'Device name for this verification',
                                                        type: 'string',
                                                        example: 'system',
                                                        nullable: true
                                                    ),
                                                    new OA\Property(
                                                        property: 'value',
                                                        description: 'Verification value (email address, phone number, etc.)',
                                                        type: 'string',
                                                        example: 'puqcloud@gmail.com'
                                                    ),
                                                    new OA\Property(
                                                        property: 'verified',
                                                        description: 'Verification status (0 = not verified, 1 = verified)',
                                                        type: 'integer',
                                                        example: 1
                                                    ),
                                                    new OA\Property(
                                                        property: 'default',
                                                        description: 'Is this the default verification method (0 = no, 1 = yes)',
                                                        type: 'integer',
                                                        example: 0
                                                    ),
                                                    new OA\Property(
                                                        property: 'last_used_at',
                                                        description: 'Last usage timestamp',
                                                        type: 'string',
                                                        format: 'date-time',
                                                        example: '2025-06-28 15:36:03',
                                                        nullable: true
                                                    ),
                                                    new OA\Property(
                                                        property: 'urls',
                                                        description: 'Available action URLs for this verification',
                                                        properties: [
                                                            new OA\Property(
                                                                property: 'get',
                                                                description: 'URL to get verification details',
                                                                type: 'string',
                                                                example: 'https://dev.puqcloud.com/api/user/verification/0119a655-cf9c-49fd-99b2-37bd4553734a'
                                                            ),
                                                            new OA\Property(
                                                                property: 'verify',
                                                                description: 'URL to verify (only present if not verified)',
                                                                type: 'string',
                                                                example: 'https://dev.puqcloud.com/api/user/verification/0119a655-cf9c-49fd-99b2-37bd4553734a/verify',
                                                                nullable: true
                                                            ),
                                                            new OA\Property(
                                                                property: 'delete',
                                                                description: 'URL to delete verification',
                                                                type: 'string',
                                                                example: 'https://dev.puqcloud.com/api/user/verification/0119a655-cf9c-49fd-99b2-37bd4553734a'
                                                            ),
                                                        ],
                                                        type: 'object'
                                                    ),
                                                ],
                                                type: 'object'
                                            )
                                        ),
                                        new OA\Property(
                                            property: 'disableOrdering',
                                            description: 'DataTables ordering disabled flag',
                                            type: 'boolean',
                                            example: false
                                        ),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'exception',
                                    description: 'Exception information if any',
                                    type: 'string',
                                    example: null,
                                    nullable: true
                                ),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getVerifications(Request $request): JsonResponse
    {
        $user = app('user');

        $allowedKeys = [
            'uuid',
            'type',
            'device_name',
            'value',
            'verified',
            'default',
            'last_used_at',
        ];

        $query = $user->verifications()->select($allowedKeys);

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('type', 'like', "%{$search}%")
                                ->orWhere('value', 'like', "%{$search}%")
                                ->orWhere('device_name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($query) {
                    $urls = [];
                    $urls['get'] = route('client.api.user.verification.get', ['uuid' => $query->uuid]);
                    if (! $query->verified) {
                        $urls['verify'] = route('client.api.user.verification.verify.get', ['uuid' => $query->uuid]);
                    }
                    $urls['delete'] = route('client.api.user.verification.delete', ['uuid' => $query->uuid]);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Get(
        path: 'client.api.user.verification.get',
        description: 'Sends a confirmation verification code to the specified verified verification method.',
        summary: 'Send confirmation code to verification',
        security: [['apiKeyAuth' => []]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the verification',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification code sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Verification not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Not found')
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getVerification(Request $request, $uuid): JsonResponse
    {
        $user = app('user');
        $verification = $user->verifications()->where('uuid', $uuid)->where('verified', true)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $generate_code = $verification->confirmationSendVerificationCode();

        return response()->json([
            'status' => 'success',
            'data' => $generate_code,
        ]);
    }

    #[OA\Get(
        path: 'client.api.user.verification.verify.get',
        description: 'Sends a verification code to the specified unverified verification method for verification purposes.',
        summary: 'Send verify code to unverified verification',
        security: [['apiKeyAuth' => []]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the verification',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification code sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Verification not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Not found')
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getVerificationVerify(Request $request, $uuid): JsonResponse
    {
        $user = app('user');
        $verification = $user->verifications()->where('uuid', $uuid)->where('verified', false)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $generate_code = $verification->verifySendVerificationCode();

        return response()->json([
            'status' => 'success',
            'data' => $generate_code,
        ]);
    }

    #[OA\Post(
        path: 'client.api.user.verification.verify.post',
        description: 'Verifies a verification method by submitting the received verification code.',
        summary: 'Verify a verification method',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['uuid', 'code'],
                    properties: [
                        new OA\Property(
                            property: 'uuid',
                            description: 'UUID of the verification to verify',
                            type: 'string',
                            format: 'uuid',
                            example: '0119a655-cf9c-49fd-99b2-37bd4553734a'
                        ),
                        new OA\Property(
                            property: 'code',
                            description: 'Verification code received',
                            type: 'string',
                            example: '123456'
                        ),
                    ]
                )
            )
        ),
        tags: ['User'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification verified successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Verification not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Not found')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Verification failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Verification failed')
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postVerificationVerify(Request $request): JsonResponse
    {
        $user = app('user');
        $uuid = $request->get('uuid');
        $code = $request->get('code');
        $verification = $user->verifications()->where('uuid', $uuid)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verify = $verification->verifyVerifyCode($code);

        if (! $verify) {
            return response()->json([
                'errors' => [__('error.Verification failed')],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Post(
        path: 'client.api.user.verification.default.post',
        description: 'Sets a verification method as the default after confirming with a verification code.',
        summary: 'Set verification as default',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['uuid', 'code'],
                    properties: [
                        new OA\Property(
                            property: 'uuid',
                            description: 'UUID of the verification to set as default',
                            type: 'string',
                            format: 'uuid',
                            example: '0119a655-cf9c-49fd-99b2-37bd4553734a'
                        ),
                        new OA\Property(
                            property: 'code',
                            description: 'Confirmation verification code',
                            type: 'string',
                            example: '123456'
                        ),
                    ]
                )
            )
        ),
        tags: ['User'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Default verification set successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Verification not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Not found')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Verification failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Verification failed')
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postVerificationDefault(Request $request): JsonResponse
    {
        $user = app('user');
        $uuid = $request->get('uuid');
        $code = $request->get('code');
        $verification = $user->verifications()->where('uuid', $uuid)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verify = $verification->confirmationVerifyCode($code);

        if (! $verify) {
            return response()->json([
                'errors' => [__('error.Verification failed')],
            ], 422);
        }

        $verification->makeDefault();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Delete(
        path: 'client.api.user.verification.delete',
        description: 'Deletes a verification method after confirming with the default verification method code.',
        summary: 'Delete a verification',
        security: [['apiKeyAuth' => []]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the verification',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['code'],
                    properties: [
                        new OA\Property(
                            property: 'code',
                            description: 'Confirmation code from the default verification method',
                            type: 'string',
                            example: '123456'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Verification not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Not found')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Verification failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Verification failed')
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteVerification(Request $request, $uuid): JsonResponse
    {
        $user = app('user');
        $code = $request->get('code');
        $verification = $user->verifications()->where('default', true)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verify = $verification->confirmationVerifyCode($code);

        if (! $verify) {
            return response()->json([
                'errors' => [__('error.Verification failed')],
            ], 422);
        }

        $verification = $user->verifications()->where('uuid', $uuid)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verification->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    /**
     * @throws TwoFactorAuthException
     */
    #[OA\Get(
        path: 'client.api.user.verification.totp.add.get',
        description: 'Generates a new TOTP secret and returns a QR code data URI for adding a TOTP authenticator device.',
        summary: 'Get TOTP setup data',
        security: [['apiKeyAuth' => []]],
        tags: ['User'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'TOTP setup data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'code',
                                    description: 'TOTP secret key',
                                    type: 'string',
                                    example: 'JBSWY3DPEHPK3PXP'
                                ),
                                new OA\Property(
                                    property: 'qrcode_uri',
                                    description: 'QR code as a data URI for scanning with authenticator app',
                                    type: 'string',
                                    example: 'data:image/png;base64,...'
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
    public function getVerificationTotpAdd(Request $request): JsonResponse
    {
        $user = app('user');
        $host = parse_url(env('APP_URL'), PHP_URL_HOST);
        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider, $host ?? 'PUQcloud');
        $secret = $tfa->createSecret();

        session(['totp_secret' => $secret]);

        $qrCodeDataUri = $tfa->getQRCodeImageAsDataUri($user->email, $secret);

        return response()->json([
            'status' => 'success',
            'data' => [
                'code' => $secret,
                'qrcode_uri' => $qrCodeDataUri,
            ],
        ]);
    }

    #[OA\Post(
        path: 'client.api.user.verification.totp.add.post',
        description: 'Verifies the TOTP code and adds the TOTP device as a verification method.',
        summary: 'Add TOTP verification device',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['code', 'device_name'],
                    properties: [
                        new OA\Property(
                            property: 'code',
                            description: 'TOTP 6-digit verification code from authenticator app',
                            type: 'string',
                            example: '123456'
                        ),
                        new OA\Property(
                            property: 'device_name',
                            description: 'Name for the TOTP device',
                            type: 'string',
                            example: 'My Phone'
                        ),
                    ]
                )
            )
        ),
        tags: ['User'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'TOTP device added successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Saved successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid code or no secret found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string', example: 'No secret found. Try again.')),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postVerificationTotpAdd(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|digits:6',
            'device_name' => 'required',
        ], [
            'code.required' => __('error.Verify Key is required'),
            'code.digits' => __('error.Verify Key must be 6 digits'),
            'device_name.required' => __('error.Device Name is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $user = app('user');
        $host = parse_url(env('APP_URL'), PHP_URL_HOST);
        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider, $host ?? 'PUQcloud');

        $secret = session('totp_secret');
        if (! $secret) {
            return response()->json(
                [
                    'status' => 'error',
                    'errors' => [__('error.No secret found. Try again.')],
                ],
                400);
        }

        $isValid = $tfa->verifyCode($secret, $request->input('code'));

        if ($isValid) {
            $verification = new Verification;
            $verification->user_uuid = $user->uuid;
            $verification->secret = session('totp_secret');
            $verification->type = 'totp';
            $verification->value = $request->get('device_name');
            $verification->device_name = $request->get('device_name');
            $verification->last_used_at = now();
            $verification->verified = true;
            $verification->save();

            return response()->json(['status' => 'success', 'message' => __('message.Saved successfully')]);
        }

        return response()->json([
            'status' => 'error',
            'message' => ['code' => [__('error.Invalid code')]],
        ], 400);
    }

    public function twoFactorAuthentication(): View|Factory|Application
    {
        $title = __('main.Two Factor Authentication');

        return view_client('user.two_factor_authentication', compact('title'));
    }

    #[OA\Post(
        path: 'client.api.user.two_factor_authentication.enable.post',
        description: 'Enables two-factor authentication for the user after confirming with the default verification method code.',
        summary: 'Enable two-factor authentication',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['code'],
                    properties: [
                        new OA\Property(
                            property: 'code',
                            description: 'Confirmation code from the default verification method',
                            type: 'string',
                            example: '123456'
                        ),
                    ]
                )
            )
        ),
        tags: ['User'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Two-factor authentication enabled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Default verification not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Not found')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Verification failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Verification failed')
                        ),
                        new OA\Property(property: 'message', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postTwoFactorAuthenticationEnable(Request $request): JsonResponse
    {
        $user = app('user');
        $code = $request->get('code');
        $verification = $user->verifications()->where('default', true)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verify = $verification->confirmationVerifyCode($code);

        if (! $verify) {
            return response()->json([
                'errors' => [__('error.Verification failed')],
                'message' => ['code' => [__('error.Invalid code')]],
            ], 422);
        }

        $user->two_factor = true;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    #[OA\Post(
        path: 'client.api.user.two_factor_authentication.disable.post',
        description: 'Disables two-factor authentication for the user after confirming with the default verification method code.',
        summary: 'Disable two-factor authentication',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['code'],
                    properties: [
                        new OA\Property(
                            property: 'code',
                            description: 'Confirmation code from the default verification method',
                            type: 'string',
                            example: '123456'
                        ),
                    ]
                )
            )
        ),
        tags: ['User'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Two-factor authentication disabled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Default verification not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Not found')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Verification failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'Verification failed')
                        ),
                        new OA\Property(property: 'message', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postTwoFactorAuthenticationDisable(Request $request): JsonResponse
    {
        $user = app('user');
        $code = $request->get('code');
        $verification = $user->verifications()->where('default', true)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verify = $verification->confirmationVerifyCode($code);

        if (! $verify) {
            return response()->json([
                'errors' => [__('error.Verification failed')],
                'message' => ['code' => [__('error.Invalid code')]],
            ], 422);
        }

        $user->two_factor = false;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }
}
