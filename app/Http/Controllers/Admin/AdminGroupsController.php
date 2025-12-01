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
use App\Models\Group;
use App\Models\NotificationRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AdminGroupsController extends Controller
{
    public function groups(): View
    {
        $title = __('main.Groups');

        return view_admin('groups.groups', compact('title'));
    }

    public function getGroups(Request $request): JsonResponse
    {
        $query = Group::query();
        $groups_types = config('groups.types');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%")
                                ->orWhere('type', 'like', "%{$search}%")
                                ->orWhere('related', 'like', "%{$search}%");

                        });
                    }
                })
                ->addColumn('urls', function ($group) {
                    $admin = app('admin');
                    $urls = [];

                    if ($admin->hasPermission('groups-view')) {
                        $urls['edit'] = route('admin.web.group', $group->uuid);
                        $urls['get'] = route('admin.api.group.get', $group->uuid);
                    }

                    if ($admin->hasPermission('groups-edit')) {
                        $urls['post'] = route('admin.api.group.post', $group->uuid);
                    }

                    if ($admin->hasPermission('groups-delete')) {
                        $urls['delete'] = route('admin.api.group.delete', $group->uuid);
                    }

                    return $urls;
                })
                ->addColumn('type_data', function ($group) use ($groups_types) {
                    foreach ($groups_types as $grouptype) {
                        if ($group->type == $grouptype['key']) {

                            $grouptype['name'] = __('main.'.$grouptype['name']);
                            $grouptype['description'] = __('main.'.$grouptype['description']);

                            return $grouptype;
                        }
                    }

                    return [];
                })
                ->make(true),
        ], 200);
    }

    public function getGroupsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (! empty($search)) {
            $groups = Group::where('name', 'like', '%'.$search.'%')->get();
        } else {
            $groups = Group::all();
        }

        $formattedGroups = $groups->map(function ($group) {
            return [
                'id' => $group->uuid,
                'text' => $group->name,
            ];
        });

        return response()->json(['data' => [
            'results' => $formattedGroups,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function group(Request $request, $uuid): View
    {
        $title = __('main.Group');
        $group = Group::find($uuid);

        try {
            if (! empty($group)) {
                $viewName = 'groups.group-'.$group->type;

                return view_admin($viewName, compact('title', 'uuid'));
            }
        } catch (\Exception $e) {
            Log::error('View not found: '.$e->getMessage());
        }

        return view_admin('groups.group', compact('title', 'uuid'));
    }

    public function getGroupTypesSelect(Request $request): JsonResponse
    {
        $groupsTypes = config('groups.types');
        usort($groupsTypes, fn ($a, $b) => $a['order'] <=> $b['order']);

        $searchTerm = $request->get('term', '');

        $filteredTypes = array_filter($groupsTypes, function ($type) use ($searchTerm) {
            return empty($searchTerm) || stripos($type['name'], $searchTerm) !== false;
        });

        $types = [];
        foreach ($filteredTypes as $type) {
            $types[] = [
                'id' => $type['key'],
                'text' => __('main.'.$type['name']),
                'description' => __('main.'.$type['description']),
            ];
        }

        return response()->json(
            ['data' => [
                'results' => $types,
                'pagination' => [
                    'more' => false,
                ],
            ]]
        );
    }

    public function getGroup(Request $request, $uuid): JsonResponse
    {
        $group = Group::find($uuid);

        if (empty($group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $type_data = [];
        foreach (config('groups.types') as $type) {
            if ($type['key'] == $group->type) {
                $type_data = [
                    'key' => $type['key'],
                    'name' => __('main.'.$type['name']),
                    'description' => __('main.'.$type['description']),
                ];
            }
        }

        $responseData = $group->toArray();
        $responseData['type_data'] = $type_data;

        // groups ------------------------------------------------------------------------------------------------------
        if ($group['type'] == 'groups') {
            $responseData['all_groups'] = $group->getAllGroups();
        }
        // system ------------------------------------------------------------------------------------------------------
        if ($group['type'] == 'system') {
            $responseData['system_permissions'] = $group->getSystemPermissions();
        }
        // adminTemplate -----------------------------------------------------------------------------------------------
        if ($group['type'] == 'adminTemplate') {
            $responseData['adminTemplate_permissions'] = $group->getAdminTemplatePermissions();
        }
        // clientTemplate -----------------------------------------------------------------------------------------------
        if ($group['type'] == 'clientTemplate') {
            $responseData['clientTemplate_permissions'] = $group->getClientTemplatePermissions();
        }
        // Modules Permissions------------------------------------------------------------------------------------------
        if ($group['type'] == 'modules') {
            $responseData['modules_permissions'] = $group->getModulesPermissions();
        }

        return response()->json([
            'data' => $responseData,
        ]);
    }

    public function postGroup(Request $request): JsonResponse
    {
        $group = new Group;
        $groupsTypes = config('groups.types');
        $types = [];
        foreach ($groupsTypes as $type) {
            $types[] = $type['key'];
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:groups,name',
            'type' => 'required|in:'.implode(',', $types),

        ], [
            'name.unique' => __('error.The name is already in taken'),
            'name.required' => __('error.The name field is required'),
            'type.in' => __('error.The selected Type is invalid'),
            'type.required' => __('error.The type field is required'),

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name') && ! empty($request->input('name'))) {
            $group->name = $request->input('name');
        }

        if (! empty($request->input('type'))) {
            $group->type = $request->input('type');
        }

        if (! empty($request->input('description'))) {
            $group->description = $request->input('description');
        }
        $group->related = '';

        $group->save();
        $group->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $group,
            'redirect' => route('admin.web.group', $group->uuid),
        ]);
    }

    public function putGroup(Request $request, $uuid): JsonResponse
    {
        $group = Group::find($uuid);
        $admin_permission = app('AdminPermission');
        if (empty($group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:groups,name,'.$group->uuid.',uuid',
        ], [
            'name.unique' => __('error.The name is already in taken'),
            'name.required' => __('error.The name field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name') && ! empty($request->input('name'))) {
            $group->name = $request->input('name');
        }

        if (! empty($request->input('description'))) {
            $group->description = $request->input('description');
        }

        $group->save();
        $group->refresh();

        // Groups ------------------------------------------------------------------------------------------------------
        if ($group->type == 'groups') {
            if ($request->has('groups')) {
                foreach ($request->groups as $request_group_uuid => $request_group) {
                    $relatedGroup = Group::find($request_group_uuid);
                    if (! empty($relatedGroup)) {
                        if ($request_group == 'yes') {
                            $group->addRelatedGroup($relatedGroup);
                        } else {
                            $group->removeRelatedGroup($relatedGroup);
                        }
                    }
                }
            }
        }

        // System ------------------------------------------------------------------------------------------------------
        if ($group->type == 'system') {
            if ($request->has('system_permissions')) {
                foreach ($request->system_permissions as $key => $value) {
                    if ($admin_permission->permissionExists($key)) {
                        if ($value == 'yes') {
                            $group->addPermission($key);
                        } else {
                            $group->removePermission($key);
                        }
                    }
                }
            }
        }
        // adminTemplate -----------------------------------------------------------------------------------------------
        if ($group->type == 'adminTemplate') {
            if ($request->has('adminTemplate_permissions')) {
                foreach ($request->adminTemplate_permissions as $key => $value) {
                    if ($admin_permission->permissionExists($key)) {
                        if ($value == 'yes') {
                            $group->addPermission($key);
                        } else {
                            $group->removePermission($key);
                        }
                    }
                }
            }
        }
        // clientTemplate -----------------------------------------------------------------------------------------------
        if ($group->type == 'clientTemplate') {
            if ($request->has('clientTemplate_permissions')) {
                foreach ($request->clientTemplate_permissions as $key => $value) {
                    if ($admin_permission->permissionExists($key)) {
                        if ($value == 'yes') {
                            $group->addPermission($key);
                        } else {
                            $group->removePermission($key);
                        }
                    }
                }
            }
        }
        // modules ------------------------------------------------------------------------------------------------------
        if ($group->type == 'modules') {
            if ($request->has('modules_permissions')) {
                foreach ($request->modules_permissions as $key => $value) {
                    if ($admin_permission->permissionExists($key)) {
                        if ($value == 'yes') {
                            $group->addPermission($key);
                        } else {
                            $group->removePermission($key);
                        }
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $group,
        ]);
    }

    public function deleteGroup(Request $request, $uuid): JsonResponse
    {
        $group = Group::find($uuid);

        if (empty($group)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $group->delete();
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

    public function getGroupNotificationRules(Request $request, $uuid): JsonResponse
    {
        $query = NotificationRule::query()
            ->where('group_uuid', $uuid)->get();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('category', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('notification_name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($notification_rule) {
                    $admin = app('admin');
                    $urls = [];

                    if ($admin->hasPermission('groups-edit') && $admin->hasPermission('notification-rules-management')) {
                        $urls['post'] = route('admin.api.notification_rule.post', $notification_rule->uuid);
                        $urls['get'] = route('admin.api.notification_rule.get', $notification_rule->uuid);
                        $urls['put'] = route('admin.api.notification_rule.put', $notification_rule->uuid);
                        $urls['delete'] = route('admin.api.notification_rule.delete', $notification_rule->uuid);
                    }

                    return $urls;
                })
                ->addColumn('notification_senders', function ($notification_rule) {
                    return $notification_rule->notificationsenders->pluck('name')->toArray();
                })
                ->addColumn('notification_template', function ($notification_rule) {
                    return $notification_rule->notificationtemplate->where('uuid', $notification_rule->notification_template_uuid)->pluck('name')->toArray();
                })
                ->addColumn('notification_layout', function ($notification_rule) {
                    return $notification_rule->notificationlayout->where('uuid', $notification_rule->notification_layout_uuid)->pluck('name')->toArray();
                })
                ->make(true),
        ], 200);
    }
}
