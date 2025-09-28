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

namespace App\Models;

use App\Traits\ConvertsTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class File extends Model
{
    use ConvertsTimezone;

    protected $table = 'files';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    protected $fillable = [
        'name', 'type', 'size', 'path', 'directory', 'is_public', 'expires_at',
    ];

    public function saveContent(string $directory, string $filename, string $content): bool
    {
        if ($this->path != null) {
            Storage::delete($this->path);
        }
        try {
            $this->path = $directory.'/'.$filename;
            $this->name = $filename;
            $this->directory = $directory;

            if (! Storage::put($this->path, $content)) {
                throw new \Exception("Failed to write file to storage: {$this->path}");
            }

            $this->size = strlen($content);
            $this->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Error saving file: '.$e->getMessage(), ['file' => $this->path]);

            return false;
        }
    }

    public function downloadResponse(): ?StreamedResponse
    {
        try {
            if (! Storage::exists($this->path)) {
                throw new \Exception("File not found: {$this->path}");
            }

            return Storage::download($this->path, $this->name);
        } catch (\Throwable $e) {
            Log::error('Error downloading file: '.$e->getMessage(), ['file' => $this->path]);

            return null;
        }
    }

    public function streamResponse(): ?StreamedResponse
    {
        try {
            if (! Storage::exists($this->path)) {
                throw new \Exception("File not found: {$this->path}");
            }

            return Storage::response(
                $this->path,
                $this->name,
                [
                    'Content-Type' => $this->type ?? 'application/octet-stream',
                    'Cache-Control' => $this->is_public ? 'public, max-age=31536000' : 'private, max-age=0',
                    'Content-Disposition' => 'inline; filename="'.$this->name.'"',
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Error streaming file: '.$e->getMessage(), ['file' => $this->path]);

            return null;
        }
    }

    public function deleteFile(): bool
    {
        try {
            if (Storage::exists($this->path)) {
                if (! Storage::delete($this->path)) {
                    throw new \Exception("Unable to delete file: {$this->path}");
                }
            }

            $this->delete();

            return true;
        } catch (\Throwable $e) {
            Log::error('Error deleting file: '.$e->getMessage(), ['file' => $this->path]);

            return false;
        }
    }
}
