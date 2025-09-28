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

namespace App\Traits;

use App\Models\File;

trait HasFiles
{
    private $loadedImages = [];

    public static function bootHasFiles()
    {
        static::retrieved(function ($model) {
            $model->loadImagesField();
        });

        static::created(function ($model) {
            $model->loadImagesField();
        });

        static::deleting(function ($model) {
            $model->deleteAttachedFiles();
        });
    }

    // For Simple Class
    public function getImages(): array
    {
        $this->loadImagesField();

        return $this->loadedImages;
    }

    // For Models
    public function getImagesAttribute(): array
    {
        return $this->loadedImages;
    }

    protected function loadImagesField(): void
    {
        $files = File::where('model_type', static::class)
            ->where('model_uuid', $this->uuid)
            ->orderBy('order')
            ->get();

        $url = fn ($uuid, $name) => route('static.file', ['uuid' => $uuid, 'name' => $name]);

        $result = ['_fields' => []];

        foreach (static::IMAGES ?? [] as $field => $config) {
            $label = is_array($config) ? $config['label'] : $config;
            $order = is_array($config) ? $config['order'] ?? 0 : 0;

            $file = $files->firstWhere('model_field', $field);
            $result[$field] = $file ? $url($file->uuid, $file->name) : null;

            $result['_fields'][$field] = [
                'label' => $label,
                'order' => $order,
                'uuid' => $file->uuid ?? null,
                'model_type' => base64_encode(static::class),
                'model_uuid' => $this->uuid ?? null,
            ];
        }

        $result['_fields'] = collect($result['_fields'])
            ->sortBy('order')
            ->toArray();

        $this->loadedImages = $result;

        if ($this instanceof \Illuminate\Database\Eloquent\Model) {
            $this->append('images');
        }
    }

    protected function deleteAttachedFiles(): void
    {
        File::where('model_type', static::class)
            ->where('model_uuid', $this->uuid)
            ->get()
            ->each(fn ($file) => $file->deleteFile());
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['images'] = $this->images ?? [];

        return $data;
    }
}
