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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\PuqPmCluster;
use Modules\Product\puqProxmox\Models\PuqPmLxcTemplate;
use Yajra\DataTables\DataTables;

class puqPmLxcTemplateController extends Controller
{
    public function lxcTemplates(Request $request): View
    {
        $title = __('Product.puqProxmox.LXC Templates');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.lxc_templates.lxc_templates', compact('title'));
    }

    public function getLxcTemplates(Request $request): JsonResponse
    {
        $query = PuqPmLxcTemplate::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('url', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['put'] = route('admin.api.Product.puqProxmox.lxc_template.put', $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.lxc_template.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postLxcTemplate(Request $request): JsonResponse
    {

        Validator::extend('has_valid_extension', function ($attribute, $value, $parameters, $validator) {
            $allowedExtensions = ['.tar.gz', '.tar.xz', '.tar.zst'];
            foreach ($allowedExtensions as $ext) {
                if (str_ends_with($value, $ext)) {
                    return true;
                }
            }
            return false;
        });

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'unique:puq_pm_lxc_templates,name',
                'regex:/^[a-zA-Z0-9._-]+$/',
                'has_valid_extension',
            ],
            'url' => [
                'required',
                'unique:puq_pm_lxc_templates,url',
                'url',
            ],
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'name.regex' => __('Product.puqProxmox.Only uppercase letters, digits, dashes and underscores are allowed'),
            'name.has_valid_extension' => __('Product.puqProxmox.Name must end with .tar.gz, .tar.xz, or .tar.zst'),

            'url.required' => __('Product.puqProxmox.The URL field is required'),
            'url.unique' => __('Product.puqProxmox.This URL is already taken'),
            'url.url' => __('Product.puqProxmox.Invalid URL format'),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmLxcTemplate;

        $model->name = $request->input('name');
        $model->url = $request->input('url');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function getLxcTemplate(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcTemplate::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $model,
        ]);
    }

    public function putLxcTemplate(Request $request, $uuid): JsonResponse
    {
        Validator::extend('has_valid_extension', function ($attribute, $value, $parameters, $validator) {
            $allowedExtensions = ['.tar.gz', '.tar.xz', '.tar.zst'];
            foreach ($allowedExtensions as $ext) {
                if (str_ends_with($value, $ext)) {
                    return true;
                }
            }
            return false;
        });
        $validator = Validator::make($request->all(), [
            'name' => 'required|regex:/^[a-zA-Z0-9._-]+$/|unique:puq_pm_lxc_templates,name,'.$uuid.',uuid|has_valid_extension',
            'url' => 'required|unique:puq_pm_lxc_templates,url,'.$uuid.',uuid',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'name.regex' => __('Product.puqProxmox.Only uppercase letters, digits, dashes and underscores are allowed'),
            'name.has_valid_extension' => __('Product.puqProxmox.Name must end with .tar.gz, .tar.xz, or .tar.zst'),

            'url.required' => __('Product.puqProxmox.The URL field is required'),
            'url.unique' => __('Product.puqProxmox.This URL is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = PuqPmLxcTemplate::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $model->name = $request->input('name');
        $model->url = $request->input('url');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteLxcTemplate(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmLxcTemplate::find($uuid);

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

    public function getLxcTemplatesSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (!empty($search)) {
            $models = PuqPmLxcTemplate::query()->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $models = PuqPmLxcTemplate::query()->get();
        }

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

    public function checkLxcTemplateFile(Request $request, $uuid): JsonResponse
    {
        $template = PuqPmLxcTemplate::where('uuid', $uuid)->first();

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


    public function getLxcTemplatesSyncTemplates(Request $request): JsonResponse
    {
        $models = PuqPmCluster::query()->where('disable', false)->get();
        foreach ($models as $model) {
            if ($model->puqPmAccessServers()->count() > 0) {
                $model->syncLxcTemplatesToStorages(false);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Templates syncing started successfully!'),
        ]);
    }

    public function getLxcTemplatesSyncDeleteTemplates(Request $request): JsonResponse
    {
        $models = PuqPmCluster::query()->where('disable', false)->get();
        foreach ($models as $model) {
            if ($model->puqPmAccessServers()->count() > 0) {
                $model->syncLxcTemplatesToStorages(true);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Templates syncing deleting started successfully!'),
        ]);
    }

}
