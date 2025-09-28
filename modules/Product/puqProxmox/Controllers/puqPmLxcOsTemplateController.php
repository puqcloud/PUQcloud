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

namespace Modules\Product\puqProxmox\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\PuqPmLxcOsTemplate;
use Modules\Product\puqProxmox\Models\PuqPmLxcPreset;
use Yajra\DataTables\DataTables;

class puqPmLxcOsTemplateController extends Controller
{
    public function lxcOsTemplates(Request $request): View
    {
        $title = __('Product.puqProxmox.LXC OS Templates');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.lxc_os_templates.lxc_os_templates',
            compact('title'));
    }

    public function getLxcOsTemplates(Request $request): JsonResponse
    {
        $query = PuqPmLxcOsTemplate::query()
            ->leftJoin('puq_pm_lxc_templates', 'puq_pm_lxc_os_templates.puq_pm_lxc_template_uuid', '=',
                'puq_pm_lxc_templates.uuid')
            ->select('puq_pm_lxc_os_templates.*', 'puq_pm_lxc_templates.name as template_name');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('puq_pm_lxc_os_templates.key', 'like', "%{$search}%")
                                ->orWhere('puq_pm_lxc_os_templates.distribution', 'like', "%{$search}%")
                                ->orWhere('puq_pm_lxc_os_templates.version', 'like', "%{$search}%")
                                ->orWhere('puq_pm_lxc_os_templates.distribution', 'like', "%{$search}%")
                                ->orWhere('puq_pm_lxc_templates.name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route('admin.web.Product.puqProxmox.lxc_os_template.tab',
                        ['uuid' => $model->uuid, 'tab' => 'general']);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.lxc_os_template.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postLxcOsTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => [
                'required',
                'unique:puq_pm_lxc_os_templates,name',
                'regex:/^[a-zA-Z0-9._-]+$/',
            ],
            'name' => 'required|string|max:50',
            'distribution' => 'required|string|max:50',
            'version' => 'required|string|max:50',
            'puq_pm_lxc_template_uuid' => 'required|uuid|exists:puq_pm_lxc_templates,uuid',
        ], [
            'key.required' => __('Product.puqProxmox.The Key field is required'),
            'key.unique' => __('Product.puqProxmox.This Key is already taken'),
            'key.regex' => __('Product.puqProxmox.Only uppercase letters, digits, dashes and underscores are allowed'),
            'name.required' => __('Product.puqProxmox.Name is required'),
            'distribution.required' => __('Product.puqProxmox.Distribution is required'),
            'version.required' => __('Product.puqProxmox.Version is required'),
            'puq_pm_lxc_template_uuid.required' => __('Product.puqProxmox.Template UUID is required'),
            'puq_pm_lxc_template_uuid.uuid' => __('Product.puqProxmox.Template UUID must be valid'),
            'puq_pm_lxc_template_uuid.exists' => __('Product.puqProxmox.Template not found'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmLxcOsTemplate;

        $model->key = $request->input('key');
        $model->name = $request->input('name');
        $model->distribution = $request->input('distribution');
        $model->version = $request->input('version');
        $model->init_script = '';
        $model->post_start_script = '';
        $model->reset_password_script = '';
        $model->puq_pm_lxc_template_uuid = $request->input('puq_pm_lxc_template_uuid');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function lxcOsTemplateTab(Request $request, $uuid, $tab): View|RedirectResponse
    {

        $lxc_os_template = PuqPmLxcOsTemplate::findOrFail($uuid);

        $validTabs = [
            'general',
            'post_install_script',
            'reset_password_script',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route(
                'admin.web.Product.puqProxmox.lxc_os_template.tab',
                ['uuid' => $lxc_os_template->uuid, 'tab' => 'general']
            );
        }

        $title = $lxc_os_template->name;

        return view_admin_module(
            'Product',
            'puqProxmox',
            'admin_area.lxc_os_templates.lxc_os_template_'.$tab,
            compact('title', 'uuid', 'tab', 'lxc_os_template')
        );
    }

    public function getLxcOsTemplate(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcOsTemplate::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();
        $puq_pm_lxc_template = $model->puqPmLxcTemplate;
        $data['puq_pm_lxc_template_data'] = [
            'id' => $puq_pm_lxc_template->uuid,
            'text' => $puq_pm_lxc_template->name,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function putLxcOsTemplate(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => [
                'required',
                'unique:puq_pm_lxc_os_templates,name,'.$uuid.',uuid',
                'regex:/^[a-zA-Z0-9._-]+$/',
            ],
            'name' => 'required|string|max:50',
            'distribution' => 'required|string|max:50',
            'version' => 'required|string|max:50',
            'puq_pm_lxc_template_uuid' => 'required|uuid|exists:puq_pm_lxc_templates,uuid',
        ], [
            'key.required' => __('Product.puqProxmox.The Key field is required'),
            'key.unique' => __('Product.puqProxmox.This Key is already taken'),
            'key.regex' => __('Product.puqProxmox.Only uppercase letters, digits, dashes and underscores are allowed'),
            'name.required' => __('Product.puqProxmox.Name is required'),
            'distribution.required' => __('Product.puqProxmox.Distribution is required'),
            'version.required' => __('Product.puqProxmox.Version is required'),
            'puq_pm_lxc_template_uuid.required' => __('Product.puqProxmox.Template UUID is required'),
            'puq_pm_lxc_template_uuid.uuid' => __('Product.puqProxmox.Template UUID must be valid'),
            'puq_pm_lxc_template_uuid.exists' => __('Product.puqProxmox.Template not found'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = PuqPmLxcOsTemplate::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $model->key = $request->input('key');
        $model->name = $request->input('name');
        $model->distribution = $request->input('distribution');
        $model->version = $request->input('version');
        $model->init_script = '';
        $model->post_start_script = '';
        $model->reset_password_script = '';
        $model->puq_pm_lxc_template_uuid = $request->input('puq_pm_lxc_template_uuid');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteLxcOsTemplate(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcOsTemplate::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    public function getLxcOsTemplatesSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        $query = PuqPmLxcOsTemplate::query();

        if (!empty($search)) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($request->has('lxc_preset_uuid')) {
            $lxc_preset = PuqPmLxcPreset::query()
                ->where('uuid', $request->input('lxc_preset_uuid'))
                ->first();

            if ($lxc_preset) {
                $os_template_uuids = $lxc_preset->puqPmLxcOsTemplates()
                    ->pluck('uuid')
                    ->toArray();

                $query->whereNotIn('uuid', $os_template_uuids ?? []);
            }
        }

        $models = $query->get();

        $results = [];
        foreach ($models->toArray() ?? [] as $model) {
            $results[] = [
                'id' => $model['uuid'],
                'text' => $model['name'],
            ];
        }

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }


    public function checkLxcOsTemplateFile(Request $request, $uuid): JsonResponse
    {
        $template = PuqPmLxcOsTemplate::where('uuid', $uuid)->first();

        if (!$template || !filter_var($template->url, FILTER_VALIDATE_URL)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.Invalid URL')],
            ], 400);
        }

        try {
            $headers = get_headers($template->url, 1);
            $status = $headers[0] ?? '';
            $size = isset($headers['Content-Length']) ? (int) $headers['Content-Length'] : null;
            $type = $headers['Content-Type'] ?? null;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => str_contains($status, '200') ? 'ok' : 'error',
                    'size' => $size,
                    'type' => $type,
                    'status_code' => $status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.Request failed')],
            ], 500);
        }
    }


    public function getLxcOsTemplateScript(Request $request, $uuid, $type): JsonResponse
    {
        $model = PuqPmLxcOsTemplate::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->puqPmScripts()->where('type', $type)->first();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function putLxcOsTemplateScript(Request $request, $uuid, $type): JsonResponse
    {
        // Find the LXC template by UUID
        $model = PuqPmLxcOsTemplate::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        // Validate request
        $request->validate([
            'script' => 'required|string',
        ]);

        // Try to find existing script
        $scriptModel = $model->puqPmScripts()->where('type', $type)->first();

        if ($scriptModel) {
            // Update existing script
            $scriptModel->script = $request->input('script');
            $scriptModel->save();
        } else {
            // Create new script
            $scriptModel = $model->puqPmScripts()->create([
                'puq_pm_lxc_os_template_uuid' => $uuid,
                'type' => $type,
                'script' => $request->input('script'),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $scriptModel,
        ]);
    }


}
