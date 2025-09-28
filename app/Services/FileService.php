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

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileService
{
    protected $disk;

    public function __construct(?string $disk = null)
    {
        $this->disk = $disk ?? config('filesystems.default');
    }

    public function saveFile(string $path, string $content): bool
    {
        return Storage::disk($this->disk)->put($path, $content);
    }

    public function getFilePath(string $path): string
    {
        if ($this->disk === 'local') {
            return Storage::disk($this->disk)->path($path);
        }

        return Storage::disk($this->disk)->temporaryUrl($path, now()->addHour());
    }

    public function deleteFile(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    public function fileExists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function getPublicUrl(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    public function copyFile(string $sourcePath, string $destinationPath): bool
    {
        return Storage::disk($this->disk)->copy($sourcePath, $destinationPath);
    }

    public function moveFile(string $sourcePath, string $destinationPath): bool
    {
        return Storage::disk($this->disk)->move($sourcePath, $destinationPath);
    }

    public function createDirectory(string $path): bool
    {
        return Storage::disk($this->disk)->makeDirectory($path);
    }

    public function deleteDirectory(string $path): bool
    {
        return Storage::disk($this->disk)->deleteDirectory($path);
    }

    public function listFiles(string $directory): array
    {
        return Storage::disk($this->disk)->files($directory);
    }

    public function listDirectories(string $directory): array
    {
        return Storage::disk($this->disk)->directories($directory);
    }

    public function readFile(string $path): string
    {
        return Storage::disk($this->disk)->get($path);
    }

    public function setFilePermissions(string $path, int $permissions): bool
    {
        if ($this->disk === 'local') {
            return chmod(Storage::disk($this->disk)->path($path), $permissions);
        }

        return false;
    }
}
