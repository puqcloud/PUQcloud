<?php

namespace Modules\Product\puqSampleProduct\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqSampleProduct\Models\PuqSampleProduct as PuqSampleProductModel;
use puqSampleProduct;
use Yajra\DataTables\DataTables;

class puqSampleProductController extends Controller
{
    public function info(Request $request): View
    {
        $title = __('Product.puqSampleProduct.Info');
        $module = new puqSampleProduct;
        $config = $module->config;

        return view_admin_module('Product', 'puqSampleProduct', 'admin_area.info', compact('title', 'config'));
    }

    public function simpleModelExample(Request $request): View
    {
        $title = __('Product.puqSampleProduct.Simple Model Example');
        $model = new PuqSampleProductModel;

        return view_admin_module('Product', 'puqSampleProduct', 'admin_area.simple_model_example', compact('title', 'model'));
    }

    public function simpleApiRequests(Request $request): View
    {
        $title = __('Product.puqSampleProduct.Simple API requests');

        return view_admin_module('Product', 'puqSampleProduct', 'admin_area.simple_api_requests', compact('title'));
    }

    public function getApiRequest(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Product.puqSampleProduct.Get API request Success'),
        ], 200);
    }

    public function putApiRequest(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Product.puqSampleProduct.PUT API request Success'),
        ], 200);
    }

    public function postApiRequest(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Product.puqSampleProduct.POST API request Success'),
        ], 200);
    }

    public function deleteApiRequest(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Product.puqSampleProduct.DELETE API request Success'),
        ], 200);
    }

    public function getSimpleModels(Request $request): JsonResponse
    {
        $query = PuqSampleProductModel::query();

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
                    if ($admin_online->hasPermission('Product-puqSampleProduct-simple-model-example')) {
                        $urls['get'] = route('admin.api.Product.puqSampleProduct.simple_model.get', $model->id);
                    }

                    if ($admin_online->hasPermission('Product-puqSampleProduct-edit-simple-model')) {
                        $urls['put'] = route('admin.api.Product.puqSampleProduct.simple_model.put', $model->id);
                    }

                    if ($admin_online->hasPermission('Product-puqSampleProduct-delete-simple-model')) {
                        $urls['delete'] = route('admin.api.Product.puqSampleProduct.simple_model.delete', $model->id);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postSimpleModel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_sample_products,name',
        ], [
            'name.required' => __('Product.puqSampleProduct.The Name field is required'),
            'name.unique' => __('Product.puqSampleProduct.This Name is already taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqSampleProductModel;

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

    public function getSimpleModel(Request $request, $id): JsonResponse
    {
        $model = PuqSampleProductModel::find($id);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqSampleProduct.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $model,
        ]);
    }

    public function putSimpleModel(Request $request, $id): JsonResponse
    {
        $model = PuqSampleProductModel::find($id);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqSampleProduct.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:puq_sample_products,name,'.$id.',id',
        ], [
            'name.required' => __('Product.puqSampleProduct.The Name field is required'),
            'name.unique' => __('Product.puqSampleProduct.This Name is already taken'),
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

    public function deleteSimpleModel(Request $request, $id): JsonResponse
    {
        $model = PuqSampleProductModel::find($id);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqSampleProduct.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('Product.puqSampleProduct.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqSampleProduct.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqSampleProduct.Deleted successfully'),
        ]);
    }
}
