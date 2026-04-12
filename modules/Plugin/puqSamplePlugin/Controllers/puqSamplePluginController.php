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

namespace Modules\Plugin\puqSamplePlugin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Plugin\puqSamplePlugin\Models\PuqSamplePlugin as PuqSamplePluginModel;
use OpenApi\Attributes as OA;
use puqSamplePlugin;
use Yajra\DataTables\DataTables;

class puqSamplePluginController extends Controller
{
    public function info(Request $request): View
    {
        $title = __('Plugin.puqSamplePlugin.Info');
        $module = new puqSamplePlugin;
        $config = $module->config;

        return view_admin_module('Plugin', 'puqSamplePlugin', 'info', compact('title', 'config'));
    }

    public function simpleModelExample(Request $request): View
    {
        $title = __('Plugin.puqSamplePlugin.Simple Model Example');
        $model = new PuqSamplePluginModel;

        return view_admin_module('Plugin', 'puqSamplePlugin', 'simple_model_example', compact('title', 'model'));
    }

    public function simpleApiRequests(Request $request): View
    {
        $title = __('Plugin.puqSamplePlugin.Simple API requests');

        return view_admin_module('Plugin', 'puqSamplePlugin', 'simple_api_requests', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.Plugin.puqSamplePlugin.simple_api_request.get',
        description: 'Returns a success message for the GET API request.',
        summary: 'Get API request',
        security: [['apiKeyAuth' => []]],
        tags: ['puqSamplePlugin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful GET API request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getApiRequest(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Plugin.puqSamplePlugin.Get API request Success'),
        ], 200);
    }

    #[OA\Put(
        path: 'admin.api.Plugin.puqSamplePlugin.simple_api_request.put',
        description: 'Returns a success message for the PUT API request.',
        summary: 'Put API request',
        security: [['apiKeyAuth' => []]],
        tags: ['puqSamplePlugin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful PUT API request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putApiRequest(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Plugin.puqSamplePlugin.PUT API request Success'),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.Plugin.puqSamplePlugin.simple_api_request.post',
        description: 'Returns a success message for the POST API request.',
        summary: 'Post API request',
        security: [['apiKeyAuth' => []]],
        tags: ['puqSamplePlugin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful POST API request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postApiRequest(Request $request): JsonResponse
    {

        return response()->json([
            'message' => __('Plugin.puqSamplePlugin.DELETE API request Success'),
        ], 200);
    }

    #[OA\Delete(
        path: 'admin.api.Plugin.puqSamplePlugin.simple_api_request.delete',
        description: 'Returns a success message for the DELETE API request.',
        summary: 'Delete API request',
        security: [['apiKeyAuth' => []]],
        tags: ['puqSamplePlugin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful DELETE API request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteApiRequest(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Plugin.puqSamplePlugin.DELETE API request Success'),
        ], 200);
    }

    #[OA\Get(
        path: 'admin.api.Plugin.puqSamplePlugin.simple_models.get',
        description: 'Returns a paginated list of simple models with search and filtering support.',
        summary: 'Get simple models list',
        security: [['apiKeyAuth' => []]],
        tags: ['puqSamplePlugin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response with list of simple models',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getSimpleModels(Request $request): JsonResponse
    {
        $query = PuqSamplePluginModel::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('test', 'like', "%{$search}%")
                                ->orWhere('test2', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('Plugin-puqSamplePlugin-simple-model-example')) {
                        $urls['get'] = route('admin.api.Plugin.puqSamplePlugin.simple_model.get', $model->id);
                    }

                    if ($admin_online->hasPermission('Plugin-puqSamplePlugin-edit-simple-model')) {
                        $urls['put'] = route('admin.api.Plugin.puqSamplePlugin.simple_model.put', $model->id);
                    }

                    if ($admin_online->hasPermission('Plugin-puqSamplePlugin-delete-simple-model')) {
                        $urls['delete'] = route('admin.api.Plugin.puqSamplePlugin.simple_model.delete', $model->id);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.Plugin.puqSamplePlugin.simple_model.post',
        description: 'Creates a new simple model record with the provided name, test, and test2 fields.',
        summary: 'Create a simple model',
        security: [['apiKeyAuth' => []]],
        tags: ['puqSamplePlugin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Simple model created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function postSimpleModel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_sample_plugins,name',
        ], [
            'name.required' => __('Plugin.puqSamplePlugin.The Name field is required'),
            'name.unique' => __('Plugin.puqSamplePlugin.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqSamplePluginModel;

        if (! empty($request->input('name'))) {
            $model->name = $request->input('name');
        }
        if (! empty($request->input('test'))) {
            $model->test = $request->input('test');
        }
        if (! empty($request->input('test2'))) {
            $model->test2 = $request->input('test2');
        }

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.Plugin.puqSamplePlugin.simple_model.get',
        description: 'Returns a single simple model record by its ID.',
        summary: 'Get a simple model',
        security: [['apiKeyAuth' => []]],
        tags: ['puqSamplePlugin'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the simple model',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response with simple model data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getSimpleModel(Request $request, $id): JsonResponse
    {
        $model = PuqSamplePluginModel::find($id);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Plugin.puqSamplePlugin.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $model,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.Plugin.puqSamplePlugin.simple_model.put',
        description: 'Updates an existing simple model record by its ID.',
        summary: 'Update a simple model',
        security: [['apiKeyAuth' => []]],
        tags: ['puqSamplePlugin'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the simple model',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Simple model updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function putSimpleModel(Request $request, $id): JsonResponse
    {
        $model = PuqSamplePluginModel::find($id);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Plugin.puqSamplePlugin.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_sample_plugins,name,'.$id.',id',
        ], [
            'name.required' => __('Plugin.puqSamplePlugin.The Name field is required'),
            'name.unique' => __('Plugin.puqSamplePlugin.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if (! empty($request->input('name'))) {
            $model->name = $request->input('name');
        }
        if (! empty($request->input('test'))) {
            $model->test = $request->input('test');
        }
        if (! empty($request->input('test2'))) {
            $model->test2 = $request->input('test2');
        }

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.Plugin.puqSamplePlugin.simple_model.delete',
        description: 'Deletes a simple model record by its ID.',
        summary: 'Delete a simple model',
        security: [['apiKeyAuth' => []]],
        tags: ['puqSamplePlugin'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the simple model',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Simple model deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteSimpleModel(Request $request, $id): JsonResponse
    {
        $model = PuqSamplePluginModel::find($id);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Plugin.puqSamplePlugin.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('Plugin.puqSamplePlugin.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Plugin.puqSamplePlugin.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Plugin.puqSamplePlugin.Deleted successfully'),
        ]);
    }
}
