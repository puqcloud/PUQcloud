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

namespace App\Console\Commands;

use App\Services\FileService;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class TestFileService extends Command
{
    protected $signature = 'test:fileservice';
    protected $description = 'Test the FileService functionality';

    protected $fileService;

    public function __construct(FileService $fileService)
    {
        parent::__construct();
        $this->fileService = $fileService;
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $this->info('Testing FileService functionality...');

        // Test file saving
        $path = 'test.txt';
        $content = 'Hello, PUQcloud!';
        if ($this->fileService->saveFile($path, $content)) {
            $this->info("File saved: $path");
        } else {
            $this->error("Failed to save file: $path");
        }

        // Test file reading
        if ($this->fileService->fileExists($path)) {
            $fileContent = $this->fileService->readFile($path);
            $this->info("File content: $fileContent");
        } else {
            $this->error("File does not exist: $path");
        }

        // Test file deletion
        if ($this->fileService->deleteFile($path)) {
            $this->info("File deleted: $path");
        } else {
            $this->error("Failed to delete file: $path");
        }

        // Test file existence check
        if ($this->fileService->fileExists($path)) {
            $this->info("File exists: $path");
        } else {
            $this->info("File does not exist: $path");
        }

        // Test public URL retrieval
        $publicUrl = $this->fileService->getPublicUrl($path);
        $this->info("Public URL: $publicUrl");

        // Test directory creation
        $directoryPath = 'test_directory';
        if ($this->fileService->createDirectory($directoryPath)) {
            $this->info("Directory created: $directoryPath");
        } else {
            $this->error("Failed to create directory: $directoryPath");
        }

        // Test file listing
        $files = $this->fileService->listFiles('');
        $this->info('Files in root directory:');
        foreach ($files as $file) {
            $this->info($file);
        }

        // Test directory listing
        $directories = $this->fileService->listDirectories('');
        $this->info('Directories in root directory:');
        foreach ($directories as $dir) {
            $this->info($dir);
        }

        // Test file copy
        $copyPath = 'copy_test.txt';
        if ($this->fileService->copyFile($path, $copyPath)) {
            $this->info("File copied to: $copyPath");
        } else {
            $this->error("Failed to copy file: $path to $copyPath");
        }

        // Test file move
        $movePath = 'moved_test.txt';
        if ($this->fileService->moveFile($copyPath, $movePath)) {
            $this->info("File moved to: $movePath");
        } else {
            $this->error("Failed to move file: $copyPath to $movePath");
        }

        // Test directory deletion
        if ($this->fileService->deleteDirectory($directoryPath)) {
            $this->info("Directory deleted: $directoryPath");
        } else {
            $this->error("Failed to delete directory: $directoryPath");
        }

        $this->info('Test completed.');
    }
}
