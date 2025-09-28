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

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function uploadImages(Request $request): JsonResponse
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_uuid' => 'required',
            'field' => 'required|string',
        ]);

        $field = $request->input('field');

        if (! $request->hasFile($field)) {
            return response()->json([
                'status' => 'error',
                'message' => [$field => [__('No file uploaded')]],
            ], 400);
        }

        $uploadedFile = $request->file($field);

        if (! str_starts_with($uploadedFile->getMimeType(), 'image/')) {
            return response()->json([
                'status' => 'error',
                'message' => [$field => [__('Invalid image format')]],
            ], 400);
        }

        $filename = Str::uuid().'.'.$uploadedFile->getClientOriginalExtension();

        $directory = 'images/'.base64_decode($request->model_type).'/'.$request->model_uuid;

        $path = $uploadedFile->storeAs($directory, $filename);

        File::where('model_type', base64_decode($request->model_type))
            ->where('model_uuid', $request->model_uuid)
            ->where('model_field', $field)
            ->each(function ($file) {
                $file->deleteFile();
            });

        $file = new File;
        $file->name = $filename;
        $file->type = $uploadedFile->getMimeType();
        $file->size = $uploadedFile->getSize();
        $file->path = $path;
        $file->directory = $directory;
        $file->is_public = true;
        $file->model_type = base64_decode($request->model_type);
        $file->model_uuid = $request->model_uuid;
        $file->model_field = $field;
        $file->order = 0;
        $file->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'uuid' => $file->uuid,
                'url' => route('static.file.img', ['uuid' => $file->uuid]),
            ],
        ]);
    }

    public function deleteImages(Request $request): JsonResponse
    {
        $request->validate([
            'uuid' => 'required|uuid',
        ]);

        $file = File::where('uuid', $request->uuid)->first();

        if (! $file) {
            return response()->json([
                'staus' => 'error',
                'errors' => ['File not found'],
            ], 404);
        }

        $file->deleteFile();

        return response()->json(['status' => 'success']);
    }

    public function download(Request $request, $uuid, $name): ?StreamedResponse
    {
        $file = File::query()->findOrFail($uuid);

        if ($file->name != $name) {
            abort(404);
        }

        if (! $file->is_public) {
            abort(403, 'Access denied');
        }

        return $file->streamResponse();
    }
}
