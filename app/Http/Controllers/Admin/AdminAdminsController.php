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
use App\Models\Admin;
use App\Models\Group;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AdminAdminsController extends Controller
{
    public function admins(): View
    {
        $title = __('main.Administrators');

        return view_admin('admins.admins', compact('title'));
    }

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
                        $urls['web_edit'] = route('admin.web.admin', $admin->uuid);
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

    public function admin(Request $request, $uuid): View
    {
        $title = __('main.Edit Administrator');

        return view_admin('admins.admin', compact('title', 'uuid'));
    }

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
            'groups' => 'required|array',
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
            'data' => $admin,
        ]);
    }

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
