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
use App\Models\Module;
use App\Models\Notification;
use App\Models\NotificationLayout;
use App\Models\NotificationRule;
use App\Models\NotificationSender;
use App\Models\NotificationTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AdminNotificationsController extends Controller
{
    public function notificationSenders(): View
    {
        $title = __('main.Notification Senders');

        return view_admin('notification_senders.notification_senders', compact('title'));
    }

    public function getNotificationSenders(Request $request): JsonResponse
    {
        $query = NotificationSender::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('sender_name', 'like', "%{$search}%");

                        });
                    }
                })
                ->addColumn('urls', function ($notification_sender) {
                    $admin = app('admin');
                    $urls = [];
                    if ($admin->hasPermission('notification-senders-management')) {
                        $urls['web_edit'] = route('admin.web.notification_sender', $notification_sender->uuid);
                        $urls['get'] = route('admin.api.notification_sender.get', $notification_sender->uuid);
                    }

                    if ($admin->hasPermission('notification-senders-management')) {
                        $urls['post'] = route('admin.api.notification_sender.post', $notification_sender->uuid);
                    }

                    if ($admin->hasPermission('notification-senders-management')) {
                        $urls['delete'] = route('admin.api.notification_sender.delete', $notification_sender->uuid);
                    }

                    return $urls;
                })
                ->addColumn('module_data', function ($notification_sender) {
                    return $notification_sender->getModuleConfig();
                })
                ->make(true),
        ], 200);
    }

    public function getNotificationSendersSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (! empty($search)) {
            $notification_senders = NotificationSender::where('name', 'like', '%'.$search.'%')->get();
        } else {
            $notification_senders = NotificationSender::all();
        }

        $results = [];
        foreach ($notification_senders->toArray() as $notification_sender) {
            $module_name = '';
            if (! empty($notification_sender['module_data']['name'])) {
                $module_name = ' ('.$notification_sender['module_data']['name'].')';
            }
            $results[] = [
                'id' => $notification_sender['uuid'],
                'text' => $notification_sender['name'].$module_name,
            ];
        }

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function postNotificationSender(Request $request): JsonResponse
    {
        $notificationSender = new NotificationSender;
        $modules = Module::query()->where('type', 'Notification')->get()->pluck('uuid')->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:notification_senders,name',
            'module' => 'required|in:'.implode(',', $modules),
        ], [
            'name.unique' => __('error.The name is already in taken'),
            'name.required' => __('error.The name field is required'),
            'module.in' => __('error.The selected Module is invalid'),
            'module.required' => __('error.The Module field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name') && ! empty($request->input('name'))) {
            $notificationSender->name = $request->input('name');
        }
        if ($request->has('module') && ! empty($request->input('module'))) {
            $notificationSender->module_uuid = $request->input('module');
        }

        $notificationSender->configuration = json_encode([]);

        if (! empty($request->input('description'))) {
            $notificationSender->description = $request->input('description');
        }

        $notificationSender->save();
        $notificationSender->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $notificationSender,
            'redirect' => route('admin.web.notification_sender', $notificationSender->uuid),
        ]);
    }

    public function notificationSender(Request $request, $uuid): View
    {
        $title = __('main.Notification Sender');

        return view_admin('notification_senders.notification_sender', compact('title', 'uuid'));
    }

    public function getNotificationSender(Request $request, $uuid): JsonResponse
    {
        $NotificationSender = NotificationSender::find($uuid);

        if (empty($NotificationSender)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $module_html = $NotificationSender->getSettingsPage();
        $responseData = $NotificationSender->toArray();
        $responseData['module_html'] = $module_html;

        return response()->json([
            'status' => 'success',
            'data' => $responseData,
        ], 200);
    }

    public function getNotificationModulesSelect(Request $request): JsonResponse
    {
        $notificationModules = Module::all();
        $modules = [];
        foreach ($notificationModules as $module) {
            if ($module->type != 'Notification') {
                continue;
            }
            $module_name = ! empty($module->module_data['name']) ? $module->module_data['name'] : $module->name;
            $modules[] = [
                'id' => $module->uuid,
                'text' => $module_name.' ('.$module->status.')',
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredModules = array_filter($modules, function ($module) use ($searchTerm) {
            return empty($searchTerm) || stripos($module['text'], $searchTerm) !== false;
        });

        return response()->json(['data' => [
            'results' => array_values($filteredModules),
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function putNotificationSender(Request $request, $uuid): JsonResponse
    {

        $NotificationSender = NotificationSender::find($uuid);

        if (empty($NotificationSender)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'unique:notification_senders,name,'.$NotificationSender->uuid.',uuid',
        ], [
            'name.unique' => __('error.The name is already in taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name') && ! empty($request->input('name'))) {
            $NotificationSender->name = $request->input('name');
        }

        if ($request->has('description') && ! empty($request->input('description'))) {
            $NotificationSender->description = $request->input('description');
        }

        $save_module_data = $NotificationSender->saveModuleData($request->all());
        if ($save_module_data['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $save_module_data['message'],
            ], $save_module_data['code']);
        }

        $NotificationSender->save();
        $NotificationSender->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $NotificationSender,
        ]);
    }

    public function deleteNotificationSender(Request $request, $uuid): JsonResponse
    {
        $NotificationSender = NotificationSender::find($uuid);

        if (empty($NotificationSender)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $NotificationSender->delete();
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

    public function notificationLayouts(): View
    {
        $title = __('main.Notification Layouts');

        return view_admin('notification_layouts.notification_layouts', compact('title'));
    }

    public function getNotificationLayouts(Request $request): JsonResponse
    {
        $query = NotificationLayout::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($notification_layout) {
                    $admin = app('admin');
                    $urls = [];
                    if ($admin->hasPermission('notification-layouts-management')) {
                        $urls['web_edit'] = route('admin.web.notification_layout', $notification_layout->uuid);
                        $urls['get'] = route('admin.api.notification_layout.get', $notification_layout->uuid);
                    }

                    if ($admin->hasPermission('notification_layout')) {
                        $urls['post'] = route('admin.api.notification_layout.post', $notification_layout->uuid);
                    }

                    if ($admin->hasPermission('notification-layouts-management')) {
                        $urls['delete'] = route('admin.api.notification_layout.delete', $notification_layout->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postNotificationLayout(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:notification_layouts,name',
        ], [
            'name.unique' => __('error.The name is already in taken'),
            'name.required' => __('error.The name field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }
        $notificationLayout = new NotificationLayout;

        if ($request->has('name') && ! empty($request->input('name'))) {
            $notificationLayout->name = $request->input('name');
        }
        if ($request->has('description') && ! empty($request->input('description'))) {
            $notificationLayout->description = $request->input('description');
        }

        $notificationLayout->save();
        $notificationLayout->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $notificationLayout,
            'redirect' => route('admin.web.notification_layout', $notificationLayout->uuid),
        ]);
    }

    public function notificationLayout(Request $request, $uuid): View
    {
        $title = __('main.Notification Layout');
        $locales = config('locale.client.locales');

        return view_admin('notification_layouts.notification_layout', compact('title', 'uuid', 'locales'));
    }

    public function getNotificationLayout(Request $request, $uuid): JsonResponse
    {
        $notificationLayout = NotificationLayout::find($uuid);

        if (empty($notificationLayout)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        if (! empty($request->input('locale'))) {
            $notificationLayout->setLocale($request->input('locale'));
        }

        return response()->json([
            'data' => $notificationLayout,
        ]);
    }

    public function putNotificationLayout(Request $request, $uuid): JsonResponse
    {

        $notificationLayout = NotificationLayout::find($uuid);

        if (empty($notificationLayout)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'unique:notification_layouts,name,'.$notificationLayout->uuid.',uuid',
            'layout' => 'required|string',
        ], [
            'name.unique' => __('error.The name is already in taken'),
            'layout.required' => __('error.The Layout field is required'),
            'layout.string' => __('error.The Layout must be a valid string'),

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if (! empty($request->input('locale'))) {
            $notificationLayout->setLocale($request->input('locale'));
        }

        if ($request->has('name') && ! empty($request->input('name'))) {
            $notificationLayout->name = $request->input('name');
        }
        if ($request->has('layout') && ! empty($request->input('layout'))) {
            $notificationLayout->layout = $request->input('layout');
        }
        if (! empty($request->input('description'))) {
            $notificationLayout->description = $request->input('description');
        }

        $notificationLayout->save();
        $notificationLayout->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $notificationLayout,
        ]);
    }

    public function deleteNotificationLayout(Request $request, $uuid): JsonResponse
    {
        $notificationLayout = NotificationLayout::find($uuid);

        if (empty($notificationLayout)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $notificationLayout->delete();
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

    public function notificationTemplates(): View
    {
        $title = __('main.Notification Templates');

        return view_admin('notification_templates.notification_templates', compact('title'));
    }

    public function getNotificationTemplates(Request $request): JsonResponse
    {
        $query = NotificationTemplate::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('category', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($notification_template) {
                    $admin = app('admin');
                    $urls = [];
                    if ($admin->hasPermission('notification-templates-management')) {
                        $urls['web_edit'] = route('admin.web.notification_template', $notification_template->uuid);
                        $urls['get'] = route('admin.api.notification_template.get', $notification_template->uuid);
                    }

                    if ($admin->hasPermission('notification-templates-management')) {
                        $urls['post'] = route('admin.api.notification_template.post', $notification_template->uuid);
                    }

                    if ($notification_template->custom and $admin->hasPermission('notification-templates-management')) {
                        $urls['delete'] = route('admin.api.notification_template.delete', $notification_template->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postNotificationTemplate(Request $request): JsonResponse
    {

        $categories = [];
        $conf = array_merge(config('adminNotifications.categories'), config('clientNotifications.categories'));
        foreach ($conf as $value) {
            $categories[] = $value['key'];
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:notification_templates,name',
            'category' => 'required|in:'.implode(',', $categories),
        ], [
            'name.unique' => __('error.The name is already in taken'),
            'name.required' => __('error.The name field is required'),
            'category.in' => __('error.The selected Category is invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }
        $notification_template = new NotificationTemplate;

        if ($request->has('name') && ! empty($request->input('name'))) {
            $notification_template->name = $request->input('name');
        }
        if ($request->has('category') && ! empty($request->input('category'))) {
            $notification_template->category = $request->input('category');
        }

        $notification_template->custom = true;
        $notification_template->save();
        $notification_template->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $notification_template,
            'redirect' => route('admin.web.notification_template', $notification_template->uuid),
        ]);
    }

    public function notificationTemplate(Request $request, $uuid): View
    {
        $title = __('main.Notification Template');
        $locales = config('locale.client.locales');

        return view_admin('notification_templates.notification_template', compact('title', 'uuid', 'locales'));
    }

    public function getNotificationTemplate(Request $request, $uuid): JsonResponse
    {
        $notification_template = NotificationTemplate::find($uuid);

        if (empty($notification_template)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        if (! empty($request->input('locale'))) {
            $notification_template->setLocale($request->input('locale'));
        }

        return response()->json([
            'data' => $notification_template,
        ]);
    }

    public function putNotificationTemplate(Request $request, $uuid): JsonResponse
    {

        $notification_template = NotificationTemplate::find($uuid);

        if (empty($notification_template)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if (! empty($request->input('locale'))) {
            $notification_template->setLocale($request->input('locale'));
        }
        if ($request->has('subject') && ! empty($request->input('subject'))) {
            $notification_template->subject = $request->input('subject');
        }
        if ($request->has('text') && ! empty($request->input('text'))) {
            $notification_template->text = $request->input('text');
        }
        if ($request->has('text_mini') && ! empty($request->input('text_mini'))) {
            $notification_template->text_mini = $request->input('text_mini');
        }

        $notification_template->save();
        $notification_template->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $notification_template,
        ]);
    }

    public function deleteNotificationTemplate(Request $request, $uuid): JsonResponse
    {
        $notification_template = NotificationTemplate::query()->where('uuid', $uuid)->where('custom', true)->first();

        if (empty($notification_template)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $notification_template->delete();
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

    public function getNotificationCategoriesSelect(Request $request): JsonResponse
    {
        $conf = array_merge(config('adminNotifications.categories'), config('clientNotifications.categories'));
        $categories = [];
        foreach ($conf as $value) {
            $categories[] = [
                'id' => $value['key'],
                'text' => $value['name'],
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredLanguages = array_filter($categories, function ($category) use ($searchTerm) {
            return empty($searchTerm) || stripos($category['text'], $searchTerm) !== false;
        });

        return response()->json(['data' => [
            'results' => array_values($filteredLanguages),
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function getNotificationCategoryNotificationsSelect(Request $request): JsonResponse
    {
        $key = $request->input('selectedCategoryId');
        $category_notifications = [];
        $conf = array_merge(config('adminNotifications.categories'), config('clientNotifications.categories'));

        foreach ($conf as $value) {
            if ($value['key'] == $key) {
                if (empty($value['notifications'])) {
                    continue;
                }
                foreach ($value['notifications'] as $notification) {
                    $category_notifications[] = [
                        'id' => $notification['name'],
                        'text' => $notification['name'],
                    ];
                }
            }

        }

        $searchTerm = $request->get('term', '');

        $filteredLanguages = array_filter($category_notifications, function ($category) use ($searchTerm) {
            return empty($searchTerm) || stripos($category['text'], $searchTerm) !== false;
        });

        return response()->json(['data' => [
            'results' => array_values($filteredLanguages),
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function postNotificationRule(Request $request): JsonResponse
    {
        $categories = [];
        $conf = array_merge(config('adminNotifications.categories'), config('clientNotifications.categories'));

        foreach ($conf as $value) {
            $categories[$value['key']] = $value['notifications'] ?? [];
        }

        $validator = Validator::make($request->all(), [
            'category' => 'required|in:'.implode(',', array_keys($categories)),
            'notification' => 'required|string',
            'senders' => 'required|array',
            'senders.*' => 'uuid',
            'notification_layout_uuid' => 'required|uuid',
            'notification_template_uuid' => 'required|uuid',
            'group_uuid' => 'required|uuid',
        ], [
            'category.required' => __('error.Category is required'),
            'category.in' => __('error.The selected Category is invalid'),
            'notification.required' => __('error.Notification is required'),
            'notification.string' => __('error.Notification must be a string'),
            'senders.required' => __('error.Senders are required'),
            'senders.array' => __('error.Senders must be an array'),
            'senders.*.uuid' => __('error.Invalid UUID provided in senders'),
            'notification_layout_uuid.required' => __('error.Layout UUID is required'),
            'notification_layout_uuid.uuid' => __('error.Invalid UUID provided in layout'),
            'notification_template_uuid.required' => __('error.Template UUID is required'),
            'notification_template_uuid.uuid' => __('error.Invalid UUID provided in template'),
            'group_uuid.required' => __('error.Group UUID is required'),
            'group_uuid.uuid' => __('error.Invalid Group UUID provided'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $group = Group::where('uuid', $request->input('group_uuid'))->where('type', 'notification')->first();

        if (! $group) {
            return response()->json(['errors' => [__('error.Group does not exist or is not of type notification')]], 400);
        }

        $notification_layout = NotificationLayout::where('uuid', $request->input('notification_layout_uuid'))->first();

        if (! $notification_layout) {
            return response()->json(['errors' => [__('error.Notification Layout does exist')]], 400);
        }

        $notification_template = NotificationTemplate::where('uuid', $request->input('notification_template_uuid'))->where('category', $request->input('category'))->first();

        if (! $notification_template) {
            return response()->json(['errors' => [__('error.Notification template does not exist')]], 400);
        }

        $selectedCategory = $request->input('category');
        $notificationName = $request->input('notification');

        $validNotifications = array_column($categories[$selectedCategory], 'name');

        if (! in_array($notificationName, $validNotifications)) {
            return response()->json(['errors' => [__('error.Notification not valid for this category')]], 400);
        }

        $invalidSenders = array_filter($request->input('senders'), function ($uuid) {
            return ! NotificationSender::where('uuid', $uuid)->exists();
        });

        if (! empty($invalidSenders)) {
            return response()->json(['errors' => [__('error.Invalid sender UUIDs: ').implode(', ', $invalidSenders)]], 400);
        }

        if (NotificationRule::where('category', $selectedCategory)->where('notification', $notificationName)->exists()) {
            return response()->json(['errors' => [__('error.Notification rule already exists for this category and notification')]], 400);
        }

        $notification_rule = new NotificationRule;
        $notification_rule->category = $request->input('category');
        $notification_rule->notification = $request->input('notification');
        $notification_rule->group_uuid = $request->input('group_uuid');
        $notification_rule->notification_layout_uuid = $request->input('notification_layout_uuid');
        $notification_rule->notification_template_uuid = $request->input('notification_template_uuid');

        $notification_rule->save();

        $notification_rule->notificationSenders()->attach($request->input('senders'));

        $notification_rule->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $notification_rule,
        ]);
    }

    public function getNotificationRule(Request $request, $uuid): JsonResponse
    {
        $notificationRule = NotificationRule::find($uuid);

        if (empty($notificationRule)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $notificationRule,
        ], 200);

    }

    public function putNotificationRule(Request $request, $uuid): JsonResponse
    {
        $notification_rule = NotificationRule::find($uuid);

        if (empty($notification_rule)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $categories = [];
        $conf = array_merge(config('adminNotifications.categories'), config('clientNotifications.categories'));

        foreach ($conf as $value) {
            $categories[$value['key']] = $value['notifications'] ?? [];
        }

        $validator = Validator::make($request->all(), [
            'senders' => 'required|array',
            'senders.*' => 'uuid',
            'notification_layout_uuid' => 'required|uuid',
            'notification_template_uuid' => 'required|uuid',
        ], [
            'senders.required' => __('error.Senders are required'),
            'senders.array' => __('error.Senders must be an array'),
            'senders.*.uuid' => __('error.Invalid UUID provided in senders'),
            'notification_layout_uuid.required' => __('error.Layout UUID is required'),
            'notification_layout_uuid.uuid' => __('error.Invalid UUID provided in layout'),
            'notification_template_uuid.required' => __('error.Template UUID is required'),
            'notification_template_uuid.uuid' => __('error.Invalid UUID provided in template'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $notification_layout = NotificationLayout::where('uuid', $request->input('notification_layout_uuid'))->first();

        if (! $notification_layout) {
            return response()->json(['errors' => [__('error.Notification Layout does exist')]], 400);
        }

        $notification_template = NotificationTemplate::where('uuid', $request->input('notification_template_uuid'))->where('category', $request->input('category'))->first();

        if (! $notification_template) {
            return response()->json(['errors' => [__('error.Notification template does not exist')]], 400);
        }

        $invalidSenders = array_filter($request->input('senders'), function ($uuid) {
            return ! NotificationSender::where('uuid', $uuid)->exists();
        });

        if (! empty($invalidSenders)) {
            return response()->json(['errors' => [__('error.Invalid sender UUIDs: ').implode(', ', $invalidSenders)]], 400);
        }

        $notification_rule->notification_layout_uuid = $request->input('notification_layout_uuid');
        $notification_rule->notification_template_uuid = $request->input('notification_template_uuid');
        $notification_rule->save();
        $notification_rule->notificationSenders()->detach();
        $notification_rule->notificationSenders()->attach($request->input('senders'));
        $notification_rule->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $notification_rule,
        ]);
    }

    public function deleteNotificationRule(Request $request, $uuid): JsonResponse
    {
        $notificationRule = NotificationRule::find($uuid);

        if (empty($notificationRule)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $notificationRule->delete();
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

    public function postNotificationMassCreationRules(Request $request): JsonResponse
    {
        $categories = [];
        $conf = array_merge(config('adminNotifications.categories'), config('clientNotifications.categories'));

        foreach ($conf as $value) {
            $categories[$value['key']] = $value['notifications'] ?? [];
        }

        $validator = Validator::make($request->all(), [
            'category' => 'required|in:'.implode(',', array_keys($categories)),
            'senders' => 'required|array',
            'senders.*' => 'uuid',
            'notification_layout_uuid' => 'required|uuid',
            'group_uuid' => 'required|uuid',
        ], [
            'category.required' => __('error.Category is required'),
            'category.in' => __('error.The selected Category is invalid'),
            'senders.required' => __('error.Senders are required'),
            'senders.array' => __('error.Senders must be an array'),
            'senders.*.uuid' => __('error.Invalid UUID provided in senders'),
            'notification_layout_uuid.required' => __('error.Layout UUID is required'),
            'notification_layout_uuid.uuid' => __('error.Invalid UUID provided in layout'),
            'group_uuid.required' => __('error.Group UUID is required'),
            'group_uuid.uuid' => __('error.Invalid Group UUID provided'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $group = Group::where('uuid', $request->input('group_uuid'))->where('type', 'notification')->first();

        if (! $group) {
            return response()->json(['errors' => [__('error.Group does not exist or is not of type notification')]], 400);
        }

        $notification_layout = NotificationLayout::where('uuid', $request->input('notification_layout_uuid'))->first();

        if (! $notification_layout) {
            return response()->json(['errors' => [__('error.Notification Layout does exist')]], 400);
        }

        $selectedCategory = $request->input('category');

        $invalidSenders = array_filter($request->input('senders'), function ($uuid) {
            return ! NotificationSender::where('uuid', $uuid)->exists();
        });

        if (! empty($invalidSenders)) {
            return response()->json(['errors' => [__('error.Invalid sender UUIDs: ').implode(', ', $invalidSenders)]], 400);
        }

        $conf = array_merge(config('adminNotifications.categories'), config('clientNotifications.categories'));

        foreach ($conf as $category) {
            foreach ($category['notifications'] as $notification) {
                if ($selectedCategory != $category['key']) {
                    continue;
                }

                if (NotificationRule::where('category', $selectedCategory)->where('notification', $notification)->exists()) {
                    continue;
                }

                $notificationTemplate = NotificationTemplate::where('name', $notification['name'])->where('category', $category['key'])->first();
                if (empty($notificationTemplate)) {
                    continue;
                }
                $notification_rule = new NotificationRule;
                $notification_rule->category = $selectedCategory;
                $notification_rule->notification = $notification['name'];
                $notification_rule->group_uuid = $request->input('group_uuid');
                $notification_rule->notification_layout_uuid = $request->input('notification_layout_uuid');
                $notification_rule->notification_template_uuid = $notificationTemplate->uuid;
                $notification_rule->save();
                $notification_rule->notificationSenders()->attach($request->input('senders'));
                $notification_rule->refresh();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
        ]);
    }

    public function getNotificationLayoutsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (! empty($search)) {
            $layout = NotificationLayout::where('name', 'like', '%'.$search.'%')->get();
        } else {
            $layout = NotificationLayout::all();
        }

        $formattedLayout = $layout->map(function ($layout) {
            return [
                'id' => $layout->uuid,
                'text' => $layout->name,
            ];
        });

        return response()->json(['data' => [
            'results' => $formattedLayout,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function getNotificationTemplatesSelect(Request $request): JsonResponse
    {
        $key = $request->input('selectedCategoryId');

        $search = $request->input('q');

        if (! empty($search)) {
            $template = NotificationTemplate::where('category', $key)->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $template = NotificationTemplate::where('category', $key)->get();
        }

        $formattedTemplate = $template->map(function ($template) {
            return [
                'id' => $template->uuid,
                'text' => $template->name,
            ];
        });

        return response()->json(['data' => [
            'results' => $formattedTemplate,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function notificationHistories(): View
    {
        $title = __('main.Notification History');

        return view_admin('notification_histories.notification_histories', compact('title'));
    }

    public function getNotificationHistories(Request $request): JsonResponse
    {
        $query = Notification::query()
            // ->with('notificationstatus')
            ->select([
                'notifications.uuid',
                'notifications.subject',
                'notifications.text_mini',
                'notifications.created_at',
                'notifications.model_type',
                'notifications.model_uuid',
                'notifications.created_at',
            ]);

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->start_date);
            $endDate = Carbon::createFromFormat('d-m-Y H:i:s', $request->end_date);
            $query->whereBetween('notifications.created_at', [$startDate, $endDate]);
        }

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('notifications.created_at', 'like', "%{$search}%")
                                ->orWhere('notifications.subject', 'like', "%{$search}%")
                                ->orWhere('notifications.text_mini', 'like', "%{$search}%")
                                ->orWhere('notifications.model_uuid', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($notification) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('notification-history-view')) {
                        if (! empty($notification->uuid)) {
                            $urls['get'] = route('admin.api.notification_history.get', $notification->uuid);
                        }
                    }

                    return $urls;
                })
                ->order(function ($query) use ($request) {
                    if ($request->has('order')) {
                        $order = $request->order[0]['column'];
                        $dir = $request->order[0]['dir'];
                        if ($order == 0) {
                            $query->orderBy('notifications.created_at', $dir);
                        }
                    }
                })
                ->make(true),
        ], 200);
    }

    public function getNotificationHistory(Request $request, $uuid): JsonResponse
    {
        $notification_history = Notification::find($uuid);

        if (empty($notification_history)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $responseData = $notification_history->toArray();

        return response()->json([
            'data' => $responseData,
        ]);
    }
}
