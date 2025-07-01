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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestS3Connection extends Command
{
    protected $signature = 'test:s3';

    protected $description = 'Test connection to S3 storage with performance metrics';

    public function handle()
    {
        $disk = 's3';

        $this->info("\n============================");
        $this->info(' S3 Connection and Performance Test ');
        $this->info("============================\n");

        $results = [];

        try {
            $testFiles = [
                '1MB' => str_repeat('A', 1024 * 1024),
                '10MB' => str_repeat('B', 10 * 1024 * 1024),
                '100MB' => str_repeat('C', 100 * 1024 * 1024),
                '200MB' => str_repeat('C', 200 * 1024 * 1024),
                '500MB' => str_repeat('D', 500 * 1024 * 1024),
            ];

            foreach ($testFiles as $size => $content) {
                $fileName = "test_{$size}.txt";
                $this->info("\n--- Testing file size: {$size} ---\n");

                // Measure upload time
                $this->info("Uploading file '{$fileName}'...");
                $uploadStart = microtime(true);
                try {
                    Storage::disk($disk)->put($fileName, $content);
                    $uploadTime = microtime(true) - $uploadStart;
                    $this->info("[✔] Uploaded in: {$uploadTime} seconds.");
                } catch (\Exception $e) {
                    $this->error("[✘] Upload failed: {$e->getMessage()}\nStack Trace: {$e->getTraceAsString()}");
                    Log::error('Upload Error: ', ['file' => $fileName, 'exception' => $e]);
                    $results[] = [
                        'size' => $size,
                        'upload_time' => 'Error',
                        'download_time' => 'N/A',
                        'data_integrity' => 'Failed',
                    ];

                    continue;
                }

                // Check file existence
                if (! Storage::disk($disk)->exists($fileName)) {
                    $this->error("[✘] Failed to confirm upload for file: {$fileName}");
                    $results[] = [
                        'size' => $size,
                        'upload_time' => $uploadTime,
                        'download_time' => 'N/A',
                        'data_integrity' => 'Failed',
                    ];

                    continue;
                }

                // Measure download time
                $this->info("Downloading file '{$fileName}'...");
                $downloadStart = microtime(true);
                try {
                    $downloadedContent = Storage::disk($disk)->get($fileName);
                    $downloadTime = microtime(true) - $downloadStart;
                    $this->info("[✔] Downloaded in: {$downloadTime} seconds.");
                } catch (\Exception $e) {
                    $this->error("[✘] Download failed: {$e->getMessage()}\nStack Trace: {$e->getTraceAsString()}");
                    Log::error('Download Error: ', ['file' => $fileName, 'exception' => $e]);
                    $results[] = [
                        'size' => $size,
                        'upload_time' => $uploadTime,
                        'download_time' => 'Error',
                        'data_integrity' => 'Failed',
                    ];

                    continue;
                }

                // Verify data integrity
                $dataIntegrity = $downloadedContent === $content ? 'Passed' : 'Failed';
                if ($dataIntegrity === 'Failed') {
                    $this->error("[✘] Data mismatch for file: {$fileName}");
                } else {
                    $this->info("[✔] Data integrity confirmed for file: {$fileName}");
                }

                // Delete file
                try {
                    Storage::disk($disk)->delete($fileName);
                    $this->info("[✔] Deleted file: {$fileName}");
                } catch (\Exception $e) {
                    $this->error("[✘] Deletion failed: {$e->getMessage()}\nStack Trace: {$e->getTraceAsString()}");
                    Log::error('Deletion Error: ', ['file' => $fileName, 'exception' => $e]);
                }

                $results[] = [
                    'size' => $size,
                    'upload_time' => $uploadTime,
                    'download_time' => $downloadTime,
                    'data_integrity' => $dataIntegrity,
                ];
            }

            $this->info("\n============================");
            $this->info(' Test Results Table ');
            $this->info("============================\n");
            $this->table(
                ['File Size', 'Upload Time (s)', 'Download Time (s)', 'Data Integrity'],
                $results
            );

            $this->info("\n============================");
            $this->info(' Test Completed Successfully ');
            $this->info("============================\n");
        } catch (\Exception $e) {
            $this->error("\n[✘] General Error: ".$e->getMessage()."\nStack Trace: ".$e->getTraceAsString());
            Log::error('S3 Test Error: ', ['exception' => $e]);
        }
    }
}
