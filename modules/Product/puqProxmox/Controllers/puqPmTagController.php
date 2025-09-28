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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqProxmox\Models\PuqPmClusterGroup;
use Modules\Product\puqProxmox\Models\PuqPmLxcPresetClusterGroup;
use Modules\Product\puqProxmox\Models\PuqPmNode;
use Modules\Product\puqProxmox\Models\PuqPmPrivateNetwork;
use Modules\Product\puqProxmox\Models\PuqPmPublicNetwork;
use Modules\Product\puqProxmox\Models\PuqPmStorage;
use Modules\Product\puqProxmox\Models\PuqPmTag;
use Yajra\DataTables\DataTables;

class puqPmTagController extends Controller
{
    public function tags(Request $request): View
    {
        $title = __('Product.puqProxmox.Tags');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.tags.tags', compact('title'));
    }

    public function getTags(Request $request): JsonResponse
    {
        $query = PuqPmTag::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['put'] = route('admin.api.Product.puqProxmox.tag.put', $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.tag.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postTag(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'unique:puq_pm_tags,name',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'name.regex' => __('Product.puqProxmox.Only uppercase letters, digits, dashes and underscores are allowed'),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmTag;

        $model->name = $request->input('name');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function getTag(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmTag::find($uuid);

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

    public function putTag(Request $request, $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'unique:puq_pm_tags,name',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.unique' => __('Product.puqProxmox.This Name is already taken'),
            'name.regex' => __('Product.puqProxmox.Only uppercase letters, digits, dashes and underscores are allowed'),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = PuqPmTag::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $model->name = $request->input('name');
        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteTag(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmTag::find($uuid);

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

    public function getClusterGroupsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (!empty($search)) {
            $models = PuqPmClusterGroup::query()->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $models = PuqPmClusterGroup::query()->get();
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

    // Tag Editor
    public function getTagEditorUpdateTags(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|string',
            'model' => 'required|string',
            'tags' => 'nullable|array',
            'type' => 'required_if:model,PuqPmLxcPresetClusterGroup|string',
            'tags.*' => 'regex:/^[a-zA-Z0-9_-]+$/',
        ], [
            'uuid.required' => __('Product.puqProxmox.UUID is required'),
            'model.required' => __('Product.puqProxmox.Model type is required'),
            'tags.array' => __('Product.puqProxmox.Tags must be an array'),
            'tags.*.regex' => __('Product.puqProxmox.Tags may only contain letters, numbers, dashes and underscores'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $modelClass = $request->input('model');
        $uuid = $request->input('uuid');
        $type = $request->input('type');
        $tags = $request->input('tags', []);

        switch ($modelClass) {
            case 'PuqPmNode':
                $model = PuqPmNode::where('uuid', $uuid)->first();
                break;

            case 'PuqPmStorage':
                $model = PuqPmStorage::where('uuid', $uuid)->first();
                break;

            case 'PuqPmPublicNetwork':
                $model = puqPmPublicNetwork::where('uuid', $uuid)->first();
                break;

            case 'PuqPmPrivateNetwork':
                $model = puqPmPrivateNetwork::where('uuid', $uuid)->first();
                break;
            case 'PuqPmLxcPresetClusterGroup':
                $model = PuqPmLxcPresetClusterGroup::where('uuid', $uuid)->first();
                break;
            default:
                return response()->json([
                    'status' => 'error', 'errors' => [__('Product.puqProxmox.Invalid model class')],
                ], 400);
        }

        if (!$model) {
            return response()->json(['status' => 'error', 'errors' => [__('Product.puqProxmox.Model not found')]], 404);
        }

        $tagUuids = collect($tags)->map(function ($tagName) {
            return (string) PuqPmTag::firstOrCreate(['name' => $tagName])->uuid;
        })->toArray();


        if ($modelClass === 'PuqPmLxcPresetClusterGroup') {
            DB::table('puq_pm_lxc_preset_cluster_group_x_tag')
                ->where('puq_pm_lxc_preset_cluster_uuid', $uuid)
                ->where('type', $type)
                ->delete();

            foreach ($tagUuids as $tagUuid) {
                DB::table('puq_pm_lxc_preset_cluster_group_x_tag')->insert([
                    'puq_pm_lxc_preset_cluster_uuid' => $uuid,
                    'puq_pm_tag_uuid' => $tagUuid,
                    'type' => $type,
                ]);
            }

        } else {
            $model->puqPmTags()->sync($tagUuids);
        }


        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Successfully'),
        ]);
    }

    public function getTagEditorSearchTags(Request $request): JsonResponse
    {
        $term = $request->input('term', '');

        $tags = PuqPmTag::where('name', 'like', '%'.$term.'%')
            ->limit(20)
            ->get(['name as id', 'name as text']);

        return response()->json([
            'status' => 'success',
            'data' => ['results' => $tags],
        ]);
    }
}
