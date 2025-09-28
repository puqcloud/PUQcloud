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
use App\Models\User;
use App\Services\UserPermissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AdminUsersController extends Controller
{
    public function users(): View
    {
        $title = __('main.Users');

        return view_admin('users.users', compact('title'));
    }

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
